<?php
abstract class gs_base_module {

	function install() {
//		foreach(array('tw_news','tw_news_stats') as $r){
//			$this->$r=new $r;
//			$this->$r->install();
//		}
	}
	static function add_subdir($data,$dir) {
		$dir=clean_path($dir).'/';
		$dir=str_replace(clean_path(cfg('lib_modules_dir')),'',$dir);
		$dir=str_replace(clean_path(cfg('modules_dir')),'',$dir);
		$subdir=trim($dir,'/');

		$d=array();
		foreach($data as $k=>$a) {
			foreach($a as $t=>$v) {
				if (strpos($t,'/')===0) {
					$d[$k][trim($t,'/')]=$v;
				} else {
					$d[$k][trim($subdir.'/'.$t,'/')]=$v;
				}
			}
		}
		return $d;
	}


	static function admin_auth($data,$params) {
		if (gs_var_storage::load('check_admin_auth')===FALSE) return true;
		gs_var_storage::save('check_admin_auth',FALSE);
		if (strpos($data['gspgid'],'admin')===0) {

			$admin_ip_access=cfg('admin_ip_access');
			if(is_array($admin_ip_access) && $admin_ip_access && !in_array($_SERVER['REMOTE_ADDR'],$admin_ip_access)) {
				$o=new admin_handler($data,array('name'=>'auth_error.html'));
				$o->show();
				return false;
			}
			$rec=gs_session::load('login_gs_admin');
			if (!$rec) {
				$o=new gs_base_handler($data,array('name'=>'admin_login.html'));
				$o->show(array());
				return false;
			}
		}
		gs_var_storage::save('check_admin_auth',TRUE);
		return true;
	}
	static function gl($name,$record,$data) {

		if (method_exists('gl',$name)) {
			$gl=new gl($record,$data,str_replace('module_','',get_called_class()));
			return $gl->$name();
		}

		return null;
	}

}

class gl {
	function __construct($record,$data,$module_name) {
		$this->module_name=$module_name;
		$this->record=$record;
		$this->data=$data;
		$tpl=gs_tpl::get_instance();
		$this->gs_data=$tpl->getTemplateVars('_gsdata');
		$this->root=isset($this->gs_data['handler_key_root']) ? $this->gs_data['handler_key_root'] : null;
		if (!$this->root) $this->root=$this->gs_data['handler_key'];
	}
	function gspgid() {
		return $this->data;
	}

	function save_cancel () {
		return $this->data['handler_key_root'];
	}

	function save_continue () {
		return $this->data['gspgid_root'];
	}

	function save_return () {
		return $this->data['handler_key_root'];
	}

	function rec_show() {
		return $this->module_name.'/'.$this->record->get_recordset_name().'/'.$this->record->get_id();
#return $this->root.'/'.$this->record->get_id();
	}
	function rec_urlkey() {
		if (!$this->record->urlkey) return $this->rec_show();
		return $this->record->get_recordset_name().'/'.$this->record->urlkey;
	}

	function rec_search() {
		return $this->rec_show();
	}


	function rec_create() {
		return $this->root.'/modify/0'.$this->__data_get().'#form';
	}
	function rec_edit() {
#return $this->module_name.'/'.$this->record->get_recordset_name().'/modify/'.$this->record->get_id().$this->__data_get().'#form';
		return $this->root.'/modify/'.$this->record->get_id().$this->__data_get();
	}
	function rec_copy() {
		return $this->root.'/copy/'.$this->record->get_id().$this->__data_get();
	}
	function rec_delete() {
		return $this->root.'/delete/'.$this->record->get_id().$this->__data_get();
	}

	function __data_get() {
		$ds=new gs_data_driver_get();
		$arr=$ds->import();
		unset($arr['gspgtype']);
		if($arr) return '?'.http_build_query($arr);
		return '';
	}
}

