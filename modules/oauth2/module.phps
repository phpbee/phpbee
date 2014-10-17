<?php
gs_dict::append(array(
	));

class module{%$MODULE_NAME%} extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array(
					'oauth2_config',					'oauth2_config_images',					'oauth2_config_images_files',				) as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		$ret=array();
		$item=array();
		$item[]='<a href="/admin/oauth2/">oauth2</a>';
					$item[]='<a href="/admin/oauth2/oauth2_config">oauth2_config</a>';														$ret[]=$item;
		return $ret;
	}
	
	static function get_handlers() {
		$data=array(
'get'=>array(
''=>array(
  'oauth2_handler.startlogin', 
),
'/admin/oauth2/oauth2_config'=>array(
  'gs_base_handler.show:name:adm_oauth2_config.html', 
),
'/admin/oauth2/oauth2_config/delete'=>array(
  'gs_base_handler.delete:{classname:oauth2_config}', 
  'gs_base_handler.redirect', 
),
'/admin/oauth2/oauth2_config/copy'=>array(
  'gs_base_handler.copy:{classname:oauth2_config}', 
  'gs_base_handler.redirect', 
),
'pushtoken/vk_app'=>array(
  'oauth2_handler.pushtoken:name:vk_app', 
),
),
'post'=>array(
''=>array(
  'oauth2_handler.startlogin', 
),
),
'handler'=>array(
'/admin/form/oauth2_config'=>array(
  'gs_base_handler.redirect_if:gl:save_cancel:return:true', 
  'gs_base_handler.post:{name:admin_form.html:classname:oauth2_config:form_class:g_forms_table}', 
  'gs_base_handler.redirect_if:gl:save_continue:return:true', 
  'gs_base_handler.redirect_if:gl:save_return:return:true', 
),
'/admin/inline_form/oauth2_config'=>array(
  'gs_base_handler.redirect_if:gl:save_cancel:return:true', 
  'gs_base_handler.post:{name:inline_form.html:classname:oauth2_config}', 
  'gs_base_handler.redirect_if:gl:save_continue:return:true', 
  'gs_base_handler.redirect_if:gl:save_return:return:true', 
),
'/admin/form/oauth2_config_images'=>array(
  'gs_base_handler.post:{name:admin_form.html:classname:oauth2_config_images:form_class:g_forms_table:return:gs_record}', 
  'gs_base_handler.redirect', 
  'gs_base_handler.post:{name:admin_form.html:classname:oauth2_config_images:form_class:g_forms_table:return:gs_record}', 
  'gs_base_handler.redirect', 
),
'loginbuttons'=>array(
  'admin_handler.show:name:loginbuttons.html', 
),
'loginlinks'=>array(
  'admin_handler.show:name:loginlinks.html', 
),
'loginlinks/menu'=>array(
  'admin_handler.show:name:loginlinks_menu.html', 
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


class oauth2_config extends gs_recordset_short {
			public $no_urlkey=1;
		public $no_ctime=true; 	public $orderby="id"; 
			function __construct($init_opts=false) { parent::__construct(array(

		
			'class'=>'fSelect verbose_name="class"  widget="select"   options="oauth2_vk,oauth2_vk_app,oauth2_google,oauth2_facebook,oauth2_twitter"  required=true        ',

		
			'APP_ID'=>'fString verbose_name="APP_ID"     required=true        ',

		
			'APP_SECRET'=>'fString verbose_name="APP_SECRET"     required=false        ',

		
			'SCOPE'=>'fString verbose_name="SCOPE"     required=false        ',

		
			'CONSUMER_KEY'=>'fString verbose_name="CONSUMER_KEY"     required=false        ',

		
			'title'=>'fString verbose_name="Title"     required=false        ',
			'enabled'=>'fCheckbox verbose_name="enabled"     default=1',

		
			'enabled'=>'fCheckbox verbose_name="enabled"     required=true  index=true default=1     ',

				
			'Logo'=>'lMany2One oauth2_config_images:Parent verbose_name="Logo"   widget="gallery"  required=false    ',

						),$init_opts);

				
			
		
	}
			
	
	
}


class oauth2_config_images extends tw_images {
			public $no_urlkey=1;
			public $orderby="id"; 
			function __construct($init_opts=false) { parent::__construct(array(

		
			'file_uid'=>'fString    options="64"  required=false  index=true      ',

		
			'group_key'=>'fString    options="32"  required=false  index=true      ',

		
			'file_uid'=>'fString    options="64"  required=false  index=true      ',

		
			'group_key'=>'fString    options="32"  required=false  index=true      ',

				
			'Parent'=>'lOne2One oauth2_config    required=false  mode=link  ',

		
			'File'=>'lOne2One oauth2_config_images_files verbose_name="File"   widget="include_form"  required=false  hidden=false  ',

		
			'Parent'=>'lOne2One oauth2_config    required=false  mode=link  ',

		
			'File'=>'lOne2One oauth2_config_images_files verbose_name="File"   widget="include_form"  required=false  hidden=false  ',

						),$init_opts);

						$this->structure['fkeys']=array(
								array('link'=>'oauth2_config.Logo','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
				
						
								array('link'=>'oauth2_config.Logo','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
				
						
				     );
		
			
		
	}
			
	
	
}


class oauth2_config_images_files extends tw_file_images {
			public $no_urlkey=1;
			public $orderby="id"; 
			function __construct($init_opts=false) { parent::__construct(array(

				
			'Parent'=>'lOne2One oauth2_config_images    required=false    ',

		
			'Parent'=>'lOne2One oauth2_config_images    required=false    ',

						),$init_opts);

						$this->structure['fkeys']=array(
								array('link'=>'oauth2_config_images.File','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
				
								array('link'=>'oauth2_config_images.File','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
				
				     );
		
			
		
	}
			
	
		function config_previews() {
		parent::config_previews();
		$this->config=array_merge($this->config,array(
					'logo'=>array('width'=>200,'height'=>40,'method'=>'use_height','bgcolor'=>array(0,0,0)),
					'orig'=>array('width'=>200,'height'=>200,'method'=>'copy','bgcolor'=>array(0,0,0)),
				));
	}
	
}






?>
