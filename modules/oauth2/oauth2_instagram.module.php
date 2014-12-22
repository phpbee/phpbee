<?php
class oauth2_instagram_handler extends gs_handler {

	function redirect_uri($d) {

		gs_session::save($this->data,'oauth2_instagram_response');
		$callback= gs_session::load('oauth2_instagram_callback');
	
	 	//return html_redirect($callback.'&code='.$this->data['code']);
	 	return html_redirect($callback);

	}

}

class oauth2_instagram implements oauth2_profile {
	function __construct($config) {
		$this->config=$config;
	}
	function authorize($callback) {
		
		gs_session::save($callback,'oauth2_instagram_callback');
		$url=base_domain().'/oauth2/instagram/redirect_uri';

		$r=array();
		$r['client_id']=$this->config->APP_ID;
		$r['redirect_uri']=$url;
		$r['response_type']='code';
		gs_session::save($r,'oauth2_instagram_request');

		$url="https://api.instagram.com/oauth/authorize/?".http_build_query($r);
		return $url;
	}
	function token($data) {
		$request=gs_session::load('oauth2_instagram_request');
		$response=gs_session::load('oauth2_instagram_response');
		$r=array();
		$r['client_id']=$this->config->APP_ID;
		$r['client_secret']=$this->config->APP_SECRET;
		$r['grant_type']='authorization_code';
		$r['redirect_uri']=$request['redirect_uri'];
		//$r['code']=$data['code'];
		$r['code']=$response['code'];

		$url="https://api.instagram.com/oauth/access_token";
		$d=array();
		$d=html_fetch($url,$r,'POST');
		$d=json_decode($d,TRUE);
		return $d;
	}
	function profile($token) {

		$langs=languages();
		$default_lang=key($langs);
		$ret=$this->profile_lang($token,$default_lang);
		/*
		foreach ($langs as $l=>$v) {
			$ret['Lang'][$l]=$this->profile_lang($token,$l);
		}
		*/
		$this->profile=$ret;

		return $ret;
	}
	protected function profile_lang($token,$lang) {
		// CONSUMER_KEY=id,name,first_name,last_name,link,gender,location,email,picture,hometown




		$access_token=$token['access_token'];
		$uid=$token['user']['id'];
		$names=explode(' ',$token['user']['full_name'],2);
		$photo=$token['user']['profile_picture'];
		$ret=array('uid'=>'ing_'.$uid,
					'oauth2_type'=>'ING',
					'oauth2_uid'=>$uid,
					'username'=>$token['user']['username'],
					'first_name'=>reset($names),
					'last_name'=>end($names),
					'type'=>'ING',
					'email'=>null,
					'photo'=>$photo,
					);



		if(!$uid || !$access_token) return array();


		return $ret;
	}
	function friends($token) {
		$friends=array();

		$uid=$token['user']['id'];
		$access_token=$token['access_token'];

		$url=sprintf("https://api.instagram.com/v1/users/%d/follows?access_token=%s",$uid,$access_token);
		$d=html_fetch($url);
		$d=json_decode($d,1);

		if (isset($d['data'])) {
			foreach ($d['data'] as $f) {
				$friends[$f['id']]=array('uid'=>$f['id'],'name'=>$f['full_name'],'photo'=>$f['profile_picture'],'oauth2_type'=>'ING','oauth2_config'=>$this->config->get_id());
			}
			$ret['friends']=$friends;
			$ret['friends_count']=count($ret['friends']);
		}
		return $friends;
	}
}

