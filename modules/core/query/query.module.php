<?php
define('GS_DATE_TIMESTAMP','Y-m-d H:i:s');


class query_handler extends gs_handler {

	function subscribe($d) {
		$rec=$d['last'];
		query_handler::add($rec,$this->params['name']);
		return $rec;
	}


	static function add($rec,$name,$email=null) {
		$cfg=record_by_field('name',$name,'sb_config');
		if (!$cfg) return $rec;
		$options=array('Config_id'=>$cfg->get_id(),'rs_name'=>$rec->get_recordset_name(),'record_id'=>$rec->get_id());


		if (!$cfg->repeat) {
			$que=new sb_query();
			$que->find_records(array_merge($options,array('completed'=>1)));
			if ($que->count()) return $rec;
		}


		$que=new sb_query();
		$que->find_records(array_merge($options,array('completed'=>0)));

		if (!$que->count()) {
			$que->new_record(array_merge($options,array(
								'exec_time'=>date(GS_DATE_TIMESTAMP,strtotime($cfg->schedule))
								)));
		}
		foreach ($que as $q) {
			$q->email=$email;
		}
		$que->commit();

		return $rec;


	}
	function stop($rec,$name=null) {
		$que=new sb_query();
		$options=array('rs_name'=>$rec->get_recordset_name(),'record_id'=>$rec->get_id(),'completed'=>0);
		if ($name) {
			$cfg=record_by_field('name',$name,'sb_config');
			if (!$cfg) return $rec;
			$options['Config_id']=$cfg->get_id();
		}
		$que->find_records($options)->delete();
		$que->commit();

		return $rec;
	}
	function process_query($d) {
		$o_p=gs_parser::get_instance();
		$options=array('completed'=>0);
		$options[]=array('field'=>'exec_time','case'=>'<=','value'=>date(GS_DATE_TIMESTAMP,strtotime('now')));
		//$options=string_to_params("completed=0 exec_time=<='".date(GS_DATE_TIMESTAMP,strtotime('now'))."'");
		$que=new sb_query();
		$nque=new sb_query();
		$que->find_records($options)->limit(100);
		foreach ($que as $q) {
			$cfg=$q->Config->first();
			$handler=$o_p->parse_val($cfg->handler);
			mlog($handler);
			$o_h=new $handler['class_name']($this->data,$handler['params']);
			$q->exec_result=$o_h->{$handler['method_name']}(array('sb_query'=>$q,'last'=>record_by_id($q->record_id,$q->rs_name)));
			$q->completed_time=date(GS_DATE_TIMESTAMP,strtotime('now'));
			$q->completed=1;
	
			if($cfg->repeat) {
				$nq=$nque->new_record();
				$nq->email=$q->email;
				$nq->rs_name=$q->rs_name;
				$nq->record_id=$q->record_id;
				$nq->record_email_field=$q->record_email_field;
				$nq->Config_id=$q->Config_id;
				$nq->exec_time=date(GS_DATE_TIMESTAMP,strtotime($cfg->schedule,strtotime($q->exec_time)));
				md($nq->get_values(),1);
				$nq->commit();
			}
			$q->commit();
		}

	}
}
class module_query extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array( 'sb_config','sb_query') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	function get_menu() {
		$ret=array();
		$item=array();
		$item[]='<a href="/admin/subscriptions/">Query</a>';
		$item[]='<a href="/admin/subscriptions/sb_config">Подписки</a>';
				$ret[]=$item;
		return $ret;
	}
	static function get_handlers() {
		$data=array(
		'get'=>array(
			'run'=>array(
			  'query_handler.process_query', 
			),
			'/admin/subscriptions/sb_config'=>array(
			  'gs_base_handler.show:name:adm_sb_config.html', 
			),
			'/admin/subscriptions/sb_config/delete'=>array(
			  'gs_base_handler.delete:{classname:sb_config}', 
			  'gs_base_handler.redirect', 
			),
			'/admin/subscriptions/sb_config/copy'=>array(
			  'gs_base_handler.copy:{classname:sb_config}', 
			  'gs_base_handler.redirect', 
			),
			),
		'handler'=>array(
			'/admin/form/sb_config'=>array(
			  'gs_base_handler.redirect_if:gl:save_cancel:return:true', 
			  'gs_base_handler.post:{name:admin_form.html:classname:sb_config:form_class:g_forms_table}', 
			  'gs_base_handler.redirect_if:gl:save_continue:return:true', 
			  'gs_base_handler.redirect_if:gl:save_return:return:true', 
			),
			'/admin/inline_form/sb_config'=>array(
			  'gs_base_handler.redirect_if:gl:save_cancel:return:true', 
			  'gs_base_handler.post:{name:inline_form.html:classname:sb_config}', 
			  'gs_base_handler.redirect_if:gl:save_continue:return:true', 
			  'gs_base_handler.redirect_if:gl:save_return:return:true', 
			),
			'subscribe'=>array(
			  'sb_handler.subscribe', 
			),
		),
		);
		return self::add_subdir($data,dirname(__file__));
	}

}


class sb_query extends gs_recordset_short {
			public $no_urlkey=1;
			public $orderby="id"; 
			function __construct($init_opts=false) { parent::__construct(array(

		
			'exec_time'=>'fTimestamp verbose_name="exec_time"     required=true  index=true      ',
			'email'=>'fEmail verbose_name="email"     required=false  index=true      ',
			'rs_name'=>'fString verbose_name="rs_name"     required=false  index=true      ',
			'record_id'=>'fInt verbose_name="record_id"     required=false  index=true      ',
			'record_email_field'=>'fString verbose_name="record_email_field"     required=false        ',
			'completed'=>'fCheckbox verbose_name="completed"   default="0"   required=true  index=true      ',
			'completed_time'=>'fTimestamp     required=true  index=true      ',
			'exec_result'=>'fText     required=false        ',
			'Config'=>'lOne2One sb_config verbose_name="Config"    required=true    ',
						),$init_opts);
						$this->structure['fkeys']=array(
								array('link'=>'Config','on_delete'=>'CASCADE','on_update'=>'CASCADE'),
				
				     );
		
			
		
	}
			
	
	
}
class sb_config extends gs_recordset_short {
			public $no_urlkey=1;
			public $orderby="id"; 
			function __construct($init_opts=false) { parent::__construct(array(
					'name'=>'fString verbose_name="name"     required=true unique=true index=true      ',
					'schedule'=>'fString verbose_name="schedule"   default="now"   required=true        ',
					'repeat'=>'fCheckbox verbose_name="repeat"   default="1"   required=true  index=true      ',
					'handler'=>'fString verbose_name="handler"   default="sb_handler.email4record:name:email.html"   required=true  index=true      ',
					'tpl_from'=>'fString verbose_name="tpl_from"     required=false        ',
					'tpl_subject'=>'fString verbose_name="tpl_subject"     required=false        ',
					'tpl_text'=>'fText verbose_name="tpl_text"     required=false        ',
					'Recordset'=>'lOne2One wz_recordsets verbose_name="Recordset"   widget="parent_list"  required=false    ',
				),$init_opts);
				$this->structure['fkeys']=array();
		
	}
	
}


