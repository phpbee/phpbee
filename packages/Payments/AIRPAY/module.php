<?php

require("airpay.class.php");
class payments_gw_AIRPAY extends payments_gateway {

	function validate() {

		$this->pmnt=$pmnt=record_by_id($this->data['transaction_id'],'payments');
		$method=$pmnt->Payment_method->first();
		$airpay = new airpay($method->parameter1, $method->parameter2);
		$this->validate_result=$airpay->request($pmnt->transaction_number);
		

		return $this->validate_result['status_id']==1;

	}
	function get_transaction_status() {
		if ($this->validate_result['status_id']==1) return 'approved';
		if ($this->validate_result['status_id']==2) return 'pending';
		if ($this->validate_result['status_id']==3) return 'declined';
		if ($this->validate_result['status_id']==4) return 'expired';
		if ($this->validate_result['status_id']==5) return 'refunded';
		return 'error';
	}
	function get_transaction_number() {
		return $this->validate_result['transaction_id'];

	}
	function get_transaction_message() {
		return $this->validate_result['payment_system_status'];
	}
	function get_payment_id() {
		return $this->validate_result['mc_transaction_id'];
	}
	function get_transaction_amount() {
		return sprintf("%.02f",$this->validate_result['amount']/100);
	}

	function get_transaction_details() {
		return $this->validate_result;
	}

	function start($pmnt) {
		$method=$pmnt->Payment_method->first();
		$order=$pmnt->Order->first();
		$customer=$order->Customer->first();




		$amount=ceil($pmnt->amount*100);

		$invoice = array(
				'amount'	=> $amount,					// minor units, e.g. 1 for 0.01
				'currency'	=> $order->Currency->f()->code,				// currency code in ISO 4217
				'invoice'	=> $pmnt->get_id(),	// unique transaction value
				'language'	=> 'ENG',				// language: LAT, RUS, ENG
				'cl_fname'	=> $customer->Lang['en']->firstname,				// client's first name
				'cl_lname'	=> $customer->Lang['en']->lastname,				// client's last name
				'cl_email'	=> $customer->email,	// client's e-mail address
				'cl_country'	=> $customer->Lang['en']->country,				// country code in ISO 3166-1-alpha-2
				'cl_city'	=> $customer->Lang['en']->city,				// city name
				'description'	=> $pmnt->get_id(),			// description of the transaction, visible to the client, e.g. description of the product
				//		'psys'		=> 'paypal', 				// payment system alias. empty for default or taken from $airpay->psystems
				);

		if (!$invoice['cl_fname']) $invoice['cl_fname']=$customer->firstname;
		if (!$invoice['cl_lname']) $invoice['cl_lname']=$customer->lastname;

		$airpay = new airpay( $method->parameter1, $method->parameter2);
		$ret=$airpay->payment_req($invoice);
		if ($ret['status']=='OK') {
			$pmnt->transaction_number=$ret['transaction_id'];
			$pmnt->commit();
			return html_redirect($ret['url']);
		} 
		mlog($pmnt->get_values());
		mlog($customer->get_values());
		mlog($invoice);
		mlog($ret);
		md($pmnt->get_values(),1);
		md($invoice,1);
		md($ret,1);

		return $ret;

	}

}
