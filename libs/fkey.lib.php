<?php
class gs_fkey {
	

	function get_instance() {
		static $instance;
		if (!isset($instance)) $instance = new gs_fkey();
		/*
		if ($instance->key_array===false) {
			$init=new gs_init('user');
			$init->init(LOAD_CORE | LOAD_STORAGE);
			$init->load_modules();
			$init->update_fkeys();
		}
		*/
		return $instance;
	}

	static function reset() {
		$fk=gs_fkey::get_instance();
		$fk->reset_fkey();
	}
	private function reset_fkey() {
		$this->key_array=array();
		$this->save();
	}

	function __construct() {
		$this->key_array= ($n=gs_cacher::load('gs_fkey_array','gs_recordset')) ? $n : false;
		if ($this->key_array===false) $this->_update_fkeys();
	}
	function save() {
		//md($this->key_array);
		gs_cacher::save($this->key_array,'gs_recordset','gs_fkey_array');
	}

	private function _update_fkeys() {
		$this->reset_fkey();
		//md('update_fkeys',1);
		$classes=get_declared_classes();
		foreach($classes as $c) {
			if($c!=='gs_recordset_handler' && is_subclass_of($c,'gs_recordset') &&(is_subclass_of($c,'gs_recordset_short') || property_exists($c,'table_name')) ) {
				$testClass     = new ReflectionClass($c);
				if (!$testClass->isAbstract()) {
					$obj=new $c;
					if (isset($obj->structure['fkeys']) && is_array($obj->structure['fkeys'])) {
						$this->update_hash($obj);
					}
					if (isset($obj->structure['recordsets'])) foreach($obj->structure['recordsets'] as $structure) {
						if (isset($structure['rs1_name']) && isset($structure['rs2_name']))  {
							$rs=new gs_rs_links($structure['rs1_name'],$structure['rs2_name'],$structure['recordset']);
							if (isset($rs->structure['fkeys'])) $this->update_hash($rs);
						}
					}
				}
			}
		}
		$this->save();
	}

	public static function update_fkeys() {
		$fk=gs_fkey::get_instance();
		$fk->_update_fkeys();
	}

	public static function register_key($rs) {
		$fk=gs_fkey::get_instance();
		$fk->update_hash($rs);

		}

	private  function update_hash($rsa) {
		$keys=$rsa->structure['fkeys'];
		if (is_array($keys)) foreach ($keys as $k) {
			$link=explode('.',$k['link']);
			if(isset($link[1])) {
				$recordset_name=$link[0];
				$o= new $link[0];
				$rs_name=$o->table_name;
				$link_name=$link[1];


				$rs=new $recordset_name;
				$linked_rs_name=$rs->structure['recordsets'][$link_name]['recordset'];

				$newrec=$rs->new_record();
				$linked_rs=$newrec->init_linked_recordset($link_name);

				$k['local_field_name']=$linked_rs->foreign_field_name;
				$k['foreign_field_name']=$linked_rs->local_field_name;
				$k['index_field_name']=$linked_rs->index_field_name;

				$this->key_array[$rs_name][$linked_rs_name][]=$k;

			} else {
				$recordset_name=get_class($rsa);
				$rs_name=$rsa->table_name;
				$link_name=$k['link'];

                if (!$link_name) return;

				$rs=$rsa;
				$linked_rs_name=$rs->structure['recordsets'][$link_name]['recordset'];


				$newrec=$rs->new_record();
				$linked_rs=$newrec->init_linked_recordset($link_name);


				$k['local_field_name']=$linked_rs->local_field_name;
				$k['foreign_field_name']=$linked_rs->foreign_field_name;
				$k['index_field_name']=$linked_rs->index_field_name;
				if (isset($rs->rs1_name) && isset($rs->rs2_name))  {
					$k['table_name']=$rs->table_name;
					$k['rs1_name']=$rs->rs1_name;
					$k['rs2_name']=$rs->rs2_name;
				}

				$this->key_array[$linked_rs_name][$rs_name][]=$k;
			}

		}
	}
	private function process_event($ev_name,$record) {
		$rs_name=$record->get_recordset()->table_name;
		//$recordset_name=($record->get_recordset());
		$ev_name=strtolower(str_replace(' ','_',$ev_name));
		if (!isset($this->key_array[$rs_name]) || !is_array($this->key_array[$rs_name])) return true;
		$keys=$this->key_array[$rs_name];
		$r=true;
		foreach($keys as $rs_name=>$k_arr) {
			foreach($k_arr as $k) {

				$this->local_field_name=$k['local_field_name'];
				$this->foreign_field_name=$k['foreign_field_name'];
				$this->oldid=$record->get_old_value($this->foreign_field_name);
				if ($ev_name=='on_update' && $this->oldid==$record->{$this->foreign_field_name} ) continue;
				if (isset($k['rs1_name']) && isset($k['rs2_name'])) {
					$this->rs=new gs_rs_links($k['rs1_name'],$k['rs2_name'],$k['table_name']);
				} else {
					//$this->rs=new $recordset_name;
					$this->rs=new $rs_name;
				}
				$option=strtolower(str_replace(' ','_',$k[$ev_name]));
				$r&=$this->{"action_".$ev_name."_".$option}($record);
			}
		}
		return $r;
	}

	private function action_on_delete_restrict(&$record) {
		if ($this->rs->count_records(array($this->local_field_name=>$this->oldid))>0) throw new gs_dbd_exception("on_delete_restrict:".get_class($this->rs),DBD_DEL_RESTRICT); 
		return true;
	}
	private function action_on_delete_set_null(&$record) {
		return $this->action_on_update_set_null($record);
	}
	private function action_on_delete_cascade(&$record) {
		$this->rs->find_records(array($this->local_field_name=>$this->oldid));
		//md('action_on_delete_cascade',1);
		//die('action_on_delete_cascade');
		$record->append_child($this->rs);
		foreach ($this->rs as $r) {
			$r->delete();
		}
		return true;
	}

	private function action_on_update_restrict(&$record) {
		if ($this->rs->count_records(array($this->local_field_name=>$this->oldid))>0) throw new gs_dbd_exception("on_update_restrict:".get_class($this->rs),DBD_UPD_RESTRICT); 
		return true;
	}
		
	private function action_on_update_set_null(&$record) { 
		$this->rs->find_records(array($this->local_field_name=>$this->oldid));
		$record->append_child($this->rs);
		foreach ($this->rs as $r) {
			$r->{$this->local_field_name}=NULL;
		}
		return true;
	}
	private function action_on_update_cascade(&$record) {
		$this->rs->find_records(array($this->local_field_name=>$this->oldid));
		$record->append_child($this->rs);
		foreach ($this->rs as $r) {
			$r->{$this->local_field_name}=$record->{$this->foreign_field_name};
		}
		return true;
	}

	public static function event($ev_name,$record) {
		$fk=gs_fkey::get_instance();
		return $fk->process_event($ev_name,$record);
	}

}

?>
