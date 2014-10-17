<?php
function smarty_function_compile_css($params, $template) {
	preg_match_all('|href=(.*?)\.css?|is',$params['data'],$out);
	$times=$files=array();
	
	foreach ($out[1] as $name) {
		$files[]=$file=$dir.ltrim(stripslashes($name),'"\'\/').'.css';
		if (file_exists($file)) {
			$times[]=filemtime($file);
		}
	}
	$mtime=max($times);
	$dir=cfg('document_root');
	
	$js_path=dirname(ltrim(stripslashes($out[1][0]),'"\''));
	$src=$js_path.'/'.'gscss.'.time().'.css';
	$file=$dir.ltrim($src,'/');
	$mask=$dir.ltrim($js_path,'/').'/gscss.*.css';
	$cache=glob($mask);
	
	if (!empty($cache) && $mtime<filemtime($cache[0])) {
		$cf=str_replace($dir,'/',realpath($cache[0]));
		return sprintf('<link href="%s" rel="stylesheet" type="text/css" title="CSS for site" />',$cf);
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
	
	return sprintf('<link href="%s" rel="stylesheet" type="text/css" title="CSS for site" />',$src);
}

?>
