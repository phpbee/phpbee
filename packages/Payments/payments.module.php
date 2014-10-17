<?php

class payments_handler extends gs_handler {

	var $CUSTOMERNAME='girls';

	function expire_payment($d) {
		$order=$d['last'];
		if (!$order) return $order;
		if ($order->Status->first()->name=='new') {
			$status=record_by_field('name','expired','order_status');
			if ($status) {
				$order->Status_id=$status->get_id();
				$order->commit();
			}
		}
		return $order;
	}

	function trigger_payment_changed($rec) {
		$o=$rec->Order->first();
		$o->payment_status=$rec->status;
		$o->commit();

		if (!$rec->profits_created && $rec->status=='approved') {

			$totals=array('Production'=>array('amount'=>$o->production_cost));

			$sellers=array('Place','Photograph','Manager');
			$minus_before_percent=array('Production');
			$minus_after_percent=array('Place','Photograph','Manager');

			foreach ($sellers as $link) {
				$seller=$o->$link->first();
				if ($seller && $seller->comission) {
					$amount=$o->amount;

					$fees=explode(',',$seller->fees);
					if ($link=='Manager' && $o->Photograph->first()) { 
						$fees[]='Photograph';
					}

					foreach ($minus_before_percent as $fee) {
						if(in_array($fee,$fees) && isset($totals[$fee])) $amount-=$totals[$fee]['amount'];
					}
					$totals[$link]['comission']=$seller->comission;
					$amount=$amount*$seller->comission*.01;

					foreach ($minus_after_percent as $fee) {
						if(in_array($fee,$fees) && isset($totals[$fee])) $amount-=$totals[$fee]['amount'];
					}

					$totals[$link]['fees']=implode(',',$fees);
					$totals[$link]['amount']=$amount;
					$transactions[$link]['info']=$totals[$link];
					$transactions[$link]['seller']=$seller;
				}
			}

			//$o->profits_info=$totals;
			foreach ($transactions as $link => $tr) {
				$profit=$rec->Profits->new_record();
				$profit->order_amount=$rec->amount;
				$profit->order_type=$rec->type;
				$profit->amount=$tr['info']['amount'];
				$lname=$link.'_id';
				$profit->$lname=$tr['seller']->get_id();
				$profit->Currency_id=$rec->Currency_id;
				$profit->Order_id=$o->get_id();
			}
			$rec->Profits->commit();


			$rec->profits_info=var_export($totals,TRUE);
			$rec->profits_created=1;
	
		}

	}

	function check_order_exists($ret) {
		$classname="payments_gw_".$this->data['gspgid_va'][0];
		$gateway=new $classname($this->data,$this->params);
		$ret=$gateway->checkstatus();
        $ret=json_encode($ret);
        die($ret);

    }

	function payment_callback_checkstatus($ret) {
        $method=record_by_field('urlkey',$this->params['payment_method'],'payment_method');
		if(!$method) return false;
		$classname="payments_gw_".$method->type;
		$gateway=new $classname($this->data,$this->params);
		$ret=$gateway->checkstatus();
        $ret=$gateway->response($ret);
        die($ret);
    }

	function payment_callback($ret) {
		$classname="payments_gw_".$this->data['gspgid_va'][0];
		$gateway=new $classname($this->data,$this->params);
		$result=$gateway->validate();

        $pmnt_id=$gateway->get_payment_id();
		$pmnt=record_by_id($pmnt_id,'payments');
		$order=$pmnt->Order->first();

        if ($pmnt->status!='approved' && $pmnt->status!='declined') {
            $pmnt->status=$gateway->get_transaction_status();
            $pmnt->status_message=$gateway->get_transaction_message();
            $pmnt->transaction_number=$gateway->get_transaction_number();
            $pmnt->details=serialize($gateway->get_transaction_details());

            $pmnt->commit();



            $order->payment_status=$pmnt->status;

            $status=record_by_field('name',$pmnt->status,'order_status');
            if ($status) $order->Status_id=$status->get_id();
            $order->commit();


            gs_eventer::send('payment_completed',$pmnt);
        }
		gs_var_storage::save('payments_gateway',$gateway);

        $ret=array();
        if ($gateway->get_transaction_status()=='error') {
            $ret['ERROR']='transaction validation error';
        } else {
            $ret['Status']=$order->payment_status;
        }
        $ret=json_encode($ret);
        die($ret);
	}
	function process_payment($ret) {
        $pmnt=record_by_id($this->data['gspgid_va'][0],'payments');
        if (!$pmnt) return $pmnt;
        $method=$pmnt->Payment_method->first();
        $classname="payments_gw_".$method->type;
        $gateway=new $classname($this->data,$this->params,$method);
		$gateway->process($pmnt);
        return $pmnt;
    }

