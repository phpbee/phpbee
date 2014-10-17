<?php
gs_dict::append(array(
		'LOAD_IMAGES'=>'добавить картинки',
	));

class module_mypages extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array('tw_mypages','tw_mypages_stats') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		return '<a href="/admin/mypages/">Новости</a>';
	}
	
	static function get_handlers() {
		$data=array(
		'get'=>array(
			''=>'gs_base_handler.show:{name:mypages.html}',
			'show/*/*'=>array(
					'gs_base_handler.validate_gl:{name:show:return:true^e404}',
					'gs_base_handler.show:{name:mypages_show.html}',
					'end',
					'e404'=>'gs_base_handler.show404:return:true',
					'555'=>'gs_base_handler.show:{name:555.html:return:true}',
					),
			'/admin/mypages'=>'gs_base_handler.show:{name:adm_mypages.html:classname:tw_mypages}',
			'/admin/mypages/delete'=>'admin_handler.deleteform:{classname:tw_mypages}',
			'/admin/mypages/iframe_gallery'=>'gs_base_handler.many2one:{name:iframe_gallery.html}',
		),
		'handler'=>array(
			''=>'gs_base_handler.show:{name:handler_mypages.html}',
			'calendar'=>'gs_base_handler.show:{name:handler_calendar.html}',
			'last'=>'gs_base_handler.show',
			'short_list'=>'gs_base_handler.show:{name:mypages_short_list.html}',
		),
	);
	return self::add_subdir($data,dirname(__file__));
	}

	static function gl($alias,$rec) {
		if(!is_object($rec)) {
			$obj=new tw_mypages;
			$rec=$obj->get_by_id(intval($rec));
		}
		switch ($alias) {
			case 'show':
				return sprintf('/{%$MODULE%}/show/%s/%d.html',
						date('Y/m',strtotime($rec->date)),
						$rec->get_id());
			break;
		}
	}
}

class handler_mypages extends gs_base_handler {
}

class tw_mypages extends gs_recordset_handler {
	const superadmin=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'date'=>"fDatetime дата",
		'subject'=>"fString 'Заголовок' keywords=1",
		'text'=>"fText текст _widget=wysiwyg images_key=Images keywords=1",
		//'keywords'=>"fText Keywords trigger=normalize:text:50 required=false",
		'Images'=>"fFile Images",
		/*
		{%foreach from=$SUBMODULES_DATA.LINKS key=K item=L%}
			'{%$K%}'=>"{%$L%}",
		{%/foreach%}
		*/
		//'hot'=>"fCheckbox горячая",
		//'hidden'=>"fCheckbox спрятать",
		),$init_opts);
		$this->structure['triggers']['before_delete'][]='stat_mypages';
		$this->structure['triggers']['before_insert'][]='stat_mypages';
		$this->structure['triggers']['before_update'][]='stat_mypages';
		
		$this->structure['triggers']['before_update'][]='keywords';
		$this->structure['triggers']['after_insert'][]='keywords';
		$this->structure['triggers']['before_delete'][]='keywords';
		
	}

	function rand($rec) {
		$rec->text=md5(rand());
	}
	
	function keywords($rec,$type) {
		$url=module_mypages::gl('show',$rec);
		if ($type=='before_update' || $type=='after_insert') {
			$keywords=metatags::get_keywords($rec);
			$description=str_replace("\n"," ",substr(strip_tags($rec->text),0,254));
			$title=strip_tags($rec->subject);
			metatags::save($url,$title,$keywords,$description);
		}elseif ($type=='before_delete') {
			metatags::delete($url);
		}
	}
	
	
	function stat_mypages($rec,$type) {
		$o=new tw_mypages_stats;
		$search=array(
			'year'=>date('Y',strtotime($rec->date)),
			'month'=>date('m',strtotime($rec->date)),
		);
		
		$search_old=array(
			'year'=>date('Y',strtotime($rec->get_old_value('date'))),
			'month'=>date('m',strtotime($rec->get_old_value('date'))),
		);
		switch ($type) {
			case 'before_insert':
				$o->find_records($search)->first(true)->num++;
				$o->commit();
			break;
			case 'before_delete':
				$o->find_records($search)->first(true)->num--;
				$o->commit();
			break;
			case 'before_update':
				if($search==$search_old) break;
				$o->find_records($search_old)->first(true)->num--;
				$o->commit();
				$o->find_records($search)->first(true)->num++;
				$o->commit();
			break;
		}
	}
}

class tw_mypages_stats extends gs_recordset_handler{
	const superadmin=0;
	function __construct($init_opts=false) { parent::__construct(array(
		'year'=>"fInt 'Год'",
		'month'=>"fInt 'Месяц'",
		'num'=>"fInt 'Количество'",
	),$init_opts);
	}
}




?>
