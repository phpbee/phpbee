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
		return '<a href="/admin/articles/">Статьи</a>';
	}
	
	static function get_handlers() {
		$data=array(
		'get_post'=>array(
			''=>'gs_base_handler.show:{name:articles.html}',
			'show'=>array(
					'gs_base_handler.validate_gl:{name:show:return:true^e404}',
					'gs_base_handler.show:{name:article_show.html}',
					'end',
					'e404'=>'gs_base_handler.show404:return:true',
					),
			'/admin/articles'=>'gs_base_handler.show:{name:adm_articles.html:classname:tw{%$MODULE_NAME%}}',
			'/admin/articles/delete'=>'admin_handler.deleteform:{classname:tw{%$MODULE_NAME%}}',
			'/admin/articles/iframe_gallery'=>'gs_base_handler.many2one:{name:iframe_gallery.html}',
		),
		'handler'=>array(
			''=>'gs_base_handler.show:{name:articles_show.html}',
			'list'=>'gs_base_handler.show:{name:articles_list.html}',
			'last'=>'gs_base_handler.show:{name:articles_last.html}',
			'short_list'=>'gs_base_handler.show:{name:news_short_list.html}',
			'/admin/form/tw{%$MODULE_NAME%}'=>array(
					'gs_base_handler.post:{name:admin_form.html:form_class:g_forms_table:classname:tw{%$MODULE_NAME%}}',
					'gs_base_handler.redirect',
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
				return sprintf('/{%$MODULE%}/show/%d.html',$rec->get_id());
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
		'description'=> "fText 'Содержание' widget=wysiwyg images_key=Images required=false keywords=1",
		'pid'=> "lOne2One tw{%$MODULE_NAME%}",
		'Childs'=>"lMany2One tw{%$MODULE_NAME%}:pid counter=false",
		'text_id'=> "fString 'Идентификатор статьи' required=false",
		'url'=>"fString required=false",
		{%foreach from=$SUBMODULES_DATA.LINKS key=K item=L%}
			'{%$K%}'=>"{%$L%}",
		{%/foreach%}
		),$init_opts);
		
		$this->structure['triggers']['before_update'][]='keywords';
		$this->structure['triggers']['after_insert'][]='keywords';
		$this->structure['triggers']['before_delete'][]='keywords';
		
	}
	
	function keywords($rec,$type) {
        $url=module{%$MODULE_NAME%}::gl('show',$rec);
		if ($type=='before_update' || $type=='after_insert') {
			$keywords=metatags::get_keywords($rec);
			$description=str_replace("\n"," ",substr(strip_tags($rec->description),0,254));
			$title=strip_tags($rec->name);
			metatags::save($url,$title,$keywords,$description);
		}elseif ($type=='before_delete') {
			metatags::delete($url);
		}
	}
	
}


?>
