<?php
if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

class gsmtpl {
	private $vars=array();
	public $template_dir='';
	public $plugins_dir='plugins';
	public $compile_dir='tpl_c';
	public $left_delimiter='{';
	public $right_delimiter='}';
	private $assign=array();
	private $page=NULL;
	private $pages=array();

	function __construct() {
		$this->compile_dir=dirname(__FILE__).DS.$this->compile_dir;
		$this->plugins_dir=dirname(__FILE__).DS.$this->plugins_dir;
		if (!defined('SMARTY_PLUGINS_DIR')) define('SMARTY_PLUGINS_DIR',$this->plugins_dir.DS);
	}
	
	static function get_plugins_dir() {
		$o=new gsmtpl;
		return $o->plugins_dir;
	}
	
	public function assign ($name,$values='GSMTPL_NO_ARG') {
		if ($values=='GSMTPL_NO_ARG') {
			foreach ($name as $key => $value) {
				$this->assign[$key]=$value;
			}
		} else {
			$this->assign[$name]=$values;
		}
		foreach ($this->pages as $pg) {
			$pg->assign($name,$values);
		}
	}
	public function get_var($name) {
		return $this->page ? $this->page->get_var($name) : NULL;
	}
	
	public function display($name) {
		echo $this->fetch($name);
	}
	
	public function fetch($name) {
		$res='';
		$info=$this->load_template($name);
		$class_name='__gs_page_'.$info['id'];
		if (!isset($this->pages[$class_name])) {
			$this->pages[$class_name]=new $class_name($this->plugins_dir);
		}
		$this->page=$this->pages[$class_name];
		//$this->page=new $class_name($this->plugins_dir);
		// @ - ahtung!!!
		$res=@$this->page->main($this->assign);
		return stripslashes($res);
	}
	
	public function load_template($name) {
		$info=$this->get_source_info($name);
		if (!$this->is_compiled($info)) {
			$this->compile($info);
		}
		return $info;
	}
	
	private function compile($info) {
		$result=$this->load_source($info);
		$file=$this->compile_dir.DS.$info['type'].DS.$info['compile_id'];
		touch($file);
		$cpl=new gstpl_compiler($result,$info['id'],$this);
		$code=$cpl->get();
		file_put_contents($file,$code);
		include_once($file);
	}
	
	private function load_source($info) {
		if (class_exists('gstpl_source_'.$info['type'],false)) {
			$result=call_user_func(array('gstpl_source_'.$info['type'],'get_source'),$info['url']);
		}
		return $result;
	}
	
	private function mtime_source($info) {
		if (class_exists('gstpl_source_'.$info['type'],false)) {
			$result=call_user_func(array('gstpl_source_'.$info['type'],'get_source_mtime'),$info['url']);
		}
		return $result;
	}
	
	private function is_compiled($info) {
		$dir=$this->compile_dir.DS.$info['type'];
		check_and_create_dir($dir);
		if (file_exists($dir.DS.$info['compile_id']) && $this->mtime_source($info)<=filemtime($dir.DS.$info['compile_id'])) {
			//md('old version');
			include_once($dir.DS.$info['compile_id']);
			return true;
		}
		md('compile');
		return false;
	}
	
	private function is_abs_path($path) {
		$abs_path=realpath($path);
		$path=str_replace("/",DS,$path);
		return $path==$abs_path;
	}
	
	public function templateExists($file) {
		return ($this->get_file($file)) ? true : false;
	}
	
	private function find_file($file) {
		$ret=$this->get_file($file);
		if ($ret) return $ret;
		throw new gs_exception('gstpl: template '.$file.' not found');
	}
	
	private function get_file($file) {
		$rname=$file;
		if ($this->is_abs_path($file)) {
			if(!file_exists($file)) throw new gs_exception('gstpl: template '.$file.' not found');
			return $file;
		}
		if (is_string($this->template_dir)) {
			$file=realpath($this->template_dir.DS.$rname);
			if(!file_exists($file)) throw new gs_exception('gstpl: template '.$file.' not found');
			return $file;
		}
		foreach ($this->template_dir as $dir) {
			$file=realpath($dir.DS.$rname);
			if(file_exists($file)) return $file;
		}
		return null;
	}
	
