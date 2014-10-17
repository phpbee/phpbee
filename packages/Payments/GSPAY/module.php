<?php

class payments_gw_gspay extends payments_gateway {

	function validate() {
		$this->validate_result=$this->checkGspayOrder($this->data['transactionTransactionID']);
		return $this->validate_result['info']['RESULTSTATUS']=='approved';
		
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
		return $this->validate_result['info']['ORDERID'];
	}
	function get_transaction_amount() {
		return sprintf("%.02f",$this->validate_result['info']['TRANSACTIONAMOUNT']);
	}

	function checkGspayOrder($transactionTransactionID) {

		$ret=array();
		$ret['info']['RESULTSTATUS']=NULL;
		$ret['info']['TRANSACTIONID']=NULL;

	      $url="https://secure.redirect2pay.com/payment/api.php";
		$values=array(
		'request'=>"
			<xml>
			<request>
			<transaction>
				<transactionType>transactionStatus</transactionType>
				<transactionTransactionID>".$transactionTransactionID."</transactionTransactionID>
			</transaction>

			
			</request>
			</xml>
		
		",
		);


		$ret['request']=$values;

		try {
			$result=$ret['result']=html_fetch($url,$values,'POST');
		} catch (gs_exception $e) {
			$ret['ERROR']=$e->get_message();
			return $ret;
		}



		$requestxml=$result;
		$p = xml_parser_create();
		if (!  xml_parse_into_struct($p, $requestxml, $vals, $index)) {
			$ret['ERROR']='can not parse XML result';
			return $ret;
		}
		xml_parser_free($p);


		if (is_array($vals)) foreach ($vals as $key=>$value) {
			if ($value[type]=='complete') {
				$transinfo[$value[tag]]=trim($value[value]);
			}
		}

		$ret['info']=$transinfo;

		return $ret;


	}

	function start($pmnt) {
		var_dump($pmnt->get_values());
		
		$url="https://secure.redirect2pay.com/payment/pay.php";
		$method=$pmnt->Payment_method->first();
		$customer=$pmnt->Order->first()->Customer->first();
		$data=array(
			'siteID'=>$method->parameter1,
			'OrderDescription'=>$pmnt->description,
			'OrderID'=>$pmnt->get_id(),
			'InvoiceID'=>$pmnt->invoiceID,
			'Amount'=>$pmnt->amount,
			'customerEmail'=>$customer->email,
			'customerPhone'=>$customer->phone,
			'customerFullName'=>"$customer->first_name $customer->last_name",
			'returnUrl'=>"http://".cfg('host')."/Payments/return/gspay",
			'TransactionMode'=>$method->parameter2,
			);
		return html_redirect($url,$data);
	}



}
