<?php

class gs_dict {
	static $words=array();

	static function add($key,$word) {
		self::$words[$key]=$word;
	}
	
	static function append($words) {
		self::$words=array_merge(self::$words,$words);
	}
	
	static function get($key) {
		if (is_array($key)) {
			foreach ($key as $k=>$v) $key[$k]=gs_dict::get($v);
			return $key;
		}
		return isset(self::$words[$key]) ? self::$words[$key] : $key;
	}
}

if (file_exists(cfg('root_dir').'words.cfg.php')) {
	require_once(cfg('root_dir').'words.cfg.php');
}
?>
