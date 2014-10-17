<?php

class wz_modules extends gs_recordset_short {
	public $gs_connector_id='wizard';
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString name",
		'title'=> "fString 'название'",
		'recordsets'=>"lMany2One wz_recordsets:Module",
		'urls'=>"lMany2One wz_urls:Module",
		'forms'=>"lMany2One wz_forms:Module",
		),$init_opts);
	}
}

class wz_recordsets extends gs_recordset_short {

	public $gs_connector_id='wizard';
	public $no_urlkey=1;
	public $sortkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString name",
		'title'=> "fString 'название'",
		'table_name'=> "fString table_name required=false",
		'database'=> "fSelect 'database' widget=select",
		'extends'=>"fString 'extends' required=false",
		'Module'=>'lOne2One wz_modules Module widget=parent_list',
		'Fields'=>"lMany2One wz_recordset_fields:Recordset",
		'Resizes'=>"lMany2One wz_recordset_resizes:Recordset",
		'Links'=>"lMany2One wz_recordset_links:Recordset",
		'Triggers'=>"lMany2One wz_recordset_triggers:Recordset",
		'Submodules'=>"lMany2One wz_recordset_submodules:Recordset",
		'showadmin'=>"fCheckbox 'show in admin'",
		'no_urlkey'=>"fCheckbox 'No URL key' default=1",
		'no_ctime'=>"fCheckbox 'No ctime'",
		'use_sortkey'=>"fCheckbox 'sortkey' default=0",
		'install'=>"fCheckbox 'make install table' default=1",
		'orderby'=>"fString 'order by' required=false default='id'",
		'id_field_name'=>"fString 'id_field_name' required=false default='id'",
		),$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Module','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
		$this->structure['triggers']['before_delete'][]='before_delete';
	}
	function before_delete($rec,$type) {
		$rs=new wz_recordset_links;
		$rs->find_records(array('classname'=>$rec->name))->delete();
		$rs->commit();
	}
	function gs_data_widget_select($rec,$field) {
		switch($field) {
			case 'database':
				$types=array_keys(cfg('gs_connectors'));
				$types=array_combine($types,$types);
				array_unshift($types,'');
				return $types;
				break;
		}
		return array();
	}
}

class wz_recordset_resizes extends gs_recordset_short {

	public $gs_connector_id='wizard';
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString name",
		'width'=> "fInt 'Ширина'",
		'height'=> "fInt 'Высота'",
		'method'=>"fSelect 'Метод' values='use_width,use_height,use_box,use_space,use_fields,use_crop,copy'",
		'bgcolor'=> "fString 'Цвет фона R,G,B' default='0,0,0'",
		'modifier'=>"fSelect 'Модификатор' values=',check_and_rotate_left,check_and_rotate_right,watermark' required=false",
		'Recordset'=>'lOne2One wz_recordsets',
		),$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Recordset','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
	}
}

class wz_recordset_fields extends gs_recordset_short {

	public $gs_connector_id='wizard';
	public $sortkey=1;
	public $no_urlkey=1;
    public $orderby = "sortkey";
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString name",
		'verbose_name'=> "fString verbose_name required=false",
		'helper_text'=> "fString helper_text required=false",
		'type'=>"fSelect type values='fString,fText,fInt' widget=select",
		'multilang'=>"fCheckbox multilang",
		'options'=>"fString options required=false",
		'extra_options'=>"fString extra_options required=false",
		'widget'=>"fSelect widget required=false widget=select",
		'default_value'=>"fString default required=false",
		'make_index'=>"fCheckbox index",
		'required'=>"fCheckbox verbose_name=required",
		'is_unique'=>"fCheckbox verbose_name=unique",
		'Recordset'=>'lOne2One wz_recordsets',
		),$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Recordset','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);



	}
	function gs_data_widget_select($rec,$field) {
		switch($field) {
			case 'type':
				$types=get_class_methods('field_interface');
				$types=array_combine($types,$types);
				$types=array_filter($types,create_function('$a','return  preg_match("|^f[A-Z]|",$a);'));
				return $types;
				break;
			case 'widget':
				$widgets=class_members('gs_widget');
				$widgets=str_replace('gs_widget_','',$widgets);
				array_unshift($widgets,'');
				$widgets=array_combine($widgets,$widgets);
				return $widgets;
				break;
		}
		return array();
	}

	function text($args,$rec) {

		$fields=$this->structure['htmlforms'];
		$ret="";
		foreach ($fields as $f=>$v) {
			if($rec->$f!='' && $f!='Recordset_id') $ret.=sprintf('%s="%s" ',$f,$rec->$f);
		}
		return $ret;
	}
}
class wz_recordset_links extends gs_recordset_short {

