<?php

final class gs_tpl {
	private  $_tpl_arr;
	
	function init()
	{
		$config=gs_config::get_instance();
		load_file($config->lib_tpl_dir.'extSmarty.class.php');
		$tpl=new extSmarty;
		$tpl->template_dir=$config->tpl_data_dir;
		$tpl->compile_dir=$config->tpl_var_dir;
		//$tpl->plugins_dir[]=$config->lib_tpl_dir.'plugins';
		//if (cfg('tpl_force_compile')) $tpl->force_compile=TRUE;
		$tpl->assign('base_dir',$config->www_dir);
		$tpl->assign('http_host',$config->host);
		return $tpl;
	}
	
	static function &get_instance()
	{
		static $instance;
		if (!isset($instance))
		{
			$loader=new gs_tpl();
			$instance = $loader->init();
		}
		return $instance;
	}
	function __destruct() {
		$msg = $_SERVER['SERVER_ADDR'].' | http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		//gs_logger::udplog($msg);
	}

	function multilang($tplanme) {
		mlog($tplname);
		$language=false;
		if (!$language) $language=gs_var_storage::load('multilanguage_lang');
		if (!$language) $language=gs_session::load('multilanguage_lang');
		if (!$language) $language=cfg('multilang_default_language');

		mlog($language);

		if ($language) {
				$newtplname=dirname($tplname).DIRECTORY_SEPARATOR.$language.DIRECTORY_SEPARATOR.(basename($tplname));
				if (file_exists($newtplname)) {
					$tplname=$newtplname;
					$old_tpl_dir=$dir=$this->getTemplateDir();
					if (!is_array($dir)) $dir=array($dir);
					array_unshift($dir,'.',dirname($newtplname));
					$this->setTemplateDir($dir);
				}
		}
		mlog($tplname);
		return $tplname;
	}
}

?>
