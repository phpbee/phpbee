<?php
class module_wizard_createmanager extends gs_wizard_strategy_module implements gs_module {
	static function _desc() {
		return "управлялка";
	}
	function __construct() {
	}
	function install() {
	}
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/admin/wizard/createmanager/form'=>array(
				'gs_strategy_createmanager_handler.createmanager:name:form.html:form_class:form_createmanager:return:gs_record',
				'gs_wizard_handler.commit:return:true',
				'gs_base_handler.redirect_gl:gl:back',
				),
			),
		'get'=>array(
			'/admin/wizard/createmanager'=>'gs_base_handler.show:name:__createmanager.html',
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
class gs_strategy_createmanager_handler extends gs_handler {
	function createmanager($ret,$d=null) {
		if (!$d) {
			$bh=new gs_base_handler($this->data,$this->params);
			$f=$bh->validate();
			if (!is_object($f) || !is_a($f,'g_forms')) return $f;
			$d=$f->clean();
		}


		$fields=new wz_recordset_fields();
		$fields->find_records(array('id'=>$d['fields']));
        /*
        $field_names=array();
        foreach($fields as $field) {
            $field_names[]=$field->name;
        }
        */
        $field_names=array();
        foreach ($d['formfields']['name'] as $k=>$n) {
            if ($d['formfields']['enabled'][$k]) $field_names[]=$n;
        }

		$links=new wz_recordset_links();
		$links->find_records(array('id'=>$d['links']));
		$extlinks=new wz_recordset_links();
		$extlinks->find_records(array('id'=>$d['extlinks']));
		$filters=new wz_recordset_links();
		$filters->find_records(array('id'=>$d['filters']));

		$datefields=new wz_recordset_fields();
		$datefields->find_records(array('id'=>$d['fields'],'type'=>'fDateTime'));


		$rs=record_by_id($this->data['handler_params']['Recordset_id'],'wz_recordsets');

		foreach ($extlinks as $link) {
			if ($link->linkname) continue;
			$l_rs=new $link->classname;
			foreach($l_rs->structure['recordsets'] as $backlink=>$rs_link) {
				if (substr($backlink,0,1)!='_' && $rs_link['recordset']==$rs->name) {
					$link->linkname=$backlink;
					continue;
				}
			}
		}

		$module=record_by_id($d['module'],'wz_modules');
		if (!$module) $module=record_by_id($this->data['handler_params']['Module_id'],'wz_modules');

		$formtplname='admin_form.html';
		if (isset($d['form_template_name'])) $formtplname=$d['form_template_name'];


		$tpl=new gs_tpl();
		$tpl=$tpl->init();
		$tpl->left_delimiter='{%';
		$tpl->right_delimiter='%}';

		$tpl->assign('rs',$rs);
		$tpl->assign('module',$module);
		$tpl->assign('fields',$fields);
		$tpl->assign('field_names',implode(",",$field_names));
		$tpl->assign('datefields',$datefields);
		$tpl->assign('links',$links);
		$tpl->assign('extlinks',$extlinks);
		$tpl->assign('filters',$filters);
		$tpl->assign('formtplname',$formtplname);

		if (isset($d['manager_rs_id'])) {
			$manager_rs=record_by_id($d['manager_rs_id'],'wz_recordsets');
			$links=$rs->Links->find(array('classname'=>$manager_rs->name,'type'=>'lOne2One'));
			$mlinks=array();
			$l_rs=new $rs->name;
			foreach ($links as $l) {
				$l=$l_rs->get_link($l->name);
				$mlinks[]=$l['local_field_name'].'=$manager->get_id()';
				//$mlinks[]=$l->name.'_id=$manager->get_id()';
			}
			$tpl->assign('manager_link', implode(' ',$mlinks));
		}

		$tplname=isset($d['template_name']) ? $d['template_name'] :'default.html';

        switch (pathinfo($tplname,PATHINFO_FILENAME)) {
            case 'profile': 
            case 'registration': 
                    $prefix=pathinfo($tplname,PATHINFO_FILENAME);
                   break; 
            default:       
                    $prefix='manager';
                   break; 
        }


		$tpl->assign('prefix',$prefix);

		$suffix=pathinfo($tplname,PATHINFO_FILENAME);
		if($suffix=='default') $suffix='manage_'; else $suffix="";

		$out=$tpl->fetch('file:'.dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR.$tplname);
		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$suffix.$rs->name.'.html';
		file_put_contents_perm($filename,$out);

		$rs->showadmin=1;

		$modulename=$module->name;
		$recordsetname=$rs->name;

	

		$template=array(
			"get"=>array(
				"$prefix/$recordsetname"=>array("gs_base_handler.show:name:".$suffix.$recordsetname.".html"),
				),
			"handler"=>array(
                /*    
				"$prefix/form/$recordsetname"=>array(
					"gs_base_handler.post:{name:$formtplname:classname:$recordsetname:form_class:g_forms_table}",
					"gs_base_handler.redirect_up:level:2",
					),
                 */    
				),
		);
		if ($prefix=='registration') {
			foreach(array("completed.html","email_registration.html","email_registration_title.html") as $page) {
				$out=$tpl->fetch('file:'.dirname(__FILE__).DIRECTORY_SEPARATOR.'registration_pages'.DIRECTORY_SEPARATOR.$page);
				$bsname=pathinfo($page,PATHINFO_FILENAME);
				$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$bsname.'_'.$recordsetname.'.html';
				file_put_contents_perm($filename,$out);
			}


			$template=array(
				"get"=>array(
					"$prefix/$recordsetname"=>array(
						"gs_base_handler.check_login:return:gs_record^show:classname:$recordsetname",
						"gs_base_handler.redirect"=>"profile",
						"end"=>"end",
						"show"=>"gs_base_handler.show:name:".$suffix.$recordsetname.".html",
						),
					 "resend_mail/$recordsetname"=>array(
						 "gs_base_handler.rec_by_id:classname:$recordsetname",
						 "gs_base_handler.email4record:email:email:template:email_registration_"."$recordsetname.html:template_title:email_registration_title_"."$recordsetname.html",
						 "gs_base_handler.redirect_rs_hkey:{href:/$modulename/completed/$recordsetname/%d/sent}",
						),
					 "verify_mail/$recordsetname"=>array( 
						 "gs_base_handler.test_id:{rs:$recordsetname:field:id:return:gs_record^er}",
						 "gs_base_handler.set_record:{field:verified:value:1}",
						 "gs_base_handler.post_login:classname:$recordsetname:name:login_form_$recordsetname.html:form_class:g_forms_html:fields:login,password",
						 "gs_base_handler.redirect:href:/$modulename",
						 "end",
						 "er"=>"gs_base_handler.show:{name:code_failed.html}",
					 	),
					"completed/$recordsetname"=>array(
						"gs_base_handler.show:name:completed_$recordsetname.html",
						),
					),
				"handler"=>array(
					"$prefix/form/$recordsetname"=>array(
						"gs_base_handler.post:{name:$formtplname:classname:$recordsetname:form_class:g_forms_table}",
						"gs_base_handler.email4record:{email:email:template:email_registration_"."$recordsetname.html:template_title:email_registration_title_"."$recordsetname.html}",
						"gs_base_handler.redirect_rs_hkey:{href:/$modulename/completed/$recordsetname/%d}",
						//"gs_base_handler.redirect:href:/$modulename/completed/$recordsetname",
						),
					),
			);
		}
		if ($prefix=='manager') {
			$arr=array(	
				'get'=>array(
					"$prefix/$recordsetname/delete"=>array(
							"gs_base_handler.delete:{classname:$recordsetname}",
							"gs_base_handler.redirect",
							),
					"$prefix/$recordsetname/copy"=>array(
							"gs_base_handler.copy:{classname:$recordsetname}",
							"gs_base_handler.redirect",
							),
					),
				);
			$template=array_merge_recursive($template,$arr);
		}

		foreach ($template as $type=>$urls) {
			foreach ($urls as $url=>$handlers) {
				$f=$module->urls->find(array('gspgid_value'=>$url,'type'=>$type));
				if($f->count()) continue;
				$wz_url=$module->urls->new_record();
				$wz_url->gspgid_value=$url;
				$wz_url->type=$type;
				$cnt=0;
				foreach ($handlers as $key=>$value) {
					md($value,1);
					$cnt++;
					$wz_h=$wz_url->Handlers->new_record();
					$wz_h->cnt=$cnt;
					$wz_h->handler_keyname=$key;
					$wz_h->handler_value=$value;
					//$wz_h->commit();
				}
			}
		}
		$module->commit();
		$rs->commit();
		//die();
		return $module;
	}
}
class form_createmanager extends form_admin{
	function __construct($hh,$params=array(),$data=array()) {
		$rs=record_by_id($data['handler_params']['Recordset_id'],'wz_recordsets');

		$module=record_by_id($data['handler_params']['Module_id'],'wz_modules');
		$dirname=dirname(__FILE__).DIRECTORY_SEPARATOR.'pages'.DIRECTORY_SEPARATOR;
		$extends=array_map(basename,glob($dirname."*"));

		$all_links=$rs->Links->recordset_as_string_array();
		$links=$rs->Links->find(array('type'=>array('lMany2Many','lOne2One')))->recordset_as_string_array();
		$extlinks=$rs->Links->find(array('type'=>'lMany2One'))->recordset_as_string_array();
		if(!is_array($links)) $links=array();
		if(!is_array($extlinks)) $extlinks=array();
		if(!is_array($all_links)) $all_links=array();


		$modules=new wz_modules;
		$modules->find_records(array());


		$hh=array(
			'module'=>array(
				'widget'=>'radio',
				'options'=>$modules->recordset_as_string_array(),
				'default'=>$data['handler_params']['Module_id'],
				),
		    'template_name' => Array
			(
			    'type' => 'select',
			    'options' => array_combine($extends,$extends),
			),
		    'fields' => Array
			(
			    'type' => 'checkboxes',
			    'options'=>$rs->Fields->recordset_as_string_array(),
			    'validate'=>'notEmpty',
			    'default'=>array_keys($rs->Fields->recordset_as_string_array()),
			),
		    'links' => Array
			(
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

