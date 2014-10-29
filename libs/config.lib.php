<?php

DEFINE ('LOAD_CORE',1);
DEFINE ('LOAD_STORAGE',2);
DEFINE ('LOAD_TEMPLATES',4);
DEFINE ('LOAD_EXTRAS',8);
DEFINE ('DEBUG_LOAD_FILE',1);
DEFINE ('DEBUG_SQL',2);

date_default_timezone_set('GMT');

if (defined('DEBUG') && DEBUG) {
	//ini_set('display_errors','On');
	//error_reporting(E_ALL ^E_NOTICE);
	//error_reporting(E_ALL | E_STRICT);
}

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

class gs_config {
	public $root_dir;
	public $host;
	public $www_dir;
	public $www_admin_dir;
	public $index_filename;
	public $data_dir;
	public $script_dir;
	public $var_dir;
	public $cache_dir;
	public $lib_dir;
	public $lib_tpl_dir;
	public $lib_data_drivers_dir;
	public $lib_handlers_dir;
	public $lib_modules_dir;
	public $lib_distpackages_dir;
	public $lib_dbdrivers_dir;
	public $tpl_blocks;
	public $class_files=array();
	private $view;
	private $registered_gs_modules;
	
	
	function __construct()
	{
		if (!isset($_SERVER['REQUEST_METHOD'])) $_SERVER['REQUEST_METHOD']='UNKNOWN';
		if (!isset($_SERVER['HTTP_HOST'])) $_SERVER['HTTP_HOST']='localhost';
		if (!isset($_SERVER['REQUEST_URI'])) $_SERVER['REQUEST_URI']=clean_path(__FILE__);

		$this->host=$_SERVER['HTTP_HOST'];
		$this->root_dir=__FILE__;
		$this->root_dir=str_replace('phar://','',$this->root_dir);
		$this->root_dir=clean_path(dirname(dirname($this->root_dir))).'/';
		$this->root_dir=str_replace('\\','/',$this->root_dir);

		//$_document_root=clean_path(realpath($_SERVER['DOCUMENT_ROOT'])).'/';
		$_document_root=clean_path(realpath(dirname($_SERVER['SCRIPT_FILENAME']))).'/';
		$this->document_root=$_document_root;


		if (strpos($_document_root,$this->root_dir)===0 && $this->root_dir>$_document_root) {
			$this->www_dir='/'.trim(str_replace($_document_root,'',$this->root_dir),'/');
		} else {
			$this->www_dir='/';
		}

		$this->created_files_perm=0666;
		$this->created_dirs_perm=0777;


		$this->www_admin_dir=$this->www_dir.'admin/';
		$this->www_image_dir=$this->www_dir.'img/';
		$this->script_dir=rtrim(dirname($_SERVER['PHP_SELF']),'/').'/';
		$this->index_filename=$_SERVER['SCRIPT_NAME'];
		$this->referer= isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
		$this->referer_path= isset($_SERVER['HTTP_REFERER']) ?  preg_replace("|^$this->www_dir|",'',parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH)) : '';
		$this->lib_dir=strpos(__FILE__,'phar://')==0  ? pathinfo(__FILE__,PATHINFO_DIRNAME).'/' : $this->root_dir.'libs/';
		$this->var_dir=$this->root_dir.'var/';
		$this->img_dir=$this->root_dir.$this->www_image_dir;
		$this->log_dir=$this->var_dir.'log/';
		$this->log_file=NULL;//'gs.log';
		$this->cache_dir=$this->var_dir.'cache/';
		$this->session_lifetime='2 week';
		$this->tmp_dir=$this->var_dir.'tmp/';
		/*
		$this->data_dir=$this->root_dir.'data/';
		$this->tpl_data_dir_default=$this->data_dir.'templates/';
		$this->tpl_data_dir=$this->tpl_data_dir_default;
		*/
		$this->tpl_data_dir=$this->root_dir.'html';
		$this->tpl_var_dir=$this->var_dir.'templates_c'.DIRECTORY_SEPARATOR.basename($this->tpl_data_dir);
		if (isset($_SERVER['HTTP_HOST'])) {
			$this->tpl_var_dir=$this->var_dir.'templates_c'.DIRECTORY_SEPARATOR.$_SERVER['HTTP_HOST'].DIRECTORY_SEPARATOR.basename($this->tpl_data_dir);
		}

