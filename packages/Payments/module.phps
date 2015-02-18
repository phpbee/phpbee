<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 5                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Original Author <author@example.com>                        |
// |          Your Name <you@example.com>                                 |
// +----------------------------------------------------------------------+
//
// $Id:$

gs_dict::append(array());
class module{%$MODULE_NAME%} extends gs_base_module implements gs_module {
    function __construct() {
    }
    function install() {
        foreach (array(
            'order_status',
            'orders',
            'payment_method',
            'payments',
            'profits',
            'payout_method',
            'currency',
            'discount_codes',
        ) as $r) {
            $this->$r = new $r;
            $this->$r->install();
        }
    }
    function get_menu() {
        $ret = array();
        $item = array();
        $item[] = '<a href="/admin/Payments/">Payments</a>';
        $item[] = '<a href="/admin/Payments/order_status">order_status</a>';
        $item[] = '<a href="/admin/Payments/orders">orders</a>';
        $item[] = '<a href="/admin/Payments/payment_method">payment_method</a>';
        $item[] = '<a href="/admin/Payments/payments">payments</a>';
        $item[] = '<a href="/admin/Payments/profits">profits</a>';
        $item[] = '<a href="/admin/Payments/payout_method">payout_method</a>';
        $item[] = '<a href="/admin/Payments/currency">currency</a>';
        $item[] = '<a href="/admin/Payments/discount_codes">discount_codes</a>';
        $ret[] = $item;
        return $ret;
    }
    static function get_handlers() {
        $data = array(
            'get' => array(
                '/admin/Payments/order_status' => array(
                    'gs_base_handler.show:name:adm_order_status.html',
                ) ,
                '/admin/Payments/currency/copy' => array(
                    'gs_base_handler.copy:{classname:currency}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/Payments/currency/delete' => array(
                    'gs_base_handler.delete:{classname:currency}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/Payments/payment_method/copy' => array(
                    'gs_base_handler.copy:{classname:payment_method}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/Payments/payment_method/delete' => array(
                    'gs_base_handler.delete:{classname:payment_method}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/Payments/payment_method' => array(
                    'gs_base_handler.show:name:adm_payment_method.html',
                ) ,
                '/admin/Payments/currency' => array(
                    'gs_base_handler.show:name:adm_currency.html',
                ) ,
                '/pay' => array(
                    'gs_base_handler.show:name:pay.html',
                    'gs_base_handler.check_login:classname:Users:return:gs_record^e404',
                    'end' => 'end',
                    'e404' => 'gs_base_handler.redirect:href:/login',
                ) ,
                'return' => array(
                    'payments_handler.payment_completed:return:true&approved^declined',
                    'approved' => 'gs_base_handler.redirect_gl:gl:payment_approved',
                    'end' => 'end',
                    'declined' => 'gs_base_handler.redirect_gl:gl:payment_declined',
                    'end' => 'end',
                    'error' => 'gs_base_handler.redirect_gl:gl:payment_error',
                ) ,
                'approved' => array(
                    'gs_base_handler.show:name:payment_approved.html',
                ) ,
                'declined' => array(
                    'gs_base_handler.show:name:payment_declined.html',
                ) ,
                'error' => array(
                    'gs_base_handler.show:name:payment_error.html',
                ) ,
                'pay' => array(
                    'payments_handler.start_payment',
                ) ,
                '/admin/Payments/payments' => array(
                    'gs_base_handler.show:name:adm_payments.html',
                ) ,
                '/admin/Payments/payments/delete' => array(
                    'gs_base_handler.delete:{classname:payments}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/Payments/payments/copy' => array(
                    'gs_base_handler.copy:{classname:payments}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/Payments/payment_status' => array(
                    'gs_base_handler.show:name:adm_payment_status.html',
                ) ,
                '/admin/Payments/payment_status/delete' => array(
                    'gs_base_handler.delete:{classname:payment_status}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/Payments/payment_status/copy' => array(
                    'gs_base_handler.copy:{classname:payment_status}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/Payments/payout_method' => array(
                    'gs_base_handler.show:name:adm_payout_method.html',
                ) ,
                '/admin/Payments/payout_method/delete' => array(
                    'gs_base_handler.delete:{classname:payout_method}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/Payments/payout_method/copy' => array(
                    'gs_base_handler.copy:{classname:payout_method}',
                    'gs_base_handler.redirect',
                ) ,
                'repay' => array(
                    'payments_handler.repay',
                ) ,
                '/admin/Payments/order_status/delete' => array(
                    'gs_base_handler.delete:{classname:order_status}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/Payments/order_status/copy' => array(
                    'gs_base_handler.copy:{classname:order_status}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/Payments/orders' => array(
                    'gs_base_handler.show:name:adm_orders.html',
                ) ,
                '/admin/Payments/orders/delete' => array(
                    'gs_base_handler.delete:{classname:orders}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/Payments/orders/copy' => array(
                    'gs_base_handler.copy:{classname:orders}',
                    'gs_base_handler.redirect',
                ) ,
                'cart/add/item' => array(
                    'gs_base_handler.rec_by_id:classname:item',
                    'discount_handler.apply:return:not_false',
                    'payments_cart_handler.add_once:return:not_false',
                    'gs_base_handler.redirect',
                ) ,
                'cart/remove/item' => array(
                    'gs_base_handler.rec_by_id:classname:item',
                    'payments_cart_handler.remove:return:not_false',
                    'gs_base_handler.redirect',
                ) ,
                'checkout' => array(
                    'payments_handler.checkout',
                    'payments_cart_handler.empty_cart',
                    'gs_base_handler.redirect_gl:gl:pay_order',
                ) ,
                'cart/add/code' => array(
                    'payments_cart_handler.add_discount_code',
                    'gs_base_handler.redirect',
                ) ,
                'discount/remove' => array(
                    'payments_cart_handler.remove_discount:return:not_false',
                    'gs_base_handler.redirect',
                ) ,
                'checkstatus' => array(
                    'payments_handler.payment_checkstatus',
                ) ,
                'callback' => array(
                    'payments_handler.payment_callback',
                ) ,
                'form' => array(
                    'gs_base_handler.show:name:payment_form.html',
                ) ,
                'process' => array(
                    'payments_handler.process_payment',
                    'gs_base_handler.redirect_gl:gl:return',
                ) ,
                'payment_callback_checkstatus' => array(
                    'payments_handler.payment_callback_checkstatus',
                ) ,
            ) ,
            'handler' => array(
                '/admin/inline_form/payment_method' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:payment_method}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/inline_form/currency' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:currency}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/form/currency' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:currency:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/form/payment_method' => array(
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:payment_method:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/form/payments' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:payments:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/form/payment_status' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:payment_status:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/form/payout_method' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:payout_method:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/form/order_status' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:order_status:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/inline_form/order_status' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:order_status}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/form/orders' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:orders:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/inline_form/orders' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:orders}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                'cart/discount_code/add' => array(
                    'payments_cart_handler.add_discount_code_form',
                    'gs_base_handler.redirect',
                ) ,
                'payment_form' => array(
                    'payments_handler.payment_form',
                    'gs_base_handler.redirect_gl:gl:process_payment',
                ) ,
                'approved/anonymous_email' => array(
                    'form_handler.process_form:return:array',
                    'payments_handler.get_email:return:string',
                ) ,
            ) ,
            'post' => array(
                'form' => array(
                    'gs_base_handler.show:name:payment_form.html',
                ) ,
            ) ,
        );
        return self::add_subdir($data, dirname(__file__));
    }
    static function gl($alias, $rec, $data) {
        $fname = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'gl.php';
        if (file_exists($fname)) {
            $x = include ($fname);
            return $x;
        }
        return parent::gl($alias, $rec, $data);
    }
    /*
    static function gl($alias,$rec) {
    if(!is_object($rec)) {
    $obj=new tw{%$MODULE_NAME%};
    $rec=$obj->get_by_id(intval($rec));
    }
    switch ($alias) {
    case '___show____':
    return sprintf('/{%$MODULE%}/show/%s/%d.html',
    		date('Y/m',strtotime($rec->date)),
    		$rec->get_id());
    break;
    }
    }
    */
}
/*
class handler{%$MODULE_NAME%} extends gs_base_handler {
}
*/
class order_status extends gs_recordset_short {
    public $no_urlkey = 1;
    public $sortkey = true;
    public $orderby = "sortkey";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'name' => 'fString verbose_name="name"     required=true unique=true index=true      ',
            'title' => 'fString verbose_name="title"     required=false    multilang=true    ',
            'Orders' => 'lMany2One orders:Status verbose_name="Orders"    required=false    ',
        ) , $init_opts);
    }
}
class orders extends gs_recordset_short {
    public $no_urlkey = 1;
    public $orderby = "id desc";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'invoice' => 'fString verbose_name="invoice"     required=true unique=true index=true      ',
            'cart' => 'fText     required=false        ',
            'orderdate' => 'fTimestamp verbose_name="orderdate"     required=true  index=true      ',
            'comments' => 'fText verbose_name="Comments"     required=false        ',
            'support_comments' => 'fText verbose_name="Support comments"     required=false        ',
            'price' => 'fString verbose_name="price"     required=true        ',
            'browser_info' => 'fText     required=false        ',
            'Customer' => 'lOne2One girls    required=true    ',
            'Status' => 'lOne2One order_status verbose_name="Status"   widget="121_radio_notnull"  required=true    ',
            'Payments' => 'lMany2One payments:Order verbose_name="Payments"    required=false    ',
            'Currency' => 'lOne2One currency verbose_name="Currency"   widget="parent_list"  required=false    ',
            'Payment_method' => 'lOne2One payment_method verbose_name="Payment_method"   widget="parent_list"  required=false    ',
            'Contest' => 'lOne2One contests verbose_name="Contest"    required=false    ',
            'Girl' => 'lOne2One girls verbose_name="Girl"    required=false    ',
            'Vote' => 'lOne2One votes verbose_name="Vote"    required=false    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array(
            array(
                'link' => 'Customer',
                'on_delete' => 'CASCADE',
                'on_update' => 'CASCADE'
            ) ,
            array(
                'link' => 'Status',
                'on_delete' => 'SET_NULL',
                'on_update' => 'SET_NULL'
            ) ,
        );
    }
}
class payment_method extends gs_recordset_short {
    public $no_urlkey = 0;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'name' => 'fString verbose_name="название"     required=true    multilang=true    ',
            'type' => 'fString verbose_name="type"     required=true        ',
            'parameter1' => 'fString verbose_name="parameter1"     required=false        ',
            'parameter2' => 'fString verbose_name="parameter2"     required=false        ',
            'parameter3' => 'fString verbose_name="parameter3"     required=false        ',
            'parameter4' => 'fString verbose_name="parameter4"     required=false        ',
            'Currency' => 'lOne2One currency verbose_name="Currency"   widget="parent_list"  required=false    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array(
            array(
                'link' => 'Currency',
                'on_delete' => 'SET_NULL',
                'on_update' => 'SET_NULL'
            ) ,
        );
    }
}
class payments extends gs_recordset_short {
    public $no_urlkey = 1;
    public $orderby = "id desc";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'amount' => 'fFloat verbose_name="amount"     required=true        ',
            'invoiceID' => 'fString verbose_name="invoiceID"     required=false        ',
            'status_message' => 'fText verbose_name="message"     required=false        ',
            'type' => 'fSelect    options="sale,refund,chargeback"  required=false        ',
            'description' => 'fString verbose_name="description"     required=false        ',
            'status' => 'fSelect verbose_name="status"  widget="select"  default="new"  options="new,pending,approved,declined,fraud,error"  required=true  index=true      ',
            'profits_created' => 'fCheckbox   default="0"   required=true  index=true      ',
            'profits_info' => 'fText     required=false        ',
            'transaction_number' => 'fString     required=false  index=true      ',
            'details' => 'fText     required=false        ',
            'Payment_method' => 'lOne2One payment_method    required=false    ',
            'Order' => 'lOne2One orders verbose_name="Order"   widget="input"  required=false    ',
            'Currency' => 'lOne2One currency    required=false    ',
            'Profits' => 'lMany2One profits:Payment    required=false    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array();
        $this->structure['triggers']['before_update'][] = 'trigger_3';
    }
    function trigger_3($rec, $type, $options = array()) {
        //$o_h=new payments_handler();
        //$o_h->trigger_payment_changed($rec);
        
    }
}
class profits extends gs_recordset_short {
    public $no_urlkey = 1;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'order_amount' => 'fFloat   default="0"   required=false        ',
            'amount' => 'fFloat   default="0"   required=true        ',
            'order_type' => 'fString     required=true        ',
            'payed' => 'fCheckbox   default="0"   required=true        ',
            'payed_date' => 'fDateTime     required=false        ',
            'Order' => 'lOne2One orders    required=true    ',
            'Payment' => 'lOne2One payments    required=true    ',
            'Currency' => 'lOne2One currency    required=true    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array();
    }
}
class payout_method extends gs_recordset_short {
    public $no_urlkey = 1;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'name' => 'fString verbose_name="name"     required=false    multilang=true    ',
        ) , $init_opts);
    }
}
class currency extends gs_recordset_short {
    public $no_urlkey = 1;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'code' => 'fString verbose_name="code"    options="3"  required=true        ',
            'name' => 'fString verbose_name="name"     required=true    multilang=true    ',
        ) , $init_opts);
    }
}
class discount_codes extends gs_recordset_short {
    public $no_urlkey = 1;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'code' => 'fString verbose_name="code"     required=true  index=true      ',
            'discount' => 'fInt verbose_name="discount"   default="20"   required=true  index=true      ',
            'discount_type' => 'fSelect verbose_name="discount_type"  widget="select"  default="%"  options="%,$"  required=true  index=true      ',
            'expdate' => 'fTimestamp verbose_name="expire date"   default="+2 week"   required=true  index=true      ',
        ) , $init_opts);
    }
}
?>
