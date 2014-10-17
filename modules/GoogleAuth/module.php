<?php

require_once (dirname(__FILE__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'GoogleAuthenticator.php');

class module_GoogleAuth extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
	}
	static function get_handlers() {
		$data=array(
			);
		return self::add_subdir($data,dirname(__file__));
	}

}

class GoogleAuth_handler extends gs_handler {
    /*

    googleauth/verify/person => 
        GoogleAuth_handler.verify_person:form_class:g_forms_table:name:form_user_googleauth.html:role:user:field_name:ga_enabled:return:true
        gs_base_handler.redirect 
    */
    function verify_person () {
        $bh=new gs_base_handler($this->data,$this->params);
        $f=$bh->validate();
        if (!is_object($f) || !is_a($f,'g_forms')) return $f;
        $d=$f->clean();

        $rec=person($this->params['role']);
        if (!$rec) {
            $f->trigger_error('FORM_ERROR','REC_NOTFOUND');
            return $bh->showform($f);
        }

        $fname=$this->params['field_name'].'_secret';
        $ga=new GoogleAuthenticator;
        $code=$ga->getCode($rec->$fname);


        if ($code!=$d['ga_code'])  {
            $f->trigger_error('FORM_ERROR','GA_INCORRECT_CODE');
            return $bh->showform($f);
        }

        $person=person();
        $fname=$this->params['field_name'].'_verified';
        $person->$fname=TRUE;
        return true;
    }
    /*
    logout/user =>
        gs_base_handler.post_logout:classname:user:role:user:return:true:
        GoogleAuth_handler.unverify_person:role:user:field_name:ga_enabled:return:true
        gs_base_handler.redirect 
    */
    function unverify_person() {
        $person=person();
        $fname=$this->params['field_name'].'_verified';
        $person->$fname=FALSE;
        return true;
    }


}
