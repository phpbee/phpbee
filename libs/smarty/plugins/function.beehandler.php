<?php
function smarty_function_beehandler($params, &$smarty) {
	if (isset($params['path']) && !class_exists('gs_base_handler',0)) {
		require_once($params['path'].'/libs/config.lib.php');
		$cfg=gs_config::get_instance();
		$init=new gs_init('auto');
		cfg_set('tpl_data_dir',array(
			cfg('tpl_data_dir'),
			realpath(cfg('root_dir').'html'),
		));
		//$init->init(LOAD_CORE);
		$init->init(LOAD_CORE | LOAD_STORAGE | LOAD_TEMPLATES | LOAD_EXTRAS);
		cfg_set('init_data',$init->data);
		cfg_set('www_dir','');
	}
	/*
		$o_h=new gs_parser($init->data);
		$config=gs_config::get_instance();
		load_file($config->lib_tpl_dir.'extSmarty.class.php');
		$tpl=new extSmarty;
		$tpl->template_dir=$config->tpl_data_dir;
		$tpl->compile_dir=$config->tpl_var_dir;
		//$tpl->plugins_dir[]=$config->lib_tpl_dir.'plugins';
		$tpl->assign('base_dir',$config->www_dir);
		$tpl->assign('http_host',$config->host);
		$smarty=$tpl;
	*/
	$smarty->assign('_gsdata',cfg('init_data'));
	$params['gspgtype'] = $_SERVER['REQUEST_METHOD']=='POST' ? 'post' : 'get';
	$ret=gs_base_handler::process_handler($params,$smarty);

		if (DEBUG) {
			$g=gs_logger::get_instance();
			$g->console();
		}
	return $ret;

}
?>
