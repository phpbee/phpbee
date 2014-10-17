<?php
gs_dict::append(array(
	));

class module{%$MODULE_NAME%} extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array(
					'sys_languages',
					'sys_languages_images',
					'sys_languages_images_files',
				) as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		$ret=array();
		$item=array();
		$item[]='<a href="/admin/multilang/">Multilang</a>';
					$item[]='<a href="/admin/multilang/sys_languages">Языки</a>';														$ret[]=$item;
		return $ret;
	}
	
	static function get_handlers() {
		$data=array(
'get'=>array(
'/setlocale'=>array(
  'handler_multilang_base.setlocale:name:Lang', 
  'gs_base_handler.redirect', 
),
'/admin/multilang/sys_languages'=>array(
  'gs_base_handler.show:name:adm_sys_languages.html', 
),
'/admin/multilang/sys_languages/delete'=>array(
  'gs_base_handler.delete:{classname:sys_languages}', 
  'gs_base_handler.redirect', 
),
'/admin/multilang/sys_languages/copy'=>array(
  'gs_base_handler.copy:{classname:sys_languages}', 
  'gs_base_handler.redirect', 
),
),
'handler'=>array(
'/setlocale/'=>array(
  'handler_multilang_base.setlocale_handler:name:Lang', 
),
'/admin/form/sys_languages'=>array(
  'gs_base_handler.redirect_if:gl:save_cancel:return:true', 
  'gs_base_handler.post:{name:admin_form.html:classname:sys_languages:form_class:g_forms_table}', 
  'gs_base_handler.redirect_if:gl:save_continue:return:true', 
  'gs_base_handler.redirect_if:gl:save_return:return:true', 
),
),
		);
		return self::add_subdir($data,dirname(__file__));
	}

	static function gl($alias,$rec) {
		$fname=dirname(__FILE__).DIRECTORY_SEPARATOR.'gl.php';
		if (file_exists($fname)) {
			$x=include($fname);
			return $x;
		}
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


class sys_languages extends gs_recordset_short {
		public $orderby="id"; 
	function __construct($init_opts=false) { parent::__construct(array(

		
			'name'=>'fString verbose_name="name"     required=true       ',

		
			'lang'=>'fString verbose_name="lang"     required=true       ',

		
			'locale'=>'fString verbose_name="locale"     required=true       ',

		
			'locale_date_format'=>'fString verbose_name="locale_date_format"   default="%Y-%m-%d"   required=false       ',

		
			'jquery_date_format'=>'fString   default="dd-mm-yy"   required=false       ',

				
			'Flag'=>'lOne2One sys_languages_images verbose_name="Flag"   widget="include_form"  required=false  mode=link required=false  ',

						),$init_opts);

						$this->structure['fkeys']=array(
						
				     );
				
	}
	
	
}


class sys_languages_images extends tw_images {
		public $orderby="id"; 
	function __construct($init_opts=false) { parent::__construct(array(

				
			'Parent'=>'lOne2One sys_languages    required=false    ',

		
			'File'=>'lOne2One sys_languages_images_files verbose_name="File"   widget="include_form"  required=false  hidden=false  ',

						),$init_opts);

						$this->structure['fkeys']=array(
								array('link'=>'sys_languages.Flag','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
				
						
				     );
				
	}
	
	
}


class sys_languages_images_files extends tw_file_images {
		public $orderby="id"; 
	function __construct($init_opts=false) { parent::__construct(array(

				
			'Parent'=>'lOne2One sys_languages_images    required=false    ',

						),$init_opts);

						$this->structure['fkeys']=array(
								array('link'=>'sys_languages_images.File','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
				
				     );
				
	}
	
		function config_previews() {
		parent::config_previews();
		$this->config=array_merge($this->config,array(
					'ico'=>array('width'=>20,'height'=>20,'method'=>'use_width','bgcolor'=>array(0,0,0)),
				));
	}
	
}






?>
