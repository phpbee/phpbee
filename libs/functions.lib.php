<?php
function person($var=null) {
		$p=person::get_instance();
		return $var ? $p->$var : $p;
}

if (!function_exists('get_called_class')) {
		function get_called_class()
		{
				$bt = debug_backtrace();
				$l = count($bt) - 1;
				$matches = array();
				while(empty($matches) && $l > -1){
						$lines = file($bt[$l]['file']);
						$callerLine = $lines[$bt[$l]['line']-1];
						preg_match('/([a-zA-Z0-9\_]+)::'.$bt[$l--]['function'].'/',
										$callerLine,
										$matches);
				}
				if (!isset($matches[1])) $matches[1]=NULL; //for notices
				if ($matches[1] == 'self') {
						$line = $bt[$l]['line']-1;
						while ($line > 0 && strpos($lines[$line], 'class') === false) {
								$line--;
						}
						preg_match('/class[\s]+(.+?)[\s]+/si', $lines[$line], $matches);
				}
				return $matches[1];
		}
}

function new_record($name) {
		$rs=recordset($name);
		return $rs->new_record();
}

function rs($name) {
		return recordset($name);
}

function recordset($name) {
		return new $name;
}
function http_host($url) {
		$s=parse_url($url);
		return sprintf('%s://%s',$s['scheme'],$s['host']);
}
function href_get_push($array,$url=null,$default_url=null) {
		if ($url===null && $_SERVER['REQUEST_URI']) $url=$_SERVER['REQUEST_URI'];
		if (($url===null || $url=='/') && $default_url!==null)  $url=$default_url; 
		if(!is_array($array)) parse_str($array,$array);

		$d=parse_url($url);
		parse_str(isset($d['query']) ? $d['query'] : '',$d_query);
		foreach ($array as $k=>$v) {
				$d_query[$k]=$v;
				//if (!$v) unset($d_query[$k]);
		}
		$d['query']=http_build_query($d_query);
		$url=http_build_url($d);
		return $url;

}
function href_url_push($array,$gsdata=array(),$url=null,$default_url=null) {
		if ($url===null && $_SERVER['REQUEST_URI']) $url=$_SERVER['REQUEST_URI'];
		if (($url===null || $url=='/') && $default_url!==null)  $url=$default_url; 
		if(!is_array($array)) parse_str($array,$array);

		$d_query=isset($gsdata['gspgid_vp']) ? $gsdata['gspgid_vp'] : array();

		$d=parse_url($url);
		foreach ($array as $k=>$v) {
				$d_query[$k]=$v;
		}
		$path="";
		foreach($d_query as $k=>$v) {
				$path.="$k/$v/";
		}
		$d['path']="/".$gsdata['handler_key']."/".trim($path,"/");
		$url=http_build_url($d);
		return $url;

}
function html_redirect($gspgid=null,$data=array(),$type='302', $clean_get=false, $target=null) {
		$query=array();
		if($gspgid===null) {
				$url=cfg('referer_path');
		} else {
				$scheme=parse_url($gspgid,PHP_URL_SCHEME);
				if ($scheme) {
						$url=$gspgid;
				} else {
						$urlprefix=gs_var_storage::load('urlprefix');
						if (substr($gspgid,0,1)=='/') {
								$url=$gspgid;
								if ($urlprefix) $url=$urlprefix.$gspgid;
						} else {
								$url=cfg('www_dir').$gspgid;
								if ($urlprefix) $url=rtrim(cfg('www_dir'),'/').$urlprefix.'/'.$gspgid;
						}
				}
		}

		if ($clean_get===false) parse_str(parse_url(cfg('referer'),PHP_URL_QUERY),$query);
		if(!isset($scheme)) $url='/'.ltrim($url,'/');
		$data=array_merge($query,$data);
		$datastr='';
		if ($data) $datastr='?'.http_build_query($data);
		switch ($type) {
				default:
				case '302':
						if ($target) {
							echo sprintf("<script>top.window.location.href='%s%s';</script>",$url,$datastr,$target);
						} else {
							$ret=sprintf('Location: %s%s',$url,$datastr);
							header($ret);
						}
						break;
		}
}
function object_to_array($obj) {
		$arr=array();
		$_arr = is_object($obj) ? get_object_vars($obj) : $obj;
		if (is_array($_arr)) foreach ($_arr as $key => $val) {
				$val = (is_array($val) || is_object($val)) ? object_to_array($val) : $val;
				$arr[$key] = $val;
		}
		return $arr;
}
function array_search_recursive($n,$a,$s=false) {
		$r=array_search($n,$a,$s);
		if ($r!==FALSE) return $r;
		foreach ($a as $aa) {
				if(is_array($aa)) {
						$r=array_search_recursive($n,$aa,$s);
						if ($r!==FALSE) return $r;
				}
		}
		return FALSE;
}
function array_key_recursive($a,$k,$v) {
		foreach ($a as $ak=>$aa) {
				if (isset($aa[$k]) && $aa[$k]==$v) return $ak;
		}
		return FALSE;
}