	public function get_source_info($url) {
		preg_match_all("|^(([\w_]{2,})?:)?(.+)$|is",$url,$d);
		$info=array();
		$type=(!empty($d[2][0])) ? $d[2][0] : 'file';
		switch ($type) {
			case 'file':
				$url=$this->find_file($d[3][0]);
				$md5=md5($url);
				$id=preg_replace("|(.*)\.\w+$|i","\\1",basename($url)).'_'.$md5;
			break;
			default:
				$url=$d[3][0];
				$id=md5($url);
			break;
		}
		
		$info=array(
			'type'=>$type,
			'url'=>$url,
			'id'=>$id,
			'compile_id'=>$id.'.php',
			'compile_url'=>realpath($this->compile_dir.DS.$type).DS.$id.'.php',
			);
		return $info;
	}

	function getTemplateVars($name=NULL) {
		$ret=$this->page ? $this->page->assign : $this->assign ;
		if ($name===NULL) return $ret;
		return isset($ret[$name]) ? $ret[$name] : NULL;
	}

}

class gstpl_compiler {
	private $source='';
	private $code='';
	private $id='';
	private $ld='';
	private $rd='';
	private $extend_file=null;
	private $extend='gs_page_blank';
	private $methods=array();
	public $tpl=null;
	private $func_num=0;
	public $includes=array();
	private $o=null;
	
	private $reserved=array('block','capture','foreach','for','section','if','else','literal');
	
	function __construct($source,$id,$gstpl) {
		$this->id=$id;
		$this->source=$source;
		$this->ld=$gstpl->left_delimiter;
		$this->rd=$gstpl->right_delimiter;
		$this->tpl=$gstpl;
		$this->o=new gs_page_blank($this->tpl->plugins_dir);
	}
	
	// we need learn parse nested blocks
	private function parse_blocks ($blockname) {
		$blocks=array();
		$result=$this->code;
		$counter=0;
		$parts=array();
		$spos=strpos($result,$this->ld.$blockname,0);
		while($spos!==false) {
			$counter++;
			$len=strlen($this->ld.$blockname);
			$result=substr_replace($result,$this->ld.$blockname.':'.$counter,$spos,$len);
			$spos=strpos($result,$this->ld.$blockname,$spos+$len);
		}
		while ($counter>0) {
			$spos=strpos($result,$this->ld.$blockname.':'.$counter,0);
			$epos=strpos($result,$this->ld.'/'.$blockname.$this->rd,$spos);
			if ($epos===false) throw new gs_exception('gstpl: closed tag of section not found');
			$len=strlen($this->ld.'/'.$blockname.$this->rd);
			$result=substr_replace($result,$this->ld.'/'.$blockname.':'.$counter.$this->rd,$epos,$len);
			$blocks[$counter]['start']=$spos;
			$blocks[$counter]['end']=$epos+strlen($this->ld.'/'.$blockname.':'.$counter.$this->rd);
			$counter--;
		}
		$res=$result;
		foreach ($blocks as $i => $block) {
			$regexp=sprintf("|%s(%s:%d)(.*?)%s(.*?)%s/\\1%s|is",$this->ld,$blockname,$i,$this->rd,$this->ld,$this->rd);
			preg_match_all($regexp,$result,$out);
			$blocks[$i]['params']=string_to_params($out[2][0]);
			$blocks[$i]['params_string']=$out[2][0];
			if (!isset($blocks[$i]['params']['name'])) $blocks[$i]['params']['name']='default_'.$i;
			
			$blocks[$i]['mode']=isset($blocks[$i]['params']['mode']) ? $blocks[$i]['params']['mode'] : 'replace';
			
			$fname='compile_'.$blockname;
			$res=$this->$fname($blockname,$blocks[$i],$i,$res);
			$fname_filter='filter_'.$blockname;
			$blocks[$i]['code']=$this->$fname_filter($out[3][0]);
		}
		$this->add_method('main',$res,'main');
		foreach ($blocks as $i => $block) {
			foreach ($blocks as $j => $subblock) {
				$regexp=sprintf("|%s(%s:%d)(.*?)%s(.*?)%s/\\1%s|is",$this->ld,$blockname,$j,$this->rd,$this->ld,$this->rd);
				$params=preg_replace("|\s|is",'',var_export($subblock['params'],true));
				$fname='compile_'.$blockname;
				$blocks[$i]['code']=$this->$fname($blockname,$subblock,$j,$blocks[$i]['code']);
			}
			$this->add_method($blockname."_".$block['params']['name'],$blocks[$i]['code'],$block['mode']);
		}
		$this->code=$res;
	}
	
