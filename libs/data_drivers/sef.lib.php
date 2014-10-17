<?php

class gs_data_driver_sef implements gs_data_driver {
	
	private $gspgid;
	
	function test_type()
	{


		$dir=gs_config::get_instance()->script_dir;
		$gspgid=preg_replace("|^".preg_quote ($dir)."|s",'',$_SERVER["REQUEST_URI"]);
		$gspgid=preg_replace('|\?.*$|is','',$gspgid);
		$gspgid=trim($gspgid,'/');



		if (class_exists('urlprefix_cfg')) {
            $px=gs_cacher::load('urlprefix_cfg','config');
            if (!is_array($px)) {
                $px=new urlprefix_cfg();
                if ($px->get_connector()->table_exists($px->table_name)) {
                    $px=$px->find_records(array())->get_values();
                    gs_cacher::save($px,'config','urlprefix_cfg');
                }
            }
            $gspgid_old=$gspgid;
            foreach ($px as $pf) {
                if (stripos($gspgid,$pf['prefix'])===0) {
                    gs_var_storage::save($pf['variable_name'],$pf['value']);
					gs_eventer::send('data_driver_sef_set_prefix',$pf);
                    $gspgid=substr($gspgid,strlen($pf['prefix']));
                    $gspgid=trim($gspgid,'/');
                }
            }
			$urlprefix=trim(str_replace($gspgid,'',$gspgid_old),'/');
            if ($urlprefix) gs_var_storage::save('urlprefix','/'.$urlprefix);
		}

		$this->gspgid=trim($gspgid,'/');
		return !empty($this->gspgid);
	}
	
	function import ()
	{
		gs_var_storage::save('gspgid',$this->gspgid);
		return $this->test_type() ? array('gspgid'=>$this->gspgid,'gspgtype'=>GS_DATA_GET) : array();
	}
}

?>