function get_output() {
		$txt=ob_get_contents();
		ob_end_clean();
		return $txt;
}


function array_merge_recursive_distinct ( array &$array1, array &$array2 ) {
		$merged = $array1;

		foreach ( $array2 as $key => &$value )
		{
				if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
				{
						$merged [$key] = array_merge_recursive_distinct ( $merged [$key], $value );
				}
				else
				{
						$merged [$key] = $value;
				}
		}

		return $merged;
}
function array_sum_recursive( array &$array1, array &$array2 )
{
		$merged = $array1;

		foreach ( $array2 as $key => &$value ) {
				if ( is_array ($value) ) {
						if (!isset($merged[$key])) $merged[$key]=array();
						$merged [$key] = array_sum_recursive ( $merged[$key], $value );
				} else {
						$merged [$key] += $value;
				}
		}

		return $merged;
}


function html_fetch($url,$data=array(),$scheme='GET') {
		mlog($url);
		mlog($data);
		if (!isset($url)) throw new gs_exception('html_fetch: empty url');

		if(!is_array($data)) $data=string_to_params($data);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		if (strtoupper($scheme)=='POST') {
				curl_setopt($ch, CURLOPT_POST, 1);
				//curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		} else {
				if($data) {
						$url.='?'.http_build_query($data);
				}
		}

		curl_setopt($ch, CURLOPT_URL, $url);


		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 180);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_MAXREDIRS,5);


		$result=curl_exec($ch);
		if (curl_errno($ch)>0) {
				throw new gs_exception(sprintf("html_fetch (%s) : CURL ERROR: %s : %s",$url,curl_errno($ch),curl_error($ch)));
		}
		curl_close($ch);
		mlog($result);
		return $result;

}
if (!function_exists('pmail')) {
		function pmail($recipients, $body="",$subject="",$add_headers=false,$from=false,$debug=1) {
				include_once("Mail.php");
				$recipients=is_array($recipients) ? $recipients : array($recipients);

				$pr_recipients=array();
				foreach ($recipients as $rec) {
						$pr_r=explode("\n",$rec);
						foreach ($pr_r as $pr_rec) {
								$pr_recipients[]=$pr_rec;
						}
				}
				$recipients=array_filter($pr_recipients);

				$params["host"] = cfg('mail_smtp_host');
				$params["port"] = cfg('mail_smtp_port');
				$params["auth"] = cfg('mail_smtp_auth');
				$params["username"] = cfg('mail_smtp_username');
				$params["password"] = cfg('mail_smtp_password');
				if ($debug) $params["debug"]=1;


				$headers['From']    = !empty($from) ? $from : cfg('mail_from');
				$headers['From'] =  (preg_replace_callback('/(.*)(<.+>)/',create_function('$a','return str_replace("."," ",$a[1]).$a[2];'),$headers['From']));
				$headers['Subject'] = $subject;
				$headers['Content-Type'] = 'text/plain; charset="UTF-8"';

				foreach ($recipients as $key=> $recipient) {
						$recipient=(preg_replace_callback('/(.*)(<.+>)/',create_function('$a','return str_replace("."," ",$a[1]).$a[2];'),$recipient));

						if (is_array($add_headers)) foreach ($add_headers as $name=>$value) {
								$headers[$name] = $value;
						}

						$headers['To']      = $recipient;


						$mail_object =& Mail::factory(cfg('mail_type'), $params);
						$ret=$mail_object->send($recipient, $headers, $body);

				}
				if ($debug) {
						md($ret,1);
				}
				return $ret;
		}
}

function bee_mail($to,$subj,$text,$from='') {
		$mail=new html_mime_mail();
		if (!$from) $from=cfg('support_email_address');
		if (!$from) $from="support@".$_SERVER['HTTP_HOST'];
		$mail->headers = $mail->headers.sprintf("From: %s\r\n",$from );
		//$subj=iconv( 'UTF-8', 'KOI8-R', $subject );
		$mail->add_html($text);
		$mail->build_message('koi8');
		return mail ($to,$subj,$mail->mime,$mail->headers);
}

