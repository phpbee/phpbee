<?php
function smarty_function_params2array($params, $template) {
	$data=$params;
	unset($data['assign']);
	return $template->assign($params['assign'],$data);
}

?>
