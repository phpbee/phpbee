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
            'person_role_cfg',
            'person_variable_cfg',
        ) as $r) {
            $this->$r = new $r;
            $this->$r->install();
        }
    }
    function get_menu() {
        $ret = array();
        $item = array();
        $item[] = '<a href="/admin/person_role/">person_role</a>';
        $item[] = '<a href="/admin/person_role/person_role_cfg">person_role_cfg</a>';
        $item[] = '<a href="/admin/person_role/person_variable_cfg">person_variable_cfg</a>';
        $ret[] = $item;
        return $ret;
    }
    static function get_handlers() {
        $data = array(
            'get' => array(
                '/admin/person_role/person_role_cfg' => array(
                    'gs_base_handler.show:name:adm_person_role_cfg.html',
                ) ,
                '/admin/person_role/person_role_cfg/delete' => array(
                    'gs_base_handler.delete:{classname:person_role_cfg}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/person_role/person_role_cfg/copy' => array(
                    'gs_base_handler.copy:{classname:person_role_cfg}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/person_role/person_variable_cfg' => array(
                    'gs_base_handler.show:name:adm_person_variable_cfg.html',
                ) ,
                '/admin/person_role/person_variable_cfg/delete' => array(
                    'gs_base_handler.delete:{classname:person_variable_cfg}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/person_role/person_variable_cfg/copy' => array(
                    'gs_base_handler.copy:{classname:person_variable_cfg}',
                    'gs_base_handler.redirect',
                ) ,
            ) ,
            'handler' => array(
                '/admin/form/person_role_cfg' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:person_role_cfg:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/inline_form/person_role_cfg' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:person_role_cfg}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/form/person_variable_cfg' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:person_variable_cfg:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/inline_form/person_variable_cfg' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:person_variable_cfg}',
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
class person_role_cfg extends gs_recordset_short {
    public $no_urlkey = 1;
    public $sortkey = true;
    public $no_ctime = true;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'name' => 'fString verbose_name="name"     required=true unique=true index=true      ',
            'login_options' => 'fString verbose_name="login_options"     required=false        ',
            'login_recordset' => 'lOne2One wz_recordsets verbose_name="login_recordset"   widget="parent_list"  required=false    ',
            'login_fields' => 'lMany2Many wz_recordset_fields verbose_name="login_fields"   widget="lMany2Many_chosen"  required=false    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array(
            array(
                'link' => 'login_recordset',
                'on_delete' => 'SET_NULL',
                'on_update' => 'SET_NULL'
            ) ,
        );
    }
}
class person_variable_cfg extends gs_recordset_short {
    public $no_urlkey = 1;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'variable_name' => 'fString verbose_name="variable_name"     required=true unique=true index=true      ',
            'Role' => 'lMany2Many person_role_cfg verbose_name="Save var to"   widget="lMany2Many_chosen"  required=true    ',
        ) , $init_opts);
    }
}
?>
