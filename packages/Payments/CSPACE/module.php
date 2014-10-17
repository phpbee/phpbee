<?php

class payments_gw_CSPACE extends payments_gateway {

	function validate() {
        $pmnt=record_by_id($this->data['gspgid_va'][1],'payments');
        if ($pmnt) {
            $info=unserialize($pmnt->details);
            if ($info) $this->validate_result['info']=$info;
            $this->method=$pmnt->Payment_method->first();
        }
		$this->validate_result['info']['PAYMENTID']=$pmnt->get_id();
        $this->request_status_from_bank($pmnt);
		return $this->get_transaction_status()=='approved';
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

    function process($pmnt) {
        if ($pmnt->status && $pmnt->status!='new') return;


		$pmtn->status=$this->validate_result['info']['TRANSACTIONSTATUS']='pending';
		$this->validate_result['info']['TRANSACTIONMESSAGE']='';
		$this->validate_result['info']['TRANSACTIONAMOUNT']=$pmnt->amount;
		$this->validate_result['info']['PAYMENTID']=$pmnt->get_id();

        mlog('process');
        $details=unserialize($pmnt->details);
        if (!is_array($details)) $details=array();

        $order=$pmnt->Order->first();

        mlog($this->method->get_values());

        $url="https://cloudspaceto.me/1stpayments/gwprocessor2.php";
        $params=array(
            "a"     =>  'init',
            "guid"	=>	$this->method->parameter1,
            "pwd"	=>	sha1($this->method->parameter2),
            "rs"	=>	$this->method->parameter3,
            //"merchant_transaction_id"	=>	sprintf("%06s",$pmnt->get_id()).'-'.rand(1000,10000),
            "merchant_transaction_id"	=>	sprintf("%06s",$pmnt->get_id()),
            "user_ip"	=>	$_SERVER['REMOTE_ADDR'],
            "description"	=>	 'Order #'.$order->get_id(),
            "amount"	=>	round(100*$pmnt->amount,PHP_ROUND_HALF_DOWN),
            "currency"	=>	$pmnt->Currency->first()->code,
            "name_on_card"	=>	$details['cardholder'],
            "street"	=>	$details['street'],
            "zip"	=>	$details['zip'],
            "city"	=>	$details['city'],
            "country"	=>	 $details['country'],
            "state"	=>	$details['state'],
            "email"	=>	$details['email'],
            "phone"	=>	$details['phone'],
            "card_bin"	=>	substr(str_replace(array('-'," "),'',$details['cardnumber']),0,6),
            "merchant_site_url"	=>	parse_url($this->method->parameter4,PHP_URL_HOST),
            "f_extended" => 1,
        );

        mlog($url);
        mlog($params);

        $ret=html_fetch($url,$params,'POST');
        mlog($ret);
        $ret=explode(':',$ret,3);

        $pmnt->transaction_number=$ret[1];
        $pmnt->commit();

        if ($ret[0]!='OK') {
            $this->validate_result['info']['TRANSACTIONSTATUS']='declined';
            $this->validate_result['info']['TRANSACTIONMESSAGE']=$ret[2];
            $pmnt->details=serialize($this->get_transaction_details());
            return;
        }
		$this->validate_result['info']['TRANSACTIONID']=$ret[1];



        $cc_type='VISA';
        if(substr($details['cardnumber'],0,1)==5) $cc_type='MC';

        $params=array(
            'a' =>  'charge',
            'init_transaction_id'   =>  $this->get_transaction_number(),
            'cc'    => $details['cardnumber'],
            'cvv'   =>  $details['cvv'],
            'cc_type' => $cc_type,
            'expire'    =>  $details['expmonth'].'/'.substr($details['expyear'],-2),
            'f_extended' => 1,
            );

        $ret=html_fetch($url,$params,'POST');
        mlog($ret);
        $ret=explode(':',$ret,4);

        /*

        if ($ret[2]=='Status' && $ret[3]=='Success') {
            $this->validate_result['info']['TRANSACTIONSTATUS']='approved';
            $this->validate_result['info']['TRANSACTIONMESSAGE']=$ret[4];
        } else {
            $this->validate_result['info']['TRANSACTIONSTATUS']='declined';
            $this->validate_result['info']['TRANSACTIONMESSAGE']=implode(':',$ret);
        }
        */

        if($ret[0]=='Redirect') {
            $pmnt->details=serialize($this->get_transaction_details());
            $pmnt->commit();
            header('Location:'.$ret[1].':'.urldecode($ret[2]));
            die();
        }

        $this->request_status_from_bank();


    }

    private function request_status_from_bank($pmnt) {
        $url="https://cloudspaceto.me/1stpayments/gwprocessor2.php";
        $params=array(
            'a' =>  'status_request',
            'request_type' => 'transaction_status',
            'init_transaction_id'   =>  $this->get_transaction_number(),
            "guid"	=>	$this->method->parameter1,
            "pwd"	=>	sha1($this->method->parameter2),
            );


        $ret=html_fetch($url,$params,'POST');
        mlog($ret);
        $ret=explode(':',$ret,4);


        if ($ret[0]=='Status' && $ret[1]=='Success') {
            $this->validate_result['info']['TRANSACTIONSTATUS']='approved';
            $this->validate_result['info']['TRANSACTIONMESSAGE']='approved';
        } else {
            $this->validate_result['info']['TRANSACTIONSTATUS']='declined';
            $this->validate_result['info']['TRANSACTIONMESSAGE']=implode(':',$ret);
        }

        mlog($this->get_transaction_details());

        $pmnt->details=serialize($this->get_transaction_details());
        $pmnt->commit();
    }

	function start($pmnt) {
		$method=$pmnt->Payment_method->first();
        $order=$pmnt->Order->first();
		$customer=$order->Customer->first();
		$url="https://cloudspaceto.me/";
		$data=array(
            'op'=>'payments',
            'id'=>$pmnt->get_id(),
            'amount'=>$pmnt->amount,
            'type'=>'paypal',
            'r'=>'8osn8z',
            'merch'=>'trp1',
            'e'=>$customer->Email,
            );
		return html_redirect($url,$data);
	}

    function sendResponse($status, $message = ''){
            $response = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
            $response .= '<result>'."\n";
            $response .= '<code>'.$status.'</code>'."\n";
            $response .= '<comment>'.$message.'</comment>'."\n";
            $response .= '</result>';

            mlog($response);

            return $response;
    }



}
