<?php 
class gs_validator {
	static function check_html_confirm_code($gspgid,$arr=array()) {
		return TRUE; //  !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!! 
		$gid=base64_encode($gspgid);	
		$data=gs_session::load('gs_validator_html_confirm_array_'.$gid);
		if (!is_array($data)) $data=gs_session::load('gs_validator_html_confirm_array_');
		return is_array($data) && (count($data)==count(array_intersect($data,$arr)));
	}


	static function sendback_html_form($data,$gspgid=null) {
		$id=gs_cacher::save($data,'sendback_html_form');
		html_redirect($gspgid,array('_gscacheid'=>$id),'302');
	}


	static function validate_html_form($data) {
		if ($data['gspgtype']!='post') throw new gs_exception('gs_validator.validate_form: gspgtype is not post');

		$gid=base64_encode($data['gspgid']);	
		unset($data['gspgtype']);
		unset($data['gs_session']);
		if (!gs_validator::check_html_confirm_code($data['gspgid'],array_keys($data))) {
			throw new gs_exception('gs_validator.validate_form: check_html_confirm_code failed');
		}

	
		$c_array=array();
		foreach ($data as $k=>$d) {
			if (strpos($k,'_validate_')===0) {
				$c=unserialize(base64_decode($d));
				if ($c===FALSE) throw new gs_exception('gs_validator.validate_form: can not decode c_array / '.$c);
				$c_array[$k]=$c;
				$c_array_fields[$c['id']]=$c['id'];
			} else if (!in_array($k,array('gspgid_v','gspgid_va'))){
				$c_data[$k]=$d;
			}
		}
		foreach ($c_data as $k=>$d) {
			if (!isset($c_array_fields[$k])) {
				$c_array[$k]=array('id'=>$k,'criteria'=>'notEmpty','message'=>'*');
			}
		}

		$ret=array('STATUS'=>null);
		foreach ($c_array as $c) {
			$d=isset($data[$c['id']]) ? $data[$c['id']] : NULL;
			if (gs_validator::validate($c['criteria'],$d,$c,$data)===TRUE) {
				if ($ret['STATUS']===NULL) $ret['STATUS']=TRUE;
			} else {
				$ret['STATUS']=false;
				$ret['ERRORS'][$c['id']]=$c['criteria'];
				$ret['MESSAGES'][$c['id']]=$c['message'];
			}

		}
		if ($ret['STATUS']===null) {
			throw new gs_exception('gs_validator.validate_form: no validate criterias');
		}
		return $ret;
	}

	static function validate($type,$value,$params=null,$formvars=null) {
		$config=gs_config::get_instance();
		load_file($config->lib_dir.'validate_criterias/validate_criteria.php');

		$methodname='gs_validate_criteria_'.$type;
		if (!function_exists($methodname)) {
			throw new gs_exception('gs_validator: method '.$methodname.'  not exists');
		}
		return $methodname($value,$params,$formvars);
	}
}
?>
