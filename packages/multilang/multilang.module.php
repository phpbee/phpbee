<?php
class handler_multilang_base  extends gs_base_handler {
	function setlocale($ret) {
		$name=$this->params['name'];
		$value=$this->data[$name];
		$langs=languages();
		if(isset($langs[$value]))  {
			gs_session::save($value,'multilanguage_lang');
			if (class_exists('sys_languages')) {
				$rs=new sys_languages();
				$r=$rs->find_records(array('lang'=>$value))->first();
				if ($r) {
					gs_session::save($r->id,'filter_'.$name);
					//gs_session::save($r->locale,'multilanguage_locale');
					return $r;
				}
			}
		}
	}
	function setlocale_handler($ret) {
		$name=$this->params['name'];
		$filter=gs_filters_handler::get($name);
		if ($filter) {
		$f=$filter->current();
		self::set_locale($f);
		return;
		}
		$cl=gs_var_storage::load('multilanguage_lang');
		if ($cl) {
			$f=record_by_field('lang',$cl,'sys_languages');
			self::set_locale($f);
		}
	}
	static function set_locale($f) {
		gs_var_storage::save('multilanguage_lang',$f->lang);
		gs_var_storage::save('multilanguage_locale',$f->locale);
		gs_var_storage::save('multilanguage_date_format',$f->locale_date_format);
		gs_var_storage::save('multilanguage',$f);
		setlocale(LC_ALL,$f->locale);
		setlocale(LC_NUMERIC,'C');
	}


	static function multilang_start() {
		$data=array(
			    'handler_params' => Array
				(
				    'gspgid' => 'filter',
				    'class' => 'select_records',
				    'recordset' => 'sys_languages',
				    'name' => 'Lang',
				    'default_value' => cfg('multilang_default_language_id'),
				    'urltype' => 'session',
				)
		);
		$params=array(
			'module_name'=>'module',
		);
		$h=new gs_filters_handler($data,$params);
		$h->init();
		$filter=gs_filters_handler::get('Lang');
		if (!$filter) return;
		$f=$filter->current();
		if ($f) self::set_locale($f);
	}
}
?>
