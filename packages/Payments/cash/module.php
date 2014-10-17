<?php

class payments_gw_cash extends payments_gateway {

	function validate() {
		return true;	
	}
	function get_transaction_status() {
		return 'pending';
	}
	function get_transaction_number() {
		return 0;

	}
	function get_transaction_message() {
		return NULL;
	}
	function get_payment_id() {
		return gs_session::load('pw_gw_cash_order_id');
	}
	function get_transaction_amount() {
		return 0;
	}


	function start($pmnt) {
		gs_session::save($pmnt->get_id(),'pw_gw_cash_order_id');
		return html_redirect($this->data['gspgid_a'][0].'/return/'.$this->data['gspgid_va'][0]);
	}



}
