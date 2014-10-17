<?php

class form_search_config extends g_forms_table {
    function __construct($hh,$params=array(),$data=array()) {
            parent::__construct($hh,$params,$data);

		$types=array_keys(cfg('gs_connectors'));
		$types=array_combine($types,$types);
	    $this->field_options['database_connector_id']['options']=$types;

    }
}


class form_search_config_filter extends g_forms_table {
    function __construct($hh,$params=array(),$data=array()) {
            parent::__construct($hh,$params,$data);
            $this->interact['Recordset_id']="
                            #Fields[].hide_if(''); 
                            #Fields[].link_values_options('wz_recordsets.Fields');
                            ";
	    $this->field_options['filter_classname']['options']=class_members('gs_filter');
		array_unshift($this->field_options['filter_classname']['options'],'');		    

    }
}
