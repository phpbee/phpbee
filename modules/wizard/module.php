<?php

require_fullpath(__FILE__,'recordsets.php');

abstract class gs_wizard_strategy_module extends gs_base_module {}


class module_wizard extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array('wz_modules','wz_recordsets','wz_recordset_fields','wz_recordset_resizes','wz_recordset_links','wz_recordset_triggers','wz_recordset_submodules','wz_urls','wz_handlers','wz_forms','wz_form_fields','wz_form_fields_validators') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		if(strpos($_SERVER['REQUEST_URI'],'/admin/wizard')===0) {
		$ret[1]='<a href="/admin/wizard/install">Install</a>';
		$ret[2]='<a href="/admin/wizard/newurl">New page</a>';
		$ret[3][]='<a href="/admin/wizard/">Modules</a>';
		$modules=new wz_modules();
		$modules->find_records(array());
		foreach($modules as $m) {
			$ret[3][]='<a href="/admin/wizard/module/'.$m->id.'">'.$m->title.'</a>';
		}
		return $ret;
		}
	}
	
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/admin/form/wz_modules_import'=>array(
				'gs_wizard_handler.module_xml_import:{name:admin_form.html:form_class:form_modules_import:return:true}',
				'gs_base_handler.redirect:href:admin/wizard',
			),

			'/admin/form/wz_modules'=>array(
				'gs_base_handler.post:{name:admin_form.html:classname:wz_modules:form_class:g_forms_table}',
				'gs_base_handler.redirect',
			),
			'/admin/form/wz_recordsets'=>array(
				'gs_base_handler.post:{name:admin_form.html:classname:wz_recordsets:form_class:g_forms_table}',
				'gs_base_handler.redirect_up:level:2',
			),
			'/admin/form/wz_recordset_links'=>array(
				'gs_base_handler.redirect_if:gl:save_cancel:return:true',
				'gs_base_handler.post:{name:admin_form.html:classname:wz_recordset_links:form_class:g_forms_table}',
				'gs_base_handler.redirect_if:gl:save_continue:return:true',
				'gs_base_handler.redirect_up:level:1',
			),
			'/admin/form/wz_recordset_triggers'=>array(
				'gs_base_handler.redirect_if:gl:save_cancel:return:true',
				'gs_base_handler.post:{name:admin_form.html:classname:wz_recordset_triggers:form_class:g_forms_table}',
				'gs_base_handler.redirect_if:gl:save_continue:return:true',
				'gs_base_handler.redirect_up:level:1',
			),
			'/admin/form/wz_recordset_fields'=>array(
				'gs_base_handler.redirect_if:gl:save_cancel:return:true',
				'gs_base_handler.post:{name:admin_form.html:classname:wz_recordset_fields:form_class:g_forms_table}',
				'gs_base_handler.redirect_if:gl:save_continue:return:true',
				'gs_base_handler.redirect_up:level:1',
			),
			'/admin/form/wz_recordset_resizes'=>array(
				'gs_base_handler.redirect_if:gl:save_cancel:return:true',
				'gs_base_handler.post:{name:admin_form.html:classname:wz_recordset_resizes:form_class:g_forms_table}',
				'gs_base_handler.redirect_if:gl:save_continue:return:true',
				'gs_base_handler.redirect_up:level:1',
			),
			'/admin/form/wz_recordset_submodules'=>array(
				'gs_base_handler.post:{name:admin_form.html:classname:wz_recordset_submodules:form_class:g_forms_table}',
				'gs_base_handler.redirect_up',
			),
			'/admin/form/wz_urls'=>array(
				'gs_base_handler.redirect_if:gl:save_cancel:return:true',
				'gs_base_handler.post:{name:admin_form.html:classname:wz_urls:form_class:g_forms_table}',
				'gs_base_handler.redirect_if:gl:save_continue:return:true',
				'gs_base_handler.redirect_up',
			),
			'/admin/form/wz_urls_inline'=>array(
				'gs_base_handler.post:{name:inline_form.html:classname:wz_urls:form_class:g_forms_table}',
				'gs_base_handler.redirect',
			),
			'/admin/form/wz_handlers'=>array(
				'gs_base_handler.post:{name:admin_form.html:classname:wz_handlers:form_class:g_forms_table}',
				'gs_base_handler.redirect_up',
			),
			'/admin/wizard/templates'=>array(
				'gs_wizard_handler.templates',
			),
			'/admin/form/templates'=>array(
				'gs_base_handler.redirect_if:gl:save_cancel:return:true',
				'gs_wizard_handler.templatespost:return:true:name:admin_form.html:form_class:gs_wizard_template_form',
				'gs_base_handler.redirect_if:gl:save_continue:return:true',
				'gs_base_handler.redirect_up',
			),
			'/admin/form/wz_forms'=>array(
				'gs_base_handler.redirect_if:gl:save_cancel:return:true',
				'gs_base_handler.post:{name:admin_form.html:classname:wz_forms:form_class:gs_wizard_forms_form}',
				'gs_base_handler.redirect_if:gl:save_continue:return:true',
				'gs_base_handler.redirect_up',
			),
			'/admin/form/wz_form_fields'=>array(
				'gs_base_handler.redirect_if:gl:save_cancel:return:true',
				'gs_base_handler.post:{name:admin_form.html:classname:wz_form_fields:form_class:gs_wizard_form_fields_form}',
				'gs_base_handler.redirect_if:gl:save_continue:return:true',
				'gs_base_handler.redirect_up',
			),
			'/admin/form/wz_form_fields_validators'=>array(
				'gs_base_handler.redirect_if:gl:save_cancel:return:true',
				'gs_base_handler.post:{name:admin_form.html:classname:wz_form_fields_validators:form_class:gs_wizard_form_fields_validators_form}',
				'gs_base_handler.redirect_if:gl:save_continue:return:true',
				'gs_base_handler.redirect_up',
			),
			'/admin/wizard/formmacros'=>array(
				'wz_handler_mc.post:name:form_submit.html',
						),
			'/admin/wizard/choosetpl'=>array(
				'gs_wizard_handler.choosetpl:name:form_submit.html:form_class:form_choosetpl:return:true',
				'gs_base_handler.redirect_up',
				),
			'/admin/wizard/macros/list'=>array(
				'gs_wizard_handler.macros_list:name:macros_list.html',
				),
			'/admin/form/strategy'=>array(
				'gs_wizard_handler.strategy:return:true:name:form_submit.html:form_class:gs_wizard_strategy_form',
			),
			'/admin/wizard/form/templates/copy'=>array(
				'gs_base_handler.redirect_if:gl:save_cancel:return:true',
				'gs_wizard_handler.templatescopy:{name:admin_form.html:form_class:gs_wizard_form_templates_copy}',
				'gs_base_handler.redirect_if:gl:save_continue:return:true',
				'gs_base_handler.redirect_up',
				),
		),
		'post'=>array(
			'/admin/wizard/iddqdblocksubmit'=>array(
						'gs_wizard_handler.iddqdblocksubmit:return:true',
						'gs_base_handler.redirect_gl:gl:iddqdblocksubmit',
						),
			'/admin/wizard/clone_urls'=>array(
				'gs_wizard_handler.clone_urls:return:true',
				'gs_base_handler.redirect_gl:gl:clone_urls',
			),

		),
		'get'=>array(
			'/admin/wizard'=>'gs_base_handler.show',
			'/admin/wizard/install'=>'gs_base_handler.show',
			'/admin/wizard/iddqd'=>'gs_wizard_handler.iddqd',
			'/admin/wizard/iddqdblock'=>array(
						'gs_wizard_handler.iddqdblock:return:true',
						'gs_base_handler.show',
						),
			'/admin/wizard/module'=>'gs_base_handler.show',
			'/admin/wizard/commit'=>array(
					'gs_base_handler.xml_export:{classname:wz_modules:return:notfalse}',
					'gs_wizard_handler.xml_save_file_to_module_dir:return:notfalse',
					'gs_wizard_handler.commit:return:true',
					'gs_base_handler.redirect',
					),
			'/admin/wizard/recordsets'=>'gs_base_handler.show',
			'/admin/wizard/recordset_fields'=>'gs_base_handler.show',
			'/admin/wizard/recordset_resizes'=>'gs_base_handler.show',
			'/admin/wizard/urls'=>'gs_base_handler.show',
			'/admin/wizard/handlers'=>'gs_base_handler.show',
			'/admin/wizard/macros'=>'gs_base_handler.show',
                        '/admin/wizard/xmlexport'=>array(
					'gs_base_handler.xml_export:{classname:wz_modules:return:notfalse}',
                                        'gs_base_handler.xml_save_file',
                                        ),
                        '/admin/wizard/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_modules}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/recordsets/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_recordsets}',
                                        'gs_base_handler.redirect',
                                        ),
			'/admin/wizard/recordsets/clone'=>array(
					'gs_base_handler.xml_clone:{classname:wz_recordsets}',
					'gs_base_handler.redirect',
			),
                        '/admin/wizard/recordset_fields/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_recordset_fields}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/recordset_fields/copy'=>array(
                                        'gs_base_handler.copy:{classname:wz_recordset_fields}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/recordset_links/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_recordset_links}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/recordset_links/copy'=>array(
                                        'gs_base_handler.copy:{classname:wz_recordset_links}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/recordset_triggers/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_recordset_triggers}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/recordset_triggers/copy'=>array(
                                        'gs_base_handler.copy:{classname:wz_recordset_triggers}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/recordset_submodules/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_recordset_submodules}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/handlers/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_handlers}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/urls/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_urls}',
                                        'gs_base_handler.redirect',
                                        ),
			'/admin/wizard/urls/clone'=>array(
					'gs_base_handler.xml_clone:{classname:wz_urls}',
					'gs_base_handler.redirect_gl:gl:clone_urls',
					//'gs_base_handler.redirect',
			),
			'/admin/wizard/forms'=>'gs_base_handler.show',
			'/admin/wizard/form_fields'=>'gs_base_handler.show',
			'/admin/wizard/form_fields_validators'=>'gs_base_handler.show',
                        '/admin/wizard/forms/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_forms}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/form_fields/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_form_fields}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/form_fields_validators/delete'=>array(
                                        'gs_base_handler.delete:{classname:wz_form_fields_validators}',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/templates/delete'=>array(
                                        'gs_wizard_handler.deletetemplate:return:true',
                                        'gs_base_handler.redirect',
                                        ),
                        '/admin/wizard/templates/copy'=>array(
                                        'gs_base_handler.show:name:templates_copy.html',
                                        ),

		),
	);
	return self::add_subdir($data,dirname(__file__));
	}
	static function gl($name,$record,$data) {
		switch ($name) {
			case 'iddqdblocksubmit':
				if ($data['save_view']) return '/admin/wizard/iddqd/'.$data['gspgid_v'];
				if ($data['save_return']) return '/admin/wizard/module/'.$data['gspgid_va'][0];
				return null;
			case 'clone_urls':
				//return '/admin/wizard/module/'.$data['module_id'];
				return '/admin/wizard/handlers/'.$record->get_id();
			break;
		}
	}
}


