<?php
class module_registry extends gs_base_module implements gs_module {
    function __construct() {
    }
    function install() {
        foreach(array(
                    'rs_registry',
                    'rs_registry_objects',
                ) as $r) {
            $this->$r=new $r;
            $this->$r->install();
        }
    }
    static function get_handlers() {
        $data=array(
              );
        return self::add_subdir($data,dirname(__file__));
    }

}
interface interface_registry {
	function __construct($type,$key);
	static function &i($rec=null);
	function merge($reg=null);
	function __get($name);
	function __set($name,$value);
}

class registry implements interface_registry {
	private $rs_reg;
	function __construct($type,$key) {
		$this->rs_reg=rs('rs_registry')->find_records(array('type'=>$type,'key'=>$key))->first(true);
	}
	static function &i($rec=null) {
		static $arr=array();
		if ($rec && is_a($rec,'gs_record')) {
			$type=$rec->get_recordset_name();
			$key=$rec->get_id();
		} else {
			$type='session';
			$key=gs_session::get_id();
		}

		if (!isset($arr[$type]) || !isset($arr[$type][$key])) {
			$arr[$type][$key]=new registry($type,$key);
		}
		return $arr[$type][$key];
	}
	function merge($reg=null) {
	}
	function __get($name) {
		$obj=$this->rs_reg->get_recordset()->find(array('name'=>$name),'Objects')->first();
		return ($obj) ? unserialize($obj->object) : new gs_null(GS_NULL_XML);
	}
	function __set($name,$value) {
		$obj=$this->rs_reg->get_recordset()->find(array('name'=>$name),'Objects')->first(true);
		$obj->object=serialize($value);
		$this->rs_reg->Objects[]=$obj;
		$this->rs_reg->commit();
		return $this;
	}
	function rs_reg() {
		return $this->rs_reg;
	}
	static function get($name,$rec=null) {
		$r=self::i($rec);
		return $r->$name;
	}
	static function set($name,$value,$rec=null) {
		$r=self::i($rec);
		$r->$name=$value;
	}

	function import($old_r) {
		foreach($old_r->rs_reg()->Objects as $o) {
			$this->__set($o->name,unserialize($o->object));
		}
	}
	function clean() {
		foreach ($this->rs_reg->Objects as $o) {
			$o->delete();
		}
		$this->rs_reg->commit();
	}
}


class rs_registry extends gs_recordset_short {
    public $no_urlkey=true;
    function __construct($init_opts=false) {
        parent::__construct(array(
		'type'=>'fSelect    options="session,record"  required=true index=true   ',
		'name'=>'fString    options="32"  required=false index=true   ',
		'key'=>'fString    options="32"  required=true index=true   ',
		'Objects'=>'lMany2One rs_registry_objects:Registry    required=false     ',

	    ),$init_opts);
    }
}


class rs_registry_objects extends gs_recordset_short {
    public $no_urlkey=true;
    function __construct($init_opts=false) {
        parent::__construct(array(
		'module'=>'fString     required=false index=true   ',
		'name'=>'fString     required=true index=true   ',
		'object'=>'fText     required=false    ',
		'Registry'=>'lOne2One rs_registry    required=true     ',
	    ),$init_opts);

        $this->structure['fkeys']=array(
                                      array('link'=>'Registry','on_delete'=>'CASCADE','on_update'=>'CASCADE'),

                                  );

    }


}

class handler_registry extends gs_base_handler {
	function array_push($data) {
		$rec=$this->hpar($data);
		$name=$this->params['name'];
		$value=$this->data['gspgid_va'][0];
		$r=registry::i($rec);
		$a=$r->$name;
		if (!$a) $a=array();
		$a[$value]=$value;
		$r->$name=$a;
		return $rec;
	}
	function array_pop($data) {
		$rec=$this->hpar($data);
		$name=$this->params['name'];
		$value=$this->data['gspgid_va'][0];
		$r=registry::i($rec);
		$a=$r->$name;
		if ($a && isset($a[$value])) unset($a[$value]);
		$r->$name=$a;
		return $rec;
	}

	function after_login($rec) {
		$old_r=registry::i();
		$r=registry::i($rec);
		$r->import($old_r);
		return $rec;
	}
	function before_logout($rec) {
		$r=registry::i();
		$r->clean();
		return $rec;
	}

}






?>
