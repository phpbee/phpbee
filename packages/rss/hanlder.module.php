<?php

class rss_cfg_form extends g_forms_html {
	function __construct($hh,$params=array(),$data=array()) {
		//$c=str_replace('module_','',class_members('gs_module'));
		$this->field_options['module_name']['variants']=class_members('gs_module');
		$this->field_options['recordset_name']['variants']=class_members('gs_recordset_short');
		parent::__construct($hh,$params,$data);
	}
}

class rss_handler extends gs_base_handler {
	function execute($ret) {
		$rss=$ret['last'];
		$host='http://'.cfg('host');
		setlocale(LC_NUMERIC,'POSIX');

		$xml=new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"></rss>');
		$channel=$xml->addChild('channel');
		$channel->title=$rss->title;
		$channel->description=$rss->description;
		$channel->link=$host;
		$channel->lastBuildDate=date(DATE_W3C);
		$channel->pubDate=date(DATE_W3C);
		$channel->ttl=1800;

		$rs=new rss_cfg();
		$rs->find_records(array('alias'=>$rss->alias));
		foreach ($rs as $rec) {
			$xrs=new $rec->recordset_name;
			foreach ($xrs->find_records(array())->orderby('_ctime desc')->limit($rec->records_limit) as $xrec) {
				$url=call_user_func($rec->module_name.'::gl',$rec->gl,$xrec,$this->data['gspgid'],'/');
				$url=$host.'/'.ltrim($url,'/');

				$i=$channel->addChild('item');
				$i->title=$xrec->{$rec->title_field_name};
				$i->description=mb_substr($xrec->{$rec->details_field_name},0,$rec->details_field_length);
				$i->link=$url;
				$i->guid=$url;
				if($xrec->_ctime) $i->pubDate=date(DATE_W3C,strtotime($xrec->_ctime));
			}
		}
		//md(xml_print($xml->asXML()),1); die();
		return $xml;
	}
}
