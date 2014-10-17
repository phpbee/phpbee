<?php
class gs_widget_ImageJSEdit_module extends gs_base_module implements gs_module {
	function __construct() {}
	function install() {}
	function get_menu() {}
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/widgets/ImageJSEdit/action'=>array(
				'gs_widget_ImageJSEdit_handler.action',
				'gs_base_handler.redirect',
			),
		),
		'post'=>array(	
			'/widgets/ImageJSEdit/upload'=>array(
				'gs_widget_ImageJSEdit_handler.upload',
			),
		),
		'get'=>array(
			'/libs/widgets/ImageJSEdit/'=>'gs_widget_ImageJSEdit_handler.public_html',
			'/widgets/ImageJSEdit/'=>'gs_widget_ImageJSEdit_handler.public_html',
		),
		);
		return self::add_subdir($data,dirname(__file__));
	}
}

class gs_widget_ImageJSEdit_handler extends gs_handler {
	function public_html() {
		$fname=dirname(__FILE__).DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.trim($this->data['gspgid_v'],DIRECTORY_SEPARATOR);
		$fname=realpath($fname);
		if(!$fname) return NULL;
		if (pathinfo($fname, PATHINFO_EXTENSION)=='jpg') header('Content-type:image/jpeg');
		if (pathinfo($fname, PATHINFO_EXTENSION)=='png') header('Content-type:image/png');
		if (pathinfo($fname, PATHINFO_EXTENSION)=='css') header('Content-type:text/css');
		if (pathinfo($fname, PATHINFO_EXTENSION)=='js') header('Content-type:application/javascript');
        if (pathinfo($fname, PATHINFO_EXTENSION)=='swf') header('Content-type:application/x-shockwave-flash');
		readfile($fname);
	}

	function upload() {
		$this->value=$_FILES['Filedata'];
		md($this->value,1);
		if (!isset($this->value['tmp_name'])) return false;

		$tmpname=cfg('var_dir').DIRECTORY_SEPARATOR.'ImageJSEdit.data';
		if (!move_uploaded_file($this->value['tmp_name'],$tmpname)) return false;

		md($tmpname,1);

		$ret=array(
				/*
				'data'=>file_get_contents($this->value['tmp_name']),
				*/
				'filename'=>$this->value['name'],
				'mimetype'=>$this->value['type'],
				'size'=>$this->value['size'],
			 );
		$ret['data']=file_get_contents($tmpname);
		if (stripos($this->value['type'],'image')===0) {
			list($ret['width'],$ret['height'])=getimagesize($tmpname);
		}
		md("=======",1);
		gs_session::save($ret,'ImageJSEdit_data');
		return "";
	}

	function li($f) {
        /*
		$tpl=gs_tpl::get_instance();
		$tpl->template_dir=cfg('lib_dir').DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'ImageJSEdit'.DIRECTORY_SEPARATOR.'templates';
        */
        $tpl=new gs_tpl();
		$tpl=$tpl->init();
        $tpl->addTemplateDir(dirname(__FILE__).DIRECTORY_SEPARATOR.'templates');
		$tpl->assign('i',$f);
		echo $tpl->fetch('li_ImageJSEdit.html');
	}
}

class gs_widget_ImageJSEdit extends gs_widget{
	function html() {
        $tpl=new gs_tpl();
		$tpl=$tpl->init();
		$tpl->assign('form',$this->form);
		$tpl->assign('name',$this->fieldname);
        $tpl->addTemplateDir(dirname(__FILE__).DIRECTORY_SEPARATOR.'templates');

		return $tpl->fetch('widget_ImageJSEdit.html');

	}
	function clean() {
		$ret=$this->form->record->{$this->fieldname};
		if (!is_array($ret)) $ret=array();
		foreach ($this->value as $k=>$v) {
			$ret[$k]=$v;
		}
		return array($this->fieldname=>$ret);
	}
}
