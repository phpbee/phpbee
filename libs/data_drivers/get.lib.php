<?php

class gs_data_driver_get implements gs_data_driver {
	
	function test_type()
	{
		return $_SERVER['REQUEST_METHOD']=='GET';
	}
	
	function import ()
	{
		$_GET['gspgtype']=GS_DATA_GET;
		if (isset($_GET['gspgid'])) {
			$_GET['gspgid']=trim($_GET['gspgid'],'/');
		}
		return get_magic_quotes_gpc() ? stripslashes_deep($_GET) : $_GET;
	}
}

?>