	private function filter_block($code) { 
		return $code;
	}
	
	private function filter_capture($code) {
		return $code;
	}
	
	private function filter_literal($code) {
		return sprintf("\$res.='%s';%s",addslashes($code),PHP_EOL);
	}
	
	private function compile_block($blockname,$block,$counter,$code) {
		$regexp=sprintf("|%s(%s:%d)(.*?)%s(.*?)%s/\\1%s|is",$this->ld,$blockname,$counter,$this->rd,$this->ld,$this->rd);
		$params=preg_replace("|\s|is",'',var_export($block['params'],true));
		return preg_replace($regexp,PHP_EOL."\$res.=\$this->".$blockname."_".$block['params']['name'].'('.$params.');'.PHP_EOL,$code);
	}
	
	private function compile_capture($blockname,$block,$counter,$code) {
		$regexp=sprintf("|%s(%s:%d)(.*?)%s(.*?)%s/\\1%s|is",$this->ld,$blockname,$counter,$this->rd,$this->ld,$this->rd);
		$params=preg_replace("|\s|is",'',var_export($block['params'],true));
		return preg_replace($regexp,PHP_EOL."\$this->assign(array('var'=>'".$block['params']['assign']."','value'=>\$this->".$blockname."_".$block['params']['name'].'('.$params.')));'.PHP_EOL,$code);
	}
	
	private function compile_literal($blockname,$block,$counter,$code) {
		$regexp=sprintf("|%s(%s:%d)(.*?)%s(.*?)%s/\\1%s|is",$this->ld,$blockname,$counter,$this->rd,$this->ld,$this->rd);
		$code=preg_replace($regexp,PHP_EOL.$this->ld.$blockname."_".$block['params']['name'].' '.$block['params_string'].$this->rd.PHP_EOL,$code);
		//$code=preg_replace($regexp,PHP_EOL.$this->ld.$blockname." name='".$block['params']['name']."' ".$block['params_string'].$this->rd.PHP_EOL,$code);
		return $code;
	}
	
