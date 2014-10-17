<?php
class module_wizard_createform_tpl extends gs_wizard_strategy_module implements gs_module {
	static function _desc() {
		return "создать шаблон формы";
	}
	function __construct() {
	}
	function install() {
	}
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/admin/wizard/createform_tpl/form'=>array(
				'gs_strategy_createform_tpl_handler.createform:name:form.html:form_class:form_createform_tpl:return:gs_record',
				'gs_base_handler.redirect_gl:gl:back',
				),
			),
		'get'=>array(
			'/admin/wizard/createform_tpl'=>'gs_base_handler.show:name:create_tpl.html',
			),
		);
		return self::add_subdir($data,dirname(__file__));
	}
	static function gl($name,$record,$data) {
		switch ($name) {
			case 'back':
				return '/admin/wizard/module/'.$data['handler_params']['Module_id'];
			break;
		}
	}
}
class gs_strategy_createform_tpl_handler extends gs_handler {
	function createform($ret,$d=null) {
		$rs=record_by_id($this->data['handler_params']['Recordset_id'],'wz_recordsets');
		$module=record_by_id($this->data['handler_params']['Module_id'],'wz_modules');
		$this->params['classname']=$rs->name;

		if(!$d) {
			$bh=new gs_base_handler($this->data,$this->params);
			$f=$bh->validate();
			if (!is_object($f) || !is_a($f,'g_forms')) return $f;
			$d=gs_base_handler::explode_data($f->clean());
		}


		$form=$module->forms->new_record($d);
		$cnt=0;
		$fields=array();
		foreach ($d['enabled'] as $k=>$e) {
			if (!$e) continue;
			$cnt++;
			$f=$form->Fields->new_record();
			$f->name=$d['name'][$k];
			$f->verbose_name=$d['verbose_name'][$k];
			$f->helper_text=$d['helper_text'][$k];
			$f->cssclass=$d['cssclass'][$k];
			$f->widget=$d['widget'][$k];
			$f->cnt=$cnt;
			$f->default_value=$d['default_value'][$k];
			$f->readonly_field=$d['readonly_field'][$k];
			$f->options=$d['options'][$k];

			$fields[]=$f;


		}




		$tpl=new gs_tpl();
		$tpl=$tpl->init();
		$tpl->left_delimiter='{%';
		$tpl->right_delimiter='%}';

		$tpl->assign('rs',$rs);
		$tpl->assign('module',$module);
		$tpl->assign('form',$form);
		$tpl->assign('fields',$fields);
		$tpl->assign($d);




		$out=$tpl->fetch('file:'.dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$d['template']);

		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$d['template_name'];
		if ($d['template_path']=='html') {
			$filename=cfg('tpl_data_dir').DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.$module->name.DIRECTORY_SEPARATOR.$d['template_name'];
		}
		file_put_contents($filename,$out);

		return $filename;


		return $module;
	}
}
class form_createform_tpl extends form_admin{
	function __construct($hh,$params=array(),$data=array()) {
		$rs=record_by_id($data['handler_params']['Recordset_id'],'wz_recordsets');
		$module=record_by_id($data['handler_params']['Module_id'],'wz_modules');

		$widgets=str_replace('gs_widget_','',class_members('gs_widget'));
		$validators=class_members('gs_validate');
		$validators_options=array();
		foreach($validators as $k=>$v) {
			$o=new $v;
			$validators_options[$o->get_name()]=$o->description();
		}
		$farr=array(
			'enabled'=>array(
				'name'=>'enabled',
				'widget'=>'checkbox',
				'default'=>1,
				) ,
			'name'=>array(
				'widget'=>'input',
				) ,
			'verbose_name'=>
				array(
				'widget'=>'input',
				'validate'=>'dummyValid',
				) ,
			'helper_text'=>
				array(
				'widget'=>'input',
				'validate'=>'dummyValid',
				) ,
			'cssclass'=>
				array(
				'widget'=>'input',
				'validate'=>'dummyValid',
				) ,
			'widget'=>array(
				'widget'=>'select',
				'options'=>array_combine($widgets,$widgets),
				)
			,
			'default_value'=>
				array(
				'widget'=>'input',
				'validate'=>'dummyValid',
				) ,
			'readonly_field'=>
				array(
				'widget'=>'checkbox',
				) ,
			'options'=>
				array(
				'widget'=>'input',
				'validate'=>'dummyValid',
				) ,
			'validate'=>
				array(
				'widget'=>'multiselect',
				'options'=>$validators_options,
				) ,
			'validate_params'=>
				array(
				'widget'=>'input',
				'validate'=>'dummyValid',
				) ,
		);
		$dirname=dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR;
		$extends=array_map('basename',glob($dirname."*"));
		$modules=new wz_modules;
		$modules->find_records(array());
		$options=array(
			/*
			'classname'=>array(
				'widget'=>'input',
				'default'=>"form_".$module->name."_".$rs->name,
			),
			'extends'=>array(
				'widget'=>'select',
				'options'=>class_members('g_forms'),
				'default'=>'g_forms_label',
			),
			*/
			'template'=>array(
				'widget'=>'select',
				'options' => array_combine($extends,$extends),
				'default'=>"bootstrap3_horizontal.html",
			),
			'template_name'=>array(
				'widget'=>'input',
				'default'=>"form_".$module->name."_".$rs->name.'.html',
			),
			'template_path'=>array(
				'verbose_name'=>'template store path',
				'widget'=>'radio',
				'options'=>array('html'=>'html','module'=>'module'),
				'default'=>'html',
			),
			/*
			'gspgid_name'=>array(
				'widget'=>'input',
				'default'=>"form/".$rs->name,
				'validate'=>'dummyValid',
			),
			*/
		);
		
		$i=0;

		$rset=new $rs->name;
		$hh=$rset->structure['htmlforms'];
		foreach ($hh as $key=>$h) {
			$i++;
			$arr=$farr;
			$arr['name']['default']=$key;
			$arr['verbose_name']['default']=$h['verbose_name'];
			$arr['helper_text']['default']=isset($h['helper_text']) ? $h['helper_text'] : null ;
			$arr['cssclass']['default']=isset($h['cssclass']) ? $h['cssclass'] : null;
			$arr['widget']['default']=isset($h['widget']) ? $h['widget'] : $h['type'];
			$arr['default_value']['default']=isset($h['default']) ? $h['default'] : null;
			$arr['readonly_field']['default']=isset($h['readonly']) ? $h['readonly'] : 0;
			$arr['options']['default']=isset($h['options']) ? $h['options'] : null;
			$arr['validate']['default']=$h['validate'];
			$arr['validate_params']['default']=isset($h['validate_params']) ? ( is_array($h['validate_params']) ? params_to_string($h['validate_params']) : $h['validate_params'] ) : null;

			foreach($arr as $ak=>$av) {
				//$options["$ak"."[$i]"]=$av;
				$options["$ak:$i"]=$av;
			}
		}
		return parent::__construct($options,$params,$data);
	}
}

