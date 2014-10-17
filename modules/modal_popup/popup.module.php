<?php

class module_modal_popup extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
	}
	static function get_handlers() {
		$data=array(
            'handler'=>array(
                '/modal_popup'=>'modal_popup_handler.popup',
                '/iframe'=>'modal_popup_handler.iframe',
                ),
			);
		return self::add_subdir($data,dirname(__file__));
	}

}

class modal_popup_handler extends gs_handler {
    public function __construct($data=null,$params=null) {
        parent::__construct($data,$params);
        $params=$this->data['handler_params'];
        $params['gspgid']=$this->data['gspgid_v'];
		if (!isset($params['popup_id'])) $params['popup_id']=preg_replace('/\W/','_',$params['gspgid']);
		$this->params=$params;
	}

	private function original_handler($d) {
        $tpl=gs_tpl::get_instance();

		$popup_handler=gs_base_handler::process_handler($this->params,$tpl);
		$tpl->assign('popup_handler',$popup_handler);
        $tpl->assign('popup_params',$this->params);
		return $tpl;
	}

    function popup($d) {
		$tpl=$this->original_handler($d);
        $tpl->addTemplateDir(dirname(__FILE__).DIRECTORY_SEPARATOR.'templates');
        $ret=$tpl->fetch('popup_handler.html');
        return $ret;
    }
    function iframe($d) {
		$tpl=$this->original_handler($d);
        $tpl->addTemplateDir(dirname(__FILE__).DIRECTORY_SEPARATOR.'templates');
        $ret=$tpl->fetch('iframe_handler.html');
        return $ret;
    }
}
