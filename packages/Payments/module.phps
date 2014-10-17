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
            'payment_method',
            'payments',
            'profits',
        ) as $r) {
            $this->$r = new $r;
            $this->$r->install();
        }
    }
    function get_menu() {
        $ret = array();
        $item = array();
        $item[] = '<a href="/admin/Payments/">Payments</a>';
        $item[] = '<a href="/admin/Payments/payment_method">payment_method</a>';
        $item[] = '<a href="/admin/Payments/payments">payments</a>';
        $ret[] = $item;
        return $ret;
    }
    static function get_handlers() {
        $data = array(
            'get' => array(
                '/admin/Payments/payment_method' => array(
                    'gs_base_handler.show:name:adm_payment_method.html',
                ) ,
                '/admin/Payments/payment_method/delete' => array(
                    'gs_base_handler.delete:{classname:payment_method}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/Payments/payment_method/copy' => array(
                    'gs_base_handler.copy:{classname:payment_method}',
                    'gs_base_handler.redirect',
                ) ,
                '/pay' => array(
                    'gs_base_handler.check_login:classname:Users:return:gs_record^e404',
                    'gs_base_handler.show:name:pay.html',
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
                    '' => 'gs_base_handler.show:name:payment_approved.html',
                ) ,
                'declined' => array(
                    'gs_base_handler.show:name:payment_declined.html',
                ) ,
                'error' => array(
                    'gs_base_handler.show:name:payment_error.html',
                ) ,
                'pay' => array(
                    '' => 'payments_handler.start_payment',
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
            ) ,
            'handler' => array(
                '/admin/form/payment_method' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:payment_method:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
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
            ) ,
        );
        return self::add_subdir($data, dirname(__file__));
    }
    static function gl($alias, $rec) {
        $fname = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'gl.php';
        if (file_exists($fname)) {
            $x = include ($fname);
            return $x;
        }
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
class payment_method extends gs_recordset_short {
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'name' => 'fString verbose_name="название"     required=true   multilang=true    ',
            'type' => 'fString verbose_name="type"     required=true       ',
            'parameter1' => 'fString verbose_name="parameter1"     required=false       ',
            'parameter2' => 'fString verbose_name="parameter2"     required=false       ',
            'shipping_method' => 'lMany2Many shipping_method:shipping_method_payment_method verbose_name="shipping_method"    required=false    ',
            'Currency' => 'lOne2One currency verbose_name="Currency"   widget="parent_list"  required=false    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array();
    }
}
class payments extends gs_recordset_short {
    public $no_urlkey = true;
    public $orderby = "id desc";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'amount' => 'fFloat verbose_name="amount"     required=true       ',
            'invoiceID' => 'fString verbose_name="invoiceID"     required=false       ',
            'status_message' => 'fText verbose_name="message"     required=false       ',
            'type' => 'fSelect    options="sale,refund,chargeback"  required=false       ',
            'description' => 'fString verbose_name="description"     required=false       ',
            'status' => 'fSelect verbose_name="status"  widget="select"  default="new"  options="new,pending,approved,declined,fraud,error"  required=true index=true      ',
            'profits_created' => 'fCheckbox     required=true index=true      ',
            'profits_info' => 'fText     required=false       ',
            'Payment_method' => 'lOne2One payment_method    required=false    ',
            'Order' => 'lOne2One orders verbose_name="Order"   widget="input"  required=false    ',
            'Currency' => 'lOne2One currency    required=false    ',
            'Profits' => 'lMany2One profits:Payment    required=false    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array();
        $this->structure['triggers']['before_update'][] = 'trigger_1';
    }
    function trigger_1($rec, $type, $options = array()) {
        $o_h = new payments_handler();
        $o_h->trigger_payment_changed($rec);
    }
}
class profits extends gs_recordset_short {
    public $no_urlkey = true;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'order_amount' => 'fFloat     required=false       ',
            'amount' => 'fFloat     required=true       ',
            'order_type' => 'fString     required=true       ',
            'payed' => 'fCheckbox     required=true       ',
            'payed_date' => 'fDateTime     required=false       ',
            'Manager' => 'lOne2One managers    required=false    ',
            'Order' => 'lOne2One orders    required=true    ',
            'Payment' => 'lOne2One payments    required=true    ',
            'Photograph' => 'lOne2One photographers    required=false    ',
            'Place' => 'lOne2One places    required=false    ',
            'Currency' => 'lOne2One currency    required=true    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array();
    }
}
?>
