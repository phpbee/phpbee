<?php
/**
* Автор: Андрей Пахомов
* module_time_machine - предоставляет возможность сохранения изменений записей во всех таблицах. 
* Для работы модуля необходимо в config.php создать новый коннектор time_machine для базы, в которую модуль будет складывать все изменившиеся и удаленные записи
* Принцип работы: модуль подписывается на изменение и удаление записей во всех таблицах. При вызове подписчика, тот на базе рекордсета из которого удаляется или изменяется запись
* создает новую таблицу в указанной коннектором time_machine базе данных с добавочными полями и помещает туда изменившийся или удаленный рекорд. При изменениях записи в таблицу
* пишутся только изменившиеся поля. 
**/

class module_time_machine extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		$connects=cfg('gs_connectors');
		if(isset($connects['time_machine'])) {
			//gs_eventer::subscribe('record_after_insert', 'time_machine_listener::backup');
			gs_eventer::subscribe('record_before_update', 'time_machine_listener::backup');
			gs_eventer::subscribe('record_before_delete','time_machine_listener::backup');
		}
	}
	static function get_handlers() {
		$data=array(
			);
		return self::add_subdir($data,dirname(__file__));
	}

}

class time_machine_listener {

	function backup($rec,$type) {
		
		if ($rec->recordstate & RECORD_NEW) return;
		
		$tm_rec=$rec;
		$type=str_replace('record_before_','',$type);
		$type=str_replace('record_after_','',$type);
		$rset_name=$tm_rec->get_recordset_name();
		
		$rs=new $rset_name;
		
		$tb_name=$rs->get_db_tablename();
		
		//if (strpos($tb_name,'time_machine')===0) return;
		
		$rs->set_connector_id('time_machine');
		$rs->set_db_tablename('time_machine_'.$tb_name);
		
		$rs['recordsets']=$rs['triggers']=array();
		
		$modified=$tm_rec->get_modified_values();
		
		if ($rs->structure['fields'][$rs->id_field_name]['type']=='serial') {
			$rs->structure['fields'][$rs->id_field_name]['type']='int';
		}
		$rs->structure['fields']['_tm_action']=array('type'=>'varchar','options'=>16,'multilang'=>'');
		$rs->structure['fields']['_tm_time']=array('type'=>'date');
		$rs->structure['fields']['_tm_remote_ip']=array('type'=>'varchar','options'=>16,'multilang'=>'');
		
		//$rs->install();
		if (!$rs->get_connector()->table_exists($rs->get_db_tablename())) {
			$rs->createtable();
		} else {
			$rs->altertable();
		}
		
		switch ($type) {
			case 'update':
				$v=$tm_rec->get_old_values();
				$nv=$tm_rec->get_values();
				if ($v==$nv) return;
				$v[$rs->id_field_name]=$tm_rec->get_id();
			break;
			default:
				$v=$tm_rec->get_values();
			break;
		}
		
		$v['_tm_time']=date('Y-m-d H:i:s',time());
		$v['_tm_action']=$type;
		$v['_tm_remote_ip']=$_SERVER['REMOTE_ADDR'];
		
		if (!empty($modified) || $type=='delete') {
			$nrec=$rs->new_record($v);
			//$ret=$rs->insert($nrec);
			$ret=$rs->get_connector()->insert($nrec);
		}
	}
}
