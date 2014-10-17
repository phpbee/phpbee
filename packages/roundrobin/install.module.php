<?php

class module_roundrobin_install extends gs_base_module implements gs_module {
    function __construct() {
    }
    function install() {
        $cfg=new roundrobin_cfg;
        foreach ($cfg->find_records(array()) as $c) {
            gs_eventer::subscribe($c->event_name, 'roundrobin_listener::add');
        }
        $gs_connector=$cfg->get_connector();

        $sql=file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'rouundrobin.sql');
        if (!$sql) throw new gs_exception('module_roundrobin_install: can not load mysql procedure source code');
        $gs_connector->query('DROP PROCEDURE IF EXISTS rr_stack');
        $gs_connector->query($sql);

    }
    function get_menu() {
    }
    static function get_handlers() {
    }
}

