<?php
ob_start('fatal_error_handler');
if (class_exists('Phar',0) && file_exists(dirname(__FILE__).'/../gs_libs.phar.gz')) {
	require_once('phar://'.dirname(__FILE__).'/../gs_libs.phar.gz/config.lib.php');
} else {
	require_once(dirname(__FILE__).'/../libs/config.lib.php');
}
mlog('1');//starts time counter in debug


$init=new gs_init();
$init->init(LOAD_CORE);
//$init->load_modules();
$o_h=new gs_parser($init->data);
$o_h->process();

function fatal_error_handler($buffer){
        $error=error_get_last();
	        if(!DEBUG && $error['type'] == 1){

			$str='<link rel="stylesheet" type="text/css" href="/css/main.css" media="screen" />';
			$str.=sprintf('<div class="gs_exception">%s on line %s in file %s</div>',$error['message'],$error['line'],$error['file']);
			if (preg_match('/^Class .* not found/',$error['message'])) {
				$url=$_SERVER['HTTPS'] ? 'https://':'http://';
				$url.=$_SERVER['HTTP_HOST'].'/install.php?install_key=12345';
				$str.="
				<div class=\"gs_exception\">
				Try to run <a href=\"$url\">$url</a> to continue. 
				<br>
				Install key could be found in the config.php file
				</div>";
			}
			return $str;
		}

	return $buffer;
}