	private function add_method($name,$code,$mode) {
		$this->methods[$name]['code']=$code;
		$this->methods[$name]['mode']=$mode;
	}
	
	
	private function make_class() {
		$includes='';
		foreach ($this->includes as $include => $key) {
			$includes.=sprintf("include_once('%s');\n",$key);
		}
		$str=sprintf("<?php\n\n%s\n\n%s\n\nclass __gs_page_%s extends %s {\n",$includes,(!empty($this->extend_file)) ? 'require_once("'.$this->extend_file.'");' : '',$this->id,$this->extend);
		foreach ($this->methods as $func => $info) {
			$str.=sprintf("\t%s function %s(\$params) {\n\t\t\$this->assign_vars(\$params);\n\t\t\$res='';%s\n\t\t%s\n%s\n\t\t
			
			return \$res;\n\t
			
			}\n\n",
					$func=='main' ? 'public' : 'protected',
					$func,
					$info['mode']=='prepend' ? "\n\t\t\$res.=parent::".$func."(\$params);" : "",
					($info['mode']=='main' && !empty($this->extend_file)) ? "\n\t\t\$res=parent::".$func."(\$params);" : str_replace("\n","\n\t\t",$info['code']),
					$info['mode']=='append' ? "\n\t\t\$res.=parent::".$func."(\$params);" : ""
			);
		}
		$str.="\n}\n?>";
		$str=preg_replace("|\n+|is","\n",$str);
		$this->code=$str;
	}
	
	function get() {
		$this->code=$this->source;
		$this->compile_comments();
		$this->parse_blocks('literal');
		$this->compile_html();
		$this->compile_extends();
		$this->code=$this->compile_strings($this->code);
		$this->compile_vars();
		$this->compile_functions();
		$this->compile_include();
		$this->compile_if();
		$this->compile_foreach();
		$this->compile_for();
		
		
		$this->parse_blocks('block');
		$this->parse_blocks('capture');
		//md($this->code);
		//$this->
		$this->make_class();
		return $this->code;
	}
	
	function compile_comments() {
		$regexp=sprintf("|%s\*(.*?)\*%s|is",$this->ld,$this->rd);
		$this->code=preg_replace($regexp,'',$this->code);
	}
	
	function compile_extends() {
		$res=$this->code;
		$regexp=sprintf("|%sextends(.*?)%s|is",$this->ld,$this->rd);
		$res=preg_replace_callback($regexp,array($this,'parse_extends'),$res);
		$this->code=$res;
	}
	
	function parse_extends($matches) {
		$params=string_to_params($matches[1]);
		if (!isset($params['file'])) throw new gs_exception('gstpl: method "extends" must have param "file"');
		$info=$this->tpl->load_template($params['file']);
		//$this->tpl->get_source_info($params['file']);
		$class_name=sprintf("__gs_page_%s",$info['id']);
		$this->extend=$class_name;
		$this->extend_file=$info['compile_id'];
		return '';
	}
	
	function compile_if() {
		$res=$this->code;
		$regexp=sprintf("|%sif(.*?)%s|is",$this->ld,$this->rd);
		$res=preg_replace_callback($regexp,array($this,'parse_if'),$res);
		$res=str_replace(sprintf("%selse%s",$this->ld,$this->rd),"} else {",$res);
		$res=str_replace(sprintf("%s/if%s",$this->ld,$this->rd),"}",$res);
		$this->code=$res;
	}
	
	function parse_if($matches) {
		$res=$matches[1];
		//$o=new gs_smarty_parser($this);
		//return $o->smarty_parser($matches[1]);
		$res=str_replace('$','$this->assign.',$matches[1]);
		$res=preg_replace("|\.([a-zA-Z0-9\_]*)|i","[\\1]",$res);
		$res=preg_replace("|\[([a-zA-Z_][a-zA-Z0-9_]*)\]|i","['\\1']",$res);
		
		return sprintf("\nif (%s) {\n",$res);
		return sprintf("\nif (%s) {\n",str_replace("\$","\$this->",$res));
	}
	
	function compile_foreach() {
		$res=$this->code;
		$regexp=sprintf("|%sforeach(.*?)%s|is",$this->ld,$this->rd);
		$res=preg_replace_callback($regexp,array($this,'parse_foreach'),$res);
		$res=str_replace(sprintf("%s/foreach%s",$this->ld,$this->rd),"}\n",$res);
		$this->code=$res;
	}
	
	function parse_foreach($matches) {
		$params=$this->string_to_params($matches[1]);
		$o=new gs_smarty_parser($this);
		$res=$o->smarty_parser($params['from']);
		if (isset($params['key'])) {
			return sprintf("\nforeach (%s as \$this->assign['%s'] => \$this->assign['%s']) {\n",$res,trim($params['key'],'"\''),trim($params['item'],'"\''));
		}
		return sprintf("\nforeach (%s as \$this->assign['%s']) {\n",$res,trim($params['item'],'"\''));
	}
	
	function compile_for() {
		$res=$this->code;
		$regexp=sprintf("|%sfor(.*?)%s|is",$this->ld,$this->rd);
		$res=preg_replace_callback($regexp,array($this,'parse_for'),$res);
		$res=str_replace(sprintf("%s/for%s",$this->ld,$this->rd),"}\n",$res);
		$this->code=$res;
	}
	
	function parse_for($matches) {
		$o=new gs_smarty_parser($this);
		$params=$this->string_to_params($matches[1]);
		reset($params);
		$key=key($params);
		$key_name=ltrim($key,'$');
		$assign=$o->smarty_parser($key);
		$start=$o->smarty_parser($params[$key]);
		$end=$o->smarty_parser($params[1]);
		$step=(isset($params[2])) ? $o->smarty_parser($params[3]) : 1;
		$res='';
		$for_var=sprintf('$this->assign[\'smarty\'][\'for\'][\'%s\']',$key_name);
		$res.=sprintf('%s%s[\'total\']=abs((%s-%s)/%s);',PHP_EOL,$for_var,$end,$start,$step);
		$res.=sprintf('%s%s[\'step\']=%s;',PHP_EOL,$for_var,$step);
		return $res.sprintf("\nfor (%s=%s,\$i=0;\$i<%s['total'];\$i++,%s+=%s['step']) {\n",$assign,$start,$for_var,$assign,$for_var);
	}
	
	function compile_strings($string) {
		$regexp=sprintf("|%s([\\\"](.*?)[\\\"](.*?))%s|is",$this->ld,$this->rd);
		$string=preg_replace_callback($regexp,array($this,'parse_string'),$string);
		return $string;
	}
	
	function parse_string($matches) {
		$o=new gs_smarty_parser($this);
		return sprintf("\n\$res.=%s;\n",$o->smarty_parser($matches[1]));
	}
	
	function compile_vars() {
		$res=$this->code;
		$regexp=sprintf("|%s(\\\$.*?)%s|is",$this->ld,$this->rd);
		$res=preg_replace_callback($regexp,array($this,'parse_var'),$res);
		$this->code=$res;
	}
	
	function parse_var($matches) {
		$o=new gs_smarty_parser($this);
		return sprintf("\n\$res.=%s;\n",$o->smarty_parser($matches[1]));
	}
	

	function string_to_params($inp) {
		$ret=array();
		$this->s2p=array();
		$inp=preg_replace_callback('|([\'"](.*?)[\'"])|is',array($this,'s2p_param'),$inp);
		$arr=explode(" ",$inp);
		$arr=array_filter ($arr);
		foreach ($arr as $k=>$s) {
			$pair=explode('=',$s);
			if (isset($pair[1])) {
				$f=substr($pair[1],0,1);
				$pair[1]=($f!='$' && $f!='"' && $f!='\'') ? "'".$pair[1]."'" : $pair[1];
				$ret[$pair[0]]=$pair[1];
			} else {
				$ret[]=$pair[0];
			}
		}
		foreach ($ret as $name => $var) {
			foreach($this->s2p as $key => $value) {
				$var=str_replace('$'.$key,$value,$var);
			}
			$ret[$name]=$var;
		}
		
		return $ret;
	}
	
	function s2p_param($matches) {
		$key=count($this->s2p);
		$this->s2p[$key]=$matches[0];
		return sprintf('$%d',$key);
	}

	function compile_functions() {
		$res=$this->code;
		$regexp=sprintf("|%s(.*?)%s|is",$this->ld,$this->rd);
		$res=preg_replace_callback($regexp,array($this,'parse_func'),$res);
		$this->code=$res;
	}
	
	function parse_func($matches) {
		$params=$this->string_to_params($matches[1]);
		$params[0]=ltrim($params[0],'/');
		if (strpos($params[0],'literal')!==false) {
			return sprintf('$res.=$this->%s();%s',$params[0],PHP_EOL);
		}
		if (in_array($params[0],$this->reserved)) {
			return $matches[0];
		}
		$func_name=$params[0];
		unset($params[0]);
		$o=new gs_smarty_parser($this);
		foreach ($params as $key => $value) {
			$value=$o->smarty_parser($value);
			$params[$key]=$value;
		}
		$params['_gsmtpl_id']=$this->func_num;
		$this->func_num++;
		$params=$this->make_params_string($params);
		$real_func_name=$this->call($func_name,$params);
		return sprintf("\n\$res.=%s;\n",$real_func_name);
	}
	
	function call($func,$params) {
		if (method_exists($this->o,'__'.$func)) {
			return sprintf('$this->__%s(%s)',$func,$params);
		}
		$func_file='function.'.$func.'.php';
		$func_full_file=$this->tpl->plugins_dir.DS.$func_file;
		$func_name=sprintf('smarty_function_%s',$func);
		if (!function_exists($func_name)) {
			if (file_exists ($func_full_file)) {
				$this->includes[$func_file]=$func_full_file;
				include_once($func_full_file);
			}
		}
		if (function_exists($func_name)) {
			return sprintf('%s(%s,$this)',$func_name,$params);
		}
		if (function_exists($func)) {
			return sprintf('%s(%s)',$func,$params);
		}
		
		return sprintf('$this->__%s(%s)',$func,$params);
	}
	
	private function make_params_string($params) {
		$d=array();
		foreach ($params as $key => $value) {
			$d[]='"'.$key.'" => '.$value;
		}
		return sprintf('array(%s)',implode(',',$d));
	}
	
	function compile_include() {
		$res=$this->code;
		$regexp=sprintf("|%s(.*?)%s|is",$this->ld,$this->rd);
		$res=preg_replace_callback($regexp,array($this,'parse_func'),$res);
		$this->code=$res;
	}
	
	function compile_html() {
		$res=$this->code;
		$spos=0;
		$epos=strpos($res,$this->ld,$spos);
		$s='';
		$len=strlen($res);
		do {
			if ($epos!==false) {
				$s.=sprintf("\$res.='%s';%s",addslashes(substr($res,$spos,$epos-$spos)),PHP_EOL);
				$spos=strpos($res,$this->rd,$epos)+strlen($this->rd);
				$s.=substr($res,$epos,$spos-$epos);
			} else {
				$epos=$len;
				$s.=sprintf("\$res.='%s';%s",addslashes(substr($res,$spos,$epos-$spos)),PHP_EOL);
				break;
			}
			$epos=strpos($res,$this->ld,$spos);
			
		} while($epos<$len-1);
		$this->code=$s;
	}
}


