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
                '/ajaxForm'=>'modal_popup_handler.ajaxForm',
                ),
            'get_post'=>array(
				'/ajaxForm'=>'modal_popup_handler.ajaxForm_post',
				),
			);
		return self::add_subdir($data,dirname(__file__));
	}

}

class modal_popup_handler extends gs_handler {
    public function __construct($data=null,$params=null) {
        parent::__construct($data,$params);
        $params=isset($this->data['handler_params']) ? $this->data['handler_params'] : array();
        $params['gspgid']=$this->data['gspgid_v'];
		if (!isset($params['popup_id'])) $params['popup_id']=preg_replace('/\W/','_',$params['gspgid']);
		$this->params=$params;

        $this->tpl=gs_tpl::get_instance();

		$this->tpl->addTemplateDir(cfg('tpl_data_dir').'modules'.DIRECTORY_SEPARATOR.'modal_popup');
        $this->tpl->addTemplateDir(dirname(__FILE__).DIRECTORY_SEPARATOR.'templates');
        $this->tpl->assign('popup_params',$this->params);

	}

	private function original_handler($d) {
		$this->tpl->assign('_gsdata',$this->data);
		$popup_handler=gs_base_handler::process_handler($this->params,$this->tpl);
		$this->tpl->assign('popup_handler',$popup_handler);
		return $this->tpl;
	}

    function popup($d) {
		$tpl=$this->original_handler($d);
        $ret=$tpl->fetch('popup_handler_bootstrap.html');
        return $ret;
    }
    function iframe($d) {
		$tpl=$this->original_handler($d);
        $ret=$tpl->fetch('iframe_handler.html');
        return $ret;
    }
    function ajaxForm($d) {
		$this->tpl->assign('_gsdata',$this->data);
		$popup_handler=$this->tpl->fetch($this->params['gspgid']);
		$this->tpl->assign('popup_handler',$popup_handler);
        $ret=$this->tpl->fetch('ajaxForm_handler.html');
        return $ret;
    }
    function ajaxForm_post($d) {
		$this->tpl->assign('_gsdata',$this->data);
		$popup_handler=$this->tpl->fetch($this->params['gspgid']);
		$this->tpl->assign('popup_handler',$popup_handler);
        $ret=$this->tpl->fetch('ajaxForm_post.html');
		echo $ret;
        return $ret;
    }
}