		$this->lib_tpl_dir=$this->lib_dir.'smarty/';
		$this->tpl_plugins_dir=$this->lib_tpl_dir.'plugins/';
		$this->controllers_dir=$this->lib_dir.'controllers/';
		$this->lib_data_drivers_dir=$this->lib_dir.'data_drivers/';
		$this->lib_handlers_dir=$this->root_dir.'handlers/';
		$this->lib_modules_dir=$this->root_dir.'modules/';
		$this->lib_distpackages_dir=$this->root_dir.'packages/';
		$this->lib_distsubmodules_dir=$this->root_dir.'packages/SUBMODULES/';
		$this->lib_dbdrivers_dir=$this->lib_dir.'dbdrivers/';

		$this->use_handler_cache=FALSE;
		$this->s_handler_cnt=0;


		//foreach(array($this->root_dir.'config.php',$this->lib_modules_dir.'config.php') as $cfg_filename) {
		$cfgfiles=array(
				$this->root_dir.'config.php',
		                dirname($this->root_dir).DIRECTORY_SEPARATOR.'config.php',
		                //dirname($this->document_root).DIRECTORY_SEPARATOR.'config.php',
				$this->lib_modules_dir.'config.php',
				);
		foreach($cfgfiles as $cfg_filename) {
			if (file_exists($cfg_filename)) {
					
				require_once($cfg_filename);
			}
		}

		if (!defined('DEBUG')) define('DEBUG',FALSE);
		if (!defined('DEBUG_LEVEL')) define('DEBUG_LEVEL',65537);
		if (DEBUG) {
			//ini_set('display_errors','On');
			//error_reporting(E_ALL ^E_NOTICE);
			//error_reporting(E_ALL | E_STRICT);
			set_exception_handler('gs_exception_handler_debug');
		}



	}

	function check_install_key() {
        if (PHP_SAPI!='cli' &&
            ( !isset($this->install_key) || empty($this->install_key) || !isset($_REQUEST['install_key']) || $this->install_key!=$_REQUEST['install_key'] ))

			throw new gs_exception('Incorrect install_key. Check config.php and run '.$this->host.'/install.php?install_key=12345 to continue. Install key could be found in the config.php file');
	}

	function register_module($name) {
		$this->registered_gs_modules[$name]=$name;
	}
	function get_registered_modules() {
		return $this->registered_gs_modules;
	}

	function set_view($view) {
		/*
		if ($this->tpl_data_dir==$this->tpl_data_dir_default) {
			$this->tpl_data_dir=$this->data_dir.'templates/'.$view;
			$this->tpl_var_dir=$this->var_dir.'templates_c/'.$view;
		}
		cfg_set('_gs_view',$view);
		*/
	}

	
	static function &get_instance()
	{
		static $instance;
		if (!isset($instance)) $instance = new gs_config;
		return $instance;
	}
}

class gs_init {
	
	public $config;
	public $tpl;
	public $data;
	private $view;
	
	function __construct()
	{
		$this->config=gs_config::get_instance();
	}
	
	
	public function init($mode)
	{
		if ($mode & LOAD_CORE) {
			$this->load_core();
			$o_data=new gs_data;
			$this->data=$o_data->get_data();
		}
		if ($mode & LOAD_STORAGE) {
			$this->load_storage();
		}
		if ($mode & LOAD_TEMPLATES) {
			$this->load_templates();
		}
		if ($mode & LOAD_EXTRAS) {
			$this->load_extras();
		}
		check_and_create_dir(cfg('tpl_var_dir'));
	}

