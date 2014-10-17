<?php

class gs_data_driver_post implements gs_data_driver {
	
	function test_type()
	{
		return $_SERVER['REQUEST_METHOD']=='POST';
	}
	
	function import ()
	{
		$_POST['gspgtype']=GS_DATA_POST;
		if (isset($_POST['gspgid'])) {
			$_POST['gspgid']=trim($_POST['gspgid'],'/');
		}
		foreach ($_FILES as $k=>$v) {
			if ($v['error']==4) unset($_FILES[$k]);
		}
		$_POST=array_merge($_POST,$_FILES);
		if (isset($_GET['gspgid_form']) && !isset($_POST['gspgid_form'])) {
			$_POST['gspgid_form']=$_GET['gspgid_form'];
		}

		//if (isset($_POST['gspgid_form'])) gs_var_storage::save('gspgid',$_POST['gspgid_form']);

		return get_magic_quotes_gpc() ? stripslashes_deep($_POST) : $_POST;
	}
}

?>
