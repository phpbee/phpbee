<?php
class module_wizard_listdetails extends gs_wizard_strategy_module implements gs_module {
	static function _desc() {
		return "list-details";
	}
	function __construct() {
	}
	function install() {
	}
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/admin/wizard/listdetails/form'=>array(
				'gs_strategy_listdetails_handler.listdetails:name:form.html:form_class:form_listdetails:return:gs_record',
				'gs_wizard_handler.commit:return:true',
				'gs_base_handler.redirect_gl:gl:back',
				),
			),
		'get'=>array(
			'/admin/wizard/listdetails'=>'gs_base_handler.show',
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
class gs_strategy_listdetails_handler extends gs_handler {
	function listdetails() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$d=$f->clean();

        md($d,1);

		$fields=new wz_recordset_fields();
		$fields->find_records(array('id'=>$d['fields']))->orderby('sortkey');
        
		$links=new wz_recordset_links();
		$links->find_records(array('id'=>$d['links']));
		$filters=new wz_recordset_links();
		$filters->find_records(array('id'=>$d['filters']));


		$rs=record_by_id($this->data['handler_params']['Recordset_id'],'wz_recordsets');
		$module=$rs->Module->first();



		$tpl=new gs_tpl();
		$tpl=$tpl->init();
		$tpl->left_delimiter='{%';
		$tpl->right_delimiter='%}';

        $tpl->assign('d',$d);
		$tpl->assign('rs',$rs);
		$tpl->assign('module',$module);
		$tpl->assign('fields',$fields);
		$tpl->assign('links',$links);
		$tpl->assign('filters',$filters);

		$filters=new wz_recordset_fields();
		$filters->find_records(array('id'=>$d['filter_like']));
		$tpl->assign('filter_like',$filters);

		$filters=new wz_recordset_fields();
		$filters->find_records(array('id'=>$d['filter_sort']));
		$tpl->assign('filter_sort',$filters);

		$fields=new wz_recordset_fields();
		$fields->find_records(array('id'=>$d['details_fields']))->orderby('sortkey');
		$tpl->assign('details_fields',$fields);

		$links=new wz_recordset_links();
		$links->find_records(array('id'=>$d['details_links']));
		$tpl->assign('details_links',$links);

		$out=$tpl->fetch('file:'.dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$d['template_name']);
		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$d['template_list_filename'];
		file_put_contents_perm($filename,$out);
		$out=$tpl->fetch('file:'.dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$d['template_details_name']);
		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$d['template_details_filename'];
        md($out,1);
		file_put_contents_perm($filename,$out);


		$rs->showadmin=1;

		$modulename=$module->name;
		$recordsetname=$rs->name;

		$template=array(
			"get"=>array(
				$d['url']=>array(
						"gs_base_handler.show:name:".$d['template_list_filename'],
						),
				$d['url'].'/show'=>array(
						"gs_base_handler.rec_by_id:classname:".$rs->name.":return:gs_record^e404",
						//"gs_base_handler.fix_gl:module:pages:gl:rec_show:return:gs_record^e404",
						"gs_base_handler.show:name:".$d['template_details_filename'],
                        "end"=>'end',
                        'e404'=>"gs_base_handler.show404",
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
		die();
		return $module;
	}
}
class form_listdetails extends form_admin{
	function __construct($hh,$params=array(),$data=array()) {
		$rs=record_by_id($data['handler_params']['Recordset_id'],'wz_recordsets');

		$module=$rs->Module->first();
		$dirname=dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR;
		$extends=array_map('basename',glob($dirname."*.list.html"));
		$extends_details=array_map('basename',glob($dirname."*.details.html"));


        $fields=$rs->Fields->orderby('sortkey')->recordset_as_string_array();
        md($fields,1);
        if (!$rs->no_ctime) $fields['ctime']='_ctime';

		$all_links=$rs->Links->recordset_as_string_array();
		$links=$rs->Links->find(array('type'=>array('lMany2Many','lOne2One')))->recordset_as_string_array();
		$extlinks=$rs->Links->find(array('type'=>'lMany2One'))->recordset_as_string_array();
		if(!is_array($links)) $links=array();
		if(!is_array($extlinks)) $extlinks=array();
		if(!is_array($all_links)) $all_links=array();


		$hh=array(
			'url'=>array(
				'verbose_name'=>'url',
				'widget'=>'input',
                'default'=>$rs->name,
				) ,
			'template_list_filename'=>array(
				'verbose_name'=>'list template  filename',
				'widget'=>'input',
                'default'=>$rs->name.'.html',
				) ,
		    'template_name' => Array
			(
			    'type' => 'select',
			    'options' => array_combine($extends,$extends),
			),
			'template_details_filename'=>array(
				'verbose_name'=>'details template filename',
				'widget'=>'input',
                'default'=>$rs->name.'_details.html',
				) ,
		    'template_details_name' => Array
			(
			    'type' => 'select',
			    'options' => array_combine($extends_details,$extends_details),
			),
			'template_path'=>array(
				'verbose_name'=>'template store path',
				'widget'=>'radio',
				'options'=>array('html'=>'html','module'=>'module'),
				'default'=>'module',
			),
		    'fields' => Array
			(
			    'verbose_name'=>'<hr>fields on list page',	
			    'type' => 'checkboxes',
				'options'=>$fields,
			    'validate'=>'notEmpty',
			    'default'=>array_keys($fields),
			),
		    'links' => Array
			(
			    'verbose_name'=>'show values',	
			    'type' => 'checkboxes',
			    'options'=>$all_links,
			    'validate'=>'notEmpty',
			    'default'=>array_keys($links),
			),
		    'filters' => Array
			(
                'verbose_name'=>'filters',
			    'type' => 'checkboxes',
			    'options'=>$all_links,
			    'validate'=>'notEmpty',
			    'default'=>array_keys($links),
			),
		    'filter_like' => Array
			(
			    'verbose_name'=>'search',	
			    'type' => 'checkboxes',
				'options'=>$fields,
			),
		    'filter_sort' => Array
			(
			    'verbose_name'=>'sort',	
			    'type' => 'checkboxes',
				'options'=>$fields,
			),
		    'filter_limit_offset' => Array
			(
			    'verbose_name'=>'paginator',	
                'type'=>'checkbox',
                'default'=>'1',
			),
		    'details_fields' => Array
			(
                'verbose_name'=>'<hr>fields',
			    'type' => 'checkboxes',
				'options'=>$fields,
			    'validate'=>'notEmpty',
			    'default'=>array_keys($fields),
			),
		    'details_links' => Array
			(
                'verbose_name'=>'links',
			    'type' => 'checkboxes',
			    'options'=>$all_links,
			    'validate'=>'notEmpty',
			    'default'=>array_keys($links),
			),
		);
		return parent::__construct($hh,$params,$data);
	}
}

