<?php
class oauth2_facebook  implements oauth2_profile {
	/*
	http://developers.facebook.com/docs/authentication/server-side/
	*/
	function __construct($config) {
		$this->config=$config;
	}
	function authorize($callback) {
		$r=array();
		$r['client_id']=$this->config->APP_ID;
		$r['redirect_uri']=$callback;
		if ($this->config->SCOPE) $r['scope']=$this->config->SCOPE;
		gs_session::save($r,'oauth2_facebook_request');

		$url="https://www.facebook.com/dialog/oauth?".http_build_query($r);
		return $url;
	}
	function token($data) {
		$request=gs_session::load('oauth2_facebook_request');
		$r=array();
		$r['code']=$data['code'];
		$r['client_id']=$this->config->APP_ID;
		$r['client_secret']=$this->config->APP_SECRET;
		$r['redirect_uri']=$request['redirect_uri'];

		$url="https://graph.facebook.com/oauth/access_token";
		$d=array();
		$d=html_fetch($url,$r,'POST');
		parse_str($d,$d);
		return $d;
	}
	function profile($token) {
		$ret=array('uid'=>null,'first_name'=>null,'last_name'=>null,'type'=>'vk','email'=>null);
		$langs=languages();
		$default_lang=key($langs);
		$ret=$this->profile_lang($token,$default_lang);
		foreach ($langs as $l=>$v) {
			$ret['Lang'][$l]=$this->profile_lang($token,$l);
		}
		/*
		*/
		$this->profile=$ret;
		return $ret;
	}
	protected function profile_lang($token,$lang) {
		// CONSUMER_KEY=id,name,first_name,last_name,link,gender,location,email,picture,hometown


		$ret=array('uid'=>null,'first_name'=>null,'last_name'=>null,'type'=>'facebook','email'=>null);
		$url=sprintf("https://graph.facebook.com/v2.0/me?fields=%s&access_token=%s",$this->config->CONSUMER_KEY,$token['access_token'],$lang);
		$d=html_fetch($url);
		$d=json_decode($d,1);

		if (!$d || !$d['id']) return $ret;


		$ret=array_merge($ret,$d);
		$ret['oauth2_type']='FB';
		$ret['oauth2_uid']=$d['id'];
		$ret['uid']='fb-'.$d['id'];

		if ($ret['gender']=='female') $ret['sex']=1;
		if ($ret['gender']=='male') $ret['sex']=2;


		if(isset($ret['first_name'])) $ret['firstname']=$ret['first_name'];
		if(isset($ret['last_name'])) $ret['lastname']=$ret['last_name'];

		if (isset($ret['age_range']['min'])) $ret['age']=$ret['age_range']['min'];
		if (isset($ret['birthday'])) $ret['age']=gs_date_to($ret['birthday'],'%y');

		if(isset($d['picture'])) $ret['photo']=$d['picture']['data']['url'];

		if (isset($d['location']['name'])) list($ret['city'],$ret['country'])=explode(',',$d['location']['name'],2);


		$fql=sprintf("SELECT current_location, hometown_location FROM user WHERE uid=%d",$ret['oauth2_uid']);
		$url=sprintf("https://api.facebook.com/method/fql.query?query=%s&format=json&access_token=%s&locale=%s",urlencode($fql),$token['access_token'],$lang);
		$d=html_fetch($url);
		$d=json_decode($d,1);
		if ($d) {
			if (($l=$d[0]['hometown_location'])) {
				$ret['country']=$l['country'];
				$ret['city']=$l['city'];
			}
			if (($l=$d[0]['current_location'])) {
				$ret['country']=$l['country'];
				$ret['city']=$l['city'];
			}
		}

		return $ret;
	}
	function friends($token) {
		/*
		https://developers.facebook.com/docs/graph-api/reference/v2.0/user/friends
		This will only return any friends who have used (via Facebook Login) the app making the request.
		If a friend of the person declines the user_friends permission, that friend will not show up in the friend list for this person.



		*/
		$friends=array();
		//$url=sprintf("https://graph.facebook.com/%s/friends/?access_token=%s",$this->profile['oauth2_uid'],$token['access_token']);
		$url=sprintf("https://graph.facebook.com/%s/friends/?access_token=%s",'me',$token['access_token']);
		$d=html_fetch($url);
		$d=json_decode($d,1);
		if (isset($d['data'])) {
			foreach ($d['data'] as $f) {
				$friends[$f['id']]=array('uid'=>$f['id'],'name'=>$f['name'],'oauth2_type'=>'FB','oauth2_config'=>$this->config->get_id());
			}
			$ret['friends']=$friends;
			$ret['friends_count']=count($ret['friends']);
		}
		return $friends;
	}
}

