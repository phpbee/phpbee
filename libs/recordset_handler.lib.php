<?php

class tw_handlers_cache extends gs_recordset_short {
	var $gs_connector_id='handlers_cache';
	protected $handler_cache_status=2;
	public $id_field_name='md5';
	function __construct($init_opts=false) { parent::__construct(array(
		'md5'=>"fString",
		'gspgid'=>"fString",
		'text'=>"fText",
		),$init_opts);
		$this->structure['indexes']['md5']=array('type'=>'unique');
	}
}

class tw_handlers extends gs_recordset_short {
	protected $handler_cache_status=2;
	function __construct($init_opts=false) { parent::__construct(array(
		'recordset'=>"fString",
		'gspgid'=>"fString",
		'options'=>"fText",
		'md5'=>"fString",
		'rec_id'=>"fInt default=00",
		'parent_name'=>"fString",
		'parent_id'=>"fInt default=00",
		),$init_opts);
		$this->structure['indexes']['recordset']='recordset';
		$this->structure['indexes']['md5']='md5';
	}
}

class gs_recordset_handler extends gs_recordset_short {
	protected $handler_cache_status=2;
	function __construct($s=false,$init_opts=false) {
		parent::__construct($s,$init_opts);
		$this->structure['triggers']['after_insert'][]='_flush_handlers';
		$this->structure['triggers']['before_delete'][]='_flush_handlers';
		$this->structure['triggers']['before_update'][]='_flush_handlers';
		$this->structure['triggers']['after_update'][]='_flush_handlers';

	}
	function _flush_handlers($rec,$type) {
		if (!cfg('use_handler_cache')) return true;
		$recordset=get_class($this);
		$hh=new tw_handlers();

		$o=array('recordset'=>$recordset, 'rec_id'=>0, 'parent_id'=>0);
		$hh->find_records($o,'id,options,gspgid');
		$this->_flush_handlers_rs($rec,$type,$hh);

		$o=array('recordset'=>$recordset, 'rec_id'=>$rec->get_id());
		$hh->find_records($o,'id,options,gspgid');
		$this->_flush_handlers_rs($rec,$type,$hh);

		foreach ($this->structure['recordsets'] as $l) {
			$lname=$l['local_field_name'];
			$o=array('recordset'=>$recordset, 'parent_name'=>$lname,'parent_id'=>$rec->$lname);
			$hh->find_records($o,'id,options,gspgid');
			$this->_flush_handlers_rs($rec,$type,$hh);
		}


	}

	function _flush_handlers_rs($rec,$type,$hh) {
		$recordset=get_class($this);

		if ($hh->first()){
			$rs=new $recordset;
			$cc=new tw_handlers_cache();
			$id=$type=='before_update' ? $rec->get_old_value($rs->id_field_name) : $rec->get_id();
		}
		foreach ($hh as $h) {
			$o=unserialize($h->options);
			if (!$o) continue;
			if(array_search_recursive('limit',$o)=='type') {
				$rs->parent_find_records($o);
				foreach ($rs as $r) $ids[]=$r->get_id();
				if (!in_array($id,$ids)) continue;

			} else {
				$o[$rs->id_field_name]=$id;
				if (!$rs->parent_find_records($o)->first()) continue;
			}
			$cc->find_records(array('md5'=>$h->md5))->first()->delete();
			$cc->commit();
		}
	}
	public function parent_find_records($options=null,$fields=null,$index_field_name=null) {
		return parent::find_records($options,$fields,$index_field_name);
	}
	public function find_records($options=null,$fields=null,$index_field_name=null) {
		$gspgid=cfg('s_gspgid');
		if (!cfg('use_handler_cache') || !$gspgid) return parent::find_records($options,$fields,$index_field_name);

		$o=array(
			'recordset'=>get_class($this),
			'md5'=>md5($gspgid),
			);

		//md($options);

		$h=new tw_handlers();
		$h->find_records($o);
		if (!$h->first()) {
			$f=$h->new_record($o);
			$f->gspgid=$gspgid;
			$f->options=serialize($options);
			if (is_array($options)) foreach ($options as $p_key=>$p) {
				if (!is_array($p)) {
					$p=array('field'=>$p_key,'case'=>'=','value'=>$p);
				}
				if(isset($p['field']) && $p['field']==$this->id_field_name) $f->rec_id=$p['value'];
				if (isset($this->structure['recordsets'])) foreach ($this->structure['recordsets'] as $r) {
					if (isset($p['field']) && $p['field']==$r['local_field_name']) {
						$f->parent_name=$p['field'];
						$f->parent_id=intval($p['value']);
					}
				}
			}
			$h->commit();
		}
		return parent::find_records($options,$fields,$index_field_name);
	}
}

?>
