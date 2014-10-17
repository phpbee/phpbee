<?php

class gs_widget_TextLines extends gs_widget{
	function __construct($fieldname,$data,$params=array(),$form=NULL) {
		parent::__construct($fieldname,$data,$params,$form);
		if(!is_array($this->value)) $this->value=explode("\n",$this->value);
		$this->value=array_filter($this->value);
		$this->value[]='';
	}
	function html() {
		$tpl=gs_tpl::get_instance();
		//$tpl->template_dir[]=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates';
		$tpl->addTemplateDir(dirname(__FILE__).DIRECTORY_SEPARATOR.'templates');
		$params=$this->params;
		$params['value']=$this->value;
		$params['fieldname']=$this->fieldname;

		$tpl->assign('params',$params);
		$tpl->assign('data',$this->data);

		return $tpl->fetch('widget.html');

	}
	function clean() {
		return implode("\n",$this->value);
	}

}
