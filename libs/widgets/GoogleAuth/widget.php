<?php

class gs_widget_GoogleAuth extends gs_widget{
	function html() {
        /*
		$tpl=gs_tpl::get_instance();
		$tpls=$tpl->template_dir;
		$tpls[]=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates';
		$tpl->setTemplateDir($tpls);
        */
        
        $tpl=new gs_tpl();
		$tpl=$tpl->init();
        $tpl->addTemplateDir(dirname(__FILE__).DIRECTORY_SEPARATOR.'templates');

		$params=$this->params;
		
        $params['value']=$this->value;
		$params['fieldname']=$this->fieldname;

		$tpl->assign('params',$params);
		$tpl->assign('data',$this->data);

		return $tpl->fetch('widget.html');

	}
	function clean() {
        $ret=array($this->fieldname=>$this->value);

        $secretname=$this->fieldname.'_secret';

        $rec=$this->form->rec;
        if ($this->value && !$rec->$secretname) {
            $ga=new GoogleAuthenticator;
            $ret[$secretname]=$ga->generateSecret();
        }
        return $ret;
	}

}
class gs_widget_GoogleAuth_module extends gs_base_module implements gs_module {
    function __construct() {}
    function install() {}
    function get_menu() {}
    static function get_handlers() {
        $data=array(
                  'handler'=>array(
                      '/widgets/GoogleAuth/img'=>array(
                          'gs_widget_GoogleAuth_handler.img',
                      ),
                  ),
              );
        return self::add_subdir($data,dirname(__file__));
    }
}
class gs_widget_GoogleAuth_handler extends gs_handler{
	function img() {
        $secret=$this->data['gspgid_va'][0];
        $user=$this->data['gspgid_va'][1];
        $ga=new GoogleAuthenticator;
        return $ga->getUrl($secret,$user);
	}
}

