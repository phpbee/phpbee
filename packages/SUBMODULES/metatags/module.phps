{%capture assign=DATA%}
	FIELDS::title::fString Заголовок required=false
	FIELDS::keywords::fText Keywords required=false
	FIELDS::description::fString Description required=false
{%/capture%}
<?php
class module{%$MODULE_NAME%} extends gs_base_module implements gs_module {
	function __construct() {
	}
	function install() {
		foreach(array() as $r){
			$this->$r=new $r;
			$this->$r->install();
		}
	}
	
	function get_menu() {
		return '';
	}
	
	static function get_handlers() {
		$data=array('get_post'=>array(),);
		return self::add_subdir($data,dirname(__file__));
	}
}

class handler{%$MODULE_NAME%} extends gs_base_handler {
}

class metatags {
	static function save($url,$title,$keywords,$description) {
		$rs=new tw_{%$PARENT_MODULE%};
		$rec=$rs->find_records(array('url'=>$url))->limit(1)->first(true);
		$rec->title=$title;
		$rec->keywords=$keywords;
		$rec->description=$description;
		$rec->commit();
	}
	
	static function delete($url) {
		$rs=new tw_{%$PARENT_MODULE%};
		$rec=$rs->find_records(array('url'=>$url))->limit(1)->first();
		$rec->delete();
		$rec->commit();
	}
	
	static function get_fields($rec) {
		$s=$rec->get_recordset()->structure['htmlforms'];
		$fields=array();
		$im=0;
		foreach ($s as $field => $opts) {
			if (isset($opts['keywords']) && $opts['keywords']>0) {
				$fields[$field]=$rec->$field;
				/*md('======',1);
				var_dump($rec->get_values($field));
				var_dump('----',1);
				var_dump($rec->get_modified_values($field));
				var_dump(intval($rec->is_modified($field)));*/
				$im+=intval($rec->is_modified($field));
			}
		}
		return $im>0 ? $fields : array();
	}
	
	static function get_keywords($fields) {
		if (get_class($fields)=='gs_record') $fields=metatags::get_fields($fields);
		$len=30;
		$text=strip_tags(implode(' ',$fields));
		$text=iconv('UTF-8','Windows-1251',$text);
		$lib=cfg('lib_dir');
		$norm=VPA_normalizator::getInstance($lib.'dicts/');
		$words=$norm->parse_text(strtolower($text));
		$w=$norm->freq_analyze_first($words);
		$res=$norm->freq_analyze_second($w);
		$res=$norm->freq_analyze_third($res);
		arsort($res,SORT_NUMERIC);
		$res=array_slice($res,0,$len);
		$keys=array();
		$l=0;
		foreach ($res as $w => $f) {
			if ($f>1) {
				$uw=iconv('CP1251','UTF-8',$w);
                var_dump($uw);
				$l+=strlen($uw)+2;
				if ($l<1000) $keys[]=$uw;
			}
		}

		return implode(', ',$keys);
	}
}
?>