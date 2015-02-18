<?php

class payments_gw_gspay_api extends payments_gateway {

	function validate() {
		$data=$this->data;
		$this->validate_result=$this->makeGspayAPITransaction($data);

		if ($this->validate_result['info']['TRANSACTIONID']) {
			$this->validate_result=$this->checkGspayOrder($this->validate_result['info']['TRANSACTIONID']);
		}
		return $this->validate_result['info']['RESULTSTATUS']=='approved';
		
	}
	function get_transaction_number() {
		return $this->validate_result['info']['TRANSACTIONID'];

	}
	function get_transaction_message() {
		return $this->validate_result['info']['TRANSACTIONMESSAGE'];
	}
	function get_merchant_order_id() {
		return $this->validate_result['info']['ORDERID'];
	}
	function get_transaction_amount() {
		return sprintf("%.02f",$this->validate_result['info']['TRANSACTIONAMOUNT']);
	}
	function get_transaction_status() {
		if (isset($this->validate_result['ERROR'])) return 'error';
		if ($this->validate_result['info']['TRANSACTIONSTATUS']=='approved') return 'approved';
		if ($this->validate_result['info']['TRANSACTIONSTATUS']=='fraud') return 'fraud';
		if ($this->validate_result['info']['TRANSACTIONSTATUS']=='declined') return 'declined';
		return $this->validate_result['info']['TRANSACTIONSTATUS'];
	}
    function get_transaction_details() {
        return $this->validate_result['info'];
    }
	function start($pmnt) {
	}


	function makeGspayAPITransaction($d) {
		$ret=array();
		$ret['info']['RESULTSTATUS']=NULL;
		$ret['info']['TRANSACTIONID']=NULL;

		$xml=simplexml_load_string("<?xml version='1.0'?><request></request>");
		$merchant=$xml->addChild('merchant');
		$merchant->merchantID=7953;
		$merchant->merchantSiteID=61468;
		$merchant->merchantPassword=123456;
		/*	
		$merchant->merchantID=5000;
		$merchant->merchantSiteID=6001;
		$merchant->merchantPassword=1234;
		*/

		$transaction=$xml->addChild('transaction');
		$transaction->transactionType='sale';
		$transaction->transactionAmount=sprintf('%.02f',abs($d['Amount']));
		$transaction->transactionOrderID=$d['OrderID'];
		$transaction->transactionDescription="Add funds to www.allflac.com account";

		$customer=$xml->addChild('customer');
		foreach($d as $k=>$v) {
			$customer->$k=$v;
		}
		$customer->customerExpireMonth=sprintf('%02d',$d['customerExpireDate_Month']);
		$customer->customerExpireYear=sprintf('%02d',$d['customerExpireDate_Year']);
		$customer->customerIP=$_SERVER['REMOTE_ADDR'];
		$customer->customerBrowser='unknown';
		$customer->customerLanguage='unknown';
		$customer->customerScreenResolution='unknown';

		$values=array('request'=>xml_print($xml->asXML()));

		$url="https://secure.redirect2pay.com/payment/api.php";
		$ret['request']=$values;
		try {
			$result=$ret['result']=html_fetch($url,$values,'POST');
		} catch (gs_exception $e) {
			$ret['ERROR']=$e->get_message();
			return $ret;
		}
		$xml=simplexml_load_string($result);

		$ret['info']['TRANSACTIONID']=trim($xml->result->transactionID);
		$ret['info']['RESULTSTATUS']=trim($xml->result->resultStatus);
		$ret['info']['TRANSACTIONMESSAGE']=trim($xml->result->resultDescription);
		return $ret;
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



}
