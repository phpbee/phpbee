<?php

class gs_widget_wysiwyg extends gs_widget {

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
		list($rs,$link)=explode(':',$this->params['images_key']);
		$rid=$this->data['id'];
		$tpl=gs_tpl::get_instance();
		$tpls=$tpl->template_dir;
		$tpls[]=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates';
		$tpl->setTemplateDir($tpls);
		$tpl->assign(array(
			'value'=>trim($this->value),
			'fieldname'=>$this->fieldname,
			'cssClass'=>isset($this->params['cssclass']) ? $this->params['cssclass'] : 'fWysiwyg',
			'rs'=>$rs,
			'link'=>$link,
			'rid'=>$rid,
		));
		return $tpl->fetch('widget_wysiwyg.html');
	}
}

class gs_widget_wysiwyg_module extends gs_base_module implements gs_module {
	function __construct() {}
	function install() {}
	function get_menu() {}
	static function get_handlers() {
		$data=array(
			'get'=>array(
				'/widgets/wysiwyg/gallery'=>'gs_widget_wysiwyg_handler.gallery_wysiwyg',
			),
		);
		return self::add_subdir($data,dirname(__file__));
	}

}
class gs_widget_wysiwyg_handler extends gs_handler {
	function gallery_wysiwyg($ret) {
		$rs_name=$this->data['gspgid_va'][0];
		$link_name=$this->data['gspgid_va'][1];
		$rec_id=$this->data['gspgid_va'][2];
		$tpl=gs_tpl::get_instance();
		$tpls=$tpl->template_dir;
		$tpls[]=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates';
		$tpl->setTemplateDir($tpls);
		$rec=record_by_id($rec_id,$rs_name);
		$images=$rec->$link_name;
		$tpl->assign('list',$images);
		echo $tpl->fetch('widget_wysiwyg_gallery.html');
	}
}
