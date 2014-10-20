<?php

/*
oauth2_handler.login:classname:photographers:login_field:login:return:not_false:first_name_field:name:full_name_field:fullname
*/

interface oauth2_profile {
		function authorize($callback);
		function token($data);
		function profile($token);
		function friends($token);
}

class oauth2_handler extends gs_handler {
	function startlogin($ret) {
		$classname=$this->data['gspgid_va'][0];
		if (isset($this->params['oauth2_classname'])) $classname=$this->params['oauth2_classname'];
		if (!class_exists($classname)) throw new gs_exception('oauth2_handler:startlogin no class found '.$classname);
		$config=record_by_field('class',$classname,'oauth2_config');
		if (!$config) throw new gs_exception('oauth2_handler:startlogin can not find config for '.$classname);

		$url=isset($this->data['url']) ? $this->data['url'] : current_url();
		$d=parse_url($url);
		parse_str(isset($d['query']) ? $d['query'] : '' ,$get_vars);
		$this->data['data']['oa2c']=$classname;
		$d['query']=http_build_query(array_merge($get_vars,$this->data['data']));
		$callback=http_build_url($d);
		$oauth=new $classname($config);
		if (isset($this->params['callback'])) {
			$dc=array (
				'scheme' => 'http',
				'host' => $_SERVER['HTTP_HOST'],
				'path' => $this->params['callback'],
				'query' => 'oa2c=oauth2_vk',
			);
			$callback=http_build_url($dc);
		}
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
		$profile=explode_data(array_filter($profile));

		gs_session::save($profile,'oauth2_profile');
		if (!$profile['uid']) return true;


		$rs=new oauth2_users;
		$options=array(
			'oauth2_uid'=>$profile['oauth2_uid'],
			'Config_id'=>$config->get_id(),
			);
		$rec=$rs->find_records($options)->first();

		if (!$rec) {
			$rec=$rs->find_records($options)->first(true);
			foreach ($rs->structure['fields'] as $k=>$f) {
				if ($f['type']=='password') $rec->$k=md5(rand());
			}
			$rec->ip=$_SERVER['REMOTE_ADDR'];


			$geoip=$this->geoip_city();
			$rec->fill_values($geoip);

			$rec->fill_values($profile);



			$person=$rec->Person->new_record($options);
			$person->ip=$_SERVER['REMOTE_ADDR'];
			$person->fill_values($geoip);
			$person->fill_values($profile);
			foreach ($person->get_recordset()->structure['fields'] as $k=>$f) {
				if ($f['type']=='password') $rec->$k=md5(rand());
			}


			$friends=$oauth->friends($token);
			if ($friends) {
				 $person->fill_values(array('Friends'=>$friends));
				 $person->_Friends_count=count($friends);
			}


			$rec->commit();

		}

		$person=$rec->Person->f();
		$options=array($person->get_recordset()->get_id_field_name()=>$rec->Person_id);
		foreach ($this->data['handler_params'] as $n=>$v) {
			if (isset($person->get_recordset()->structure['fields'][$n])) $options[$n]=$v;
		}
		$person=$person->get_recordset()->find_records($options)->first();
		if (!$person) return false;


		//$rec->fill_values($profile);
		$rec->oauth2_profile=serialize($profile);
		

		$rec->token=$token['access_token'];
		$rec->commit();
		gs_session::save($person->get_id(),'login_'.$this->params['classname']);
		if(function_exists('person') && isset($this->params['role'])) person()->add_role($this->params['role'],$person);
			return $rec;
		}
	function fill_values($rec,$values) {
		$langs=languages();
		$default_lang=key($langs);

		foreach ($values as $k=>$v) {
			if ($k!='Lang') $rec->$k=$v;
		}
		if (is_array($values['Lang'])) foreach ($values['Lang'] as $lang=>$w) {
			if (is_array($w)) foreach ($w as $k=>$v) {
				if (!$v) continue;
				if ($lang==$default_lang) {
					$rec->$k=$v;
				} else {
					$rec->Lang[$lang]->$k=$v;
				}
			}
		}
	}
	function pushtoken($d) {
		$token=array('access_token'=>$this->data['access_token'],'user_id'=>$this->data['user_id']);
		gs_session::save($token,'oauth2_token_'.$this->params['name']);
		html_redirect(gs_session::load('oauth2_callback'));
	}
	function geoip_city() {
		$key="AIzaSyCz02EZ8sXEpW0Rfejf8P2AAI09HOuz_A0";


		$addr=array('Lang'=>array());


		$baseurl="https://maps.googleapis.com/maps/api/geocode/json?key=%s&latlng=%s,%s&language=%s&result_type=locality|country";

		$langs=languages();
		$default_lang=gs_var_storage::load('multilanguage_lang');
		if(!$default_lang) $default_lang=key($langs);

		foreach ($langs as $l=>$v) {
			$url=sprintf($baseurl,$key,$_SERVER['GEOIP_LATITUDE'],$_SERVER['GEOIP_LONGITUDE'],$l);
			$ret=json_decode(html_fetch($url),1);
			if (!is_array($ret)) continue;

			$ai=array();
			foreach ($ret['results'][0]['address_components'] as $a) {
				if (in_array('locality',$a['types'])) {
					$ai['city']=$a['long_name'];
				}
				if (in_array('country',$a['types'])) {
					$ai['country']=$a['long_name'];
				}
			}
			if ($l==$default_lang) $addr=array_merge($addr,$ai);
			$addr['Lang'][$l]=$ai;
		}
	

		return $addr;

	}
}
class oauth2_twitter implements oauth2_profile {
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
	function friends($token) {
		return array();
	}
}


