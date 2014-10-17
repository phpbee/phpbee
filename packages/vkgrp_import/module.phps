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
            'vkgrp_import_cfg',
        ) as $r) {
            $this->$r = new $r;
            $this->$r->install();
        }
    }
    function get_menu() {
        $ret = array();
        $item = array();
        $item[] = '<a href="/admin/vkgrp_import/">vkgrp_import</a>';
        $item[] = '<a href="/admin/vkgrp_import/vkgrp_import_cfg">vkgrp_import_cfg</a>';
        $ret[] = $item;
        return $ret;
    }
    static function get_handlers() {
        $data = array(
            'handler' => array(
                '/admin/form/vkgrp_import_cfg' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:vkgrp_import_cfg:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/inline_form/vkgrp_import_cfg' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:vkgrp_import_cfg}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
            ) ,
            'get' => array(
                '/admin/vkgrp_import/vkgrp_import_cfg/copy' => array(
                    'gs_base_handler.copy:{classname:vkgrp_import_cfg}',
                    'gs_base_handler.redirect',
                ) ,
                'execute' => array(
                    'vkgrp_import_handler.execute',
                ) ,
                '/admin/vkgrp_import/vkgrp_import_cfg' => array(
                    'gs_base_handler.show:name:adm_vkgrp_import_cfg.html',
                ) ,
                '/admin/vkgrp_import/vkgrp_import_cfg/delete' => array(
                    'gs_base_handler.delete:{classname:vkgrp_import_cfg}',
                    'gs_base_handler.redirect',
                ) ,
                'execute/get_token' => array(
                    'vkgrp_import_handler.get_token',
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
class vkgrp_import_cfg extends gs_recordset_short {
    public $no_urlkey = true;
    public $no_ctime = true;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'group_id' => 'fString verbose_name="group_id"     required=false        ',
            'name' => 'fString verbose_name="name"     required=false        ',
            'disabled' => 'fCheckbox verbose_name="disabled"     required=false        ',
            'rec_default_values' => 'fString verbose_name="rec_default_values"     required=false        ',
            'APP_ID' => 'fString verbose_name="APP_ID"     required=false        ',
            'APP_SECRET' => 'fString verbose_name="APP_SECRET"     required=false        ',
            'SCOPE' => 'fString verbose_name="SCOPE"   default="groups,offline"   required=false        ',
            'TOKEN' => 'fText     required=false        ',
            'only_with_body' => 'fCheckbox verbose_name="body not empty"     required=true        ',
            'only_with_images' => 'fCheckbox verbose_name="only with images"     required=true        ',
            'max_count' => 'fInt verbose_name="max articles count"   default="25"   required=true        ',
            'min_body_length' => 'fInt verbose_name="min_body_length"     required=false        ',
            'recordset' => 'lOne2One wz_recordsets verbose_name="recordset"   widget="parent_list"  required=true    ',
            'title_fieldname' => 'lOne2One wz_recordset_fields verbose_name="title_fieldname"   widget="parent_list"  required=false    ',
            'description_fieldname' => 'lOne2One wz_recordset_fields verbose_name="description_fieldname"   widget="parent_list"  required=false    ',
            'images_linkname' => 'lOne2One wz_recordset_links verbose_name="images_linkname"   widget="parent_list"  required=false    ',
            'link_fieldname' => 'lOne2One wz_recordset_fields verbose_name="link_fieldname"   widget="parent_list"  required=false    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array();
    }
}
?>
