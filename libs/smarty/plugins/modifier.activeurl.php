<?php
function smarty_modifier_activeurl($string) {
	$smarty=gs_tpl::get_instance();
	$data=$smarty->getTemplateVars('_gsdata');
	if (isset($data['handler_key_root']) && $data['handler_key_root']==trim($string,'/')) return '" class="active"';
	if (isset($data['handler_key']) && $data['handler_key']==trim($string,'/')) return '" class="active"';
	if (isset($data['gspgid']) && $data['gspgid']==trim($string,'/')) return '" class="active"';
};
