<?php

class module_modal extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
	}
	static function get_handlers() {
		$data=array(
            'handler'=>array(
                '/modal'=>'modal_handler.modal',
                ),
			);
		return self::add_subdir($data,dirname(__file__));
	}

}

class modal_handler extends gs_handler {
    function modal($d) {
        $params=$this->data['handler_params'];
        $params['gspgid']=$this->data['gspgid_v'];
        $str="";
        foreach($params as $k=>$v) $str.=sprintf('%s="%s" ',$k,$v);
        $str=sprintf('{handler %s}',$str);

        $tpl=gs_tpl::get_instance();
        $tpl->assign('modal_handler',$tpl->fetch('string:'.$str));
        $tpl->assign('modal_params',$params);
        $tpl->addTemplateDir(dirname(__FILE__).DIRECTORY_SEPARATOR.'templates');

        $ret=$tpl->fetch('modal_handler.html');
        return $ret;
    }
}
