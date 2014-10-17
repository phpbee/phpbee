<?php

class gs_widget_ParentListWithInsert extends gs_widget_parent_list {
	function __construct($fieldname,$data,$params=array(),$form=NULL) {
		parent::__construct($fieldname,$data,$params,$form);
		//md($this->params,1);
	}
	function option_string($fieldname) {
		$url="/widgets/ParentListWithInsert/action/add/".$this->params['options']['recordset'];
		return sprintf("<select ParentListWithInsertURL=\"$url\"  class=\"lOne2One fInteract\" name=\"%s\">\n", $fieldname);
	}
}

class gs_data_widget_ParentListWithInsert extends gs_data_widget_parent_list {
}


class gs_widget_ParentListWithInsert_module extends gs_base_module implements gs_module {
	function __construct() {}
	function install() {}
	function get_menu() {}
	static function get_handlers() {
		$data=array(
		'post'=>array(	
			'/widgets/ParentListWithInsert/action/add'=>array(
				'gs_widget_ParentListWithInsert_handler.add',
			),
		),
		);
		return self::add_subdir($data,dirname(__file__));
	}
}
class gs_widget_ParentListWithInsert_handler extends gs_handler {
	function add() {
		$rs=new $this->data['gspgid_va'][0];
		$fieldname=$rs->get_name_field();
		$options=array();
		if (isset($this->data['interact_request']) && is_array($this->data['interact_request'])) {

			foreach ($this->data['interact_request'] as $k=>$v) {
				if (isset($rs->structure['fields'][$k])) {
					$options[$k]=$v;
				}
			}
		}
		$options[$fieldname]=$this->data['value'];
		$rec=$rs->find_records($options)->first(true);
		$rec->commit();
		echo json_encode(array('id'=>$rec->get_id(),'value'=>trim($rec)));
	}
}
