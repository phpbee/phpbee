<?php
gs_dict::append(array(
	));

class module{%$MODULE_NAME%} extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array(
					'rss_cfg',				) as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		$ret=array();
		$item=array();
		$item[]='<a href="/admin/rss/">rss</a>';
					$item[]='<a href="/admin/rss/rss_cfg">rss_cfg</a>';				$ret[]=$item;
		return $ret;
	}
	
	static function get_handlers() {
		$data=array(
'get'=>array(
'/admin/rss/rss_cfg'=>array(
  'gs_base_handler.show:name:adm_rss_cfg.html', 
),
'/admin/rss/rss_cfg/delete'=>array(
  'gs_base_handler.delete:{classname:rss_cfg}', 
  'gs_base_handler.redirect', 
),
'/admin/rss/rss_cfg/copy'=>array(
  'gs_base_handler.copy:{classname:rss_cfg}', 
  'gs_base_handler.redirect', 
),
''=>array(
  'gs_base_handler.rec_by_fieldname:classname:rss_cfg:fieldname:alias:return:gs_record^e404', 
  'rss_handler.execute:return:not_false', 
  'gs_base_handler.xml_show:return:not_false', 
 'end'=> 'end', 
  'gs_base_handler.save_file_public_html:filename:rss.xml:return:not_false', 
 'e404'=> 'gs_base_handler.show404:name:404.html', 
),
),
'handler'=>array(
'/admin/form/rss_cfg'=>array(
  'gs_base_handler.redirect_if:gl:save_cancel:return:true', 
  'gs_base_handler.post:{name:admin_form.html:classname:rss_cfg:form_class:rss_cfg_form}', 
  'gs_base_handler.redirect_if:gl:save_continue:return:true', 
  'gs_base_handler.redirect_if:gl:save_return:return:true', 
),
'/admin/inline_form/rss_cfg'=>array(
  'gs_base_handler.redirect_if:gl:save_cancel:return:true', 
  'gs_base_handler.post:{name:inline_form.html:classname:rss_cfg}', 
  'gs_base_handler.redirect_if:gl:save_continue:return:true', 
  'gs_base_handler.redirect_if:gl:save_return:return:true', 
),
),
		);
		return self::add_subdir($data,dirname(__file__));
	}

	static function gl($alias,$rec,$data) {
		$fname=dirname(__FILE__).DIRECTORY_SEPARATOR.'gl.php';
		if (file_exists($fname)) {
			$x=include($fname);
			return $x;
		}
		return parent::gl($alias,$rec,$data);
	}

	/*
	static function gl($alias,$rec) {
		if(!is_object($rec)) {
			$obj=new tw{%$MODULE_NAME%};
			$rec=$obj->get_by_id(intval($rec));
		}
		switch ($alias) {
			case '___show____':
				return sprintf('/{%$MODULE%}/show/%s/%d.html',
						date('Y/m',strtotime($rec->date)),
						$rec->get_id());
			break;
		}
	}
	*/
}
/*
class handler{%$MODULE_NAME%} extends gs_base_handler {
}
*/


class rss_cfg extends gs_recordset_short {
		public $no_urlkey=1;
	public $sortkey=true; 	public $no_ctime=true; 	public $orderby="id"; 
	function __construct($init_opts=false) { parent::__construct(array(

		
			'module_name'=>'fString verbose_name="module_name"  widget="select"    required=true        ',

		
			'recordset_name'=>'fString verbose_name="recordset_name"  widget="select"    required=true        ',

		
			'gl'=>'fString verbose_name="gl"   default="rec_show"   required=true        ',

		
			'disabled'=>'fCheckbox verbose_name="disabled"     required=false        ',

		
			'title_field_name'=>'fString verbose_name="title_field_name"     required=true        ',

		
			'details_field_name'=>'fString verbose_name="details_field_name"     required=true        ',

		
			'details_field_length'=>'fInt verbose_name="details_field_length"   default="500"   required=true        ',

		
			'records_limit'=>'fInt verbose_name="records_limit"   default="50"   required=true        ',

		
			'alias'=>'fString verbose_name="alias"     required=true  index=true      ',

		
			'title'=>'fString verbose_name="title"     required=true        ',

		
			'description'=>'fString verbose_name="description"     required=true        ',

								),$init_opts);

				
			
		
	}
			
	
	
}






?>
