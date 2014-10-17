<?php

class sitemap_cfg_form extends g_forms_html {
	function __construct($hh,$params=array(),$data=array()) {
		//$c=str_replace('module_','',class_members('gs_module'));
		$this->field_options['module_name']['variants']=class_members('gs_module');
		$this->field_options['recordset_name']['variants']=class_members('gs_recordset_short');
		parent::__construct($hh,$params,$data);
	}
}

class sitemap_handler extends gs_base_handler {
	function execute($ret) {
		$host='http://'.cfg('host');
		setlocale(LC_NUMERIC,'POSIX');

		$xml=new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
		$xml->lastmod=date(DATE_W3C);
		$xml->priority=1.0;
		$xml->changefreq='daily';

		$rs=new sitemap_cfg();
		$rs->find_records(array('disabled'=>0));
		foreach ($rs as $rec) {
			$xrs=new $rec->recordset_name;
			foreach ($xrs->find_records(array())->limit(1000) as $xrec) {
				$url=call_user_func($rec->module_name.'::gl',$rec->gl,$xrec,$this->data['gspgid'],'/');
				$url=$host.'/'.ltrim($url,'/');

				$i=$xml->addChild('url');
				$i->loc=$url;
				if($xrec->_ctime) $i->lastmod=date(DATE_W3C,strtotime($xrec->_ctime));
				$i->changefreq='weekly';
				$i->priority=0.5;

			}
		}
		return $xml;
	}
}