	function h_sort($a,$b) {
		$c1=preg_match_all('|/|',$a,$c);
		$c2=preg_match_all('|/|',$b,$c);
		return $c1==$c2 ? strcmp($a,$b) : $c1-$c2;
	}

    function clear_handlers() {
		gs_cacher::clear('handlers','config');
		gs_cacher::clear('url_handler_routes','config');
		gs_cacher::clear('urlprefix_cfg','config');
    }

	function save_handlers() {
		gs_cacher::clear('classes','config');
        $this->clear_handlers();
		$o_h=new gs_parser();
		$handlers=$o_h->get_registered_handlers();
		foreach ($handlers as $k=>$h) {
			uksort($h,array($this,'h_sort'));
			$handlers[$k]=$h;
		}

		
		gs_cacher::save($handlers,'config','handlers');

		$txt="\n";
		foreach ($handlers as $type=>$h) {
			foreach ($h as $key=>$arr) {
				$prefix="\n\t";
				$txt.=sprintf("%s:%s => $prefix%s\n\n",
						$type,$key,
						implode($prefix,$arr['handlers']));
			}
		}
		md($txt);
		file_put_contents_perm(cfg('var_dir').DIRECTORY_SEPARATOR.'urls_handlers.txt',$txt);


		$cl_array=array();

		$classes=get_declared_classes();
		foreach ($classes as $cl) {
			$r=new ReflectionClass($cl);
			if($r->getFileName()) $cl_array[$cl]=$r->getFileName();
		}
		mlog($cl_array);
		gs_cacher::save($cl_array,'config','classes');
		file_put_contents_perm(cfg('var_dir').DIRECTORY_SEPARATOR.'classes.txt',md($cl_array));
	}

