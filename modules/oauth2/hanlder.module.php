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


		$old_person=null;
		if(function_exists('person') && isset($this->params['role'])) $old_person=person($this->params['role']);


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

			if (!$old_person) {

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
			}


			$rec->commit();

		}

		if ($old_person)  {
			$rec->Person=$person=$old_person;
			$rec->Person_id=$rec->Person->get_id();
		} else {

			$person=$rec->Person->f();
			$options=array($person->get_recordset()->get_id_field_name()=>$rec->Person_id);
			foreach ($this->data['handler_params'] as $n=>$v) {
				if (isset($person->get_recordset()->structure['fields'][$n])) $options[$n]=$v;
			}
			$person=$person->get_recordset()->find_records($options)->first();
			if (!$person) return false;
		}


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



