<?php
class module_wizard_createlogin extends gs_wizard_strategy_module implements gs_module {
	static function _desc() {
		return "логинилка";
	}
	function __construct() {
	}
	function install() {
	}
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/admin/wizard/createlogin/form'=>array(
				'gs_strategy_createlogin_handler.createlogin:name:form.html:form_class:form_createlogin:return:gs_record',
				'gs_wizard_handler.commit:return:true',
				'gs_base_handler.redirect_gl:gl:back',
				),
			),
		'get'=>array(
			'/admin/wizard/createlogin'=>'gs_base_handler.show:name:create.html',
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
class gs_strategy_createlogin_handler extends gs_handler {
	function createlogin($ret,$d=null) {
		if (!$d) {
			$bh=new gs_base_handler($this->data,$this->params);
			$f=$bh->validate();
			if (!is_object($f) || !is_a($f,'g_forms')) return $f;
			$d=$f->clean();
		}


		$rs=record_by_id($this->data['handler_params']['Recordset_id'],'wz_recordsets');
		$module=record_by_id($this->data['handler_params']['Module_id'],'wz_modules');



		$tpl=new gs_tpl();
		$tpl=$tpl->init();
		$tpl->left_delimiter='{%';
		$tpl->right_delimiter='%}';

		$tpl->assign('rs',$rs);
		$tpl->assign('module',$module);
		$tpl->assign($d);



		$out=$tpl->fetch('file:'.dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$d['template_name']);
		$out_form=$tpl->fetch('file:'.dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$d['form_template_name']);

		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'login_'.$rs->name.'.html';
		check_and_create_dir(dirname($filename));
		file_put_contents_perm($filename,$out);

		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'login_form_'.$rs->name.'.html';
		file_put_contents_perm($filename,$out_form);


		$rs->showadmin=1;

		$modulename=$module->name;
		$recordsetname=$rs->name;


		$template=array(
			"get"=>array(
				"logout/$recordsetname"=>array(
					"gs_base_handler.post_logout:classname:$recordsetname:role:$recordsetname:return:true:",
					"gs_base_handler.redirect",
					),
				),
			"handler"=>array(
				"login/$recordsetname"=>array(
					"gs_base_handler.check_login:return:gs_record^show:classname:$recordsetname",
					//"show"=>"gs_base_handler.show:name:login_$recordsetname.html",
				),
				"login/form/$recordsetname"=>array(
					//"oauth2_handler.login:classname:$recordsetname:login_field:".reset($d['login_fields']).":full_name_field:fullName:return:not_false",
					"gs_base_handler.post_login:return:gs_record:classname:$recordsetname:name:login_form_$recordsetname.html:form_class:".$d['form_class'].":fields:".implode(',',$d['login_fields']).":role:$recordsetname",
					"gs_base_handler.redirect",
					),
				"/oauth2/checklogin/$recordsetname"=>array(
					"oauth2_handler.login:classname:$recordsetname:login_field:".reset($d['login_fields']).":full_name_field:fullName:email_name_field:email:role:$recordsetname",
					"gs_base_handler.redirect",
					),
				),
		);

		foreach ($template as $type=>$urls) {
			foreach ($urls as $url=>$handlers) {
				//$f=$module->urls->find(array('gspgid_value'=>$url));
				//if($f->count()) continue;
				$f=$module->urls->find_records(array('gspgid_value'=>$url));
				$f->delete();

				$wz_url=$module->urls->new_record();
				$wz_url->gspgid_value=$url;
				$wz_url->type=$type;
				$cnt=0;
				foreach ($handlers as $key=>$value) {
					$cnt++;
					$wz_h=$wz_url->Handlers->new_record();
					$wz_h->cnt=$cnt;
					$wz_h->handler_keyname=$key;
					$wz_h->handler_value=$value;
					//$wz_h->commit();
				}
				$module->commit();
			}
		}
		$rs->commit();

		return $module;
	}
}
class form_createlogin extends form_admin{
	function __construct($hh,$params=array(),$data=array()) {
		$rs=record_by_id($data['handler_params']['Recordset_id'],'wz_recordsets');

		$module=record_by_id($data['handler_params']['Module_id'],'wz_modules');
		$dirname=dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR;
		$extends=array_map(basename,glob($dirname."*"));

		$fields=$rs->Fields->recordset_as_string_array();
		$fields=array_combine($fields,$fields);


		$forms=class_members('g_forms');


		$hh=array(
		    'template_name' => Array
			(
			    'type' => 'select',
			    'options' => array_combine($extends,$extends),
			    'default'=>'default.html',
			),
		    'form_template_name' => Array
			(
			    'type' => 'select',
			    'options' => array_combine($extends,$extends),
			    'default'=>'default_form.html',
			),
		    'assign' => Array 
		    	(
				'type'=>'input',
				'default'=>$rs->name,
				 'validate'=>'notEmpty',
			),
		    'login_fields' => Array
			(
			    'type' => 'checkboxes',
			    'options'=>$fields,
			    'validate'=>'notEmpty',
			),
		    'profile_fields' => Array
			(
			    'type' => 'checkboxes',
			    'options'=>$fields,
			    'validate'=>'notEmpty',
			),
		    'form_fields' => Array
			(
			    'type' => 'radio',
			    'options'=>array('basic'=>'basic','detailed'=>'detailed'),
			    'validate'=>'notEmpty',
			),
		    'form_class' => Array
			(
			    'type' => 'select',
			    'options'=>class_members('g_forms'),
			    'validate'=>'notEmpty',
			),
		);
		return parent::__construct($hh,$params,$data);
	}
}

