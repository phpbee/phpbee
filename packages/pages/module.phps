<?php
gs_dict::append(array(
		'LOAD_BANNERS'=>'добавить баннеры',
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
		return '<a href="/admin/pages/">Страницы</a>';
	}
	
	static function get_handlers() {
		$data=array(
		'default'=>array(
			'default'=>'gs_base_handler.show404:{name:404.html}',
		),
		'handler'=>array(
			'info'=>array(
					'get'=>'handler{%$MODULE_NAME%}.get_info:return:gs_record',
					'show'=>'gs_base_handler.fetch:name:meta.html:hkey:get',
			),
			'/admin/form/tw_pages'=>array(
					'gs_base_handler.post:return:gs_record:{name:admin_form.html:form_class:g_forms_table:classname:tw{%$MODULE_NAME%}:href:/admin/pages}',
					'gs_base_handler.redirect:{href:/admin/pages}',
			),
		),
		'get_post'=>array(
			''=>'gs_base_handler.show:{name:pages.html}',
			'/admin/pages'=>'gs_base_handler.show:{name:adm_pages.html:classname:tw{%$MODULE_NAME%}}',
			'/admin/pages/delete'=>'admin_handler.deleteform:{classname:tw{%$MODULE_NAME%}}',
		),
	);
	return self::add_subdir($data,dirname(__file__));
	}
}

class handler{%$MODULE_NAME%} extends gs_base_handler {
	function get_info() {
		$o=new tw_pages;
		$url='/'.ltrim($this->data['gspgid_root'],'/');
		$rec=$o->find_records(array('url'=>$url),array('id','title','keywords','description'))->limit(1)->first();
		$info['title']=$rec->title;
		$info['keywords']=$rec->keywords;
		$info['description']=$rec->description;
		//$tpl=gs_tpl::get_instance();
		//$tpl->assign('page_info',$info);
		return $rec;
	}
}

class tw{%$MODULE_NAME%} extends gs_recordset_handler{
	const superadmin=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'url'=>"fString URL",
		{%foreach from=$SUBMODULES_DATA.FIELDS key=K item=L%}
			'{%$K%}'=>"{%$L%}",
		{%/foreach%}
		{%foreach from=$SUBMODULES_DATA.LINKS key=K item=L%}
			'{%$K%}'=>"{%$L%}",
		{%/foreach%}
		),$init_opts);
	}
}
?>