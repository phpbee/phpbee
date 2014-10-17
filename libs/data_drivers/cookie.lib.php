<?php

class gs_data_driver_cookie implements gs_data_driver {
	
	function test_type()
	{
		return isset($_COOKIE);
	}
	
	function import ()
	{
		return (isset($_COOKIE) && !empty($_COOKIE)) ? (get_magic_quotes_gpc() ? stripslashes_deep($_COOKIE) : $_COOKIE) : array();
	}
}

?>
