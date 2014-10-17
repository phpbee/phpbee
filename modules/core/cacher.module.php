<?php
class module_cacher extends gs_base_module implements gs_module {
    function __construct() {
    }
    function install() {
        foreach(array(
                    'cacher_cache',
                    'cacher_depends',
                ) as $r) {
            $this->$r=new $r;
            $this->$r->install();
        }
        gs_eventer::subscribe('record_after_insert', 'cacher_listener::cache_clean');
        gs_eventer::subscribe('record_after_update', 'cacher_listener::cache_clean');
        gs_eventer::subscribe('record_before_delete','cacher_listener::cache_clean');
    }
    static function get_handlers() {
        $data=array(
              );
        return self::add_subdir($data,dirname(__file__));
    }

}
class cacher_listener {
    static function __callStatic($name,$arg) {
        if (strpos($name,'cache_depends_')===0) {
            self::cache_depends(end(explode('_',$name)),$arg);
        }
    }
    function cache_depends($uid,$data) {
        list($rset,$event)=$data;
        $rset_name=$rset->get_recordset_name();
        if (in_array($rset_name, array('cacher_depends','cacher_cache'))) return;
        $rs=new cacher_depends();
        $r=$rs->find_records(array('uid'=>$uid,'recordset'=>$rset_name))->first(true);
        $r->options=serialize($rset->query_options);
        $r->commit();
    }
    function cache_clean($rec) {
        $rset_name=$rec->get_recordset_name();
        if (in_array($rset_name, array('cacher_depends','cacher_cache'))) return;
        $dep=new cacher_depends;
        $dep->find_records(array('recordset'=>$rset_name));


        $c=new cacher_cache();
        foreach ($dep as $d) {
            $options=array('uid'=>$d->uid,'expire'=>array('field'=>'expire','case'=>'<','value'=>date(DATE_ATOM)));
            $c->find_records($options);
            $c->delete();
            $c->commit();
        }
    }
}

class cacher_cache extends gs_recordset_short {
	//var $gs_connector_id='handlers_cache';
	public $id_field_name='uid';
	function __construct($init_opts=false) { parent::__construct(array(
		'uid'=>"fString",
        'expire'=>'fTimestamp',
		'address'=>"fString",
		'text'=>"fText",
		),$init_opts);
		$this->structure['indexes']['uid']=array('type'=>'unique');
	}
}
class cacher_depends extends gs_recordset_short {
	//var $gs_connector_id='handlers_cache';
	function __construct($init_opts=false) { parent::__construct(array(
		'uid'=>"fString",
		'address'=>"fString",
		'recordset'=>"fString",
		'options'=>"fText",
		),$init_opts);
		$this->structure['indexes']['uid']=array('type'=>'key');
		$this->structure['indexes']['recordset']=array('type'=>'key');
	}
}

