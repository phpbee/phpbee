<?php

class rss_import_cfg_form extends g_forms_html {

	    function __construct($hh,$params=array(),$data=array()) {
			parent::__construct($hh,$params,$data);

			/*
		$this->interact['recordset_id']="
			#title_fieldname_id.display_if(1);
			#description_fieldname_id.display_if(1);
			#images_linkname_id.display_if(1);
		";
			*/
		$this->interact['recordset_id']="
			#title_fieldname_id.display_if(0,'!=');
			#description_fieldname_id.display_if(0,'!=');
			#link_fieldname_id.display_if(0,'!=');
			#images_linkname_id.display_if(0,'!=');

			#title_fieldname_id.link_values('wz_recordsets.Fields');
			#description_fieldname_id.link_values('wz_recordsets.Fields');
			#link_fieldname_id.link_values('wz_recordsets.Fields');
			#images_linkname_id.link_values('wz_recordsets.Links');
			";

		$this->set_option('title_fieldname_id','widget','radio');		

		}


}


