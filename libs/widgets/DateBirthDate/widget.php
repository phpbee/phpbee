<?php

class gs_widget_DateBirthDate extends gs_widget{
	function html() {
		$tpl=gs_tpl::get_instance();
		//$tpl->template_dir[]=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates';
		$tpls=$tpl->template_dir;
		$tpls[]=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates';
		$tpl->setTemplateDir($tpls);
		$params=$this->params;
		
		if (!is_array($this->value)) {
			$date=strtotime($this->value);
			$params['value']['Date_Year']=date('Y',$date);
			$params['value']['Date_Month']=date('m',$date);
			$params['value']['Date_Day']=date('d',$date);
		} else {
			$params['value']=$this->value;
		}
		$params['fieldname']=$this->fieldname;

		$tpl->assign('params',$params);
		$tpl->assign('data',$this->data);

		return $tpl->fetch('widget.html');

	}
	function clean() {
		$ret=date('Y-m-d',strtotime(sprintf('%d-%d-%d',
					$this->value['Date_Year'],
					$this->value['Date_Month'],
					$this->value['Date_Day']
					)));
		return $ret;
	}

}
