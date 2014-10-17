{%capture assign=DATA%}
	LINKS::Tags::lMany2Many tw{%$MODULE_NAME%}:link{%$MODULE_NAME%} 'Теги' widget=lMany2Many
{%/capture%}
<?php
gs_dict::append(array(
		'LOAD_RECORDS'=>'Добавить картинки',
		'SUBMIT_FORM'=>'Сохранить',
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
		return '<a href="/admin/tags/">Теги</a>';
	}
	
	static function get_handlers() {
		$data=array(
		'get_post'=>array(
			''=>'gs_base_handler.show:{name:tags.html}',
			'show'=>array(
					'gs_base_handler.validate_gl:{name:show:return:true^e404}',
					'gs_base_handler.show:{name:tags_show.html}',
					'end',
					'e404'=>'gs_base_handler.show404:return:true',
					),
			'/admin/tags'=>'gs_base_handler.show:{name:adm_tags.html:classname:tw{%$MODULE_NAME%}}',
			'/admin/tags/delete'=>'admin_handler.deleteform:{classname:tw{%$MODULE_NAME%}}',
		),
		'handler'=>array(
			''=>'gs_base_handler.show:{name:tags_show.html}',
			'list'=>'gs_base_handler.show:{name:tags_list.html}',
			'/admin/form/tw{%$MODULE_NAME%}'=>array(
					'gs_base_handler.redirect_if:gl:save_cancel:return:true',
					'gs_base_handler.post:{name:admin_form.html:classname:tw{%$MODULE_NAME%}:form_class:g_forms_table}',
					'gs_base_handler.redirect_if:gl:save_continue:return:true',
					'gs_base_handler.redirect_if:gl:save_return:return:true',
),

		),
	);
	return self::add_subdir($data,dirname(__file__));
	}

	static function gl($alias,$rec) {
		if(!is_object($rec)) {
			$obj=new tw{%$MODULE_NAME%};
			var_dump($rec);
			$rec=$obj->get_by_id(intval($rec));
		}
		switch ($alias) {
			case 'show':
				return sprintf('/{%$PARENT_MODULE%}/{%$MODULE%}/show/%d.html',$rec->get_id());
			break;
		}
	}
}

class handler{%$MODULE_NAME%} extends gs_base_handler {
}

class tw{%$MODULE_NAME%} extends gs_recordset_handler {
	const superadmin=1;
	var $no_urlkey=true;
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString 'Название' keywords=1",
		'Parent'=>"lMany2Many {%$PARENT_RECORDSET%}:link{%$MODULE_NAME%}",
		{%foreach from=$SUBMODULES_DATA.LINKS key=K item=L%}
			'{%$K%}'=>"{%$L%}",
		{%/foreach%}
		),$init_opts);
	}
	
	function norm() {
        $this->preload();
        $this->shuffle();
        $weight=$num=array();
		foreach ($this as $r) {
			$num[$r->get_id()]=$r->_Parent_count;
		}
		$min=min($num);
		$max=max($num);
        $step=11/($max-$min);
        foreach ($num as $key => $n) {
			$weight[$key]=10+ceil(($n-$min)*$step);
		}
		return $weight;
	}
}


?>
