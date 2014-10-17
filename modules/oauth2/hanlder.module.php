<?php

/*
oauth2_handler.login:classname:photographers:login_field:login:return:not_false:first_name_field:name:full_name_field:fullname
*/
class oauth2_handler extends gs_handler {
	function startlogin($ret) {
		$classname=$this->data['gspgid_va'][0];
		if (isset($this->params['oauth2_classname'])) $classname=$this->params['oauth2_classname'];
		if (!class_exists($classname)) throw new gs_exception('oauth2_handler:startlogin no class found '.$classname);
		$config=record_by_field('class',$classname,'oauth2_config');
		if (!$config) throw new gs_exception('oauth2_handler:startlogin can not find config for '.$classname);
	
		$url=isset($this->data['url']) ? $this->data['url'] : current_url();
		$d=parse_url($url);
		parse_str($d['query'],$get_vars);
		$this->data['data']['oa2c']=$classname;
		$d['query']=http_build_query(array_merge($get_vars,$this->data['data']));
		$callback=http_build_url($d);
		$oauth=new $classname($config);
		gs_session::save($callback,'oauth2_callback');
		$url=$oauth->authorize($callback);
		header('Location: '.$url);
	}

	function login($ret) {
		$ds=new gs_data_driver_get();
		$data=$ds->import();
		if (!isset($data['oa2c'])) return null;
		$classname=$data['oa2c'];
		if (!class_exists($classname)) throw new gs_exception('oauth2_handler:login no class found '.$classname);
		$config=record_by_field('class',$classname,'oauth2_config');
		if (!$config) throw new gs_exception('oauth2_handler:startlogin can not find config for '.$classname);
		$oauth=new $classname($config);
		$token=$oauth->token($data);
		if(!$token) return true;
		$profile=$oauth->profile($token);

		gs_session::save($profile,'oauth2_profile');
		if (!$profile['uid']) return true;

		$rs=new $this->params['classname'];
		$options=array(
			$this->params['login_field']=>$profile['uid']
			);
		//if (isset($profile['email']) && $profile['email']) $options[$this->params['login_field']]=$profile['email'];
		$rec=$rs->find_records($options)->first();
		if (!$rec) {
			$rec=$rs->find_records($options)->first(true);

			$rec->fill_values($profile);

			foreach ($rs->structure['fields'] as $k=>$f) {
				if ($f['type']=='password') $rec->$k=md5(rand());
			}
			if (isset($this->params['first_name_field'])) $rec->{$this->params['first_name_field']}=$profile['first_name'];
			if (isset($this->params['last_name_field'])) $rec->{$this->params['last_name_field']}=$profile['last_name'];
			if (isset($this->params['full_name_field'])) $rec->{$this->params['full_name_field']}=$profile['first_name'].' '.$profile['last_name'];
			if (isset($this->params['email_name_field'])) $rec->{$this->params['email_name_field']}=$profile['email'];
			$rec->commit();
		}
		foreach ($this->data['handler_params'] as $n=>$v) {
			if (isset($rs->structure['fields'][$n])) $options[$n]=$v;
		}
		$rec=$rs->find_records($options)->first();
		if (!$rec) return false;


		$rec->fill_values(array_filter($profile));
		if ($profile && isset($profile['friends']) && ($friends=$profile['friends'])) {
			 $rec->fill_values(array('Friends'=>$friends));
			 $rec->_Friends_count=count($friends);
		 }


		$rec->token=$token['access_token'];
		$rec->commit();
		gs_session::save($rec->get_id(),'login_'.$this->params['classname']);
        if(function_exists('person') && isset($this->params['role'])) person()->add_role($this->params['role'],$rec);
		return $rec;
	}
	function pushtoken($d) {
		$token=array('access_token'=>$this->data['access_token'],'user_id'=>$this->data['user_id']);
		gs_session::save($token,'oauth2_token_'.$this->params['name']);
		html_redirect(gs_session::load('oauth2_callback'));
	}
}
class oauth2_twitter{
	/*
	http://habrahabr.ru/post/114955/
	*/
	function __construct($config) {
		$this->config=$config;
		load_file(dirname(__FILE__) . DIRECTORY_SEPARATOR. 'lib'.DIRECTORY_SEPARATOR.'twitter'.DIRECTORY_SEPARATOR.'twitteroauth'.DIRECTORY_SEPARATOR.'twitteroauth.php');
		load_file(dirname(__FILE__) . DIRECTORY_SEPARATOR. 'lib'.DIRECTORY_SEPARATOR.'twitter'.DIRECTORY_SEPARATOR.'config.php');

	}
	function authorize($callback) {

		$connection = new TwitterOAuth($this->config->CONSUMER_KEY, $this->config->APP_SECRET);
		$request_token = $connection->getRequestToken($callback);
		gs_session::save($request_token,'oauth2_twitter_token');
		$url=$connection->getAuthorizeURL($request_token);
		return $url;
	}
	function token($data) {
		$request_token=gs_session::load('oauth2_twitter_token');
		$connection = new TwitterOAuth($this->config->CONSUMER_KEY, $this->config->APP_SECRET,$request_token['oauth_token'],$request_token['oauth_token_secret']);
		$access_token = $connection->getAccessToken($data['oauth_verifier']);
		return $connection;
	}
	function profile($connection) {
		$ret=array('uid'=>null,'first_name'=>null,'last_name'=>null,'type'=>'twitter','email'=>null);
		$d=$connection->get('account/verify_credentials');
		if (!$d || !$d->id) return $ret;
		$ret['uid']='tw-'.$d->id;
		list($ret['first_name'],$ret['last_name'])=array_map(trim,explode(' ',$d->name,2));
		return $ret;
	}
}

