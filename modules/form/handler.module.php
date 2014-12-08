<?php

class form_handler extends gs_handler  {

	protected $form;
	protected $form_html='';
	protected $fields=array();
	protected $tpl;
	protected $record=null;
	protected $values=array();
	protected $default_values=array();


	public function __construct($data=null,$params=null) {
		parent::__construct($data,$params);

		$this->gs_base_handler=new gs_base_handler($this->data,$this->params);
		$this->form=new g_forms_html();


		$this->tpl=gs_tpl::get_instance();
		$this->set_module_tpldir($this->tpl);

		$this->tpl->assign('form',$this->form);
		$this->tpl->assign('params',$this->params);

		if(isset($this->params['record'])) {
			$this->record=$this->params['record'];
			$this->form->record=$this->form->rec=$this->record;
		}


		$this->load_template('template');
		if (!isset($this->params['success']) && isset($this->params['template_success'])) $this->params['success']=$this->params['template_success'];
		$this->load_template('success');

		if (isset($this->params['values'])) $this->values=string_to_params($this->params['values']);
		if (isset($this->params['default_values'])) $this->default_values=string_to_params($this->params['default_values']);

	}

	public function form($d) {
		$this->add_fields();

		$this->form->set_values($this->default_values);
		$this->form->set_values($this->data);

		$this->set_handler_values();

		$this->set_placeholders();
		$this->set_validators();
		$this->set_widgets();


		$clean=$this->process_form();

		if (isset($this->params['event'])) gs_eventer::send($this->params['event'],$clean);
		if (isset($this->params['message'])) gs_session::add_message($this->params['message']);

		return $clean;
	}

	public function prepare_record_form($d) {
		$this->record=$this->params['record'];
		$this->form->record=$this->form->rec=$this->record;
		$this->add_fields();
		$this->form->set_values($this->default_values);

		$rec_fields=array_map(function($a){return end(explode(':',$a));},$this->fields);


		$this->form->set_values($this->record->get_values($rec_fields));

		$vrecord=clone($this->record);
		foreach($rec_fields as $f) {
			$this->form->set_value($f,$vrecord->$f);
		}


		$fields2=explode_data(array_flip($this->fields));
		if (isset($fields2['Lang']) && is_array($fields2['Lang'])) foreach($fields2['Lang'] as $l=>$lfields) {
			$vrecord=clone($this->record);
			$v_lang=$vrecord->Lang[$l];
			foreach ($lfields as $f=>$j) {
				$lfname="Lang:$l:$f";
				$lfvalue=$v_lang->$f;
				$this->form->set_value($lfname,$lfvalue);
			}
		}	

		$this->form->set_values($this->data);

		$this->set_handler_values();

		$this->set_placeholders();
		$this->set_validators();
		$this->set_widgets();

		return $this->record;

	}


	public function record($d) {
		$this->prepare_record_form($d);


		$clean=$this->process_form();
		if (!is_array($clean)) return $clean;



		if (isset($this->params['message'])) {
			gs_session::add_message($this->params['message']);
		} else if($this->record->get_id()) {
			gs_session::add_message($this->record->get_recordset_name().'_RECORD_UPDATED');
		} else {
			gs_session::add_message($this->record->get_recordset_name().'_RECORD_INSERTED');
		}



		$rec_fields=$this->record->get_recordset()->structure['htmlforms'];
		foreach($rec_fields as $f) {
			if ($this->record->$f instanceof gs_rs_links && array_key_exists($f,$clean)) {
				foreach ($this->record->$f->array_keys() as $k) $this->record->$f->unlink($k);
				$this->record->$f->commit();
			}
		}
		$this->record->fill_values($clean);
		$this->record->fill_values($this->values);
		$this->record->commit();


		if (isset($this->params['event'])) gs_eventer::send($this->params['event'],$this->params);



		if ($this->params['success']) return $this->fetch('success');

		return $this->record;

	}
	function process_form(){
		$this->form_html=$this->fetch('template'); //needed to ensure form modifications from template, i.e. $form->add_validator
		if ($this->data['gspgtype']==GS_DATA_GET) return $this->form_html;

		$this->form->set_values($this->data);

		$validate=$this->form->validate();
		if ($validate!==TRUE && $validate['STATUS']!==TRUE) {
			$this->form_html=$this->fetch('template');
			return $this->form_html;
		}

		$clean=$this->form->clean();
		$clean=explode_data($clean);


		return $clean;

	}




	protected function fetch($name) {
		if (!isset($this->templates[$name])) throw new gs_exception('form_handler.fetch : can not find template file for '.$name);


		return $this->tpl->fetch($this->templates[$name]);
	}

	protected function set_handler_values() {
		$values=array();
		foreach($this->fields as $fname) {
			if (isset($this->params[$fname])) $values[$fname]=$this->params[$fname];
		}
		$this->form->set_values($values);

	}
	protected function add_fields() {
		$this->fields=$fields=string_to_params(str_replace(',',' ',$this->params['fields']));
		$types=isset($this->params['type']) ? string_to_params($this->params['type']) : array();

		$rec_fields=array();
		if ($this->record) $rec_fields=$this->record->get_recordset()->structure['htmlforms'];

		foreach ($fields as $field=>$options) {
			if (is_numeric($field)) { 
				$field=$options;
				$options=array();
			}
			if (isset($types[$field]) && is_array($options)) $options['type']=$types[$field];

			if (isset($rec_fields[$field])) {
				$fa=$rec_fields[$field];
				$fa=$this->apply_data_widget($field,$fa);
				$this->form->add_field($field,$fa);
			} else {
				$this->form->add_field($field,$options);
			}
		}

	}

	protected function apply_data_widget($field,$fa) {
		if (isset($fa['widget'])) {
			$dclass='gs_data_widget_'.$fa['widget'];
			if (class_exists($dclass)) {
				$d=new $dclass();
				$variants=$d->gd($this->record,$field,array($field=>$fa),$this->params,$this->data);
				if (isset($variants[$field]['variants'])) $fa['variants']=$variants[$field]['variants'];
			}
		}
		return $fa;
	}

	protected function set_validators() {
		$validators=isset($this->params['validators']) ? string_to_params(str_replace(',',' ',$this->params['validators'])) : array();
		foreach ($validators as $k=>$v) {
			$this->form->replace_validator($k,$v); 
		}
	}

	protected function set_widgets() {
		$widgets=isset($this->params['widgets']) ? string_to_params(str_replace(',',' ',$this->params['widgets'])) : array();
		foreach ($widgets as $field=>$value) {
			$this->form->set_option($field,'widget',$value);
		}
	}

	protected function set_placeholders() {
		$pholders=isset($this->params['placeholder']) ? string_to_params(str_replace(',',' ',$this->params['placeholder'])) : array();

		foreach ($pholders as $field=>$value) {
			$this->form->set_option($field,'placeholder',$value);
		}
	}


	protected function load_template($name) {
		if (!isset($this->params[$name])) return;

		$fname=pathinfo($this->params[$name],PATHINFO_BASENAME);
		$this->templates[$name]=$fname;
	}

}
