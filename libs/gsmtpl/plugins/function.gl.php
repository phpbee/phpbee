<?php
function smarty_function_gl($params, $template) {
	$p=$template->getTemplateVars('_gsparams');
	var_dump($p);
	var_dump(key($params));
	return call_user_func($p['module_name'].'::gl',key($params),current($params));
}

?>
