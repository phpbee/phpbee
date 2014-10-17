<?php
function smarty_function_compile_js($params, $template) {
	preg_match_all('|src=(.*?)\.js?|is',$params['data'],$out);
	$times=$files=array();
	
	foreach ($out[1] as $name) {
		$files[]=$file=$dir.ltrim(stripslashes($name),'"\'\/').'.js';
		if (file_exists($file)) {
			$times[]=filemtime($file);
		}
	}
	$mtime=max($times);
	$dir=cfg('document_root');
	
	$js_path=dirname(ltrim(stripslashes($out[1][0]),'"\''));
	$src=$js_path.'/'.'gsjs.'.time().'.js';
	$file=$dir.ltrim($src,'/');
	$mask=$dir.ltrim($js_path,'/').'/gsjs.*.js';
	$cache=glob($mask);
	
	if (!empty($cache) && $mtime<filemtime($cache[0])) {
		$cf=str_replace($dir,'/',realpath($cache[0]));
		return sprintf('<script type="text/javascript" src="%s"></script>',$cf);
	}
	
	foreach ($cache as $cf) {
		unlink($cf);
	}
	$jsc=array();
	foreach ($files as $js) {
		if (file_exists($js)) {
			$jsc[]=file_get_contents($js);
		}
	}
	$res=implode("\n",$jsc);
	if (file_put_contents($file,$res)===false) {
		mlog('ERROR: JS cache '.$file.' not created (test permissions)');
		return $params['data'];
	}
	
	return sprintf('<script type="text/javascript" src="%s"></script>',$src);
}

?>
