<?php
class module_wizard_newurl extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
	}
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/admin/wizard/newurl/simpleform'=>array(
				'handler_wizard_newurl.form:name:admin_form.html:form_class:form_wizard_newurl:return:gs_record',
				'gs_base_handler.redirect_gl:gl:back',
				),
			'/admin/wizard/newurl/form'=>array(
				'handler_wizard_newurl.form:name:newurl_form.html:form_class:form_wizard_newurl:return:gs_record',
				//'gs_wizard_handler.commit:return:true',
				'gs_base_handler.redirect_gl:gl:back',
				),
			),
		'get'=>array(
			'/admin/wizard/newurl'=>'gs_base_handler.show:name:newurl.html',
			),
		);
		return self::add_subdir($data,dirname(__file__));
	}
	static function gl($name,$record,$data) {
		switch ($name) {
			case 'back':
				return '/admin/wizard/module/'.$data['module'];
			break;
		}
	}
}
class handler_wizard_newurl extends gs_handler {
	function form() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$d=gs_base_handler::explode_data($f->clean());

		$module=record_by_id($d['module'],'wz_modules');
		$url=$module->urls->find_records(array('Module_id'=>$module->get_id(),'gspgid_value'=>$d['url'],'type'=>$d['type']))->first(TRUE);
		$url->Handlers->delete();
		$cnt=0;

		/*
		if ($d['type']=='get') {
			$cnt+=10;
			$url->Handlers->new_record(array('cnt'=>$cnt,'handler_value'=>'gs_base_handler.validate_gl:{name:'.$d['gl'].':return:true^e404}'));
		}
		*/


		foreach(explode("\n",$d['handlers']) as $h) {
			$cnt+=10;
			if($h) {
				$h=preg_replace('/:module_name:[^:]+/','',$h);
				if (strpos($h,'gs_base_handler.show')===0) $h.=':gl:'.$d['gl'];
				$url->Handlers->new_record(array('cnt'=>$cnt,'handler_value'=>$h));
			}
		}
		/*
		if ($d['type']=='get') {
			$cnt+=10;
			$url->Handlers->new_record(array('cnt'=>$cnt,'handler_keyname'=>'end','handler_value'=>'end'));
			$cnt+=10;
			$url->Handlers->new_record(array('cnt'=>$cnt,'handler_keyname'=>'e404','handler_value'=>'gs_base_handler.show404:name:404.html:return:true'));
		}
		*/
		$url->commit();
		return $url;
	}
}
class form_wizard_newurl extends g_forms_table{
	function __construct($hh,$params=array(),$data=array()) {
		$modules=new wz_modules;
		$modules->find_records(array());
		$gl=get_class_methods('gl');
		$gl=array_filter($gl,create_function('$a','return strpos($a,"__")!==0;'));
		$hh=array(
			'type'=>array(
				'widget'=>'radio',
				'options'=>'get,handler,post',
				),
			'gl'=>array(
				'widget'=>'select',
				'options'=>array_combine($gl,$gl),
				),
			'module'=>array(
				'widget'=>'radio',
				'options'=>$modules->recordset_as_string_array(),
				),
			'url'=>array(
				'verbose_name'=>'url/gspgid',
				'widget'=>'input',
				) ,
			'handlers'=>
				array(
				'widget'=>'TextLines',
				) ,
		);
		parent::__construct($hh,$params,$data);
		$this->interact['type']="
				#gl.display_if('get');
				";
		$this->set_option('type','cssclass','fRadio lOne2One');		

	}
}