function string_to_safeurl($str, $replace=array(), $delimiter='-') {
		$old_locale=setlocale(LC_ALL,"0");
		setlocale(LC_ALL, 'ru_RU.UTF-8');
		if( !empty($replace) ) {
				$str = str_replace((array)$replace, ' ', $str);
		}

		$clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
		$clean_test = preg_replace("/[^a-zA-Z0-9]/", '', $clean);
		if($str && !$clean_test) {
				$clean = iconv("UTF-8", "KOI8-R//IGNORE", $str);
				$clean=str_split($clean,1);
				$clean=implode(array_map(create_function('$a','return chr(ord($a) & 127) ;'),$clean));
		}

		$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
		$clean = strtolower(trim($clean, '-'));
		$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

		setlocale(LC_ALL, $old_locale);

		return $clean;
}


function record_by_id($id=0,$classname='gs_null') {
		$r=new $classname;
		return $r->get_by_id($id);
}
function record_by_urlkey($id=0,$classname='gs_null') {
		return record_by_field('urlkey',$id,$classname);
}
function record_by_field($field,$id=0,$classname='gs_null') {
		$r=new $classname;
		return $r->get_by_field($field,$id);
}
function s2p($inp) {
		return string_to_params($inp);
}
function stp($inp) {
		return string_to_params($inp);
}
function string_to_params($inp) {
		$arr=is_array($inp) ? $inp : array($inp);
		$ret=array();
		$arr=preg_replace('|=\s*([^\'\"][^\s]*)|i','=\'\1\'',$arr);
		foreach ($arr as $k=>$s) {
				$s.=' ';
				preg_match_all(':(\s*(([a-z0-9_\:]+)=)?[\'\"](.*?)[\'\"]\s|([^\s]+)):i',$s,$out);
				$r=array();
				$j=0;
				foreach ($out[3] as $i => $v) {
						$key= $v ? $v : $j++;
						//$value = strlen($out[4][$i]) ? $out[4][$i] : $out[1][$i];
						$value= ($v || $out[4][$i])  ? $out[4][$i] :  $out[1][$i];
						//if (strtolower($value)=='false') $value=false;
						//if (strtolower($value)=='true') $value=true;
						$prefix=explode(':',$value,2);
						if(strtoupper($prefix[0])=='ARRAY') $value=explode(':',$prefix[1]);
						$r[$key]=$value;
				}
				$ret[$k]=$r;
		}
		return is_array($inp) ? $ret : reset($ret);
}
function params_to_string ($params) {
		if (is_string($params)) return $params;
		$s='';
		foreach ($params as $k=>$v) {
				$s.=sprintf('%s="%s" ',$k,$v);
		}
		return trim($s);
}

function empty_array($a,$b) {
		return is_array($b) ? $a || array_reduce($b,'empty_array') : $a || ($b && $b!=4);
}

if (!function_exists('xml_print')) {
		function xml_print($xml) {
				if (!$xml) return;
				$dom = new DOMDocument('1.0');
				$dom->preserveWhiteSpace = false;
				$dom->formatOutput = true;
				$dom->loadXML($xml);
				return $dom->saveXML();
		}
}





function load_submodules($parent_name,$dirname) {
		$files = glob($dirname.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'*.phps');
		foreach ($files as $f) {
				$pf=str_replace(basename($f),'___'.basename($f),$f);
				$pf=preg_replace('/.phps$/','.xphp',$pf);
				if (!file_exists($pf) || filemtime($pf) < filemtime($f)) {
						$s=file_get_contents($f);
						$s=str_replace('{PARENT_MODULE}',$parent_name.'_',$s);
						file_put_contents_perm($pf,$s);
				}
				load_file($pf);
		}
}

function rrmdir($dir) {
		if (is_dir($dir)) {
				$objects = scandir($dir);
				foreach ($objects as $object) {
						if ($object != "." && $object != "..") {
								if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object);
								else unlink($dir."/".$object);
						}
				}
				reset($objects);
				rmdir($dir);
		}
}

function gsdict($t) {
		return gs_dict::get($t);
}

function copy_directory($src,$dst) {
		check_and_create_dir($dst);
		$dst=realpath($dst);
		$files=glob(realpath($src).DIRECTORY_SEPARATOR.'*');
		foreach ($files as $f) {
				$newname=$dst.DIRECTORY_SEPARATOR.basename($f);
				if (is_dir($f)) {
						copy_directory($f,$newname);
				} else {
						copy($f,$newname);
				}
		}
}