	function check_compile_modules($path='') {
		$modified=false;
		$dir=$this->config->lib_modules_dir;
		$subdirs=array();
		if (cfg('modules_priority')) $subdirs=glob($dir.$path.sprintf('{%s}',cfg('modules_priority')),GLOB_BRACE+GLOB_ONLYDIR);
		$subdirs=array_unique(array_merge($subdirs,glob($dir.$path.'*',GLOB_ONLYDIR)));
		$dir=rtrim($dir,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		$path=trim($path,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
		foreach ($subdirs as $s) {
			if ($this->check_compile_modules($path.basename($s).DIRECTORY_SEPARATOR)) return true;
		}
		
		$files=glob($dir.$path.'*.phps');
		/*
		$tpldir=$dir.$path.'templates';
		$tplcdir=$dir.$path.DIRECTORY_SEPARATOR.'___templates';
		*/
		foreach ($files as $f) {
			$pf=str_replace(basename($f),'___'.basename($f),$f);
			$pf=preg_replace('/.phps$/','.xphp',$pf);
			if (!file_exists($pf) || filemtime($pf) < filemtime($f)) return true;


			/*
			if (file_exists($tpldir)) {
				check_and_create_dir($tplcdir);
				$mtime=filemtime($tpldir);
				$mctime=filemtime($tplcdir);
				$tpls=glob($tpldir.DIRECTORY_SEPARATOR.'*');
				foreach ($tpls as $t) {
					$mt=filemtime($t);
					$mct=file_exists($tplcdir.DIRECTORY_SEPARATOR.basename($t)) ? filemtime($tplcdir.DIRECTORY_SEPARATOR.basename($t)) : $mt+1;
					if ($mt>$mct) return true;
				}
			}
			*/
			
		}
		return false;
	}

	function compile_modules($path='') {
		//throw new gs_exception('gs_base_handler.show: empty params[name]');
		mlog('COMPILE_MODULES in ' .$path);
		$tpl=null;
		$data=array('LINKS'=>array(),'FIELDS'=>array());
		$ret=array();
		$dir=$this->config->lib_modules_dir;
		$subdirs=glob($dir.$path.'*',GLOB_ONLYDIR);
		foreach ($subdirs as $s) {
			$d=$this->compile_modules($path.basename($s).DIRECTORY_SEPARATOR);
			$data=array_merge_recursive($data,$d);
		}
		$files=glob($dir.$path.'*.phps');
		$module_name=str_replace(DIRECTORY_SEPARATOR,'_',trim($path,DIRECTORY_SEPARATOR));
		$module_dir_name=str_replace(DIRECTORY_SEPARATOR,'_',basename($path,DIRECTORY_SEPARATOR));
		$parent_module=str_replace(DIRECTORY_SEPARATOR,'_',dirname($path));
		if (empty($files)) return $ret;
		$tpl=new gs_tpl();
		$tpl=$tpl->init();

		$ldelim=$tpl->left_delimiter;
		$rdelim=$tpl->right_delimiter;
		$tpl->left_delimiter='{%';
		$tpl->right_delimiter='%}';

		$tpl->assign('MODULE_NAME','_'.$module_name);
		//$tpl->assign('MODULE',$module_name);
		$tpl->assign('MODULE',$module_dir_name);
		$tpl->assign('PARENT_MODULE',$parent_module);
		$tpl->assign('PARENT_RECORDSET','tw_'.$parent_module);
		$tpl->assign('SUBMODULE_NAME',basename($path));
		$tpl->assign('SUBMODULES_DATA',$data);
		foreach ($files as $f) {
			$pf=str_replace(basename($f),'___'.basename($f),$f);
			$pf=preg_replace('/.phps$/','.xphp',$pf);
			
			/*$s=file_get_contents($f);
			$s=$tpl->fetch('string:'.$s);*/
			$s=$tpl->fetch('file:'.$f);
			if ($tpl->get_var('DATA')) {
				$r=$tpl->get_var('DATA');
				//$r=array_filter(array_map('trim',explode(PHP_EOL,$r)));
				preg_match_all('|(\w+)::(.+?)::(.*)|i',$r,$r);
				foreach ($r[0] as $k=>$v) {
					$ret[$r[1][$k]][$r[2][$k]]=trim($r[3][$k]);
					$ret['MODULE'][$module_dir_name][$r[1][$k]][$r[2][$k]]=trim($r[3][$k]);
				}
			}
			file_put_contents_perm($pf,$s);
		}
		/*
		$tpldir=$dir.$path.'templates';
		$tplcdir=$dir.$path.'___templates';
		if (file_exists($tpldir)) {
			rrmdir($tplcdir);
			check_and_create_dir($tplcdir);
			@touch($tplcdir);
			$files=glob($tpldir.DIRECTORY_SEPARATOR.'*');
			foreach ($files as $f) {
				$pf=$tplcdir.DIRECTORY_SEPARATOR.basename($f);
				if (is_dir($f)) {
					copy_directory($f,$pf);
				} else {
					$s=$tpl->fetch($f);
					if(file_put_contents_perm($pf,$s)===FALSE) {
						throw new gs_exception('Can`t copy template '.$f.' into '.$pf);
					}
				}
			}
		}
		*/
		$tpl->clearCompiledTemplate();
		$tpl->left_delimiter=$ldelim;
		$tpl->right_delimiter=$rdelim;
		return $ret;
	}


	public function clear_cache() {
		gs_cacher::clear('classes','config');
		gs_cacher::clear('handlers','config');
		gs_cacher::clear('url_handler_routes','config');
		gs_cacher::clear('urlprefix_cfg','config');
		rrmdir(cfg('tpl_var_dir'));
	}


	public function load_modules($mask='*module.{php,xphp}') {
        /*
		if ($this->check_compile_modules()) {
			$init=new gs_init('user');
			$init->init(LOAD_CORE | LOAD_STORAGE | LOAD_TEMPLATES | LOAD_EXTRAS);
			$this->compile_modules();
			$this->save_handlers();
		}
        */

		$path=$this->config->lib_modules_dir;
		while (($files = glob($path.$mask,GLOB_BRACE)) && !empty($files)) {
			$classes=get_declared_classes();
			foreach ($files as $f) {
				load_file($f);
				$nc=array_diff(get_declared_classes(),$classes);
				foreach($nc as $c) $this->config->class_files[$c]=$f;
				$classes=get_declared_classes();
			}
			$path.='*'.DIRECTORY_SEPARATOR;
		}
		$cfg=gs_config::get_instance();
		$loaded_classes=get_declared_classes();
		foreach ($loaded_classes as $classname) {
			$refl= new ReflectionClass($classname);
			$interfaces=$refl->getInterfaces();
			if (isset($interfaces['gs_module']) && !$refl->isAbstract()) {
				$cfg->register_module($classname);
			}
		}
	}
	public function install_modules() {
		$cfg=gs_config::get_instance();
		$modules=$cfg->get_registered_modules();
		if (is_array($modules)) foreach ($modules as $m) {
			md($m);
			$mod=new $m;
			$mod->install();
		}
	}

	
	public function load_templates()
	{
		load_file($this->config->lib_dir.'tpl.lib.php');
		load_file($this->config->lib_dir.'forms.lib.php');
		load_file($this->config->lib_dir.'forms_interact.lib.php');
		load_file($this->config->lib_dir.'widgets.lib.php');
		$widgets=glob($this->config->lib_dir.'widgets'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'widget.php');
		foreach($widgets as $w) load_file($w);
		load_file($this->config->lib_dir.'helpers.lib.php');
		load_file($this->config->lib_dir.'dict.lib.php');
		load_file($this->config->lib_dir.'filters.lib.php');
	}
	public function load_core()
	{
		//load_file($this->config->lib_dir.'__all.php'); return;

		load_file($this->config->lib_dir.'compat.lib.php');
		load_file($this->config->lib_dir.'core.lib.php');
		load_file($this->config->lib_dir.'parser.lib.php');
		load_file($this->config->lib_dir.'module.lib.php');
		load_file($this->config->lib_dir.'handler.lib.php');
		load_file($this->config->lib_dir.'functions.lib.php');
		load_file($this->config->lib_dir.'eventer.lib.php');
	}

	public function load_storage() {
		load_file($this->config->lib_dir.'fkey.lib.php');
		load_file($this->config->lib_dir.'indexator.lib.php');
		load_file($this->config->lib_dir.'record.lib.php');
		load_file($this->config->lib_dir.'storage.lib.php');
		load_file($this->config->lib_dir.'recordset.lib.php');
		load_file($this->config->lib_dir.'recordset_handler.lib.php');
	}

	public function load_extras() {
		load_file($this->config->lib_dir.'vpa_mail.lib.php');
		load_file($this->config->lib_dir.'vpa_normalizator.lib.php');
		load_file($this->config->lib_dir.'validator.lib.php');
		load_file($this->config->lib_dir.'newvalidator.lib.php');
		load_file($this->config->lib_dir.'vpa_gd.lib.php');
		load_file($this->config->lib_dir.'tpl_static.lib.php');
	}
	
	
}


function cfg_set($name,$value) {
	$config=gs_config::get_instance();
	$config->$name=$value;
	return cfg($name);
}

function cfg($name) {
	$config=gs_config::get_instance();
	if (isset($config->$name)) return $config->$name ;
	if (method_exists($config,$name)) return $config->$name();
	return NULL ;
}
function mlog($data,$debug_level=255) {


    gs_logger::udplog($data);

	if (defined('DEBUG') && DEBUG && ($debug_level & DEBUG_LEVEL)) {
		$log=gs_logger::get_instance();
		$txt=$log->log($data);

		/*
		$trace=debug_backtrace();
		$caller=$trace[0];
		$caller_file=basename($caller['file']);
		$log->log_to_file($data,$caller_file);
		*/
		return $txt;
    }
	return $data;
}
function md($output,$type=1)
{
	if ($type) {
		$txt=htmlentities(print_r($output,true));
		echo "<pre>\n".$txt."</pre>\n";
	}
	return mlog($output);
}
class gs_logger {
	
	private $messages=array();	
	private $gmessages=array();
	private $t,$tt;
	static $udplog_addr=NULL;
	function __construct() {
		$this->tt=$this->time_start=microtime(true);
	}
	static function &get_instance()
	{
		static $instance;
		if (!isset($instance))
		{
			$instance = new gs_logger();
		}
		return $instance;
	}
	static function udplog($msg) {
        if (!defined('UDP_DEBUG') || !UDP_DEBUG) return;
		if (! self::$udplog_addr) self::$udplog_addr=gethostbyname('ping.phpbee.org');
		$fp = fsockopen("udp://".self::$udplog_addr, 8080, $errno, $errstr);
		if ($fp) {
			fwrite($fp, "$msg");
			fclose($fp);
		}
	}
	function log($data,$prefix='') {
		$backtrace = debug_backtrace();
		foreach ($backtrace as $trace) {
			if (isset($trace['file']) && $trace['file']!==__FILE__ && isset($trace['class'])) break;
		}
		if (!isset($trace['class'])) $trace['class']="_unknkwn_";

		/*
		ob_start();
		print_r($data);
		$txt=ob_get_contents();
		ob_end_clean();
		*/
		$txt=@trim($data);

		$t=microtime(true);
		$txt_time=sprintf("%.3f/%.4f",$t-$this->time_start,$t-$this->tt);
		$txt_class=sprintf("%s.%s",$trace['class'],$trace['function']);
		$classname=$trace['class'];
		$funcname=$trace['function'];
		$this->messages[]=sprintf("%s [%s] > %s",$txt_time,$txt_class,$txt);
		$this->gmessages[$classname][$funcname][]=sprintf("%s\t> %s",$txt_time,$txt);


		/*
		foreach ($backtrace as $trace) {
				$txt=sprintf("%-20s %s:%s",$trace['class'].'.'.$trace['function'],$trace['line'],$trace['file']);
				$this->gmessages[$classname][$funcname][]=sprintf("\t\t\t> %s",$txt);
		}
		*/
		$this->gmessages[$classname][$funcname][]="";

		$this->tt=$t;
		$this->log_to_file($data);
        self::udplog($txt);
		return $txt;
	}
	function log_to_file($data,$prefix='') {
		if (cfg('log_file')) {
			check_and_create_dir(cfg('log_dir'));
			ob_start();
			print_r($data);
			$txt=ob_get_contents();
			ob_end_clean();
			
			$prefix=basename($prefix);
			if ($prefix) $prefix=$prefix.'.';

			file_put_contents_perm(cfg('log_dir').$prefix.cfg('log_file'),$txt."\n\n",FILE_APPEND);
		}
	}
	function show() {
		if (!defined('LOG_CONSOLE') || !LOG_CONSOLE) return '';
		mlog(sprintf('total time: %.4f seconds',microtime(TRUE)-$this->time_start));
		$ret='';
		if (is_array($this->messages)) foreach ($this->messages as $msg) {
			ob_start();
			print_r($msg);
			$txt=ob_get_contents();
			ob_end_clean();
			$txt=preg_replace("/\n/",'\\r\\n',addslashes($txt));
			$ret.="console.log('$txt');\n";
		}
		return $ret;
	}
	static function dump() {
		$log=gs_logger::get_instance();
		$txt2 = $log->show();
		echo "<pre>\n";
		foreach ($log->messages as $msg) {
			ob_start();
			print_r($msg);
			$txt=ob_get_contents();
			ob_end_clean();
			echo htmlentities($txt)."\n";
		}
		echo "\n<pre>";
	}
	function gmessages() {
		return $this->gmessages;
	}
	static function console() {
		$log=gs_logger::get_instance();
		$txt2 = $log->show();
		echo <<<TXT
<script type="text/javascript">
if (typeof console == 'object') {
	$txt2;
}
</script>
TXT;
	}
	/*
	function __destruct() {
		$this->dump();
		ob_end_flush();
	}
	*/
}

function gs_exception_handler_debug($ex)
{
	//if (in_array('xdebug',get_loaded_extensions())) throw $ex;
	md('');
	md("EXCEPTION ".get_class($ex)." ".$ex->getFile().":".$ex->getLine());
	md($ex->getMessage());
	$trace=$ex->getTrace();
	foreach ($trace as $k=>$t) {
		foreach ($t['args'] as $j=>$a) {
			if (is_a($a,'gs_record') || is_a($a,'gs_recordset')) {
				$trace[$k]['args'][$j]=get_class($a);
			}
		}
		//unset($trace[$k]['args']);
	}

	md($trace);
	gs_logger::dump();
}
function gs_exception_handler($ex)
{
		echo '<link rel="stylesheet" type="text/css" href="/css/main.css" media="screen" />';
		echo '<div class="gs_exception">'.$ex->getMessage().'</div>';
		die();
}

function load_dbdriver($name) {
	if (class_exists('gs_dbdriver_'.$name,FALSE)) return;
	$cfg=gs_config::get_instance();
	$name=gs_validator::validate('plain_word',$name);
	load_file($cfg->lib_dbdrivers_dir.$name.'.driver.php');
}
function check_and_create_dir($dir) {
		if (!file_exists($dir)) {
			if (!mkdir($dir,0777,TRUE)) {
				throw new gs_exception('check_and_create_dir: '.$dir.'  can not create directory');
			}
			chmod($dir,cfg('created_dirs_perm'));

		} else if (!is_writable($dir)) {
			if (!is_dir($dir)) {
				throw new gs_exception('check_and_create_dir: '.$dir.'  is not a directory');
			}
			throw new gs_exception('check_and_create_dir: '.$dir.'   not writeble');
		}
		return $dir;
}

function file_put_contents_perm($filename,$data,$flags = 0, $context = NULL) {
	$ret= ($context===NULL) ? file_put_contents($filename,$data,$flags) : file_put_contents($filename,$data,$flags = 0, $context );
	if ($ret) @chmod($filename,cfg('created_files_perm'));
	return $ret;

}

function load_file($file,$return_contents=FALSE,$return_file=FALSE)
{
	//mlog('LOAD_FILE '.$file,DEBUG_LOAD_FILE);
	if (!file_exists($file))
	{
		throw new gs_exception('load_file: '.$file.'  not found');
	}
	if ($return_contents) return unserialize(file_get_contents($file));
	if ($return_file) return file_get_contents($file);
	require_once($file);
}


function clean_path($path) {
	$path=str_replace('\\','/',$path);
	return $path;
}

function stripslashes_deep($value)
{
	$value = is_array($value) ?
		array_map('stripslashes_deep', $value) :
		stripslashes($value);

	return $value;
}

class gs_exception extends Exception {

}

class gs_var_storage {
	private $arr=array();
	static function &get_instance()
	{
		static $instance;
		if (!isset($instance)) $instance = new gs_var_storage();
		return $instance;
	}
	static function genid($id) {
		return md5($id);
	}
	static function save($id,$value) {
		$id=self::genid($id);
		$t=gs_var_storage::get_instance();
		$t->arr[$id]=$value;
	}
	static function load($id) {
		$id=self::genid($id);
		$t=gs_var_storage::get_instance();
		$ret=isset($t->arr[$id]) ? $t->arr[$id] : NULL;
		return $ret;
	}
}


function __class_filename($class_name) {
	$classes=gs_cacher::load('classes','config');
	if (!$classes) return null;
	return (array_key_exists($class_name,$classes)) ? $classes[$class_name] : null;
}


function __gs_autoload($class_name) {
	$filename=__class_filename($class_name);
	if($filename && strpos($class_name,'_smarty')!==0) load_file($filename);
	//if (!class_exists($class_name)) gs_logger::udplog(new exception( "$class_name not loaded"));
}

spl_autoload_register('__gs_autoload');

set_exception_handler('gs_exception_handler');



?>