	function payment_completed($ret) {


		$classname="payments_gw_".$this->data['gspgid_va'][0];
		$gateway=new $classname($this->data,$this->params);
		$result=$gateway->validate();

        $pmnt_id=$gateway->get_payment_id();
		$pmnt=record_by_id($pmnt_id,'payments');
		$order=$pmnt->Order->first();

        if ($pmnt->status!='approved' && $pmnt->status!='declined') {
            $pmnt->status=$gateway->get_transaction_status();
            $pmnt->status_message=$gateway->get_transaction_message();
            $pmnt->transaction_number=$gateway->get_transaction_number();
            $pmnt->details=serialize($gateway->get_transaction_details());
            $pmnt->commit();

            $order->payment_status=$pmnt->status;

            $status=record_by_field('name',$pmnt->status,'order_status');
            if ($status) $order->Status_id=$status->get_id();
            $order->commit();

            gs_eventer::send('payment_completed',$pmnt);
        }

		gs_var_storage::save('payments_gateway',$gateway);
		gs_var_storage::save('payment',$pmnt);

		return $result;
		
	}
	function repay() {
		$order=record_by_field('number',$this->va(0),'orders');
		die();
	}

    function checkout($d) {
        $customer=person($this->CUSTOMERNAME);
        $cart=person('cart');
        if (!is_a($cart,'payment_cart')) return false;

        $order=new orders();
        $order=$order->new_record();
        $order->Customer_id=$customer->get_id();
        $order->invoice=substr(md5(rand()),-8);
        $order->price=$cart->get_total_price();
        $order->Currency_id=$cart->get_currency();
        $order->Status_id=1;
        $order->cart=serialize($cart);
        $order->orderdate=time();

		if (isset($this->data['gspgid_va'][0])) {
			$method=record_by_urlkey($this->data['gspgid_va'][0],'payment_method');
            if ($method) $order->Payment_method_id=$method->get_id();
		}

        $order->commit();

		gs_eventer::send('order_created',$order);


        return $order;
    }


    function payment_form($d) {
        $fh=new form_handler($this->data,$this->params);
        $clean=$fh->process_form();
        if (!is_array($clean)) return $clean;
        $pmnt=record_by_id($this->data['gspgid_va'][0],'payments');
        $pmnt->details=serialize($clean);
        $pmnt->commit();
        return $pmnt;
    }

	function start_payment($ret) {


		$customer=person($this->CUSTOMERNAME);

	
		//$order=gs_session::load($this->data['gspgid_va'][0]);
		$order=$customer->Orders[$this->data['gspgid_va'][0]];
		$method=$order->Payment_method->first();
		if (isset($this->data['gspgid_va'][1])) {
                $method = is_int($this->data['gspgid_va'][1]) ?
                        record_by_id($this->data['gspgid_va'][1],'payment_method') :
                        record_by_urlkey($this->data['gspgid_va'][1],'payment_method') ;
		}
		if (!$method) {
			$method=new payment_method;
			$method=$method->find_records(array())->first();
		}


		$classname="payments_gw_".$method->type;
		$gateway=new $classname($this->data,$this->params,$method);

		$pmnt=$order->Payments->new_record();
		$pmnt->Currency_id=$order->Currency_id;
		$amount=$order->amount;
		if (!$amount) $amount=$order->price;
		if ($method->Currency->first()) {
			$curr_from=$order->Currency->first()->code;
			if (!$curr_from) {
				$curr_from=new currency(array());
				$curr_from=$curr_from->find_records('array')->first()->code;
			}
			$curr_to=$method->Currency->first()->code;
			if ($curr_to!=$curr_from) {
				$amount=currency_converter::convert_google($amount.$curr_from,$curr_to);
				$pmnt->Currency_id=$method->Currency_id;
			}
		}
		$pmnt->amount=$amount;
		$pmnt->Payment_method_id=$method->get_id();
		$pmnt->status='new';
		$pmnt->type='sale';
		$pmnt->invoiceID=$order->number;
		if (!$pmnt->invoiceID) $pmnt->invoiceID=substr(md5(time()),-8);

		$pmnt->description=$order->description;
		if (!$pmnt->description) $pmnt->description=$pmnt->invoiceID;


		$order->commit();


		gs_var_storage::save('payments_gateway',$gateway);
		gs_var_storage::save('payment',$pmnt);

		$result=$gateway->start($pmnt);

		return $result;
		
	}

}

interface i_payments_gateway {
    public function start($pmnt);
    public function process($pmnt);
    public function validate();
    public function payment_form();
    public function get_transaction_number();
    public function get_transaction_message();
    public function get_transaction_status();
    public function get_transaction_details();

}

abstract class payments_gateway implements i_payments_gateway {
	function __construct($data,$params,$method=null) {
		$this->data=$data;
		$this->params=$params;
        $this->method=$this->info=$method;
	}
	function process($pmnt) {
	}
	function validate() {
		return FALSE;
	}
	function payment_form() {
		return FALSE;
	}
	function get_transaction_number() {
		return NULL;
	}
    function checkstatus() {
            $ret=array();
            $ret['Status']='error';
            $ret['ERROR']='method checkstatus should be implemented'; 
            return $ret;
    }
	function response($ret) {
		return json_encode($ret);
	}


}
