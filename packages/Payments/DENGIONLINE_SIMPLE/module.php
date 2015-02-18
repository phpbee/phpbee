<?php

class payments_gw_DENGIONLINE_SIMPLE extends payments_gateway {

	function validate() {

		$ret=array();
		$ret['info']['TRANSACTIONSTATUS']='failed';
		$ret['info']['TRANSACTIONMESSAGE']='';
		$ret['info']['TRANSACTIONAMOUNT']=$this->data['amount'];
		$ret['info']['TRANSACTIONID']=$this->data['paymentid'];

        $rec=null;
        if (isset($this->data['nick_extra'])) {
            $ret['info']['ORDERID']=$this->data['nick_extra'];
            $ret['info']['PAYMENTID']=$this->data['order_id'];
            $rec=record_by_id($this->data['nick_extra'],'orders');
        }
        if (isset($this->data['userid_extra'])) {
            $ret['info']['ORDERID']=$this->data['userid_extra'];
            $ret['info']['PAYMENTID']=$this->data['orderid'];
            $rec=record_by_id($this->data['userid_extra'],'orders');
        }

        if (!$rec) { 
            $ret['ERROR']='order not found'; 
        } else {
            $ret['info']['TRANSACTIONSTATUS']='declined';

            if ($this->data['paymentid'] && $this->data['amount'] && isset($this->data['key'])) {
                $ret['info']['TRANSACTIONSTATUS']='approved';
            }
        }


        $this->validate_result=$ret;

		return $this->validate_result['info']['TRANSACTIONSTATUS']=='approved';
		
	}
    function checkstatus() {
		mlog($this->data,1);
		mlog($_REQUEST);
        $rec=record_by_id($this->data['userid_extra'],'orders');
        if (!$rec) { 
            $ret['ERROR']='order not found'; 
            return $ret;
        }
        $ret=$rec->get_values('id');
        $ret['Status']=$rec->Status->first()->name;
		mlog($ret);
        return $ret;
    }
	function get_transaction_status() {
		if (isset($this->validate_result['ERROR'])) return 'error';
		if ($this->validate_result['info']['TRANSACTIONSTATUS']=='approved') return 'approved';
		if ($this->validate_result['info']['TRANSACTIONSTATUS']=='fraud') return 'fraud';
		if ($this->validate_result['info']['TRANSACTIONSTATUS']=='declined') return 'declined';
		return $this->validate_result['info']['TRANSACTIONSTATUS'];
	}
	function get_transaction_number() {
		return $this->validate_result['info']['TRANSACTIONID'];

	}
	function get_transaction_message() {
		return $this->validate_result['info']['TRANSACTIONMESSAGE'];
	}
	function get_payment_id() {
		return $this->validate_result['info']['PAYMENTID'];
	}
	function get_transaction_amount() {
		return sprintf("%.02f",$this->validate_result['info']['TRANSACTIONAMOUNT']);
	}

    function get_transaction_details() {
        return $this->validate_result['info'];
    }


	function start($pmnt) {
		$method=$pmnt->Payment_method->first();
        $order=$pmnt->Order->first();
		$customer=$order->Customer->first();
		$url="http://paymentgateway.ru/";
		$data=array(
			'project'=>$method->parameter1,
			'source'=>$method->parameter1,
			'order_id'=>$pmnt->get_id(),
			'nickname'=>$order->get_id(),
            'nick_extra'=>$order->get_id(),
            'amount'=>$pmnt->amount,
            'paymentCurrency'=>$pmnt->Currency->first()->code,
            'mode_type'=>34,
            );
		return html_redirect($url,$data);
	}

    function response($ret){
            $response = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $response .= '<result>'."\n";
            $response .= '<code>'.($ret['id']?'YES':'NO').'</code>'."\n";
            $response .= '</result>';

            mlog($response);

            return $response;
    }



}
