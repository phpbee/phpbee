{%capture assign=DATA%}
	LINKS::Banners::lMany2Many tw{%$MODULE_NAME%}:link{%$MODULE_NAME%} 'Баннеры' required=false  widget=lMany2Many
{%/capture%}
<?php

gs_dict::append(array(
		'left'=>'слева',
		'center_top'=>'по центру вверху',
		'center_bottom'=>'по центру внизу',
	));

class module{%$MODULE_NAME%} extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array('tw{%$MODULE_NAME%}','tw{%$MODULE_NAME%}_files') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		return '<a href="/admin/banners/">Баннеры</a>';
	}
	
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/banners/left'=>array(
					'get'=>'handler{%$MODULE_NAME%}.get_banners:return:gs_recordset',
					'show'=>'gs_base_handler.fetch:name:banners_left.html:hkey:get',
			),
			'/banners/center_top'=>array(
					'get'=>'handler{%$MODULE_NAME%}.get_banners:return:gs_recordset',
					'show'=>'gs_base_handler.fetch:name:banners_top.html:hkey:get',
			),
			'/banners/center_bottom'=>array(
					'get'=>'handler{%$MODULE_NAME%}.get_banners:return:gs_recordset',
					'show'=>'gs_base_handler.fetch:name:banners_bottom.html:hkey:get',
			),
			'/admin/form/tw{%$MODULE_NAME%}'=>array(
					'gs_base_handler.post:return:gs_record:{name:admin_form.html:form_class:g_forms_table:classname:tw{%$MODULE_NAME%}:href:/admin/banners}',
					'gs_base_handler.redirect:{href:/admin/banners}',
			),
		),
		'get_post'=>array(
			'list'=>'gs_base_handler.show',
			'/admin/banners'=>'gs_base_handler.show:{name:adm_banners.html:classname:tw{%$MODULE_NAME%}}',
			'/admin/banners/delete'=>'admin_handler.deleteform:{classname:tw{%$MODULE_NAME%}}',
		),
	);
	return self::add_subdir($data,dirname(__file__));
	}
}

class handler{%$MODULE_NAME%} extends gs_base_handler {
	function get_banners()
	{
		$tpl=gs_tpl::get_instance();
		$gspgid=$this->data['gspgid_root'];
		$parts=explode('/',$gspgid);
		$tb=new tw_{%$PARENT_MODULE%};
		$bns=new tw{%$MODULE_NAME%};
		$info=null;
		if (!empty($gspgid))
		{
			do {
				$turl="/".implode('/',$parts);
				$rec=$tb->find_records(array('url'=>$turl),array('id'))->first();
				if (!empty($rec)) {
					$info=$rec->Banners->find(array('position'=>$this->data['gspgid_a'][1]));
				}
				if (!empty($parts)) unset($parts[count($parts)-1]);
				else $parts=-1;
			}while (empty($info)  && $parts!=-1);
		}
		if (empty($gspgid) || empty($info)) {
			$turl='/';
			$rec=$tb->find_records(array('url'=>$turl),array('id'))->first();
				if (!empty($rec)) {
					$info=$rec->Banners->find(array('position'=>$this->data['gspgid_a'][1]));
				}
		}

		//gs_logger::dump();
		//die();
		return $info;
	}
}

class tw{%$MODULE_NAME%} extends gs_recordset_short {
	const superadmin=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'subject'=>"fString 'Название'",
		'url'=>"fString 'URL'",
		'position'=>"fSelect Положение values='left,center_top,center_bottom'",
		'File'=>"lOne2One tw{%$MODULE_NAME%}_files 'Баннер' hidden=false widget=include_form",
		'Pages'=> "lMany2Many tw_{%$PARENT_MODULE%}:link{%$MODULE_NAME%}",
		{%foreach from=$SUBMODULES_DATA.LINKS key=K item=L%}
			'{%$K%}'=>"{%$L%}",
		{%/foreach%}
		),$init_opts);
	}
}

class tw{%$MODULE_NAME%}_files extends tw_file_images {
	function __construct($init_opts=false) {
		parent::__construct(array(),$init_opts);
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
