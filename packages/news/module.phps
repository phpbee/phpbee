<?php
gs_dict::append(array(
		'LOAD_IMAGES'=>'добавить картинки',
	));

class module{%$MODULE_NAME%} extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array('tw{%$MODULE_NAME%}','tw{%$MODULE_NAME%}_stats') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		return '<a href="/admin/news/">Новости</a>';
	}
	
	static function get_handlers() {
		$data=array(
		'get_post'=>array(
			''=>'gs_base_handler.show:{name:news.html}',
			'archive/*/*'=>array(
					'gs_base_handler.validate_gl:{name:no_record:return:true^e404}',
					'gs_base_handler.show:{name:news.html}',
					'end',
					'e404'=>'gs_base_handler.show404:return:true',
					),
			'show/*/*'=>array(
					'gs_base_handler.validate_gl:{name:show:return:true^e404}',
					'gs_base_handler.show:{name:news_show.html}',
					'end',
					'e404'=>'gs_base_handler.show404:return:true',
					),
			'/admin/news'=>'gs_base_handler.show:{name:adm_news.html:classname:tw{%$MODULE_NAME%}}',
			'/admin/news/delete'=>'admin_handler.deleteform:{classname:tw{%$MODULE_NAME%}}',
			'/admin/news/iframe_gallery'=>'gs_base_handler.many2one:{name:iframe_gallery.html}',
		),
		'handler'=>array(
			''=>'gs_base_handler.show:{name:handler_news.html}',
			'calendar'=>'gs_base_handler.show:{name:handler_calendar.html}',
			'last'=>'gs_base_handler.show',
			'short_list'=>'gs_base_handler.show:{name:news_short_list.html}',
			'/admin/form/tw{%$MODULE_NAME%}'=>array(
					'gs_base_handler.post:{name:admin_form.html:form_class:g_forms_table:classname:tw{%$MODULE_NAME%}}',
					'gs_base_handler.redirect:{href:/admin/news}',
					),
		),
	);
	return self::add_subdir($data,dirname(__file__));
	}

	static function gl($alias,$rec,$url='') {
		if (!is_null($rec) && !empty($rec)) {
            if(!is_object($rec)) {
				$obj=new tw{%$MODULE_NAME%};
				$rec=$obj->get_by_id(intval($rec));
		    }
        } else {
			$alias='no_record';
		}
		
		switch ($alias) {
			case 'show':
				return sprintf('/{%$MODULE%}/show/%s/%d.html',
						date('Y/m',strtotime($rec->date)),
						$rec->get_id());
			break;
            case 'stat':
				return sprintf('/{%$MODULE%}/archive/%d/%d',
						$rec->year,
						$rec->month);
			break;
			case 'no_record':
				list($year,$month)=sscanf($url,'news/archive/%d/%d');
				$rs=new tw_news_stats;
				$rec_num=$rs->find_records(array('year'=>$year,'month'=>$month))->first()->num;
				return intval($rec_num)>0 ? sprintf('/news/archive/%d/%d',$year,$month) : false;
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
		'date'=>"fDatetime дата",
		'subject'=>"fString 'Заголовок' keywords=1",
		'text'=>"fText текст _widget=wysiwyg images_key=Images keywords=1",
		//'keywords'=>"fText Keywords trigger=normalize:text:50 required=false",
		'Images'=>"fFile Images",
		{%foreach from=$SUBMODULES_DATA.LINKS key=K item=L%}
			'{%$K%}'=>"{%$L%}",
		{%/foreach%}
		//'hot'=>"fCheckbox горячая",
		//'hidden'=>"fCheckbox спрятать",
		),$init_opts);
		$this->structure['triggers']['before_delete'][]='stat_news';
		$this->structure['triggers']['before_insert'][]='stat_news';
		$this->structure['triggers']['before_update'][]='stat_news';
		
		$this->structure['triggers']['before_update'][]='keywords';
		$this->structure['triggers']['after_insert'][]='keywords';
		$this->structure['triggers']['before_delete'][]='keywords';
		
	}

	function rand($rec) {
		$rec->text=md5(rand());
	}
	
	function keywords($rec,$type) {
		$url=module{%$MODULE_NAME%}::gl('show',$rec);
		if ($type=='before_update' || $type=='after_insert') {
			$keywords=metatags::get_keywords($rec);
			$description=str_replace("\n"," ",substr(strip_tags($rec->text),0,254));
			$title=strip_tags($rec->subject);
			metatags::save($url,$title,$keywords,$description);
		}elseif ($type=='before_delete') {
			metatags::delete($url);
		}
	}
	
	/*function normalize($rec,$type,$args) {
		$field=array_shift($args);
		$src=array_shift($args);
		$len=intval(array_shift($args));
		$len=$len ? $len : 20;
		if ($rec->is_modified($src)) {
			$rec->$field=strlen($rec->$src);
			
			$text=strip_tags($rec->$src);
			$text=iconv('UTF-8','Windows-1251',$text);
			$lib=cfg('lib_dir');
			$norm=VPA_normalizator::getInstance($lib.'dicts/');
			$words=$norm->parse_text(strtolower($text));
			$w=$norm->freq_analyze_first($words);
			$res=$norm->freq_analyze_second($w);
			$res=$norm->freq_analyze_third($res);
			arsort($res,SORT_NUMERIC);
			$res=array_slice($res,0,$len);
			$keys=array();
			$l=0;
			foreach ($res as $w => $f) {
				if ($f>1) {
					$uw=iconv('Windows-1251','UTF-8',$w);
					$l+=strlen($uw)+2;
					if ($l<1000) $keys[]=$uw;
				}
			}
			$rec->$field=implode(', ',$keys);
		}
	}*/
	
	function stat_news($rec,$type) {
		$o=new tw{%$MODULE_NAME%}_stats;
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

class tw{%$MODULE_NAME%}_stats extends gs_recordset_handler{
	const superadmin=0;
	function __construct($init_opts=false) { parent::__construct(array(
		'year'=>"fInt 'Год'",
		'month'=>"fInt 'Месяц'",
		'num'=>"fInt 'Количество'",
	),$init_opts);
	}
}




?>
