<?php

class payments_gw_TEST extends payments_gateway {

	function validate() {
        $this->payment=gs_session::load('payments_gw_test_payment');
		$this->validate_result=$this->data['status'];
		return $this->validate_result=='approved';
		
	}
	function get_transaction_status() {
        if ($this->validate_result=='approved') return 'approved';
        return $this->validate_result;
	}
	function get_payment_id() {
		return $this->payment->id;

	}
	function get_transaction_message() {
		return 'TEST_message '.$this->validate_result;
	}
	function get_transaction_number() {
		return 'TEST_paymentid '.trim(time());
	}

    function get_transaction_details() {
        return 'TEST_details '.$this->validate_result;
    }


	function start($pmnt) {
        gs_session::save($pmnt,'payments_gw_test_payment');
        $tpl=gs_tpl::get_instance();
		$dir=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates';
        $tpl->addTemplateDir($dir);
        $tpl->assign('gw',$this);
        $bh=new gs_base_handler(array(),array('name'=>'payments_gw_test_start.html'));
        return $bh->show(array());
	}



}
