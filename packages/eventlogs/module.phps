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
            'eventlogs_events',
            'eventlogs',
        ) as $r) {
            $this->$r = new $r;
            $this->$r->install();
        }
    }
    function get_menu() {
        $ret = array();
        $item = array();
        $item[] = '<a href="/admin/eventlogs/">eventlogs</a>';
        $item[] = '<a href="/admin/eventlogs/eventlogs_events">eventlogs_events</a>';
        $ret[] = $item;
        return $ret;
    }
    static function get_handlers() {
        $data = array(
            'get' => array(
                '/admin/eventlogs/eventlogs_events' => array(
                    'gs_base_handler.show:name:adm_eventlogs_events.html',
                ) ,
                '/admin/eventlogs/eventlogs_events/delete' => array(
                    'gs_base_handler.delete:{classname:eventlogs_events}',
                    'gs_base_handler.redirect',
                ) ,
                '/admin/eventlogs/eventlogs_events/copy' => array(
                    'gs_base_handler.copy:{classname:eventlogs_events}',
                    'gs_base_handler.redirect',
                ) ,
            ) ,
            'handler' => array(
                '/admin/form/eventlogs_events' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:admin_form.html:classname:eventlogs_events:form_class:g_forms_table}',
                    'gs_base_handler.redirect_if:gl:save_continue:return:true',
                    'gs_base_handler.redirect_if:gl:save_return:return:true',
                ) ,
                '/admin/inline_form/eventlogs_events' => array(
                    'gs_base_handler.redirect_if:gl:save_cancel:return:true',
                    'gs_base_handler.post:{name:inline_form.html:classname:eventlogs_events}',
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
class eventlogs_events extends gs_recordset_short {
    public $no_urlkey = 1;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'title' => 'fString verbose_name="title"     required=false        ',
            'event' => 'fString verbose_name="event"     required=false  index=true      ',
            'role' => 'fString verbose_name="role"     required=false  index=true      ',
            'class' => 'fString verbose_name="class"     required=false  index=true      ',
            'active' => 'fCheckbox verbose_name="active"   default="0"   required=true  index=true      ',
        ) , $init_opts);
    }
}
class eventlogs extends gs_recordset_short {
    public $no_urlkey = 1;
    public $orderby = "id";
    function __construct($init_opts = false) {
        parent::__construct(array(
            'event' => 'fString verbose_name="event"     required=false  index=true      ',
            'role' => 'fString verbose_name="role"     required=false  index=true      ',
            'person_id' => 'fInt     required=false  index=true      ',
            'class' => 'fString     required=false  index=true      ',
            'record_id' => 'fInt     required=false  index=true      ',
            'url' => 'fString     required=false  index=true      ',
            'info' => 'fText     required=false        ',
            'Config' => 'lOne2One eventlogs_events verbose_name="Config"   widget="parent_list"  required=false    ',
        ) , $init_opts);
        $this->structure['fkeys'] = array(
            array(
                'link' => 'Config',
                'on_delete' => 'SET_NULL',
                'on_update' => 'SET_NULL'
            ) ,
        );
    }
}
?>
