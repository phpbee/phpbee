{%capture assign=DATA%}
	LINKS::Files::lMany2One tw{%$MODULE_NAME%}:Parent 'Файлы' widget=lMany2One counter=false
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
							'gs_base_handler.post:{name:form.html:classname:tw{%$MODULE_NAME%}:form_class:form_admin:return:gs_record}',
							'gs_base_handler.redirect',
							),
			'list'=>'gs_base_handler.show',
		),
	);
	$d=self::add_subdir($data,dirname(__file__));
	return $d;
	}
}

class tw{%$MODULE_NAME%} extends gs_recordset_handler {
	static $parent_id_name='Parent_id';
	function __construct($init_opts=false) {
		parent::__construct( array(
			//'Name'=> "fString 'Название' required=false",
			'Parent'=>"lOne2One tw_{%$PARENT_MODULE%} mode=link",
			'File'=>"lOne2One tw{%$MODULE_NAME%}_files 'Файл' hidden=false widget=include_form",
		),$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Parent','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
	}
	function record_as_string() {
		if ($this->first()->File->first()->File_filename) {
			return $this->first()->File->first()->File_filename;
		}
		return '-';
	}

}

class tw{%$MODULE_NAME%}_files extends gs_recordset_short {
	var $gs_connector_id='file_public';
	var $config=array();
	var $fields=array(
		'File'=> "fFile 'Файл'",
	);
	function __construct($init_opts=false) {
		parent::__construct($this->fields,$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'tw{%$MODULE_NAME%}.File','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
	}
}



?>
