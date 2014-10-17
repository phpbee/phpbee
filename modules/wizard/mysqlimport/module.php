<?php
class module_wizard_mysqlimport extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
	}
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/admin/wizard/mysqlimport/form/import'=>array(
				'handler_wizard_mysqlimport.form:name:form_import.html:form_class:form_wizard_mysqlimport:return:gs_record',
				'gs_base_handler.redirect_gl:gl:back',
				),
			),
		'get'=>array(
			'/admin/wizard/mysqlimport'=>array(
					'handler_wizard_mysqlimport.start:return:array',
					'gs_base_handler.show:name:start.html',
				),
			'/admin/wizard/mysqlimport/import'=>array(
					'tables'=>'handler_wizard_mysqlimport.start:return:array',
					'explain'=>'handler_wizard_mysqlimport.explain:return:array',
					'gs_base_handler.show:name:import.html',
				),
				),
		'post'=>array(		
			'/admin/wizard/mysqlimport/process'=>array(
					'handler_wizard_mysqlimport.process',
					'gs_base_handler.redirect_gl:gl:back',
				),
			),
		);
		return self::add_subdir($data,dirname(__file__));
	}
	static function gl($name,$record,$data) {
		switch ($name) {
			case 'back':
				return '/admin/wizard/recordset_fields/'.$record->get_id();
			break;
		}
	}
}
class handler_wizard_mysqlimport extends gs_handler {
	function process() {
		$rs=new wz_recordsets();
		$rec=$rs->find_records(array('name'=>$this->data['name']))->first(true);
		if (!$rec->get_id()) $rec->fill_values($this->data);
		foreach ($this->data['field'] as $f) {
			if (!isset($f['import'])) continue;

				
			$l=$rec->Fields->find(array('name'=>$f['name']))->first();
			if ($l) continue;
			$rec->Fields->new_record($f);
		}
		foreach ($this->data['link'] as $f) {
			if (!isset($f['import'])) continue;
			$rec->Links->new_record($f);
		}

		$rec->commit();

		return $rec;
	}
	static function select($k,$f) {
		if ($f['Key']=='PRI' && $f['Field']=='id') return false;
		if ($f['Key']!='PRI') {
			if (preg_match('/^_/',$k)) return false;
			if (preg_match('/_id$/i',$k)) return false;
			if (preg_match('/ID$/',$k)) return false;
			if (preg_match('/Id$/',$k)) return false;
		}
		return true;
	}
	static function lOne2One($k,$f,$tables) {
		$link=false;
		$patterns=array('/_id$/i','/ID$/','/Id$/');
		foreach ($patterns as $p) {
			if (preg_match($p,$f['Field'])) {
				$link=preg_replace($p,'',$f['Field']);
				break;
			}
		}
		if (!$link) return false;
		
		$link_rs=record_by_field('name',$link,'wz_recordsets');
		if ($link_rs) return $link_rs->name;

		$link_rs=record_by_field('table_name',$link,'wz_recordsets');
		if ($link_rs) return $link_rs->name;

		return ucfirst(($link))	;

	}
	static function type($t) {
		if (preg_match('/^varchar/i',$t)) return 'fString';
		if (preg_match('/^int/i',$t)) return 'fInt';
		if (preg_match('/^int/i',$t)) return 'fInt';
		if (preg_match('/^datetime/i',$t)) return 'fDatetime';
		if (preg_match('/^text/i',$t)) return 'fText';
		if (preg_match('/^bigtext/i',$t)) return 'fText';

		return 'fString';

	}
	function start() {
		$pool=gs_connector_pool::get_instance();
		$conn=$pool->get_connector($this->data['gspgid_va'][0]);
		$tables=$conn->get_table_names();
		return $tables;
	}
	function explain() {
		$pool=gs_connector_pool::get_instance();
		$conn=$pool->get_connector($this->data['gspgid_va'][0]);

		$fields=$conn->get_fields_info($this->data['gspgid_va'][2]);
		return $fields;

	}


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
class form_wizard_mysqlimport extends g_forms_table{
	function _____construct($hh,$params=array(),$data=array()) {
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

