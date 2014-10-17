<?php

class gs_widget_iOSCheckbox extends gs_widget{
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
}
class gs_widget_iOSCheckbox_module extends gs_base_module implements gs_module {
    function __construct() {}
    function install() {}
    function get_menu() {}
    static function get_handlers() {
        $data=array(
                   'get'=>array(
                        '/libs/widgets/iOSCheckbox/'=>'gs_widget_iOSCheckbox_handler.public_html',
                   ),
              );
        return self::add_subdir($data,dirname(__file__));
    }
}
class gs_widget_iOSCheckbox_handler extends gs_handler{
	function public_html() {
		$fname=dirname(__FILE__).DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.trim($this->data['gspgid_v'],DIRECTORY_SEPARATOR);
		$fname=realpath($fname);
		if(!$fname) return NULL;
		if (pathinfo($fname, PATHINFO_EXTENSION)=='css') header('Content-type:text/css');
		if (pathinfo($fname, PATHINFO_EXTENSION)=='js') header('Content-type:application/javascript');
		readfile($fname);
	}
}

