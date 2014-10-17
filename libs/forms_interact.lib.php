<?php

class form_interact {
	static $interact_regexps=array(
		'|#([\w\[\]]+)|s'=>'$this->field(\'\1\')',
		'|\.hide|s'=>'->hide',
		'|\.display_if|s'=>'->display_if',
		'|\.show_if|s'=>'->display_if',
		'|\.hide_if|s'=>'->hide_if',
		'|\.link_values|s'=>'->link_values',
		'|\.copy_value|s'=>'->copy_value',
		'|\.select_values|s'=>'->select_values',
		);
	var $actions=array();
	function __construct($form,$interact,$str) {
		$this->interactname=$interact;
		$this->form=$form;
		$this->code=$str;
		$form->validate();
		$this->data=$form->clean();
		$this->value=$form->clean($interact);
	}
	function i($ret) {
		$this->old_ret=$ret;
		if (!$this->code) return $this->actions;
		ob_start();
		$ret_status=eval($this->code);
		$ret_msg=ob_get_clean();
		if ($ret_status===FALSE) {
			$this->actions[]=array('field'=>$this->fieldname,'action'=>'error','message'=>strip_tags($ret_msg).' '.$this->code,'debug'=>DEBUG);
		}
		return $this->actions;
	}

	function field($name) {
		$this->fieldname=$name;
		return $this;
	}

	function hide($condition) {
		$this->actions[]=array('field'=>$this->fieldname,'action'=> 'hide');
	}
	function display_if($condition, $eq='==') {
		foreach ($this->old_ret as $or) {
			if (is_object($or)) $or=get_object_vars($or);
			if ($or['field']==$this->interactname && $or['action']=='hide') {
				$this->actions[]=array('field'=>$this->fieldname,'action'=>'hide');
				return;
			}
		}
		$str='$res= ($condition '.$eq.' $this->value );';
		eval($str);
		$this->actions[]=array('field'=>$this->fieldname,'action'=>$res ? 'show' : 'hide');
	}
	function hide_if($condition) {
		$this->display_if($condition,'!=');
	}
	function link_values($condition) {
		if(is_array($condition)) {
			$data=$condition;
		} else {
			$data['']='';
			list($rsname,$linkname)=explode('.',$condition);
			$rec=record_by_id($this->value,$rsname);
			foreach ($rec->$linkname as $r) {
				$data[$r->get_id()]=trim($r);
			}
		} 
		$fieldname=str_replace('[]','',$this->fieldname);
		$this->form->set_variants($fieldname,$data);
		$this->form->_prepare_inputs();
		$html=($this->form->get_input($fieldname));

		$this->actions[]=array('field'=>$this->fieldname,'action'=>'replace_element','html'=>$html);
	}
	function link_values_options($condition) {
		if(is_array($condition)) {
			$data=$condition;
		} else {
			$data['']='';
			list($rsname,$linkname)=explode('.',$condition);
			$rec=record_by_id($this->value,$rsname);
			foreach ($rec->$linkname as $r) {
				$data[$r->get_id()]=trim($r);
			}
		} 
		$fieldname=str_replace('[]','',$this->fieldname);
		$this->form->set_variants($fieldname,$data);
		$this->form->_prepare_inputs();
		$html=($this->form->get_input($fieldname));

		$this->actions[]=array('field'=>$this->fieldname,'action'=>'replace_options','html'=>$html);
	}
	function copy_value($condition) {
		$this->actions[]=array('field'=>$this->fieldname,'action'=>'set_value','value'=>$this->value);
	}

	function select_values($condition) {
		$data=call_user_func($condition,$this->fieldname,$this->value);
		$this->link_values($data);
	}
}