function class_members($classname=null) {
		$classes=gs_cacher::load('classes','config');
		if (!$classname) return $classes;
		$func=create_function('$a','return  is_subclass_of($a,"'.$classname.'");');
		$classes=array_filter(array_keys($classes),$func);
		$names=array();
		foreach ($classes as $c) {
				$names[]=method_exists($c,'_desc') ? call_user_func(array($c,'_desc')) : $c;
		}
		return array_combine($classes,$names);
}

function filter($name) {
		return gs_filters_handler::get($name);
}

function languages() {
		$langs=cfg('languages');
		if (is_string($langs)) {
				$rs=new $langs;
				$langs=$rs->find_records(array())->recordset_as_string_array();
				$langs=array_combine($langs,$langs);
		}
		if (!is_array($langs)) $langs=array();
	reset($langs);
		return $langs;
}
function mln_rus($variants,$n) {
		$variants=string_to_params($variants);

		$ld=substr($n,-1);
		$l2d=substr($n,-2);

		if (11<=$l2d and $l2d<=19) return $variants[2];

		if ($ld==1) return $variants[0];
		if ($ld==0 || $ld>=5) return $variants[2];
		return $variants[1];
}

function mln_eng($variants,$n) {
		$variants=string_to_params($variants);

		$ld=substr($n,-1);
		$l2d=substr($n,-2);

		if (11<=$l2d and $l2d<=19) return $variants[1];

		if ($ld==1) return $variants[0];
		return $variants[1];
}
function ml() {
		$cl=gs_var_storage::load('multilanguage_lang');
		if (!$cl) return func_get_arg(0);
		$values=func_get_args();
		if (count($values)==1) {
			$values=explode('|',reset($values));
		}
		$langs=languages();
		foreach($langs as $l=>$name) {
				if ($l==$cl) {
						$v=current($values);
						return $v;
				}
				next($values);
		}
		return func_get_arg(0);

}