	public $gs_connector_id='wizard';
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=> "fString name",
		'verbose_name'=> "fString verbose_name required=false",
		'type'=>"fSelect type values='lOne2One,lMany2One,lMany2Many' widget=radio",
		'classname'=>"fSelect classname widget=select_enter",
		'linkname'=>"fString linkname required=false",
		'options'=>"fString options required=false",
		'extra_options'=>"fString extra_options required=false",
		'widget'=>"fSelect widget required=false widget=select",
		'required'=>"fCheckbox verbose_name=required",
		'fkey_on_delete'=>"fSelect on_delete values='NONE,RESTRICT,CASCADE,SET_NULL' widget=radio ",
		'fkey_on_update'=>"fSelect on_update values='NONE,RESTRICT,CASCADE,SET_NULL' widget=radio ",
		'fkey_name'=>"fString required=false",
		'Recordset'=>'lOne2One wz_recordsets',
		),$init_opts);
		$this->structure['triggers']['before_insert'][]='before_insert';
		$this->structure['fkeys']=array(
			array('link'=>'Recordset','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);




	}
	function before_insert($rec,$type) {
		if (!is_subclass_of($rec->classname,'wz_link')) return;

		$wzl=new $rec->classname;
		$type=$rec->type;

		$wzl->$type($rec);

	}
	function gs_data_widget_select($rec,$field) {
		switch($field) {
			case 'classname':
				$rs=new wz_recordsets();
				$rsets=$rs->find_records(array())->recordset_as_string_array();
				$rsets=array_combine($rsets,$rsets);
				$rsets=array_merge($rsets,class_members('gs_recordset_short'));

				$links=class_members('wz_link');

				$rsets=array(
					'magic'=>$links,
					'recordsets'=>$rsets,
					);
				return $rsets;
				break;
			case 'widget':
				$widgets=str_replace('gs_widget_','',class_members('gs_widget'));
				$widgets=array_combine($widgets,$widgets);
				$widgets=array_merge(array(''=>''),$widgets);
				return $widgets;
				break;
		}
		return array();
	}

	function text($args,$rec) {

		$fields=$this->structure['htmlforms'];
		$ret="";
		foreach ($fields as $f=>$v) {
			if($rec->$f!='' && $f!='Recordset_id') $ret.=sprintf('%s="%s" ',$f,$rec->$f);
		}
		return $ret;
	}
}
class wz_recordset_triggers extends gs_recordset_short {

	public $gs_connector_id='wizard';
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'on_insert'=>"fSelect widget='radio' on_insert options=',before,after' required=false",
		'on_update'=>"fSelect widget='radio' on_update options=',before,after' required=false",
		'on_delete'=>"fSelect widget='radio' on_delete options=',before,after' required=false",
		'code'=>"fText code",
		'Recordset'=>'lOne2One wz_recordsets',
		),$init_opts);

	}
}
class wz_recordset_submodules extends gs_recordset_short {

	public $gs_connector_id='wizard';
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=>"fSelect name widget=select",
		'Recordset'=>'lOne2One wz_recordsets',
		),$init_opts);

	}
	function gs_data_widget_select($rec,$field) {
		$ret=array_map(basename,glob(cfg('lib_distsubmodules_dir').'*'));
		return $ret;
	}
}
class wz_urls extends gs_recordset_short {

	public $gs_connector_id='wizard';
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'gspgid_value'=> "fString gspgid required=false unique=true",
		'type'=>'fSelect type values="get,handler,post,wrapper,template"',
		'Module'=>'lOne2One wz_modules Module widget="parent_list"',
		'Handlers'=>"lMany2One wz_handlers:Url",
		),$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Module','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
	}
        function check_unique($field,$value,$params,$record=null,$data=null) {
		$recs=$this->find_records(array($field=>$value,'type'=>$data['type'],'Module_id'=>$data['Module_id']));
		if ($recs->count()==0) return true;
		return $recs->first()->get_id()===$params['rec_id'];
        }

}
class wz_handlers extends gs_recordset_short {

	public $gs_connector_id='wizard';
	public $no_urlkey=1;
	public $sortkey=1;

	function __construct($init_opts=false) { parent::__construct(array(
		'cnt'=> "fInt",
		'handler_keyname'=> "fString key required=false",
        'handler_template' => "fString template required=false widget=SelectHandlerTemplate",
		'handler_value'=>"fString value",
		'Url'=>'lOne2One wz_urls',
		),$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Url','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
	}
}
class wz_forms extends gs_recordset_short {

	public $gs_connector_id='wizard';
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'classname'=> "fString classname required=true unique=true",
		'extends'=>'fSelect extends ',
		'Module'=>'lOne2One wz_modules',
		'Fields'=>"lMany2One wz_form_fields:Form",
		),$init_opts);
	}
        function check_unique($field,$value,$params,$record=null) {
		$recs=$this->find_records(array($field=>$value));
		if ($recs->count()==0) return true;
		return $recs->first()->get_id()===$params['rec_id'];
        }

}
class wz_form_fields extends gs_recordset_short {

	public $gs_connector_id='wizard';
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'name'=>'fString name min_length=3 max_length=55',
		'verbose_name'=>'fString verbose_name required=false',
		'cssclass'=>'fString cssclass required=false',
		'widget'=>"fSelect widget ",
		'default_value'=>"fString default required=false",
		'readonly_field'=>"fCheckbox readonly",
		'options'=>"fString options required=false",
		'extra_options'=>"fString extra_options required=false",
		'cnt'=> "fInt cnt required=false",
		'Form'=>'lOne2One wz_forms',
		'Validators'=>"lMany2One wz_form_fields_validators:Field",
		'interact'=>'fText "interactive" required=false',
		),$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Form','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
	}
}
class wz_form_fields_validators extends gs_recordset_short {

	public $gs_connector_id='wizard';
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(array(
		'class'=>'fSelect class',
		'options'=>'fString options required=false',
		'Field'=>'lOne2One wz_form_fields',
		),$init_opts);
		$this->structure['fkeys']=array(
			array('link'=>'Field','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
		);
	}
}

?>
