<?php
$config=gs_config::get_instance();
load_file($config->lib_tpl_dir.'gsmtpl.lib.php');

class extSmarty extends gsmtpl {}

?>