function require_fullpath($from_filename,$filename) {
		require_once(rtrim(dirname($from_filename),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$filename);
}

function array_group($arr,$field) {
		$ret=array();
		foreach($arr as $a) {
				$ret[$a->$field][$a->get_id()]=$a;
		}
		return $ret;
}

function add_quote($a,$q="'") {
		$ret=$a;
		if ( is_array($a)) {
				return array_map('add_quote',$a);
		}
		return $q.$ret.$q;
}

function tidy_obj($str,$options=array()) {
		if (!function_exists(tidy_parse_string)) return $str;
		$config = array(	'indent' => FALSE,
						'show-body-only' => TRUE,
						'output-xml' => TRUE,
						'wrap-script-literals'=>TRUE,
					   );
		$config=array_merge($config,$options);
		$tidy = tidy_parse_string($str, $config,'UTF8');
		$tidy->cleanRepair();
		return $tidy;
}


function tidy_html($str,$options=array()) {
		$tidy=tidy_obj($str,$options);		
		$txt=trim($tidy);
		//$txt=preg_replace('/&[a-zA-Z]+;/','',$txt);

		$txt=str_ireplace(array('&nbsp;'),'',$txt);

		return $txt;
}
function make_paragraph($a,$class="") {
		if ($class) $class="class=\"$class\"";
		return "<p $class>".PHP_EOL.trim($a).PHP_EOL."</p>";
}
function rec_autoformat($rec,$txtfield='text',$imgfield=null) {

		$odd=false;

		$txt=$rec->$txtfield;
		$txt=explode("\n",$txt);
		$txt=array_map('make_paragraph',$txt);

		if ($imgfield && $rec->$imgfield->count()>0) {
				//$images=$rec->$imgfield->img('prev');
				$images=array();
				foreach ($rec->$imgfield as $img) {
						$images[]=sprintf('<a href="%s" class="fancybox" rel="gallery%s%d"><img src="%s"></a>',
										$img->src1('large'),
										$rec->get_recordset_name(),
										$rec->get_id(),
										$img->src1('prev')
										);
				}
				$atxt=array();
				$cnt=ceil(0.5*count($txt)/$rec->$imgfield->count());
				$imgcnt=ceil($rec->$imgfield->count()/count($txt));
				reset($txt);
				do {
						for ($i=0; $i<$cnt; $i++) {
								array_push($atxt,current($txt));
								if (next($txt)===FALSE) break;
						}
						for ($i=0; $i<$imgcnt; $i++) {
								$ip=make_paragraph(current($images),$odd?"left":"right");
								$odd=!$odd;
								array_push($atxt,$ip);
								if (next($images)===FALSE) break;
						}
				} while (current($txt)!==FALSE);

				$txt=$atxt;

		}


		$txt=implode("\n",$txt);

		return $txt;
}

function base_domain() {
		$protocol = 'http';
		if ($_SERVER['SERVER_PORT'] == 443 || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')) {
				$protocol = 'https';
				$protocol_port = $_SERVER['SERVER_PORT'];
		} else {
				$protocol_port = 80;
		}
		$host = $_SERVER['HTTP_HOST'];
		$port = $_SERVER['SERVER_PORT'];
		$toret = $protocol . '://' . $host . ($port == $protocol_port ? '' : ':' . $port);
		return $toret;

}


function base_url() {
		$toret=base_domain();
		if (gs_var_storage::load('urlprefix')) $toret.=gs_var_storage::load('urlprefix');
		return $toret;
}
function current_url() {
		$request = $_SERVER['REQUEST_URI'];
		$query='';
		if (isset($_SERVER['argv'])) $query = substr($_SERVER['argv'][0], strpos($_SERVER['argv'][0], ';') + 1);
		$toret = base_domain() . $request . (empty($query) ? '' : '?' . $query);
		return $toret;
}



function va($n) {
		$tpl=gs_tpl::get_instance();
		$data=$tpl->getTemplateVars('_gsdata');
		return $data['gspgid_va'][$n];
}
function current_handler() {
		$tpl=gs_tpl::get_instance();
		$data=$tpl->getTemplateVars('_gsdata');
		return $data['handler_key'];
}
function ch() {
		return current_handler();
}
function current_handler_url() {
		return base_url().'/'.current_handler();
}

function beautify($out) {
		include_once('PHP/Beautifier.php');
		if (class_exists('PHP_Beautifier')) {
				$out=str_replace(array('{%','%}'),array('::tpl_ldelim::','::tpl_rdelim::'),$out);
				$oBeautifier = new PHP_Beautifier(); 
				$oBeautifier->addFilter('ArrayNested');
				$oBeautifier->addFilter('Pear',array('add_header'=>'php'));
				$oBeautifier->setIndentChar(' ');
				$oBeautifier->setIndentNumber(4);
				$oBeautifier->setNewLine("\n");
				$oBeautifier->setInputString($out); 
				$oBeautifier->process();
				$out=$oBeautifier->get();
				$out=str_replace(array('::tpl_ldelim::','::tpl_rdelim::'),array('{%','%}'),$out);
		}
		return $out;
}

function array_var_replace($arr,$variables) {
		$ret=array();
		$nk=array();
		$nv=array();
		foreach($variables as $k=>$v) {
				$nk[]='$'.$k;
				$nv[]=$v;
		}
		foreach ($arr as $k=>$v) {
				$ret[str_ireplace($nk,$nv,$k)] = str_ireplace($nk,$nv,$v);
		}
		return $ret;
}

function gs_setcookie($name,$new_id) {
		$_COOKIE[$name]=$new_id;
		$t=strtotime("now +".cfg('session_lifetime'));

		$path=cfg('www_dir');
		$domain=cfg('host');
		$e=implode('.',array_slice(explode('.',$domain),-2));

		$domains=array();
		array_push($domains,$domain);
		array_push($domains,'www.'.$domain);
		array_push($domains,$e);
		array_push($domains,'.'.$e);


		foreach($domains as $d) {
				setcookie($name,$new_id,$t,$path,$d);
		}
		setcookie($name,$new_id,$t,cfg('www_dir'));
}

function object_id($obj) {
		ob_start();
		var_dump($obj);
		$ret=ob_get_clean();
		preg_match('/^[^\s]+/',$ret,$m);
		$r=reset($m);
		if (is_a($obj,'gs_record')) $r.='#'.$obj->get_recordset_name();
		return $r;

}


function gs_date_interval($date1,$date2,$format="%r%a") {
		$iv=$date2->diff($date1);
		if (!$format) return $iv;
		$r=$iv->format($format);
		if ($r==0) return 0;
		return $r;
}	

function gs_date_from($d,$format="%r%a") {
		$date1 = new DateTime();
		$date2 = new DateTime($d);
		return gs_date_interval($date1,$date2,$format);
}	

function gs_date_to($d,$format="%r%a") {
		$date2 = new DateTime();
		$date1 = new DateTime($d);
		return gs_date_interval($date1,$date2,$format);
}	

function explode_data($data) {
		if (!is_array($data)) return $data;
		$newdata=array();
		foreach ($data as $k=>$v) {
				$s=explode(':',$k);
				while (($i=array_pop($s))!==NULL) {
						$dd=array();
						$dd[$i]=$v;
						$v=$dd;
				}
				$newdata=array_merge_recursive_distinct($newdata,$v);
		}
		$ret=array_merge($data,$newdata);
		return $ret;
}

