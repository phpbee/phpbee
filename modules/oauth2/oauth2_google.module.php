<?php

class oauth2_google  implements oauth2_profile {
	/*
	https://developers.google.com/accounts/docs/OAuth2Login?hl=ru
	https://developers.google.com/accounts/docs/OAuth2WebServer

	https://developers.google.com/accounts/docs/OAuth2?hl=ru

	scope: space delimeted https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email
	*/
	function __construct($config) {
		$this->config=$config;
	}
	function authorize($callback) {
		$d=parse_url($callback);
		if ($this->config->CONSUMER_KEY) $d['path']=$this->config->CONSUMER_KEY;
		$callback=http_build_url($d);
		$r=array();
		$r['response_type']='code';
		$r['client_id']=$this->config->APP_ID;
		$r['scope']=$this->config->SCOPE;
		$r['redirect_uri']=$callback;
		$r['state']=$callback;
		return "https://accounts.google.com/o/oauth2/auth?".http_build_query($r);
	}
	function token($data) {
		$r=array();
		$r['code']=$data['code'];
		$r['client_id']=$this->config->APP_ID;
		$r['client_secret']=$this->config->APP_SECRET;
		$r['grant_type']='authorization_code';
		$r['redirect_uri']=$data['state'];

		$url="https://accounts.google.com/o/oauth2/token";
		$d=json_decode(html_fetch($url,$r,'POST'));
		return $d;
	}
	function profile($token) {
		$ret=array('uid'=>null,'first_name'=>null,'last_name'=>null,'type'=>'google','email'=>null);
		$url=sprintf("https://www.googleapis.com/oauth2/v1/userinfo?access_token=%s",$token['access_token']);
		$d=json_decode(html_fetch($url));
		if (!$d || !$d->id) return $ret;
		$ret['oauth2_type']='GOOGLE';
		$ret['uid']='google-'.$d->id;
		$ret['first_name']=$d->given_name;
		$ret['last_name']=$d->family_name;
		if (isset($d->email) && $d->email) $ret['email']=$d->email; 
		return $ret;
	}
	function friends($token) {
		return array();
	}
}