class gs_smarty_parser {
	var $tpl;
	var $parts=array();
	var $o=null;
	function __construct ($tpl) {
		$this->tpl=$tpl;
		$this->o=new gs_page_blank($tpl->tpl->plugins_dir);
	}
	
	function smarty_parser($string) {
		$this->parts=array('pattern'=>'','childs'=>array());
		$string=preg_replace_callback('/`(\$|"|\')(.*?)`/is',array($this,'escape'),$string);
		$string=preg_replace_callback('|"(.*?)"|is',array($this,'parse_single'),$string);
		$this->parts['pattern']=$string;
		foreach ($this->parts['childs'] as $key => $part) {
			$this->current_part=$key;
			$this->parts['childs'][$key]['pattern']=preg_replace_callback('/`(\$|\')(.*?)`/is',array($this,'parse_inline'),$part['pattern']);
			foreach ($this->parts['childs'][$key]['childs'] as $ckey => $children) {
				$this->current_subpart=$ckey;
				$this->parts['childs'][$key]['childs'][$ckey]['pattern']=preg_replace_callback("|'(.*?)'|is",array($this,'parse_mod_param'),$children['pattern']);
				$this->compile_modifiers($this->parts['childs'][$key]['childs'][$ckey]);
			}
			$this->compile_modifiers($this->parts['childs'][$key]);
		}
		$this->compile_modifiers($this->parts);
		return $this->parts['pattern'];
	}
	
