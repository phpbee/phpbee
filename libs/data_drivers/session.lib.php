<?php

class gs_data_driver_session implements gs_data_driver {
	
	function test_type()
	{
		return isset($_SESSION);
	}
	
	function import ()
	{
		return (isset($_SESSION) && !empty($_SESSION)) ? $_SESSION : array();
	}
}

?>