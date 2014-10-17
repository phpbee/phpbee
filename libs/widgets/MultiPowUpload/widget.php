<?php

class gs_widget_MultiPowUpload extends gs_widget{
	function html() {
		$hash_field_name=$this->params['linkname'].'_hash';
		$hash=isset($this->data[$hash_field_name]) ? $this->data[$hash_field_name] : time().rand(10,99);
		$rid_name=$this->params['options']['local_field_name'];
		$rid=isset ($this->data[$rid_name]) ? $this->data[$rid_name] : 0;
		$r=new $this->params['options']['recordset'];

		$images=$r->find_records(array(
				$this->params['options']['foreign_field_name']=>0,
				array('field'=>'_ctime','case'=>'<=','value'=>date(DATE_ATOM,strtotime('now -1 day'))),
				));
		$images->delete();
		$images->commit();

		$find=array();
		if (isset ($this->data[$rid_name])) {
			$find[$this->params['options']['foreign_field_name']]=$this->data[$rid_name];
		} else {
			$find[$this->params['options']['foreign_field_name'].'_hash']=$hash;
		}
			
        /*
		$tpl=gs_tpl::get_instance();
		$tpls=$tpl->template_dir;
		$tpls[]=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates';
		$tpl->setTemplateDir($tpls);
        */

        $tpl=new gs_tpl();
		$tpl=$tpl->init();
        $tpl->addTemplateDir(dirname(__FILE__).DIRECTORY_SEPARATOR.'templates');

		$params=array();
		$params['recordset']=$this->params['options']['recordset'];
		$params['linkname']=$this->params['linkname'];
		$params['foreign_field_name']=$this->params['options']['foreign_field_name'];
		$params['rid']=$rid;
		$params['hash']=$hash;
		$params[$params['linkname'].'_hash']=$hash;

		$params_str=json_encode(str_replace(array('&','='),array('|',';'),http_build_query($params)));
		$tpl->assign('params',$params);
		$tpl->assign('params_str',$params_str);

		$images=$r->find_records($find)->orderby('group_key');
		$g_images=array();
		foreach($images as $i) {
			$key=$i->group_key;
			if (!$key) $key='nogrp';
			$g_images[$key][]=$i;
		}
		$tpl->assign('images',$images);
		$tpl->assign('g_images',$g_images);

		return $tpl->fetch('widget_MultiPowUpload.html');

	}
	function clean() {
		return array();
	}
}

class gs_widget_MultiPowUpload_module extends gs_base_module implements gs_module {
	function __construct() {}
	function install() {}
	function get_menu() {}
	static function get_handlers() {
		$data=array(
		'handler'=>array(
			'/widgets/MultiPowUpload/action'=>array(
				'gs_widget_MultiPowUpload_handler.action',
				'gs_base_handler.redirect',
			),
		),
		'post'=>array(	
			'/widgets/MultiPowUpload/upload'=>array(
				'gs_widget_MultiPowUpload_handler.upload',
			),
		),
		'get'=>array(
			/*
			'/libs/widgets/MultiPowUpload/widget.css'=>'gs_widget_MultiPowUpload_handler.public_html:content-type:text/css',
			'/libs/widgets/MultiPowUpload/widget.js'=>'gs_widget_MultiPowUpload_handler.public_html:content-type:application/javascript',
			'/libs/widgets/MultiPowUpload/Extra/swfobject.js'=>'gs_widget_MultiPowUpload_handler.public_html:content-type:application/javascript',
			*/
			'/libs/widgets/MultiPowUpload/'=>'gs_widget_MultiPowUpload_handler.public_html',
			'/widgets/MultiPowUpload/'=>'gs_widget_MultiPowUpload_handler.public_html',
		),
		);
		return self::add_subdir($data,dirname(__file__));
	}
}

class gs_widget_MultiPowUpload_handler extends gs_handler {
	function public_html() {
		$fname=dirname(__FILE__).DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.trim($this->data['gspgid_v'],DIRECTORY_SEPARATOR);
		$fname=realpath($fname);
		if(!$fname) return NULL;
		//if(isset($this->params['content-type'])) header('Content-type:'.$this->params['content-type']);
		if (pathinfo($fname, PATHINFO_EXTENSION)=='css') header('Content-type:text/css');
		if (pathinfo($fname, PATHINFO_EXTENSION)=='js') header('Content-type:application/javascript');
        if (pathinfo($fname, PATHINFO_EXTENSION)=='swf') header('Content-type:application/x-shockwave-flash');
		readfile($fname);
	}
	function action() {
		$this->handler_params=$this->data['handler_params'];
		if ($this->data['gspgtype']!==GS_DATA_POST) return '';

		$rs=new $this->handler_params['recordset'];
		$options=array(
			$this->handler_params['foreign_field_name']=>$this->handler_params['rid'],
			'id'=>$this->data['checked_items'],
		);
		$this->recs=$rs->find_records($options);
		if ($this->data['checked_items_action']=='group') $this->action_group();
		if ($this->data['checked_items_action']=='delete') $this->action_delete();
		return $this->recs->first();
	}
	function action_group() {
		$id=array_keys($this->recs->get_values());
		asort($id);
		$key=implode('-',$id);
		foreach ($this->recs as $rec) $rec->group_key=$key;
		$this->recs->commit();
	}
	function action_delete() {
		foreach ($this->recs as $rec) $rec->delete();
		$this->recs->commit();
	}

	function upload() {
		$rs_name=$this->data['recordset'];
		$f_name=$this->data['foreign_field_name'];
		$f_hash_name=$f_name.'_hash';

		$values=$_FILES['Filedata'];

		$ret=array(
				'File_data'=>file_get_contents($values['tmp_name']),
				'File_filename'=>$values['name'],
				'File_mimetype'=>$values['type'],
				'File_size'=>$values['size'],
				'File_width'=>max($_REQUEST['thumbnailWidth'],$_REQUEST['imageWidth']),
				'File_height'=>max($_REQUEST['thumbnailHeight'],$_REQUEST['imageHeight']),
			 );

		if ($this->data['gspgid_va'][0]=='original' && $this->data['fileId'] ) {
			$f=new $rs_name;
			$f=$f->find_records(array('file_uid'=>$this->data['fileId']))->first()->File->first();
			if ($f) {
				$f->File_data=$ret['File_data'];
				$rs=$f->get_recordset();
				$rs->config=array(
				            'orig_'.$this->data['fileId']=>array('width'=>0,'height'=>0,'method'=>'copy'),
							);
				$rs->resize($f);
				return $this->li($f);

			}
		}

		

		$f=new $rs_name;
		$f=$f->new_record();

		$f->$f_hash_name=$this->data['hash'];
		$f->$f_name=$this->data['rid'];
		if($this->data['fileId']) $f->file_uid=$this->data['fileId'];



		
		$ff=$f->File->new_record($ret);

		$f->commit();

		return $this->li($f);

	}
	function li($f) {
        /*
		$tpl=gs_tpl::get_instance();
		$tpl->template_dir=cfg('lib_dir').DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'MultiPowUpload'.DIRECTORY_SEPARATOR.'templates';
        */
        $tpl=new gs_tpl();
		$tpl=$tpl->init();
        $tpl->addTemplateDir(dirname(__FILE__).DIRECTORY_SEPARATOR.'templates');
		$tpl->assign('i',$f);
		echo $tpl->fetch('li_image_MultiPowUpload.html');
	}
}
