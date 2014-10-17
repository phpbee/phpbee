<?php

class gs_widget_wysibb extends gs_widget {

	function clean() {
		parent::clean();
		//$result=iconv('CP1251','UTF-8',$result);
		if (function_exists ('tidy_parse_string')) {
			$config = array('indent' => TRUE,
							'show-body-only' => TRUE,
							'output-xhtml' => TRUE,
						);
			$tidy = tidy_parse_string($this->value, $config, 'UTF8');
			$tidy->cleanRepair();
			$this->value=trim($tidy);
		}
		return $this->value;
	}

	function html() {
		$rid=$this->data['id'];
		$tpl=gs_tpl::get_instance();
		$tpls=$tpl->template_dir;
		$tpls[]=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates';
		$tpl->setTemplateDir($tpls);
		$tpl->assign(array(
			'value'=>trim($this->value),
			'fieldname'=>$this->fieldname,
			'cssClass'=>isset($this->params['cssclass']) ? $this->params['cssclass'] : 'fWysibb',
			'rid'=>$rid,
		));
		return $tpl->fetch('widget_wysibb.html');
	}
}

class gs_widget_wysibb_module extends gs_base_module implements gs_module {
	function __construct() {}
	function install() {}
	function get_menu() {}
	static function get_handlers() {
		$data=array(
			'get'=>array(
			),
		);
		return self::add_subdir($data,dirname(__file__));
	}

}