class oauth2_vk_app extends oauth2_vk {
	function authorize($callback) {
		$url=sprintf("http://api.vk.com/oauth/authorize?client_id=%s&redirect_uri=http://api.vk.com/blank.html&scope=%s&display=page&response_type=token",$this->config->APP_ID,$this->config->SCOPE);
		return $url;
	}
	function token($data) {
		return gs_session::load('oauth2_token_vk_app');
	}

}

class oauth2_vk {
	function __construct($config) {
		$this->config=$config;
		$this->fields="first_name,last_name,nickname,screen_name,sex,country,bdate,city,photo_medium,photo,photo_big";
	}
	function authorize($callback) {
		gs_session::save($callback,'oauth2_vk_request');
		$callback=urlencode($callback);
		$url="http://oauth.vk.com/authorize?client_id=".$this->config->APP_ID."&scope=".$this->config->SCOPE."&redirect_uri=$callback&response_type=code";
		#$url=sprintf("http://api.vk.com/oauth/authorize?client_id=%s&redirect_uri=http://api.vk.com/blank.html&scope=%s&display=page&response_type=token",$this->config->APP_ID,$this->config->SCOPE);
		return $url;
	}
	function token($data) {
		$r=array(
			'client_id'=>$this->config->APP_ID,
			'client_secret'=>$this->config->APP_SECRET,
			'code'=>$data['code'],
			'redirect_uri'=>(gs_session::load('oauth2_vk_request')),
			);
		$url="https://oauth.vk.com/access_token?".http_build_query($r);
		$html=html_fetch($url);
		$d=json_decode($html);
		$d=get_object_vars($d);
		return $d;
	}
	function profile($token) {
		$ret=array('uid'=>null,'first_name'=>null,'last_name'=>null,'type'=>'vk','email'=>null);
		$url=sprintf("https://api.vk.com/method/getProfiles?uid=%d&access_token=%s&fields=%s",$token['user_id'],$token['access_token'],$this->fields);
		$d=json_decode(html_fetch($url));
		if (!$d) return $ret;
		$d=reset($d->response);
		if (!$d->uid) return $ret;
		$uid=$d->uid;
		$ret=array_merge($ret,get_object_vars($d));
		$ret['uid']='vk-'.$d->uid;
		$ret['name']=implode(' ',array($ret['first_name'],$ret['last_name']));
		$ret['username']=$ret['screen_name'];
		if ($ret['sex']==2) $ret['gender']='male';
		if ($ret['sex']==1) $ret['gender']='female';
		$ret['locale']=$ret['country'];

		$url=sprintf("https://api.vk.com/method/friends.get?access_token=%s&fields=%s",$token['access_token'],$this->fields);
		$d=html_fetch($url);
		$d=json_decode($d,1);
		if (isset($d['response'])) {
			$friends=array();
			foreach ($d['response'] as $f) {
				$friend=array_merge($f,array('uid'=>$f['uid'],'user_uid'=>$uid,'name'=>$f['first_name'].' '.$f['last_name']));
				$friends[$f['uid']]=$friend;
			}
			$ret['friends']=$friends;
			$ret['friends_count']=count($ret['friends']);
		}
		return $ret;
	}

	function exec($method,$data=array()) {
		$url=sprintf("https://api.vk.com/method/%s?uid=%d&access_token=%s&%s",
					$method,
					$this->token['user_id'],
					$this->token['access_token'],
					http_build_query($data));

		$d=json_decode(html_fetch($url));
		return $d;

	}


}
class oauth2_google{
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
		$ret['uid']='google-'.$d->id;
		$ret['first_name']=$d->given_name;
		$ret['last_name']=$d->family_name;
		if (isset($d->email) && $d->email) $ret['email']=$d->email; 
		return $ret;
	}
}
class oauth2_facebook{
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
		$ret=array('uid'=>null,'first_name'=>null,'last_name'=>null,'type'=>'facebook','email'=>null);
		$url=sprintf("https://graph.facebook.com/me?fields=%s&access_token=%s",$this->config->CONSUMER_KEY,$token['access_token']);
		$d=html_fetch($url);
		$d=json_decode($d,1);
		if (!$d || !$d['id']) return $ret;
		$ret=array_merge($ret,$d);
		$ret['uid']='fb-'.$d['id'];

		$url=sprintf("https://graph.facebook.com/me/friends/?access_token=%s",$token['access_token']);
		$d=html_fetch($url);
		$d=json_decode($d,1);
		if (isset($d['data'])) {
			$friends=array();
			foreach ($d['data'] as $f) {
				$friends[$f['id']]=array('uid'=>$f['id'],'name'=>$f['name']);
			}
			$ret['friends']=$friends;
			$ret['friends_count']=count($ret['friends']);
		}
		/*
		$ret['first_name']=$d['first_name'];
		$ret['last_name']=$d['last_name'];
		if ($d['email']) $ret['email']=$d['email'];
		*/
		return $ret;
	}
}