	function compile_modifiers(&$res) {
		$mods=explode('|',$res['pattern']);
		for ($i=0;$i<count($mods)-1;$i++) {
			$params=explode(":",$mods[$i+1]);
			//$func='$this->_'.ltrim(array_shift($params),'@');
			$func=$this->call(ltrim(array_shift($params),'@'));
			array_unshift($params,$mods[$i]);
			if (!isset($res['parsed'])) {
				$params=array_map(array($this,'parse_array'),$params);
				$res['parsed']=true;
			}
			$mods[$i+1]=sprintf("%s(%s)",$func,implode(',',$params));
		}
		
		$tpl=end($mods);
		foreach ($res['childs'] as $key => $value) {
			if (!isset($value['parsed'])) {
				$value['pattern']=$this->parse_array($value['pattern']);
			}
			$tpl=str_replace('$'.$key,$value['pattern'],$tpl);
		}
		$res['pattern']=(empty($res['childs'])&& !isset($res['parsed'])) ? $this->parse_array($tpl) : $tpl;
		$res['parsed']=true;
	}
	
	function call($func) {
		$func_file='modifier.'.$func.'.php';
		$plugins_dir=gsmtpl::get_plugins_dir();
		$func_full_file=$plugins_dir.DS.$func_file;
		$func_name=sprintf('smarty_modifier_%s',$func);
		if (method_exists($this->o,'_'.$func)) {
			return sprintf('$this->_%s',$func);
		}
		
		if (!function_exists($func_name) && file_exists ($func_full_file)) {
			$this->tpl->includes[$func_file]=$func_full_file;
			include_once($func_full_file);
		}
		if (function_exists($func_name)) {
			return $func_name;
		}
		if (function_exists($func)) {
			return $func;
		}
	}
	
