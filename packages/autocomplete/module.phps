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
            'autocomplete_cfg',
        ) as $r) {
            $this->$r = new $r;
            $this->$r->install();
        }
    }
    function get_menu() {
        $ret = array();
        $item = array();
        $item[] = '<a href="/admin/autocomplete/">autocomplete</a>';
        $item[] = '<a href="/admin/autocomplete/autocomplete_cfg">autocomplete_cfg</a>';
        $ret[] = $item;
        return $ret;
    }
    static function get_handlers() {
        $data = array(
            'get' => array(
                '/admin/autocomplete/autocomplete_cfg' => array(
                    'gs_base_handler.show:name:adm_autocomplete_cfg.html',
                ) ,
                '/admin/autocomplete/autocomplete_cfg/delete' => array(
                    'gs_base_handler.delete:{classname:autocomplete_cfg}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/autocomplete/autocomplete_cfg/copy' => array(
                    'gs_base_handler.copy:{classname:autocomplete_cfg}',
                    'gs_base_handler.redirect',
                ) ,
                '/autocomplete/plain' => array(
                    'cfg' => 'gs_base_handler.rec_by_fieldname:classname:autocomplete_cfg:fieldname:name',
                    'input' => 'autocomplete_handler.input_jquery:return:array',
                    'result' => 'autocomplete_handler.process:return:array',
                    'autocomplete_handler.array_values:return:array',
                    'autocomplete_handler.output_json:return:true',
                ) ,
                '/autocomplete' => array(
                    'cfg' => 'gs_base_handler.rec_by_fieldname:classname:autocomplete_cfg:fieldname:name',
                    'input' => 'autocomplete_handler.input_jquery:return:array',
                    'result' => 'autocomplete_handler.process:return:array',
                    'autocomplete_handler.output_json:return:true',
                ) ,
            ) ,
            'handler' => array(
                '/admin/form/autocomplete_cfg' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:autocomplete_cfg:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/inline_form/autocomplete_cfg' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:autocomplete_cfg}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
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
class autocomplete_cfg extends gs_recordset_short {
    public $no_urlkey = 1;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'name' => 'fString verbose_name="name"     required=true unique=true index=true      ',
            'params' => 'fText verbose_name="params"     required=false        ',
            'limit' => 'fInt verbose_name="limit"   default="0"   required=false        ',
            'searchtype' => 'fSelect verbose_name="Search type"    options="STARTS,FULLTEXT,LIKE"  required=true  index=true      ',
            'fields' => 'fString verbose_name="fields"     required=false        ',
            'Recordset' => 'lOne2One wz_recordsets verbose_name="Recordset"   widget="parent_list"  required=true    ',
            'Fields' => 'lMany2Many wz_recordset_fields verbose_name="Fields"   widget="multiselect_chosen"  required=false    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array(
            array(
                'link' => 'Recordset',
                'on_delete' => 'SET_NULL',
                'on_update' => 'CASCADE'
            ) ,
        );
    }
}
?>
