<?php
if (class_exists('Phar',0) && file_exists(dirname(__FILE__).'/../gs_libs.phar.gz')) {
	require_once('phar://'.dirname(__FILE__).'/../gs_libs.phar.gz/config.lib.php');
} else {
	require_once(dirname(__FILE__).'/../libs/config.lib.php');
}
$gs_node_id=1;
$init=new gs_init('user');
$cfg=gs_config::get_instance();
set_time_limit(300);
$cfg->check_install_key();
$init->init(LOAD_CORE | LOAD_STORAGE | LOAD_TEMPLATES | LOAD_EXTRAS);
gs_eventer::clean_subscribes();
$init->clear_cache();
$init->compile_modules();
$init->load_modules();
$init->install_modules();
$init->save_handlers();
gs_fkey::update_fkeys();

gs_logger::dump();

echo "install done";

?>
