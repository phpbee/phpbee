<?php
function smarty_function_field_name($params, $template) {
	$img_dir=cfg('document_root').'i/admin/';
	switch ($params['type']) {
		case 'fCheckbox':
			$file1='ico_'.$params['key'].'_'.$params['id'].'.gif';
			$file0='ico_'.$params['key'].'.gif';
			$file=file_exists($img_dir.$file1) ? '/i/admin/'.$file1 : (file_exists($img_dir.$file0)? '/i/admin/'.$file0 : '');
			$str=$file ? '<img src="'.$file.'" title="'.$params['name'].'">' : $params['name'];
			echo $str;
		break;
		default:
			echo $params['name'];
		break;
	}
	
	return '';
}

?>
