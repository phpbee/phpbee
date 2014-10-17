<?php
class module_wizard_createadmin extends gs_wizard_strategy_module implements gs_module {
	static function _desc() {
		return "админка";
	}
	function __construct() {
	}
	function install() {
	}
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/admin/wizard/createadmin/form'=>array(
				'gs_strategy_createadmin_handler.createadmin:name:form.html:form_class:form_createadmin:return:gs_record',
				'gs_wizard_handler.commit:return:true',
				'gs_base_handler.redirect_gl:gl:back',
				),
			),
		'get'=>array(
			'/admin/wizard/createadmin'=>'gs_base_handler.show',
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
class gs_strategy_createadmin_handler extends gs_handler {
	function createadmin() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$d=$f->clean();

		$fields=new wz_recordset_fields();
		$fields->find_records(array('id'=>$d['fields']))->orderby('sortkey');
		$links=new wz_recordset_links();
		$links->find_records(array('id'=>$d['links']));
		$extlinks=new wz_recordset_links();
		$extlinks->find_records(array('id'=>$d['extlinks']));
		$filters=new wz_recordset_links();
		$filters->find_records(array('id'=>$d['filters']));

		$datefields=new wz_recordset_fields();
		$datefields->find_records(array('id'=>$d['fields'],'type'=>'fDateTime'));


		$rs=record_by_id($this->data['handler_params']['Recordset_id'],'wz_recordsets');
		$module=$rs->Module->first();



		$tpl=new gs_tpl();
		$tpl=$tpl->init();
		$tpl->left_delimiter='{%';
		$tpl->right_delimiter='%}';

		$tpl->assign('rs',$rs);
		$tpl->assign('module',$module);
		$tpl->assign('fields',$fields);
		$tpl->assign('datefields',$datefields);
		$tpl->assign('links',$links);
		$tpl->assign('extlinks',$extlinks);
		$tpl->assign('filters',$filters);


		$out=$tpl->fetch('file:'.dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$d['template_name']);



		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'adm_'.$rs->name.'.html';

		file_put_contents_perm($filename,$out);

		$rs->showadmin=1;

		$modulename=$module->name;
		$recordsetname=$rs->name;

		$template=array(
			"get"=>array(
				"/admin/$modulename/$recordsetname"=>array("gs_base_handler.show:name:adm_$recordsetname.html"),
				"/admin/$modulename/$recordsetname/delete"=>array(
						"gs_base_handler.delete:{classname:$recordsetname}",
						"gs_base_handler.redirect",
						),
				"/admin/$modulename/$recordsetname/copy"=>array(
						"gs_base_handler.copy:{classname:$recordsetname}",
						"gs_base_handler.redirect",
						),
				),
			"handler"=>array(
				"/admin/form/$recordsetname"=>array(
					"gs_base_handler.redirect_if:gl:save_cancel:return:true",
					"gs_base_handler.post:{name:admin_form.html:classname:$recordsetname:form_class:g_forms_table}",
					"gs_base_handler.redirect_if:gl:save_continue:return:true",
					"gs_base_handler.redirect_if:gl:save_return:return:true",
					//"gs_base_handler.redirect_up:level:2",
					),
				"/admin/inline_form/$recordsetname"=>array(
					"gs_base_handler.redirect_if:gl:save_cancel:return:true",
					"gs_base_handler.post:{name:inline_form.html:classname:$recordsetname}",
					"gs_base_handler.redirect_if:gl:save_continue:return:true",
					"gs_base_handler.redirect_if:gl:save_return:return:true",
					//"gs_base_handler.redirect_up:level:2",
					),
				),
		);

		foreach ($template as $type=>$urls) {
			foreach ($urls as $url=>$handlers) {
				$f=$module->urls->find(array('gspgid_value'=>$url));
				if($f->count()) continue;
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
			}
		}
		$rs->commit();
		//die();
		return $module;
	}
}
class form_createadmin extends form_admin{
	function __construct($hh,$params=array(),$data=array()) {
		$rs=record_by_id($data['handler_params']['Recordset_id'],'wz_recordsets');

		$module=$rs->Module->first();
		$dirname=dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR;
		$extends=array_map('basename',glob($dirname."*"));

		$all_links=$rs->Links->recordset_as_string_array();
		$links=$rs->Links->find(array('type'=>array('lMany2Many','lOne2One')))->recordset_as_string_array();
		$extlinks=$rs->Links->find(array('type'=>'lMany2One'))->recordset_as_string_array();
		if(!is_array($links)) $links=array();
		if(!is_array($extlinks)) $extlinks=array();
		if(!is_array($all_links)) $all_links=array();


		$hh=array(
		    'template_name' => Array
			(
			    'type' => 'select',
			    'options' => array_combine($extends,$extends),
			),
		    'fields' => Array
			(
			    'type' => 'checkboxes',
				'options'=>$rs->Fields->orderby('sortkey')->recordset_as_string_array(),
			    'validate'=>'notEmpty',
			    'default'=>array_keys($rs->Fields->recordset_as_string_array()),
			),
		    'links' => Array
			(
			    'verbose_name'=>'show values',	
			    'type' => 'checkboxes',
			    'options'=>$all_links,
			    'validate'=>'notEmpty',
			    'default'=>array_keys($links),
			),
		    'extlinks' => Array
			(
			    'verbose_name'=>'show links',	
			    'type' => 'checkboxes',
			    'options'=>$all_links,
			    'validate'=>'notEmpty',
			    'default'=>array_keys($extlinks),
			),
		    'filters' => Array
			(
			    'type' => 'checkboxes',
			    'options'=>$all_links,
			    'validate'=>'notEmpty',
			    'default'=>array_keys($links),
			),
		);
		return parent::__construct($hh,$params,$data);
	}
}