class gs_wizard_handler extends gs_handler {
    function xml_save_file_to_module_dir($ret) {
		$module=record_by_id($this->data['gspgid_va'][0],'wz_modules');

        $x=xml_print($ret['last']->asXML());
		$dirname=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR;
		check_and_create_dir($dirname);
        $filename='wizard_module_'.$module->name.'.xml';
        if (!file_put_contents_perm($dirname.$filename,$x)) return false;
		return $x;
    }
	function module_xml_import() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$d=$f->clean();
		$xml=!empty($d['xmlfile_data']) ? $d['xmlfile_data'] : $d['xml'];
		if (!$xml && $d['xmlpath']) $xml=file_get_contents($d['xmlpath']);
		
		$xml=trim($xml);
		if(!$xml) throw new gs_exception('empty XML');

		$newrs=xml_import(trim($xml));
		$newrs->commit();
		return TRUE;
	}

	function commit($rec=null) {

		if ($rec['last'] && is_a($rec['last'],'gs_record')) $module=$rec['last'];
			else $module=record_by_id($this->data['gspgid_va'][0],'wz_modules');

		$dirname=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR;
		check_and_create_dir($dirname);
		check_and_create_dir($dirname.'templates');

		foreach ($module->recordsets as $rs) 
		  foreach ($rs->Submodules as $sm) {
			  copy_directory(cfg('lib_distsubmodules_dir').$sm,$dirname.$sm->name);
			  $files=glob($dirname.$sm->name.DIRECTORY_SEPARATOR.'*.phps');
			  foreach($files as $fname) {
				  $txt=file_get_contents($fname);
				  $txt=str_replace('{%$PARENT_RECORDSET%}',$rs->name,$txt);
				  file_put_contents($fname,$txt);
			  }
		}

		$tpl=new gs_tpl();
		$tpl=$tpl->init();
		$tpl->left_delimiter='<*';
		$tpl->right_delimiter='*>';

		$tpl->assign('module',$module);

		$urls=array();
		foreach ($module->urls as $u) {
			$urls[$u->type][]=$u;
		}
		$tpl->assign('urls',$urls);

		$out=$tpl->fetch('file:'.dirname(__FILE__).DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'compile_phps.html');

		$out=beautify($out);


		//md($out,1); md($dirname,1); die();

		$ret=file_put_contents($dirname.'module.phps',$out);

			$init=new gs_init('user');
			$init->init(LOAD_CORE | LOAD_STORAGE | LOAD_TEMPLATES | LOAD_EXTRAS);
            $init->load_modules();
			$init->compile_modules();
			$init->clear_handlers();
			$init->save_handlers();
        
        return $ret!==FALSE;


	}
	
	function clone_urls() {
		
		$ids=$this->data['manage'];
		$m_id=$this->data['module_id'];
		$urls=new wz_urls;
		$urls_new=new wz_urls;
		$r_urls=$urls->find_records(array('id'=>array_keys($ids)));
		foreach ($r_urls as $rec) {
				$values=$rec->get_values();
				if (isset($rec->get_recordset()->id_field_name)) unset($values[$rec->get_recordset()->id_field_name]);
				$values['Module_id']=$m_id;
				$r=$urls_new->new_record($values);
				$r_handlers=$rec->Handlers->find(array());
				foreach ($r_handlers as $rec_h) {
					$values=$rec_h->get_values();
					unset($values[$rec_h->get_recordset()->id_field_name]);
					$r->Handlers->new_record($values);
				}
		}
		$urls_new->commit();
		return true;
	}

	function iddqd($data) {
		$module=record_by_id($this->data['gspgid_va'][0],'wz_modules');
		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$this->data['gspgid_va'][1];

		$tpl=gs_tpl::get_instance();
		$tpl->force_compile=true;
		$tpl->iddqd=true;

		$out=$tpl->fetch('string:'.file_get_contents($filename));
		$out=str_ireplace('</head',"<script src=\"/js/admin_iddqd.js\"></script>\n<link rel=\"stylesheet\" type=\"text/css\" href=\"/css/admin_iddqd.css\" media=\"screen\" />\n</head",$out);
		echo($out);


	}
	function iddqdblock() {
		$module=record_by_id($this->data['gspgid_va'][0],'wz_modules');
		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$this->data['gspgid_va'][1];

		$template=file_get_contents($filename);
		$tpl=gs_tpl::get_instance();

		if (isset($this->data['gspgid_va'][2])) {
			preg_match("|{block name=\"".$this->data['gspgid_va'][2]."\"}(.*?){/block}|is",$template,$block);
			$template=$block[1];
		}

		$tpl->assign('block_content',$template);
		return true;

	}
	function iddqdblocksubmit() {
		$module=record_by_id($this->data['gspgid_va'][0],'wz_modules');
		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$this->data['gspgid_va'][1];
	
		$template=$this->data['block_content'];
		if (isset($this->data['gspgid_va'][2])) {
			$template=file_get_contents($filename);
			$template=preg_replace("|{block name=\"".$this->data['gspgid_va'][2]."\"}.*?{/block}|is",'',$template);
			$template.='{block name="'.$this->data['gspgid_va'][2].'"}'.$this->data['block_content'].'{/block}'.PHP_EOL;
		}
		file_put_contents($filename,$template);
		return true;
	}

	function templates() {

		$module=record_by_id($this->data['gspgid_va'][0],'wz_modules');
		$dirname=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR;

		$templates=array_map('basename',glob($dirname.'*'));


		$tpl=gs_tpl::get_instance();

		$tpl->assign('templates',$templates);
		$tpl->assign('module',$module);

		return $tpl->fetch('templates.html');
	}
	function deletetemplate() {
		$module=record_by_id($this->data['gspgid_va'][0],'wz_modules');
		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$this->data['gspgid_va'][1];
		return unlink($filename);
	}

	function templatespost() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$d=$f->clean();


		if(strpos($d['template_name'],'.')===FALSE) $d['template_name'].='.html';

		$module=record_by_id($this->data['handler_params']['Module_id'],'wz_modules');

		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$d['template_name'];
		if (file_exists($filename)) {
			return true;
		}

		$text="";
		if (!empty($d['extends'])) $text='{extends file="'.$this->data['extends'].'"}'.PHP_EOL;
		file_put_contents($filename,$text);

		if (empty($d['url'])) return true;	

		$template=array(
			"get"=>array(
				$d['url']=>array("gs_base_handler.show:name:".$d['template_name']),
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
				}
			}
		}
		$module->commit();



		return true;
	}
	function templatescopy() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$d=$f->clean();
		$module=record_by_id($d['from_Module_id'],'wz_modules');

		$from_filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.basename($d['from_name']);
		if (!file_exists($from_filename))  return false;

		$module=record_by_id($d['Module_id'],'wz_modules');
		$filename=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.basename($d['name']);
		
		return copy($from_filename,$filename);
		
	}
	function choosetpl() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;

		$handler=record_by_id($this->data['handler_params']['Handler_id'],'wz_handlers');

		$hv=preg_replace('|:name:[^:]+|','',$handler->handler_value);
		if ($f->clean('template_name')) $hv.=':name:'.$f->clean('template_name');

		$handler->handler_value=$hv;
		$handler->commit();

		return true;
	}
	function strategy() {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
		$d=$f->clean();
		//$filename=basename(dirname(__class_filename($d['strategy'])));	
		$filename=str_replace('module_wizard_','',$d['strategy']);
		$href="/admin/wizard/".$filename."/".$d['recordset']."/".$this->data['handler_params']['Module_id'];
		return html_redirect($href);
	}


	function macros_list() {
		$tpl=gs_tpl::get_instance();
		$cl=class_members('wz_macros');
		$tpl->assign('macros_list',$cl);
		$bh=new gs_base_handler($this->data,$this->params);
		return $bh->show($this->data);
	}

}
class form_choosetpl extends g_forms_inline{
	function __construct($hh,$params=array(),$data=array()) {
		$module=record_by_id($data['handler_params']['Module_id'],'wz_modules');
		$dirname=cfg('lib_modules_dir').$module->name.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR;
		$extends=array_map(basename,glob($dirname."*"));
		//array_unshift($extends,'');
		$hh=array(
		    'template_name' => Array
			(
			    'type' => 'select_enter',
			    'validate' => 'dummyValid',
			    'options' => array_combine($extends,$extends),
			),
		);
		return parent::__construct($hh,$params,$data);
	}

}

