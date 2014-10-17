<?php
gs_dict::append(array(
	));

class module{%$MODULE_NAME%} extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array(
					'{%$MODULE%}',
					'{%$MODULE%}_images',
					'{%$MODULE%}_images_files',
				) as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		$ret=array();
		$item=array();
		$item[]='<a href="/admin/{%$MODULE%}/">Галерея</a>';
		$ret[]=$item;
		return $ret;
	}
	
	static function get_handlers() {
		$data=array(
			'handler'=>array(
				'/admin/form/{%$MODULE%}_images'=>array(
				'gs_base_handler.post:{name:admin_form.html:classname:{%$MODULE%}_images:form_class:g_forms_table:return:gs_record}', 
				'gs_base_handler.redirect', 
				),
			'/admin/form/{%$MODULE%}'=>array(
				'gs_base_handler.redirect_if:gl:save_cancel:return:true', 
				'gs_base_handler.post:{name:admin_form.html:classname:{%$MODULE%}:form_class:g_forms_table}', 
				'gs_base_handler.redirect_if:gl:save_continue:return:true', 
				'gs_base_handler.redirect_if:gl:save_return:return:true', 
				),
			),
			'get'=>array(
				'/admin/{%$MODULE%}'=>array(
					'gs_base_handler.show:name:adm_{%$MODULE%}.html', 
					),
				'/admin/{%$MODULE%}/delete'=>array(
					'gs_base_handler.delete:{classname:{%$MODULE%}}', 
					'gs_base_handler.redirect', 
					),
				'/admin/{%$MODULE%}/copy'=>array(
					'gs_base_handler.copy:{classname:{%$MODULE%}}', 
					'gs_base_handler.redirect', 
					),
				''=>array(
					'gs_base_handler.show:{name:list.html}', 
					),
				'show'=>array(
					'gs_base_handler.validate_gl:{name:show:return:true^e404}', 
					'gs_base_handler.show:name:{%$MODULE%}_show.html', 
					'end', 
					'e404'=> 'gs_base_handler.show404:{name:404.html:return:true}', 
					),
				),
			);
		return self::add_subdir($data,dirname(__file__));
	}

	static function gl($alias,$rec) {
		if(!is_object($rec)) {
			list($g_id,$i_id)=sscanf($rec,'%d/%d.html');
			$obj=new gallery_images;
			$rec=$obj->get_by_id(intval($i_id));
		}

		switch ($alias) {
			case 'show':
				return sprintf('/{%$MODULE%}/show/%d/%d.html',$rec->Parent->first()->get_id(),$rec->get_id());
			break;
		}
	}
}

	class {%$MODULE%} extends gs_recordset_short {
	public $no_urlkey=true;
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=>'fString verbose_name="Название"     required=true       ',
		'Images'=>'lMany2One {%$MODULE%}_images:Parent verbose_name="Картинки"   widget="MultiPowUpload"  required=false    ',
		),$init_opts);
	}
}

class {%$MODULE%}_images extends tw_images {
	public $no_urlkey=true; 	public $orderby="id"; 
	function __construct($init_opts=false) { parent::__construct(array(
		'group_key'=>'fString    options="32"  required=false index=true      ',
		'Parent'=>'lOne2One {%$MODULE%}    required=false  mode=link  ',
		'File'=>'lOne2One {%$MODULE%}_images_files verbose_name="File"   widget="include_form"  required=false  hidden=false  ',
		),$init_opts);

		$this->structure['fkeys']=array(array('link'=>'{%$MODULE%}.Images','on_delete'=>'CASCADE','on_update'=>'CASCADE'));
	}
}

class {%$MODULE%}_images_files extends tw_file_images {
	public $no_urlkey=true; 	public $orderby="id"; 
	function __construct($init_opts=false) { parent::__construct(array(
		'Parent'=>'lOne2One {%$MODULE%}_images    required=false    ',
		),$init_opts);

		$this->structure['fkeys']=array(array('link'=>'{%$MODULE%}_images.File','on_delete'=>'CASCADE','on_update'=>'CASCADE'));
	}
	
	function config_previews() {
		parent::config_previews();
		$this->config=array_merge($this->config,array(
			'tumb'=>array('width'=>200,'height'=>150,'method'=>'use_crop','bgcolor'=>array(),'position'=>array('50%','50%')),
			'small'=>array('width'=>100,'height'=>75,'method'=>'use_crop','bgcolor'=>array(),'position'=>array('50%','50%')),
			'full'=>array('width'=>570,'height'=>500,'method'=>'use_width','bgcolor'=>array(255,255,255),'position'=>array('50%','50%')),
		));
	}
}

?>
