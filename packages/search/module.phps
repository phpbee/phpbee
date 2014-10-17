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
            'search_config',
            'search_config_filter',
        ) as $r) {
            $this->$r = new $r;
            $this->$r->install();
        }
    }
    function get_menu() {
        $ret = array();
        $item = array();
        $item[] = '<a href="/admin/search/">search</a>';
        $item[] = '<a href="/admin/search/search_config">search_config</a>';
        $item[] = '<a href="/admin/search/search_config_filter">filters</a>';
        $ret[] = $item;
        return $ret;
    }
    static function get_handlers() {
        $data = array(
            'get' => array(
                '/admin/search/search_config_filter' => array(
                    'gs_base_handler.show:name:adm_search_config_filter.html',
                ) ,
                '/admin/search/search_config_filter/delete' => array(
                    'gs_base_handler.delete:{classname:search_config_filter}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/search/search_config_filter/copy' => array(
                    'gs_base_handler.copy:{classname:search_config_filter}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/search/search_config/delete' => array(
                    'gs_base_handler.delete:{classname:search_config}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/search/search_config/copy' => array(
                    'gs_base_handler.copy:{classname:search_config}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/search/search_config' => array(
                    'gs_base_handler.show:name:adm_search_config.html',
                ) ,
                '' => array(
                    'search' => 'search_handler.search:return:array',
                    'gs_base_handler.show:name:search.html',
                ) ,
                'commit' => array(
                    'search_handler.commit',
                ) ,
                'index' => array(
                    'search_handler.index',
                ) ,
                'dropindex' => array(
                    'search_handler.dropindex',
                ) ,
            ) ,
            'handler' => array(
                '/admin/form/search_config' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:search_config:form_class:form_search_config}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/inline_form/search_config' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:search_config}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/form/search_config_filter' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:search_config_filter:form_class:form_search_config_filter}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/inline_form/search_config_filter' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:search_config_filter}',
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
class search_config extends gs_recordset_short {
    public $no_urlkey = 1;
    public $sortkey = true;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'alias' => 'fString verbose_name="alias"     required=true unique=true index=true      ',
            'database_connector_id' => 'fSelect verbose_name="database_connector_id"  widget="select"    required=true        ',
            'Filters' => 'lMany2One search_config_filter:Parent verbose_name="Filters"    required=false    ',
        ) , $init_opts);
    }
}
class search_config_filter extends gs_recordset_short {
    public $no_urlkey = 1;
    public $sortkey = true;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'group_name' => 'fString verbose_name="group_name"     required=true  index=true      ',
            'filter_classname' => 'fSelect verbose_name="filter_classname"  widget="select"    required=true        ',
            'gl' => 'fString verbose_name="module_name:gl_name"     required=false        ',
            'Parent' => 'lOne2One search_config verbose_name="Parent"   widget="parent_list"  required=false    ',
            'Recordset' => 'lOne2One wz_recordsets verbose_name="Recordset"   widget="parent_list"  required=true    ',
            'Fields' => 'lMany2Many wz_recordset_fields verbose_name="Fields"   widget="multiselect_chosen"  required=false    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array(
            array(
                'link' => 'search_config.Filters',
                'on_delete' => 'CASCADE',
                'on_update' => 'CASCADE'
            ) ,
            array(
                'link' => 'Recordset',
                'on_delete' => 'CASCADE',
                'on_update' => 'CASCADE'
            ) ,
        );
    }
}
?>
