<?php

class gs_widget_slider extends gs_widget_int {
	function html() {
		$tpl=gs_tpl::get_instance();
		$tpls=$tpl->template_dir;
		$tpls[]=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates';
		$tpl->setTemplateDir($tpls);
		$params=$this->params;
		$params['value']=$this->value;
		$params['fieldname']=$this->fieldname;
		list($min,$max)=explode(":",$this->params['attributes']['range']);
		$params['min']=$min;
		$params['max']=$max;
		$tpl->assign('params',$params);
		$tpl->assign('data',$this->data);

		return $tpl->fetch('widget.html');

	}
}