<?php

class payments_gw_ROBOKASSA extends payments_gateway {
	private $_pament_id=null;
	private $_pament=null;

	function validate() {
		$this->_pament_id=(int)$this->data['inv_id'];
		$this->validate_result=$this->checkOrder($this->_pament_id);
		return $this->get_transaction_status()=='approved';

	}
	function get_transaction_status() {
		if (isset($this->validate_result['ERROR'])) return 'error';
		if ($this->validate_result['info']['CODE']=='100') return 'approved';
		if ($this->validate_result['info']['CODE']=='50') return 'approved';
		return 'declined';
		/*
		if ($this->validate_result['info']['TRANSACTIONSTATUS']=='fraud') return 'fraud';
		if ($this->validate_result['info']['TRANSACTIONSTATUS']=='declined') return 'declined';
		return $this->validate_result['info']['TRANSACTIONSTATUS'];
		*/
	}
	function get_transaction_number() {
		return $this->validate_result['info']['DATE'];

	}
	function get_transaction_message() {
		return $this->validate_result['info']['DESCRIPTION'];
	}
	function get_payment_id() {
		return $this->validate_result['PAYMENTID'];
	}
	function get_transaction_amount() {
		return sprintf("%.02f",$this->validate_result['info']['OUTSUM']);
	}

	function get_transaction_details() {
		return $this->validate_result['info'];
	}

	function checkOrder($transactionTransactionID) {


		$ret=array();
		$ret['info']['RESULTSTATUS']=NULL;
		$ret['info']['TRANSACTIONID']=NULL;
		$ret['PAYMENTID']=NULL;

		$this->_payment=$pmnt=record_by_id($transactionTransactionID,'payments');
		if (!$pmnt) {
			$ret['ERROR']='can not find payment by id '.$transactionTransactionID;
			return $ret;
		};
		$ret['PAYMENTID']=$pmnt->get_id();
		$method=$pmnt->Payment_method->first();

		$url="https://auth.robokassa.ru/Merchant/WebService/Service.asmx/OpState";
		$data=array(
				'MerchantLogin'=>$method->parameter1,
				'InvoiceID'=>$pmnt->get_id(),
				);
		$data['Signature']=md5(implode(':',array($data['MerchantLogin'],$data['InvoiceID'],$method->parameter3)));


		try {
			$result=$ret['result']=html_fetch($url,$data,'GET');
		} catch (gs_exception $e) {
			$ret['ERROR']=$e->get_message();
			return $ret;
		}


		$requestxml=$result;
		/*
		$p = xml_parser_create();
		if (!  xml_parse_into_struct($p, $requestxml, $vals, $index)) {
			$ret['ERROR']='can not parse XML result';
			return $ret;
		}
		xml_parser_free($p);
		

		md($vals,1);



		if (is_array($vals)) foreach ($vals as $key=>$value) {
			if ($value['type']=='complete') {
				$transinfo[$value['tag']]=trim($value['value']);
			}
		}
		*/


		$xml=simplexml_load_string($result);
		if (!$xml) {
			$ret['ERROR']='can not parse XML result';
			return $ret;
		}
		if (!$xml->State) {
			$ret['ERROR']='incompleted XML result';
			return $ret;
		}
		$transinfo=array();
		$transinfo['CODE']=$xml->State->Code->__toString();
		$transinfo['DATE']=$xml->State->StateDate->__toString();
		$transinfo['OUTSUM']=$xml->Info->OutSum->__toString();
		$transinfo['DESCRIPTION']=$xml->Info->PaymentMethod->Description->__toString();


		$ret['info']=$transinfo;





		return $ret;


	}

	function start($pmnt) {
		$method=$pmnt->Payment_method->first();
		$customer=$pmnt->Order->first()->Customer->first();
		$amount=sprintf('%.02f',$pmnt->amount);
		$crc  = (implode(':',array($method->parameter1,$amount,$pmnt->get_id(),$method->parameter2))); //"$mrh_login:$out_summ:$inv_id:$mrh_pass1");
		$data=array(
				'MerchantLogin'=>$method->parameter1,
				'OutSum'=>$amount,
				'InvoiceID'=>$pmnt->get_id(),
				'Description'=>$pmnt->description,
				'SignatureValue'=>md5($crc),
				);
		$url="https://auth.robokassa.ru/Merchant/Index.aspx";

		return html_redirect($url,$data);
	}

	function print_callback_result($ret) {
		echo "OK".$this->_payment->get_id();
		die();
	}



}