	function parse_array($v) {
		if (substr($v,0,1)!='$' || is_numeric(substr($v,1,1))) return $v;
		// Realise direct access to assign variable as element of array
		$v=str_replace('$','$this->assign.',$v);
		// Realise access to assign variable as property
		//$v=str_replace('$','$this->',$v);
		$v=preg_replace("|\.([^\.><=\[\|\,\-\+\*\/]*)|i","[\\1]",$v);
		
		return preg_replace("|\[([A-Za-z_][A-Za-z0-9\_]*)\]|i","['\\1']",$v);
	}
	
	function escape($matches) {
		return '`'.str_replace('"',"'",$matches[1].$matches[2]).'`';
	}
	
	function parse_single($matches) {
		$idx=count($this->parts['childs']);
		$res=preg_replace('|(.^`)\$([\w\d\_]*)|is','\\1`$\\2`',$matches[1]);
		$res=str_replace('``','`',$res);
		$this->parts['childs'][$idx]['pattern']='"'.$res.'"';
		$this->parts['childs'][$idx]['childs']=array();
		return '$'.$idx;
	}
	
	function parse_inline ($matches) {
		$idx=count($this->parts['childs'][$this->current_part]['childs']);
		$this->parts['childs'][$this->current_part]['childs'][$idx]['pattern']=$matches[1].$matches[2];
		$this->parts['childs'][$this->current_part]['childs'][$idx]['childs']=array();
		return sprintf('".$%d."',$idx);
	}
	
	function parse_mod_param($matches) {
		$idx=count($this->parts['childs'][$this->current_part]['childs'][$this->current_subpart]['childs']);
		$this->parts['childs'][$this->current_part]['childs'][$this->current_subpart]['childs'][$idx]['pattern']="'".$matches[1]."'";
		$this->parts['childs'][$this->current_part]['childs'][$this->current_subpart]['childs'][$idx]['childs']=array();
		return sprintf("$%d",$idx);
	}
}


class gs_page_blank {
	public $assign=array();
	public $plugins_dir;
	protected $cycle;
	
