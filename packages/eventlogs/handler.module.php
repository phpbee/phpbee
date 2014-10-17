<?php


class eventlog_handler extends gs_handler {

	private static function get_roles() {
		$person=person();
		//$roles=array_keys($person->get_roles());
		$roles=$person->get_roles();
		if(!$roles) $roles=array('system'=>new gs_null(GS_NULL_XML));
		return $roles;

	}

	private static function get_classname($rec) {
		$class='';
		if(is_string($rec)) $class=$rec;
		if (is_object($rec)) {
		//	$class= is_a($rec,'gs_record') ? $rec->get_recordset_name() : get_class($rec);
			$class=get_class($rec);
			if (is_a($rec,'gs_record')) {
				 $class= $rec->get_recordset_name() ;
				 /*
				 if ($class=='gs_rs_links') {
					 $rs=$rec->get_recordset();
				 }
				 */
			}
			
		}
		return $class;
	}



	function log_event($rec,$event) {
		$class=self::get_classname($rec);
		if($class=='eventlogs_events') return;
		$roles=self::get_roles();


		$el=new eventlogs_events();
		foreach ($roles as $role=>$person) {

			$el=$el->find_records(array(
					'event'=>$event,
					'role'=>$role,
					'class'=>$class,
					'active'=>1,
					))->first();

			if (!$el) continue;		

			$l=new eventlogs();
			$l=$l->new_record(array(
					'event'=>$event,
					'role'=>$role,
					'class'=>$class,
					'url'=>gs_var_storage::load('top_handler_key'),
					'Config_id'=>$el->get_id(),
					));

			if ($person) $l->person_id=$person->get_id();

			if (is_object($rec) && is_a($rec,'gs_record')) {
				$rs=$rec->get_recordset();
				$rs_links=isset($rs->structure['recordsets']) ? $rs->structure['recordsets'] :array();

				$l->record_id=$rec->get_id();
				$mod_values=array();
				foreach ($rec->get_modified_values() as $k=>$v) {
					if ($k=='_mtime' || $k=='_ctime') continue;

					
					if (substr($k,-3)=='_id') {
						$linkname=preg_replace('/_id$/','',$k);
						if (isset($rs_links[$linkname])) {
							$l_rs=$rec->$linkname;
							$olv=record_by_id($rec->get_old_value($k),$l_rs->get_recordset_name());
							$nlv=record_by_id($v,$l_rs->get_recordset_name());

							$mod_values['links'][$linkname]['removed_links'][$rec->get_old_value($k)]=trim($olv);
							$mod_values['links'][$linkname]['new_links'][$v]=trim($nlv);

							continue;
						}
					}
						
					$mod_values['fields'][$k]=array('old'=>$rec->get_old_value($k),'new'=>$v);
				}

				foreach ($rec->get_modified_links() as $k=> $v) {
					foreach ($v as $type=>$links) {
						foreach ($links as $lk=>$link) {
							$mod_values['links'][$k][$type][$lk]=trim($link->childs->first());
						}

					}
				}

				//md("HL:".object_id($rec),1);
				//md($mod_values,1);



				$l->info=serialize($mod_values);

			}

			$l->commit();

		}


	}

	function collect_events($rec,$event) {
		$class=self::get_classname($rec);
		if($class=='eventlogs_events') return;


		$roles=self::get_roles();


		$el=new eventlogs_events();
		foreach ($roles as $role=>$person) {
			$el=$el->find_records(array(
					'event'=>$event,
					'role'=>$role,
					'class'=>$class,
					))->first(true);
			$el->commit();

		}

		/*

		$roles=implode(',',array_keys($person->get_roles()));
		$top_handler_key=gs_var_storage::load('top_handler_key');




		$el=new eventlogs_events();
		$el=$el->find_records(array('class'=>$class, 'type'=>'get','url'=>$top_handler_key,'role'=>$roles))->first(true);
		$el->commit();


		//$type=gs_var_storage::load('type');
		$handler_key=gs_var_storage::load('handler_key');

		$el=new eventlogs_events();
		$el=$el->find_records(array('class'=>$class,'type'=>'handler','url'=>$handler_key,'role'=>$roles))->first(true);
		$el->commit();

		*/

	}

}

class module_eventlog_init extends gs_base_module implements gs_module {
    function __construct() {
    }
    function install() {
		gs_eventer::subscribe('person_add_role', 'eventlog_handler::collect_events');
		gs_eventer::subscribe('person_remove_role', 'eventlog_handler::collect_events');
		gs_eventer::subscribe('record_after_insert', 'eventlog_handler::collect_events');
		gs_eventer::subscribe('record_after_update', 'eventlog_handler::collect_events');
		gs_eventer::subscribe('record_after_delete','eventlog_handler::collect_events');

		gs_eventer::subscribe('person_add_role', 'eventlog_handler::log_event');
		gs_eventer::subscribe('person_remove_role', 'eventlog_handler::log_event');
		gs_eventer::subscribe('record_after_insert', 'eventlog_handler::log_event');
		gs_eventer::subscribe('record_after_update', 'eventlog_handler::log_event');
		gs_eventer::subscribe('record_after_delete','eventlog_handler::log_event');
    }
    function get_menu() {
    }
    static function get_handlers() {
    }
    static function gl($alias, $rec, $data) {
	}
}
