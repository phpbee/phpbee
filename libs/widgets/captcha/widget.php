<?php

/*
	{$form->add_field('captcha',string_to_params("type=input widget=captcha"))}
	{$form->_prepare_inputs()}
*/


class gs_widget_captcha extends gs_widget {
	function html() {
		if ($this->validate()) return sprintf('<input type="hidden" name="%s" value="%s">%s',$this->fieldname,$this->value,gs_dict::get('CAPTCHA_VERIFIED'));
		return sprintf('<img src="/widgets/captcha/img">').parent::html();
	}

	function validate() {
		if (gs_session::load('gs_widget_captcha_verified')) {
			$this->value='gs_widget_captcha_verified';
			return true;
		}
		if (gs_session::load('gs_widget_captcha_code') && $this->value== gs_session::load('gs_widget_captcha_code')) {
			gs_session::save(TRUE,'gs_widget_captcha_verified');
			return TRUE;
		}
		return FALSE;
	}
}
class gs_widget_captcha_module extends gs_base_module implements gs_module {
    function __construct() {}
    function install() {}
    function get_menu() {}
    static function get_handlers() {
        $data=array(
                  'get'=>array(
                      '/widgets/captcha/img'=>array(
                          'gs_widget_captcha_handler.img',
                      ),
                  ),
              );
        return self::add_subdir($data,dirname(__file__));
    }
}
class gs_widget_captcha_handler extends gs_handler{
	function img() {
		session_start();
		$dir=realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR;
		require_once($dir.'kcaptcha.php');
		//$c=new KCAPTCHA(gs_session::load('gs_widget_captcha_code')); //не меняем картинку
		$c=new KCAPTCHA;
		gs_session::save($c->getKeyString(),'gs_widget_captcha_code');
	}
}