	function __construct($plugins_dir) {
		$this->plugins_dir=$plugins_dir;
		$this->assign['smarty']=array();
		$this->assign['smarty']['now']=time();
	}
	
	function __call($func,$params) {
		$mode='function';
		if (substr($func,0,1)!='_') {
			$params=reset($params);
		} else {
			$real_func=$func;
			$func=substr($func,1);
			$mode='modifier';
		}
		ob_start();
		$func_file=$this->plugins_dir.DS.$mode.'.'.$func.'.php';
		$func_name=sprintf('smarty_%s_%s',$mode,$func);
		if (!function_exists($func_name)) {
			if (file_exists ($func_file)) {
				include_once($func_file);
			}
		}
		if (function_exists($func_name)) {
			switch ($mode) {
				case 'function':
					$ret=$func_name($params,$this);
				break;
				case 'modifier':
					$ret=call_user_func_array($func_name,$params);
				break;
			}
			$ret_ob=ob_get_contents();
			ob_end_clean();
			return $ret.$ret_ob;
		}
		if (function_exists($func)) {
			return call_user_func_array($func,$params);
		}
	}
	
	function __get($name) {
		return isset($this->assign[$name]) ? $this->assign[$name] : null;
	}
	
	public function main($params) {
		$this->assign_vars($params);
	}
	
	public function __assign($name,$values='GSMTPL_NO_ARG') {
		if ($values=='GSMTPL_NO_ARG') {
			if (isset($name['var']) && isset($name['value'])) {
				if ($name['value']=='null') $name['value']=null;
				$this->assign[$name['var']]=$name['value'];
				return;
			}
			foreach ($name as $key => $value) {
				if ($value=='null') $value=null;
				$this->assign[$key]=$value;
			}
		} else {
			$this->assign[$name]=$values;
		}
	}
	
	public function assign ($name,$values='GSMTPL_NO_ARG') {
		return $this->__assign($name,$values);
	}
	
	protected function assign_vars($params) {
		if (!empty($params) && is_array($params)) {
			$this->assign=array_merge($this->assign,$params);
		}
	}
	
	function get_var($name) {
		return isset($this->assign[$name]) ? $this->assign[$name] : NULL;
	}
	
	function getTemplateVars($name=NULL) {
		if ($name===NULL) return $this->assign;
		return isset($this->assign[$name]) ? $this->assign[$name] : NULL;
	}
	
	
	function _default() {
		$params=func_get_args();
		return (empty($params[0])) ? $params[1] : $params[0];
	}
	
	function _cat($part1,$part2) {
		return $part1.$part2;
	}
	
	function _replace($subject,$search,$replace) {
		return str_replace($search,$replace,$subject);
	}
	
	function __cycle($params) {
		$cn='func'.$params['_gsmtpl_id'];
		if(!isset($this->cycle[$cn])) {
			$this->cycle[$cn]['idx']=1;
			$this->cycle[$cn]['values']=is_array($params['values']) ? $params['values'] : explode(',',$params['values']);
			$this->cycle[$cn]['cnt']=count($this->cycle[$cn]['values']);
		}
		$this->cycle[$cn]['idx']++;
		return $this->cycle[$cn]['values'][$this->cycle[$cn]['idx']%$this->cycle[$cn]['cnt']];
	}
	
	function __include($params) {
		$tpl=gs_tpl::get_instance();
		$file=trim($params['file'],'"\'');
		$tpl->assign($params);
		return $info=$tpl->fetch($file);
	}
	
	function __handler($params) {
		return gs_base_handler::process_handler($params,$this);
	}
	

}

abstract class gstpl_source {}

class gstpl_source_file extends gstpl_source {
	
	static function get_source($url) {
		return file_get_contents($url);
	}
	
	static function get_source_mtime($url) {
		return filemtime($url);
	}
}

class gstpl_source_string extends gstpl_source {
	
	static function get_source($url) {
		return $url;
	}
	
	static function get_source_mtime($url) {
		return 0;
	}
}
?>
