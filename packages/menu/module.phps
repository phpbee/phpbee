<?php
gs_dict::append(array(
		'menu_top'=>'Верхнее меню',
		'menu_bottom'=>'Нижнее меню',
	));

class module{%$MODULE_NAME%} extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array('tw{%$MODULE_NAME%}') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		return '<a href="/admin/menu/">Навигация</a>';
	}
	
	static function get_handlers() {
		$data=array(
		'get_post'=>array(
			'/admin/menu'=>'gs_base_handler.show:{name:adm_menu.html:classname:tw{%$MODULE_NAME%}}',
			'/admin/menu/delete'=>'admin_handler.deleteform:{classname:tw{%$MODULE_NAME%}}',
			'/admin/menu/iframe_gallery'=>'gs_base_handler.many2one:{name:iframe_gallery.html}',
		),
		'handler'=>array(
			'top'=>'gs_base_handler.show:{name:menu_top.html}',
			'bottom'=>'gs_base_handler.show:{name:menu_bottom.html}',
			'/admin/form/tw{%$MODULE_NAME%}'=>array(
					'gs_base_handler.post:{name:form.html:form_class:g_forms_table:classname:tw{%$MODULE_NAME%}:form_class:form_admin:return:gs_record}',
					'gs_base_handler.redirect',
					),
		),
	);
	return self::add_subdir($data,dirname(__file__));
	}

	static function gl($alias,$rec) {
		if(!is_object($rec)) {
			$obj=new tw{%$MODULE_NAME%};
			$rec=$obj->get_by_id(intval($rec));
		}
		switch ($alias) {
			case 'show':
				return sprintf('/{%$MODULE%}/show/%d.html',$rec->get_id());
			break;
		}
	}
}

class handler{%$MODULE_NAME%} extends gs_base_handler {
}

class tw{%$MODULE_NAME%} extends gs_recordset_handler {
	const superadmin=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString 'Название'",
		'url'=>"fString 'URL'",
		'sort'=>"fInt 'Сортировка' widget='int'",
		'position'=>"fSelect Положение values='menu_top,menu_bottom'",
		'File'=>"lOne2One tw{%$MODULE_NAME%}_files 'Изображение в шапку' hidden=false widget=include_form",
		'pid'=> "lOne2One tw{%$MODULE_NAME%}",
		'Childs'=>"lMany2One tw{%$MODULE_NAME%}:pid counter=false",
		{%foreach from=$SUBMODULES_DATA.LINKS key=K item=L%}
			'{%$K%}'=>"{%$L%}",
		{%/foreach%}
		),$init_opts);
	}
}

class tw{%$MODULE_NAME%}_files extends tw_file_images {
	function __construct($init_opts=false) {
		parent::__construct($init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'tw{%$MODULE_NAME%}.File','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
	}
	
	function config_previews() {
		parent::config_previews();
		$this->config=array_merge($this->config,array(
			'small'=>array('width'=>50,'height'=>50,'method'=>'use_fields','bgcolor'=>array(255,255,255)),
			'left'=>array('width'=>160,'height'=>160,'method'=>'use_width'),
			'center'=>array('width'=>606,'height'=>81,'method'=>'use_crop'),
		));
	}
}


?>