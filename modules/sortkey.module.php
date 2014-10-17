<?php
class module_sortkey extends gs_base_module implements gs_module {
    function __construct() {
    }
    function install() {
    }
    static function get_handlers() {
        $data=array(
		'handler'=>array(
			'/sortkey'=>'sortkey_handler.sort',
		),
              );
        return self::add_subdir($data,dirname(__file__));
    }

}

class sortkey_handler extends gs_base_handler{

	function sort($data) {
		$d=$this->data;
		if (!isset($d['sortkey_id']) ) return;
		if (isset($d['handler_params']['sortkey_id']) && $d['handler_params']['sortkey_id'] != $d['sortkey_id']) return;
		$rsname=$d['handler_params']['recordset_name'];
		$rs=new $rsname;
		$rec1=record_by_id($d['dir_rec_id'],$rsname);


		switch($d['dir']) {
			case 'before':
				$case='<'; $order='sortkey desc';
				break;
			case 'after':
				$case='>'; $order='sortkey';
				break;
			default:
				return;
		}

		$rec2=$rs->find_records(array(
			'sortkey'=>array('field'=>'sortkey','case'=>$case,'value'=>$rec1->sortkey),
		))->orderby($order)->limit(1)->first();
    
        if (!$rec2) $rec2=$rs->new_record(array());


		$sortkey=abs($rec2->sortkey + $rec1->sortkey)/2;
		$rec=record_by_id($d['rec_id'],$rsname);
		$rec->sortkey=$sortkey;
		$rec->commit();
		return($this->flush(json_encode(array('sort'=>'ok'))));

	}

}

