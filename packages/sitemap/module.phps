<?php
gs_dict::append(array(
	));

class module{%$MODULE_NAME%} extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array(
					'sitemap_cfg',				) as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		$ret=array();
		$item=array();
		$item[]='<a href="/admin/sitemap/">sitemap</a>';
					$item[]='<a href="/admin/sitemap/sitemap_cfg">sitemap_cfg</a>';				$ret[]=$item;
		return $ret;
	}
	
	static function get_handlers() {
		$data=array(
'get'=>array(
'/admin/sitemap/sitemap_cfg'=>array(
  'gs_base_handler.show:name:adm_sitemap_cfg.html', 
),
'/admin/sitemap/sitemap_cfg/delete'=>array(
  'gs_base_handler.delete:{classname:sitemap_cfg}', 
  'gs_base_handler.redirect', 
),
'/admin/sitemap/sitemap_cfg/copy'=>array(
  'gs_base_handler.copy:{classname:sitemap_cfg}', 
  'gs_base_handler.redirect', 
),
'execute'=>array(
  'sitemap_handler.execute:return:not_false', 
  'gs_base_handler.xml_show:return:not_false', 
  'gs_base_handler.save_file_public_html:filename:sitemap.xml:return:not_false', 
),
),
'handler'=>array(
'/admin/form/sitemap_cfg'=>array(
  'gs_base_handler.redirect_if:gl:save_cancel:return:true', 
  'gs_base_handler.post:{name:admin_form.html:classname:sitemap_cfg:form_class:sitemap_cfg_form}', 
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


class sitemap_cfg extends gs_recordset_short {
		public $no_urlkey=true; 	public $no_ctime=true; 	public $orderby="id"; 
	function __construct($init_opts=false) { parent::__construct(array(

		
			'module_name'=>'fString verbose_name="module_name"  widget="select"    required=true        ',

		
			'recordset_name'=>'fString verbose_name="recordset_name"  widget="select"    required=true        ',

		
			'gl'=>'fString verbose_name="gl"   default="rec_show"   required=true        ',

		
			'disabled'=>'fCheckbox verbose_name="disabled"     required=false        ',

								),$init_opts);

				
			
		
	}
			
	
	
}






?>
