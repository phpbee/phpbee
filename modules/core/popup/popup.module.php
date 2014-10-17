<?php

class module_popup extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
	}
	static function get_handlers() {
		$data=array(
            'handler'=>array(
                '/popup'=>'popup_handler.popup',
                ),
			);
		return self::add_subdir($data,dirname(__file__));
	}

}

class popup_handler extends gs_handler {
    function popup($d) {
        $tpl=gs_tpl::get_instance();
        $params=$this->data['handler_params'];
        $params['gspgid']=$this->data['gspgid_v'];

		if (!isset($params['popup_id'])) $params['popup_id']=preg_replace('/\W/','_',$params['gspgid']);
		/*
        $str="";
        foreach($params as $k=>$v) $str.=sprintf('%s="%s" ',$k,$v);
        $str=sprintf('{handler %s}',$str);

        $tpl->assign('popup_handler',$tpl->fetch('string:'.$str));
		*/
		$popup_handler=gs_base_handler::process_handler($params,$tpl);
		$tpl->assign('popup_handler',$popup_handler);
        $tpl->assign('popup_params',$params);
        $tpl->addTemplateDir(dirname(__FILE__).DIRECTORY_SEPARATOR.'templates');

        $ret=$tpl->fetch('popup_handler.html');
        return $ret;
    }
}
