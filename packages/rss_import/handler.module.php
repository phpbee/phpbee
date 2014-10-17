<?php
class rss_import_handler extends gs_base_handler {
	function execute($ret) {
		$rs=new rss_import_cfg();
		$options=array('disabled'=>0);
		if ($this->data['gspgid_va'][0]) $options['id']=$this->data['gspgid_va'][0];
		//$rec=record_by_id($this->data['gspgid_va'][0],'rss_import_cfg');
		foreach ($rs->find_records($options) as $rec) {
			md($rec->get_values(),1);
			$rss=html_fetch($rec->url);
			$x=simplexml_load_string($rss);
			$wz_rset=record_by_id($rec->recordset_id,'wz_recordsets');
			$target_rs=new $wz_rset->name;
			$title_fieldname=record_by_id($rec->title_fieldname_id,'wz_recordset_fields')->name;
			$description_fieldname=record_by_id($rec->description_fieldname_id,'wz_recordset_fields')->name;
			$link_fieldname=record_by_id($rec->link_fieldname_id,'wz_recordset_fields')->name;
			$images_linkname=record_by_id($rec->images_linkname_id,'wz_recordset_links')->name;

			foreach ($x->channel->item as $a) {
				$link=trim($a->link);
				if ($link_fieldname) {
					$rs=new $wz_rset->name;
					$rs->find_records(array($link_fieldname=>$link));
					if ($rs->count()) continue;
				}
				$r=$target_rs->new_record();
				if($title_fieldname) $r->$title_fieldname=trim($a->title);
				if($description_fieldname) $r->$description_fieldname=trim($a->description);
				if($link_fieldname) $r->$link_fieldname=$link;
				if ($rec->rec_default_values) {
					$r->fill_values(string_to_params($rec->rec_default_values));
				}
				if ($images_linkname && $a->enclosure) {
					foreach ($a->enclosure as $enc) {
						$url=http_host($rec->url).trim($enc['url']);
						md($url,1);
						$img=$r->$images_linkname->new_record();
						$file=$img->File->new_record($img->File->fetch_image($url));
					}
					$r->commit();
				}
			}
			$target_rs->commit();
			md($target_rs->get_values(),1);
		}
	}
}
