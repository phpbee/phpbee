<?php
gs_dict::append(array(
	));


class module{%$MODULE_NAME%}  extends gs_base_module implements gs_module {
	function __construct() { }
	function install() {
		foreach(array('tw{%$MODULE_NAME%}') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	function get_menu() { }
	
	static function get_handlers() {
		$data=array(
		'default'=>array(
			'default'=>'gs_base_handler.show404:{name:404.html}',
		),
		'get_post'=>array(
			'/admin/form/tw{%$MODULE_NAME%}'=>'gs_base_handler.postform:{name:form.html:classname:tw{%$MODULE_NAME%}:form_class:form_admin}',
		),
	);
	return self::add_subdir($data,dirname(__file__));
	}
}

class tw{%$MODULE_NAME%} extends gs_recordset_short {
	function __construct($init_opts=false) { parent::__construct(array(
		'fullname'=> "fString 'Имя'",
		'sex'=> "fSelect 'Пол' values=',м,ж' required=false",
		'icq'=> "fString 'ICQ' required=false",
		'Parent'=> "lOne2One tw_users",
		),$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Parent','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
	}
}



?>
