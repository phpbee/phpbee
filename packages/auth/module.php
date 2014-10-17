<?php
/*gs_dict::append(array(
	''=>'',
));*/


class module_auth extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array('tw_users') as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		return '<a href="/admin/users/">Пользователи</a>';
	}
	
	static function get_handlers() {
		$data=array(
		'get'=>array(
			'registration'=>array(
					'auth_handler.checklogin:{return:gs_record&redirect^show}',
					'show'=>'gs_base_handler.show:{name:registration.html}',
					'end',
					'redirect'=>'gs_base_handler.redirect:{href:/auth/profile}',
					),
			'profile'=>array(
					'auth_handler.checklogin:{return:gs_record^redirect}',
					'gs_base_handler.show:{name:profile.html}',
					'end',
					'redirect'=>'gs_base_handler.redirect:{href:/auth/registration}',
					),
			'profile/update'=>array(
					'auth_handler.checklogin:{return:gs_record^redirect}',
					'gs_base_handler.show:{name:profile_update.html}',
					),
			'/admin/users'=>'gs_base_handler.show:{name:adm_users.html:classname:tw_users}',
			'/admin/users/delete'=>'admin_handler.deleteform:{classname:tw_users}',
			'logout'=>array(
					'auth_handler.logout',
					'gs_base_handler.redirect',
					),
		),
		'handler'=>array(
			'formlogin'=>array(
					'auth_handler.checklogin:{return:gs_record^gspgtype}',
					'gs_base_handler.fetch:{name:welcome.html}',
					'end',
					'gspgtype'=>'gs_base_handler.is_post:{return:true^showform}',
					'login'=>'auth_handler.login:{return:gs_record^showform:name:form_login.html:form_class:g_form_auth_login:classname:tw_users}',
					'gs_base_handler.redirect',
					'showform'=>'auth_handler.showauthform:{return:gs_record:name:form_login.html:form_class:g_form_auth_login:classname:tw_users}',
					),
			'form/profile'=>array(
						'gs_base_handler.post:{return:gs_record:name:form.html:classname:tw_users}',
						'auth_handler.logout',
						'auth_handler.login:{return:gs_record:form_class:g_form_auth_login:classname:tw_users}',
						'gs_base_handler.redirect:{href:/auth/profile}',
						),
			'form/registration'=>array(
						'post'=>'gs_base_handler.post:{return:gs_record:name:form.html:classname:tw_users}',

						'user_email'=>'auth_handler.user_email:hkey:post',
						'user_txt'=>'gs_base_handler.fetch:name:email_user_text.html:hkey:post',
						'admin_txt'=>'gs_base_handler.fetch:name:email_admin_text.html:hkey:post',

						'gs_base_handler.send_email:txt:user_txt:hkey:user_email',
						'gs_base_handler.send_email:txt:admin_txt:email:alex@kochetov.com',

						'login'=>'auth_handler.login:{return:gs_record:form_class:g_form_auth_login:classname:tw_users}',
						'gs_base_handler.redirect:{href:/auth/profile}',
						),
			'/admin/form/tw_users'=>'gs_base_handler.postform:{name:form.html:form_class:g_forms_table:classname:tw_users:href:/admin/users:form_class:form_admin}',
		),
	);
	return self::add_subdir($data,dirname(__file__));
	}
}

class auth_handler extends gs_base_handler {

	function user_email($data) {
		$user=$this->hpar($data);
		return array($user->login);
	}
	
	/*function _auth($ret=false) {
        $user=gs_session::load('user');
        if(!$user) {
                return $this->fetch();
        }
        $user=record_by_id($user,'tw_users');
        if($ret) return $user;
        $tpl=gs_tpl::get_instance();
        $tpl->assign('user',$user);
    }*/
	
	function logout() {
		gs_session::clear('user');
	}
	function checklogin() {
		$user=gs_session::load('user');
		$tpl=gs_tpl::get_instance();
		$tpl->assign('user',$user);
		return $user;
	}
	function login() {
        $f=$this->get_form();
        $validate=$f->validate();
        if ($validate['STATUS']===true) {
            $u=new tw_users();
            $c=$f->clean();
            $user=$u->find_records(array('login'=>$c['login'],'pass'=>$c['pass'],'active'=>1))->first();
            if($user) {
		$user->get_values();
                gs_session::save($user,'user');
                return $user;
            }
        }
        $tpl=gs_tpl::get_instance();
        $tpl->assign('validate',$validate);
        $tpl->assign('error',1);
	return false;
    }
	
	function showauthform() {
		return $this->showform('as_inline');
    }
}

class tw_users extends gs_recordset_short {
	const superadmin = 1;
	function __construct($init_opts=false) { parent::__construct(array(
		'login'=> "fEmail E-mail unique=true",
		'pass'=> "fPassword 'Пароль'",
		'Profile'=> "lMany2One tw_auth_profile:Parent 'Профиль' widget=include_form",
		'active'=> "fCheckbox 'Активен' default=1",
		),$init_opts);
	}
	
	function check_field($field,$value,$params,$record=null) {
		switch ($field) {
			case 'login':
				if(!$value) return false;
				$users=$this->find_records(array($field=>$value));
				if (!($users->current())) return true;
				if ($record && $users->count()==1 ) {
					$c=$users->current();
					if ($c->get_id()==$params['rec_id']) return true;
				}
			return false;
			break;
		}
		return true;
	}
}

class g_form_auth_login extends g_forms_html {
	function __construct($h,$data=array(),$rec=null)  {
		$h=array(
			'login'=>array('type'=>'input','verbose_name'=>'E-mail'),
			'pass'=>array('type'=>'password','verbose_name'=>'Пароль'),
			);
		parent::__construct($h,$data,$rec);
		$this->view = new gs_glyph('helper',array('class'=>'table'));
		$this->view->addNode('helper',array('class'=>'tr'),array_keys($h));
	}

}

load_submodules(basename(dirname(__FILE__)),dirname(__FILE__));
