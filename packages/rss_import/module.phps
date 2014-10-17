<?php
gs_dict::append(array(
	));

class module{%$MODULE_NAME%} extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array(
					'rss_import_cfg',				) as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		$ret=array();
		$item=array();
		$item[]='<a href="/admin/rss_import/">rss_import</a>';
					$item[]='<a href="/admin/rss_import/rss_import_cfg">rss_import_cfg</a>';				$ret[]=$item;
		return $ret;
	}
	
	static function get_handlers() {
		$data=array(
'get'=>array(
'/admin/rss_import/rss_import_cfg'=>array(
  'gs_base_handler.show:name:adm_rss_import_cfg.html', 
),
'/admin/rss_import/rss_import_cfg/delete'=>array(
  'gs_base_handler.delete:{classname:rss_import_cfg}', 
  'gs_base_handler.redirect', 
),
'/admin/rss_import/rss_import_cfg/copy'=>array(
  'gs_base_handler.copy:{classname:rss_import_cfg}', 
  'gs_base_handler.redirect', 
),
'execute'=>array(
  'rss_import_handler.execute', 
),
),
'handler'=>array(
'/admin/form/rss_import_cfg'=>array(
  'gs_base_handler.redirect_if:gl:save_cancel:return:true', 
  'gs_base_handler.post:{name:admin_form.html:classname:rss_import_cfg:form_class:rss_import_cfg_form}', 
  'gs_base_handler.redirect_if:gl:save_continue:return:true', 
  'gs_base_handler.redirect_if:gl:save_return:return:true', 
),
'/admin/inline_form/rss_import_cfg'=>array(
  'gs_base_handler.redirect_if:gl:save_cancel:return:true', 
  'gs_base_handler.post:{name:inline_form.html:classname:rss_import_cfg}', 
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


class rss_import_cfg extends gs_recordset_short {
		public $no_urlkey=true; 	public $no_ctime=true; 	public $orderby="id"; 
	function __construct($init_opts=false) { parent::__construct(array(

		
			'url'=>'fString verbose_name="url"     required=false        ',

		
			'cron_line'=>'fString verbose_name="cron_line"     required=false        ',

		
			'disabled'=>'fCheckbox verbose_name="disabled"     required=false        ',

		
			'rec_default_values'=>'fString verbose_name="rec_default_values"     required=false        ',

				
			'recordset'=>'lOne2One wz_recordsets verbose_name="recordset"   widget="parent_list"  required=true    ',

		
			'title_fieldname'=>'lOne2One wz_recordset_fields verbose_name="title_fieldname"   widget="parent_list"  required=false    ',

		
			'description_fieldname'=>'lOne2One wz_recordset_fields verbose_name="description_fieldname"   widget="parent_list"  required=false    ',

		
			'images_linkname'=>'lOne2One wz_recordset_links verbose_name="images_linkname"   widget="parent_list"  required=false    ',

		
			'link_fieldname'=>'lOne2One wz_recordset_fields verbose_name="link_fieldname"   widget="parent_list"  required=false    ',

						),$init_opts);

						$this->structure['fkeys']=array(
						
						
						
						
						
				     );
		
			
		
	}
			
	
	
}






?>
