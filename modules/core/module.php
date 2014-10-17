<?php
class module extends gs_base_module implements gs_module {
	function __construct() {}
	
	function install() {
		$n=new tw_handlers;
		$n->install();
		$n=new tw_handlers_cache;
		$n->install();
	}
	
	static function get_handlers() {
		$data=array(
			'get'=>array(
				/*
				'/'=>array(
					'gs_base_handler.show:{name:index.html}',
					),
				*/	
				'/admin'=>'admin_handler.show:{name:admin_page.html}',
				'/admin/logout'=>array(
					  'admin_handler.post_logout:return:true',
					),
				'*'=>'gs_base_handler.show404:{name:404.html}',
			),
			'handler'=>array(
				'/admin/menu'=>'admin_handler.show_menu',
				'/admin/login'=>array(        
					  'admin_handler.check_login:return:true^show', 
					  'show'=> 'gs_base_handler.show:name:admin_login.html', 
				  ),              
				 '/admin/form/login'=>array(
					  'admin_handler.post_login:return:true:form_class:form_admin_login',
					  'gs_base_handler.redirect',
				  ),


				'/filter'=>'gs_filters_handler.init',
				'/filter/show'=>'gs_filters_handler.show',
				'/debug'=>'debug_handler.show',
			),
			'template' => array(
				'/admin/auth' => array(
					'session_login' => 'admin_handler.check_login:return:array&continue^auth_page',
					#'post_login' => 'admin_handler.post_login:classname:$classname:fields:$fields:__name:login_form_users.html:form_class:g_forms_html:return:gs_record&continue^auth_page',
					'auth_page' => 'gs_base_handler.show:name:admin_login.html',
					'continue' => 'gs_base_handler.nop:return:not_false',
					'$original_handlers',
				) ,
			),
			'wrapper' => array (	
				'/admin'=>array (
						'template.admin/auth',
					),
			),
		);
		return self::add_subdir($data,dirname(__file__));
	}
	static function gl($name,$record,$data) {
		switch ($name) {
			case 'save_cancel':
				return $data['handler_key_root'];
			case 'save_continue':
				return $data['gspgid_root'];
			case 'save_return':
				return $data['handler_key_root'];
			break;
			}
		return null;
	}
}


class form_admin_login extends g_forms_html {
	function __construct($hh,$params=array(),$data=array()) {
		$hh=array(
			'admin_user_name' => Array
				(
					'type' => 'input',
					'verbose_name'=>'login',
				),
			'admin_password' => Array
				(
					'type' => 'password',
					'verbose_name'=>'password',
				),

		 );
		 return parent::__construct($hh,$params,$data);
	}
}



class admin_handler extends gs_base_handler {
	function check_login($data) {
		if(cfg('multilang_admin_language')) cfg_set('multilang_default_language',cfg('multilang_admin_language'));
		if(cfg('multilang_admin_language_id')) cfg_set('multilang_default_language_id',cfg('multilang_admin_language_id'));
		$rec=gs_session::load('login_gs_admin');
		if(isset($this->data['handler_params']['assign'])) {
			gs_var_storage::save($this->data['handler_params']['assign'],$rec);
		}
		if(isset($this->params['assign'])) {
			gs_var_storage::save($this->params['assign'],$rec);
		}
		return $rec;

	}
	function post_logout($data) {
		//$rec=$this->check_login();
		gs_session::clear('login_gs_admin');
		html_redirect('/admin');
		return true;
	}

	function post_login($data) {
		$bh=new gs_base_handler($this->data,$this->params);
		$f=$bh->validate();
		if (!is_object($f) || !is_a($f,'g_forms')) return $f;

		$rec=FALSE;

		$d=$f->clean();
		if (cfg('admin_user_name')==$d['admin_user_name'] && cfg('admin_password')==$d['admin_password']) {
			$rec=$d;
		}
		if (!$rec) {
			$f->trigger_error('FORM_ERROR','LOGIN_ERROR');
			return $this->showform($f);
		}
		gs_session::save($rec,'login_gs_admin');
		return true;
	}
	function show_menu () {
		$init=new gs_init('auto');
		$init->load_modules();

		$cfg=gs_config::get_instance();
		$modules=$cfg->get_registered_modules();
		$pr_modules=array();
		if (cfg('modules_priority')) foreach (explode(',',cfg('modules_priority')) as $pm) {
			$pm='module_'.$pm;
			if (in_array($pm,$modules)) {
				$pr_modules[$pm]=$pm;
			}
		}
		$modules=array_merge($pr_modules,$modules);
		$menu=array();
		if (is_array($modules)) foreach ($modules as $m) {
			$mod=new $m;
			if (method_exists($mod,'get_menu') && $menuitem=$mod->get_menu()) {
				if (!is_array($menuitem)) $menuitem=array($menuitem);
				$menu=array_merge($menu,$menuitem);
			}
		}
		$tpl=gs_tpl::get_instance();
		$tpl->assign('menu',$menu);
		return $tpl->fetch('admin_menu.html');
	}
	
	function show($ret) {
		parent::show($ret);
		return false;
	}
	
	function deleteform() {
		$id=$this->data['gspgid_va'][0];
		$res=preg_replace("|/delete/\d+|is","/",$this->data['gspgid']);
		$rs=new $this->params['classname'];
		$rec=$rs->get_by_id($id);
		$rec->delete();
		$rec->commit();
		$query=array();
		parse_str(parse_url(cfg('referer'),PHP_URL_QUERY),$query);
		return html_redirect($res,$query);
	}
	
}


class form_admin extends  g_forms_html {
	function __construct($h,$data=array(),$rec=null)  {
		parent::__construct($h,$data,$rec);
		$this->view = new gs_glyph('helper',array('class'=>'table_admin'));
		$this->addNode(array_keys($h));
	}
	function addNode($name) {
		$this->view->addNode('helper',array('class'=>'tr'),$name);
	}
}
class form_table extends  g_forms_html {
	function __construct($h,$data=array(),$rec=null)  {
		parent::__construct($h,$data,$rec);
		$this->view = new gs_glyph('helper',array('class'=>'table_submit'));
		$this->addNode(array_keys($h));
	}
	function addNode($name) {
		$this->view->addNode('helper',array('class'=>'tr'),$name);
	}
}


class debug_handler extends gs_handler {
	function show($ret) {
		$tpl=gs_tpl::get_instance();
		$log=gs_logger::get_instance();
		$tpl->assign('gmessages',$log->gmessages());
		return $tpl->fetch('debug.html');
	}
}
?>
