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
            'roundrobin_cfg',
            'roundrobin',
            'roundrobin_cfg_stack',
            'roundrobin_stack',
        ) as $r) {
            $this->$r = new $r;
            $this->$r->install();
        }
    }
    function get_menu() {
        $ret = array();
        $item = array();
        $item[] = '<a href="/admin/roundrobin/">roundrobin</a>';
        $item[] = '<a href="/admin/roundrobin/roundrobin_cfg">roundrobin_cfg</a>';
        $ret[] = $item;
        return $ret;
    }
    static function get_handlers() {
        $data = array(
            'get' => array(
                '/admin/roundrobin/roundrobin_cfg' => array(
                    'gs_base_handler.show:name:adm_roundrobin_cfg.html',
                ) ,
                '/admin/roundrobin/roundrobin_cfg/delete' => array(
                    'gs_base_handler.delete:{classname:roundrobin_cfg}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/roundrobin/roundrobin_cfg/copy' => array(
                    'gs_base_handler.copy:{classname:roundrobin_cfg}',
                    'gs_base_handler.redirect',
                ) ,
                'rotate' => array(
                    'roundrobin_handler.rotate',
                ) ,
                '/admin/roundrobin/roundrobin_stack' => array(
                    'gs_base_handler.show:name:adm_roundrobin_stack.html',
                ) ,
                '/admin/roundrobin/roundrobin_stack/delete' => array(
                    'gs_base_handler.delete:{classname:roundrobin_stack}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/roundrobin/roundrobin_stack/copy' => array(
                    'gs_base_handler.copy:{classname:roundrobin_stack}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/roundrobin/roundrobin_cfg_stack' => array(
                    'gs_base_handler.show:name:adm_roundrobin_cfg_stack.html',
                ) ,
                '/admin/roundrobin/roundrobin_cfg_stack/delete' => array(
                    'gs_base_handler.delete:{classname:roundrobin_cfg_stack}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/roundrobin/roundrobin_cfg_stack/copy' => array(
                    'gs_base_handler.copy:{classname:roundrobin_cfg_stack}',
                    'gs_base_handler.redirect',
                ) ,
                'stack' => array(
                    'roundrobin_handler.stack',
                ) ,
            ) ,
            'handler' => array(
                '/admin/form/roundrobin_cfg' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:roundrobin_cfg:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/inline_form/roundrobin_cfg' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:roundrobin_cfg}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/form/roundrobin_cfg_stack' => array(
                    'gs_base_handler.post:{name:admin_form.html:classname:roundrobin_cfg_stack:form_class:g_forms_table:return:gs_record}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/form/roundrobin_stack' => array(
                    'gs_base_handler.post:{name:admin_form.html:classname:roundrobin_stack:form_class:g_forms_table:return:gs_record}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/inline_form/roundrobin_stack' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:roundrobin_stack}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/inline_form/roundrobin_cfg_stack' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:roundrobin_cfg_stack}',
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
class roundrobin_cfg extends gs_recordset_short {
    public $no_urlkey = 1;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'name' => 'fString verbose_name="name"     required=true unique=true index=true      ',
            'event_name' => 'fString verbose_name="event_name"     required=true  index=true      ',
            'timeslot' => 'fString verbose_name="timeslot"     required=true        ',
            'slotcount' => 'fInt verbose_name="slotcount"   default="1"   required=true  index=true      ',
            'rs_intvalue_field' => 'fString verbose_name="rs_intvalue_field"     required=false        ',
            'rs_stringvalue_field' => 'fString verbose_name="rs_stringvalue_field"     required=false        ',
            'Recordset' => 'lOne2One wz_recordsets verbose_name="Recordset"   widget="parent_list"  required=true    ',
            'Stack' => 'lMany2One roundrobin_cfg_stack:Parent verbose_name="Stack"    required=false    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array(
            array(
                'link' => 'Recordset',
                'on_delete' => 'CASCADE',
                'on_update' => 'CASCADE'
            ) ,
        );
    }
}
class roundrobin extends gs_recordset_short {
    public $no_urlkey = 1;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'name' => 'fString     required=true  index=true      ',
            'slot' => 'fInt   default="0"   required=true  index=true      ',
            'counter' => 'fFloat   default="0"   required=true  index=true      ',
            'record_id' => 'fInt     required=true  index=true      ',
            'recordset_name' => 'fString     required=true  index=true      ',
            'add_intvalue' => 'fInt     required=false  index=true      ',
            'add_stringvalue' => 'fString     required=false  index=true      ',
            'Config' => 'lOne2One roundrobin_cfg verbose_name="Config"    required=true    ',
            'Stack' => 'lMany2One roundrobin_stack:Parent    required=false    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array(
            array(
                'link' => 'Config',
                'on_delete' => 'CASCADE',
                'on_update' => 'CASCADE'
            ) ,
        );
    }
}
class roundrobin_cfg_stack extends gs_recordset_short {
    public $no_urlkey = 1;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'name' => 'fString verbose_name="name"     required=false        ',
            'slotcount' => 'fInt verbose_name="slotcount"   default="1"   required=true  index=true      ',
            'startslot' => 'fInt verbose_name="startslot"   default="0"   required=true        ',
            'Parent' => 'lOne2One roundrobin_cfg    required=false    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array(
            array(
                'link' => 'roundrobin_cfg.Stack',
                'on_delete' => 'CASCADE',
                'on_update' => 'CASCADE'
            ) ,
        );
    }
}
class roundrobin_stack extends gs_recordset_short {
    public $no_urlkey = 1;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'name' => 'fString verbose_name="name"     required=false        ',
            'counter' => 'fFloat   default="0"   required=true  index=true      ',
            'slot' => 'fInt   default="0"   required=false  index=true      ',
            'record_id' => 'fInt     required=true  index=true      ',
            'recordset_name' => 'fString     required=true  index=true      ',
            'add_intvalue' => 'fInt     required=false  index=true      ',
            'add_stringvalue' => 'fString     required=false  index=true      ',
            'Config' => 'lOne2One roundrobin_cfg    required=false    ',
            'Stack' => 'lOne2One roundrobin_cfg_stack    required=false    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array(
            array(
                'link' => 'roundrobin.Stack',
                'on_delete' => 'CASCADE',
                'on_update' => 'CASCADE'
            ) ,
            array(
                'link' => 'Stack',
                'on_delete' => 'CASCADE',
                'on_update' => 'CASCADE'
            ) ,
        );
    }
}
?>
