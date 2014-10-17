<?php
function smarty_function_default($params, &$smarty) {
	return (empty($params[0])) ? $params[1] : $params[0];
}
?>