class gs_wizard_template_form extends g_forms_table{
	function __construct($hh,$params=array(),$data=array()) {
		$extends=array_map(basename,glob(cfg('tpl_data_dir')."*"));
		array_unshift($extends,'');
		$hh=array(
		    'extends' => Array
			(
			    'type' => 'select',
			    'validate' => 'dummyValid',
			    'options' => array_combine($extends,$extends),
			),
		    'template_name' => Array
			(
			    'type' => 'input',
			    'validate' => 'notEmpty',
			),
		    'url' => Array
			(
			    'type' => 'input',
			    'validate' => 'dummyValid',
			),

		);
		return parent::__construct($hh,$params,$data);
	}

}
class gs_wizard_strategy_form extends g_forms_inline{
	function __construct($hh,$params=array(),$data=array()) {
		$module=record_by_id($data['handler_params']['Module_id'],'wz_modules');

				$rs=new wz_recordsets();
				$rsets=$rs->find_records(array())->recordset_as_string_array();

				$rsets=array(
					'module'=>$module->recordsets->recordset_as_string_array(),
					'wizard'=>$rsets,
					//'all'=>class_members('gs_recordset_short'),
					);
		$hh=array(
		    'recordset' => Array
			(
			    'type' => 'select',
			    'options'=>$rsets,
			),
		    'strategy' => Array
			(
			    'type' => 'select',
			    'options' => class_members('gs_wizard_strategy_module'),
			),

		);
		return parent::__construct($hh,$params,$data);
	}

}
class gs_wizard_forms_form extends g_forms_table {
	function __construct($hh,$params=array(),$data=array()) {
		$this->field_options['extends']['options']=class_members('g_forms');
		$this->field_options['extends']['default']='g_forms_label';
		return parent::__construct($hh,$params,$data);
	}
}
class gs_wizard_form_fields_form extends g_forms_table {
	function __construct($hh,$params=array(),$data=array()) {
		$widgets=str_replace('gs_widget_','',class_members('gs_widget'));
		$this->field_options['widget']['options']=array_combine($widgets,$widgets);
		$this->field_options['widget']['default']='input';
		return parent::__construct($hh,$params,$data);
	}
}
class gs_wizard_form_fields_validators_form extends g_forms_table {
	function __construct($hh,$params=array(),$data=array()) {
		$validators=class_members('gs_validate');
		$options=array();
		foreach($validators as $k=>$v) {
			$o=new $v;
			$options[$o->get_name()]=$o->description();
		}
		$this->field_options['class']['options']=$options;
		$this->field_options['class']['default']='notEmpty';
		return parent::__construct($hh,$params,$data);
	}
}
class gs_wizard_form_templates_copy extends g_forms_table {
	function __construct($hh,$params=array(),$data=array()) {
		$default=string_to_params($data['handler_params']['_default']);
		$modules=new wz_modules;
		$modules->find_records(array());
		$hh=array(
			'Module_id'=>array(
				'widget'=>'radio',
				'options'=>$modules->recordset_as_string_array(),
				'default'=>$default['Module_id'],
				),
			'name'=>array(
				'widget'=>'input',
				'default'=>$default['name'],
				) ,
			'from_Module_id'=>array(
				'widget'=>'hidden',
				'default'=>$data['handler_params']['from_Module_id'],
				) ,
			'from_name'=>array(
				'widget'=>'hidden',
				'default'=>$data['handler_params']['from_name'],
				) ,
		);
		return parent::__construct($hh,$params,$data);
	}
}

class wz_handler_mc extends gs_handler {

	function post() {
		$this->params['form_class']=$this->data['gspgid_va'][1];
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;
	
		
		$tpl=gs_tpl::get_instance();
		$tpl->assign('macros',json_encode($f->macros()));
		return $tpl->fetch('macros_insert_close.html');
	}

}

class form_modules_import  extends g_forms_table{
	function __construct($hh,$params=array(),$data=array()) {

		$xmlfiles=glob(cfg('lib_modules_dir').'*'.DIRECTORY_SEPARATOR."*.xml");
		array_unshift($xmlfiles,'');


		$hh=array(
		    'xmlfile' => Array
			(
			    'type' => 'file',
				'validate' => 'dummyValid',
			),
		    'xmlpath' => Array
			(
			    'type' => 'select',
				'verbose_name'=>'xml filename',
				'validate' => 'dummyValid',
				'options'=> array_combine($xmlfiles,$xmlfiles),
			),
		    'xml' => Array
			(
			    'type' => 'text',
				'validate' => 'dummyValid',
			),
		);
		return parent::__construct($hh,$params,$data);
	}
}

require_fullpath(__FILE__,'macros.php');
require_fullpath(__FILE__,'links.php');

?>
