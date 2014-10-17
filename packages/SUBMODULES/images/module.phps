{%capture assign=DATA%}
	LINKS::Images::lMany2One tw{%$MODULE_NAME%}:Parent 'Картинки' widget=gallery  counter=false
	__LINKS::Stats::lMany2One tw{%$MODULE_NAME%}_stats:Parent 'Статсы'
	__KEYS::Images::ondelete cascade
{%/capture%}
<?php


gs_dict::append(array(
		'LOAD_IMAGES'=>'добавить картинки',
	));


class module{%$MODULE_NAME%}  extends gs_base_module implements gs_module {
	function __construct() { }
	function install() {
		foreach(array('tw{%$MODULE_NAME%}','tw{%$MODULE_NAME%}_files') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	function get_menu() { }
	
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/admin/form/tw{%$MODULE_NAME%}'=>array(
							'gs_base_handler.post:{name:admin_form.html:classname:tw{%$MODULE_NAME%}:form_class:g_forms_table:return:gs_record}',
							'gs_base_handler.redirect',
							),
			'list'=>'gs_base_handler.show',
		),
	);
	$d=self::add_subdir($data,dirname(__file__));
	return $d;
	}
}

class tw{%$MODULE_NAME%} extends tw_images {
	static $parent_id_name='Parent_id';
	function __construct($init_opts=false) {
		parent::__construct( array(
			//'Name'=> "fString 'Название' required=false",
			'Parent'=>"lOne2One {%$PARENT_RECORDSET%} mode=link",
			'File'=>"lOne2One tw{%$MODULE_NAME%}_files 'File' hidden=false widget=include_form",
		),$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Parent','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
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
			'admin'=>array('width'=>50,'height'=>50,'method'=>'use_height','bgcolor'=>array(200,200,200)),
			'small'=>array('width'=>100,'height'=>100,'method'=>'use_fields','bgcolor'=>array(200,200,200)),
			'gallery'=>array('width'=>150,'height'=>150,'method'=>'use_height','bgcolor'=>array(200,200,200)),
		));
	}
}



?>
