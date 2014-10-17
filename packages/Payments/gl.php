<?php
	switch($alias) {
		case 'cart_remove':
			return '/Payments/cart/remove/'.$rec->get_recordset_name().'/'.$rec->get_id();
		case 'cart':
			return '/Payments/cart/add/'.$rec->get_recordset_name().'/'.$rec->get_id();
		case 'cart_remove_discount':
			return '/Payments/discount/remove/'.$rec;
		case 'payment_approved':
            $pmnt=gs_var_storage::load('payment');
			return sprintf('/Payments/approved/%d',$pmnt->get_id());
		case 'payment_declined':
            $pmnt=gs_var_storage::load('payment');
			return sprintf('/Payments/declined/%d',$pmnt->get_id());
		case 'payment_error':
            //$gateway=gs_var_storage::load('payments_gateway');
            //gs_session::save($gateway,'payments_gateway');
            $pmnt=gs_var_storage::load('payment');
			return sprintf('/Payments/error/%d',$pmnt->get_id());
        case 'pay_order':
            return sprintf('Payments/pay/%s',$rec->get_id());
        case 'process_payment':
            return sprintf('Payments/process/%s',$rec->get_id());
        case 'return':
            return sprintf('Payments/return/%s/%d',$rec->Payment_method->first()->type,$rec->get_id());
        case 'members_area':
            return 'http://'.cfg('active_domain').'/members_area';
	}
	return parent::gl($alias,$rec,$data);
