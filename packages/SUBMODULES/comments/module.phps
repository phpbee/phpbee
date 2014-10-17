{%capture assign=DATA%}
	LINKS::Comments::lMany2One tw{%$MODULE_NAME%}:Parent 'Комментарии' widget=window_form counter=false
	__KEYS::Images::ondelete cascade
{%/capture%}
<?php

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
		'handler'=>array(
			'list'=>'gs_base_handler.show',
			'form/add'=>array(
					'gs_base_handler.post:{return:gs_record:name:form.html:classname:tw{%$MODULE_NAME%}:form_class:form_table}',
					'gs_base_handler.redirect',
					),
			'/admin/form/tw{%$MODULE_NAME%}'=>'gs_base_handler.postform:{name:form.html:classname:tw{%$MODULE_NAME%}:form_class:form_admin}',
		),
		'get_post'=>array(
		),
	);
	$d=self::add_subdir($data,dirname(__file__));
	return $d;
	}
}

class tw{%$MODULE_NAME%} extends gs_recordset_handler{
	static $parent_id_name='Parent_id';
	function __construct($init_opts=false) {
		parent::__construct( array(
			'CommentText'=>"fText комментарий",
			'Parent'=>"lOne2One {%$PARENT_RECORDSET%} _mode=link widget=parent_list Parent",
		{%foreach from=$SUBMODULES_DATA.LINKS key=K item=L%}
			'{%$K%}'=>"{%$L%}",
		{%/foreach%}
		),$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Parent','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
	}
}


?>
