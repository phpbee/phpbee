<?php

class gs_data_driver_cli implements gs_data_driver {
	
	private $gspgid;
	
	function test_type()
	{
		global $argv;
		if(isset($argv[1])) $this->gspgid=trim($argv[1],'/');
		return !empty($this->gspgid);
	}
	
	function import ()
	{
		return $this->test_type() ? array('gspgid'=>$this->gspgid,'gspgtype'=>GS_DATA_GET) : array();
	}
}
