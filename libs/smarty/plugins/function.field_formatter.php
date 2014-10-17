<?php
function smarty_function_field_formatter($params, $template) {
	if (!isset($params['type']) && !isset($params['value'])) {
			$params=array('type'=>key($params),'value'=>current($params));
	}
	switch ($params['type']) {
		case 'fInt':
			return sprintf("%d",$params['value']);
		break;
		case 'fFloat':
			return sprintf("%.02f",$params['value']);
		break;
		case 'fCheckbox':
			$str=$params['value']==1 ? '/i/admin/ico_on.gif' : '/i/admin/ico_off.gif';
			return '<img src="'.$str.'">';
		break;
        case 'fText':
            return nl2br($params['value']);
		default:
			return $params['value'];
		break;
	}
	
	return '';
}

?>
