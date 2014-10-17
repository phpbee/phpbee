<?php

final class gs_tpl_static {
	
	protected function init()
	{
		$config=gs_config::get_instance();
		$this->template_dir=$config->tpl_data_dir;
		/*
		$tpl->assign('base_dir',$config->www_dir);
		$tpl->assign('http_host',$config->host);
		*/
		return $this;
	}

	function assign() {
	}

	function display($name) {
		readfile($this->template_dir.'/'.$name);
	}

	
	function &get_instance()
	{
		static $instance;
		if (!isset($instance))
		{
			$loader=new gs_tpl_static();
			$instance = $loader->init();
		}
		return $instance;
	}
}

?>
