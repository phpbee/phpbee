<?php

class gs_data_driver_stdin implements gs_data_driver {
	
	function test_type()
	{
		return PHP_SAPI=='cli';
		return isset($_SERVER['argc']) && $_SERVER['argc']>1;
	}
	
	function import ()
	{
		$data=array();
		if (isset($_SERVER['argv']) && count($_SERVER['argv'])>1)
		{
			$source=array_slice($_SERVER['argv'],1);
			foreach ($source as $line)
			{
				$vals=explode('=',$line);
				if (count($vals)==2) {
					$data[$vals[0]]=$vals[1];
				}
			}
			if (isset($data['gspgid'])) {
				$data['gspgid']=trim($data['gspgid'],'/');
			}
			if (!isset($data['gspgtype'])) {
				$data['gspgtype']=GS_DATA_GET;
			}
		}
		return $data;
	}
}

?>
