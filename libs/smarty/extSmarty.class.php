<?php

$config=gs_config::get_instance();
if (!class_exists('Smarty',FALSE)) load_file($config->lib_tpl_dir.'Smarty.class.php');

class gs_Smarty extends Smarty {

	public $_display_called = false;
	public $_validate_processed = false;
	public $_validate_error = false;
	public $_validate_error_fields = array();


	protected $_tpl_arr = array();
	function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null, $display = false, $merge_tpl_vars = true, $no_output_filter = false) {
		if(!is_string($template)) return parent::fetch($template, $cache_id , $compile_id , $parent, $display, $merge_tpl_vars, $no_output_filter);
		$id=md5($template);
		$dirs=$this->getTemplateDir();

		if (!isset($this->_tpl_arr[$id])) {
			if (!$this->templateExists($template)) {
				throw new gs_exception('gs_Smarty.fetch: can not find template file for '.$template);
			}
			$this->_tpl_arr[$id]=$this->createTemplate($template, $cache_id , $compile_id , $parent, $display);
		}
		$t=$this->_tpl_arr[$id];
		$t->assign($this->getTemplateVars());
		$_output=$t->fetch($t, $cache_id , $compile_id , $parent, $display, false, $no_output_filter);
		$this->_tpl_arr[$id]=$t;
		//$t->assign($this->getTemplateVars());
		return $_output;
	}
	function get_var($name) {
		$t=reset($this->_tpl_arr);
		return  ($t && isset($t->tpl_vars[$name])) ? $t->tpl_vars[$name]->value : NULL;
	}
	function multilang($tplname=4) {
		//mlog($tplname);
		$language=false;
		if (!$language) $language=gs_var_storage::load('multilanguage_lang');
		if (!$language) $language=gs_session::load('multilanguage_lang');
		if (!$language) $language=cfg('multilang_default_language');

		//mlog($language);

		if ($language) {
				$newtplname=dirname($tplname).DIRECTORY_SEPARATOR.$language.DIRECTORY_SEPARATOR.(basename($tplname));
				if (file_exists($newtplname)) {
					$tplname=$newtplname;
					$old_tpl_dir=$dir=$this->getTemplateDir();
					if (!is_array($dir)) $dir=array($dir);
					array_unshift($dir,'.',dirname($newtplname));
					$this->setTemplateDir($dir);
				}
		}
		//mlog($tplname);
		return $tplname;
	}

    function addTemplateDir($dir, $key = NULL) {
                $tpldir=$this->getTemplateDir();
                if (!is_array($tpldir)) $tpldir=array($tpldir);
                array_unshift($tpldir, $dir);
                $this->setTemplateDir($tpldir);
    }

}

class extSmarty extends gs_Smarty {}