class oauth2_vk  implements oauth2_profile {
	var $fields="first_name,last_name,nickname,screen_name,sex,country,bdate,city,photo_medium,photo,photo_big";
	function __construct($config) {
		$this->config=$config;
		if ($config->SCOPE) $this->fields=$config->SCOPE;
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
		$langs=languages();
		$default_lang=gs_var_storage::load('multilanguage_lang');
		if(!$default_lang) $default_lang=key($langs);

		$ret=$this->profile_lang($token,$default_lang);
		foreach ($langs as $l=>$v) {
			$ret['Lang'][$l]=$this->profile_lang($token,$l);
		}
		return $ret;
	}
	function friends($token) {
		$fields="uid,first_name,last_name,nickname,sex,bdate,city,country,timezone,photo,photo_medium,photo_big,domain,has_mobile,rate,contacts,education";

		$friends=array();
		$url=sprintf("https://api.vk.com/method/friends.get?lang=%s&access_token=%s&fields=%s",$lang,$token['access_token'],$fields);
		$d=html_fetch($url);
		$d=json_decode($d,1);
		if (isset($d['response'])) {
			foreach ($d['response'] as $f) {
				$friend=array_merge($f,array('uid'=>$f['uid'],'user_uid'=>$uid,'name'=>$f['first_name'].' '.$f['last_name']));
				$friends[$f['uid']]=$friend;
			}
		}
		return $friends;
	}
	protected function profile_lang($token,$lang,$friends=false) {
		$ret=array('uid'=>null,'first_name'=>null,'last_name'=>null,'type'=>'vk','email'=>null);
		$url=sprintf("https://api.vk.com/method/getProfiles?lang=%s&uid=%d&access_token=%s&fields=%s",$lang,$token['user_id'],$token['access_token'],$this->fields);
		$d=json_decode(html_fetch($url));
		if (!$d) return $ret;
		$d=reset($d->response);
		if (!$d->uid) return $ret;
		$uid=$d->uid;
		$ret=array_merge($ret,get_object_vars($d));
		$ret['oauth2_type']='VK';
		$ret['oauth2_uid']=$d->uid;
		$ret['uid']='vk-'.$d->uid;
		$ret['name']=implode(' ',array($ret['first_name'],$ret['last_name']));
		$ret['username']=$ret['screen_name'];
		if ($ret['sex']==2) $ret['gender']='male';
		if ($ret['sex']==1) $ret['gender']='female';
		$ret['photo']=$ret['photo_big'];
		$ret['locale']=$ret['country'];
		$ret['age']=gs_date_to($ret['bdate'],'%y');

		if(isset($ret['first_name'])) $ret['firstname']=$ret['first_name'];
		if(isset($ret['last_name'])) $ret['lastname']=$ret['last_name'];


		if (isset($ret['city'])) {
			$ret['city_id']=$ret['city'];
			$city=json_decode(html_fetch("https://api.vk.com/method/database.getCitiesById?lang=".$lang."&city_ids=".$ret['city_id']),1);
			if (isset($city['response'])) foreach ($city['response'] as $c) {
				if ($c['cid']==$ret['city_id']) {
					$ret['city']=$c['name'];
					break;
				}
			}
		}

		if (isset($ret['country'])) {
			$ret['country_id']=$ret['country'];
			$country=json_decode(html_fetch("https://api.vk.com/method/database.getCountriesById?lang=".$lang."&country_ids=".$ret['country_id']),1);
			if (isset($country['response'])) foreach ($country['response'] as $c) {
				if ($c['cid']==$ret['country_id']) {
					$ret['country']=$c['name'];
					break;
				}
			}
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
class oauth2_vk_app extends oauth2_vk {
	function authorize($callback) {
		$url=sprintf("http://api.vk.com/oauth/authorize?client_id=%s&redirect_uri=http://api.vk.com/blank.html&scope=%s&display=page&response_type=token",$this->config->APP_ID,$this->config->SCOPE);
		return $url;
	}
	function token($data) {
		return gs_session::load('oauth2_token_vk_app');
	}

}

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
				$friends[$f['id']]=array('uid'=>$f['id'],'name'=>$f['name']);
			}
			$ret['friends']=$friends;
			$ret['friends_count']=count($ret['friends']);
		}
		return $friends;
	}
}
