<?php

class oauth2_vk  implements oauth2_profile {
	var $fields="first_name,last_name,nickname,screen_name,sex,country,bdate,city,photo_medium,photo,photo_big";
	function __construct($config) {
		$this->config=$config;
		if ($config->SCOPE) $this->fields=$config->SCOPE;
	}
	function authorize($callback) {
		gs_session::save($callback,'oauth2_vk_request');
		$callback=urlencode($callback);
		$url="http://oauth.vk.com/authorize?client_id=".$this->config->APP_ID."&scope=".$this->config->SCOPE."&redirect_uri=$callback&response_type=code&v=5.25";
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
				$friend=array_merge($f,array('uid'=>$f['uid'],'user_uid'=>$uid,'name'=>$f['first_name'].' '.$f['last_name'],'oauth2_type'=>'VK'));
				$friends[$f['uid']]=$friend;
			}
		}
		return $friends;
	}
	protected function profile_lang($token,$lang,$friends=false) {
		$ret=array('uid'=>null,'first_name'=>null,'last_name'=>null,'type'=>'vk','email'=>null);
		if (!$token || !is_array($token) || !isset($token['access_token'])) return $ret;
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

