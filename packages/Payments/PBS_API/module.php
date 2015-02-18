<?php

class payments_gw_pbs_api_module extends gs_base_module implements gs_module {
	function install() {}
	function get_menu() {}
	static function get_handlers() {
		$data=array(
				'handler'=>array(
					'form'=>array(
						'form_handler.process_form:return:array',
						'payments_gw_pbs_api_handler.form',
						),
					),		
				'get'=>array(
					'form'=>array(
						'gs_base_handler.show:name:payments_pbs_api_start.html',
						),
					),
				);

		return self::add_subdir($data,dirname(__file__));
	}
}

class payments_gw_pbs_api_handler extends gs_handler {

	function form($ret) {

		$this->data['ip']=$_SERVER['REMOTE_ADDR'];
		gs_session::save($this->data,'payments_gw_pbs_api_data');
        $pmnt=gs_session::load('payments_gw_pbs_api');

		$url='/Payments/return/PBS_API/';//.$pmnt->get_id();
		return html_redirect($url);
	}
}

class payments_gw_pbs_api extends payments_gateway {

	/*

	function start($pmnt) {
		$method=$pmnt->Payment_method->first();
		$url="https://fotowithyou.com/Payments/return/pbs_api/".$pmnt->invoiceID;
		return html_redirect($url);

	}
	*/

	function start($pmnt) {
        gs_session::save($pmnt,'payments_gw_pbs_api');

		$url='/Payments/PBS_API/form/'.$this->data['gspgid_v'];
		html_redirect($url);
	}

	function validate() {

		$pmnt=gs_session::load('payments_gw_pbs_api');
		$data=gs_session::load('payments_gw_pbs_api_data');
		$method=$pmnt->Payment_method->first();


		$this->validate_result=$this->makePbsAPITransaction($method,$pmnt,$data);

		return $this->get_transaction_status()=='approved';
		
	}
	function get_transaction_status() {
		if (1==$this->validate_result['info']['TRANSACTIONSTATUS']) return 'approved';
		if (0==$this->validate_result['info']['TRANSACTIONSTATUS']) return 'declined';
		if (-1==$this->validate_result['info']['TRANSACTIONSTATUS']) return 'pending';
		if (-2==$this->validate_result['info']['TRANSACTIONSTATUS']) return 'unconfirmed';
		return 'error';
	}
	function get_transaction_details() {
		return $this->validate_result;
	}
	function get_transaction_number() {
		return $this->validate_result['info']['TRANSACTIONID'];

	}
	function get_payment_id() {
		return $this->validate_result['info']['ORDERID'];
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

	function makePbsAPITransaction($m,$p,$d) {
		$ret=array();
		$ret['info']['TRANSACTIONSTATUS']=NULL;
		$ret['info']['TRANSACTIONID']=NULL;



		$v=array(
			'merNo'=>$m->parameter1,
			'gatewayNo'=>$m->parameter2,
			'orderNo'=>$p->get_id(),
			'orderCurrency'=>$m->Currency->f()->code,
			'orderAmount'=>sprintf("%.02f",$p->amount),
			'paymentMethod'=>'Credit Card',
			'cardNo'=>$d['cardNo'],
			'cardExpireMonth'=>$d['cardExpireMonth'],
			'cardExpireYear'=>$d['cardExpireYear'],
			'cardSecurityCode'=>$d['cardSecurityCode'],
			'issuingBank'=>$d['issuingBank'] ? $d['issuingBank'] : 'Unknown',
			'customerID'=>$p->get_id(),
			'firstName'=>$d['firstName'],
			'lastName'=>$d['lastName'],
			'email'=>$d['email'],
			'phone'=>$d['phone'],
			'country'=>$d['country'],
			'state'=>$d['state'],
			'city'=>$d['city'],
			'address'=>$d['address'],
			'zip'=>$d['zip'],
			'ip'=>$d['ip']
			);

		$control=
			$v['merNo'].
			$v['gatewayNo'].
			$v['orderNo'].
			$v['orderCurrency'].
			$v['orderAmount'].
			$v['customerID'].
			$v['firstName'].
			$v['lastName'].
			$v['cardNo'].
			$v['cardExpireYear'].
			$v['cardExpireMonth'].
			$v['cardSecurityCode'].
			$v['email'].
			$m->parameter3;

		$v['signInfo']=hash('sha256',$control);





		$url="https://secureonlinemart.com/TestTPInterface";
		$ret['request']=$values;
		try {
			$result=$ret['result']=html_fetch($url,$v,'POST');
		} catch (gs_exception $e) {
			$ret['ERROR']=$e->get_message();
			md($ret);
			return $ret;
		}

		mlog($url);
		mlog($ret);

		$xml=simplexml_load_string($result);

		$res=array();
		foreach ($xml as $k=>$v) {
			$res[$k]=trim($v);
		}
		mlog($res);


		$ret['info']['TRANSACTIONID']=$res['tradeNo'];
		$ret['info']['TRANSACTIONSTATUS']=$res['orderStatus'];
		$ret['info']['TRANSACTIONMESSAGE']=$res['orderInfo'];
		$ret['info']['ORDERID']=$res['orderNo'];
		$ret['info']['TRANSACTIONAMOUNT']=$res['orderAmount'];

		return $ret;
	}

	function checkPbsOrder($transactionTransactionID) {

		$ret=array();
		$ret['info']['TRANSACTIONSTATUS']=NULL;
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
