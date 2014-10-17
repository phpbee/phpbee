<?php

class b_tree {
	const levels=5;
	private $tree;
	
	function __construct ($dir) {
		$this->dir=$dir;
		check_and_create_dir($this->dir);
	}
	
	private function parse_key($key) {
		return is_numeric($key) ? $this->int2str($key) : $key;
	}
	
	
	public function find($key,$strong=true) {
		$key=$this->parse_key($key);
		$k=str_split($key,1);
		$path=$this->dir;
		$path.=DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR,$k);
		$data=(file_exists($path.DIRECTORY_SEPARATOR.'idx')) ? file($path.DIRECTORY_SEPARATOR.'idx') : array();
		mlog('Find index '.$path.' with value '.$key);
		$res=array();
		if ($strong) {
			foreach ($data as $v) {
				if (strpos($v,'!')!==false) {
					$res[]=ltrim($v,'!');
				}
			}
		} else {
			foreach ($data as $v) {
				$res[]=ltrim($v,'!');
			}
		}
		return $res;
	}
	
	public function add($key,$value) {
		mlog("add index: $key $value");
		$key=$this->parse_key($key);
		$k=str_split($key,1);
		$path=$this->dir;
		$len=strlen($key)-1;
		foreach ($k as $i => $v) {
			if ($i<self::levels) {
				$path.=DIRECTORY_SEPARATOR.$v;
				check_and_create_dir($path);
				$this->add_value($path,($i==$len) ? '!'.$value : $value);
			}
		}
	}
	
	public function delete($key,$value) {
		mlog("delete index: $key $value");
		$key=$this->parse_key($key);
		$k=str_split($key,1);
		$path=$this->dir;
		foreach ($k as $i => $v) {
			if ($i<self::levels) {
				$path.=DIRECTORY_SEPARATOR.$v;
				$this->del_value($path,$value);
			}
		}
	}
	
	private function add_value($path,$value) {
		$fd=fopen($path.DIRECTORY_SEPARATOR.'idx','a');
		fwrite($fd,$value.PHP_EOL);
	}
	
	private function del_value($path,$value) {
		$value.=PHP_EOL;
		$path=$path.DIRECTORY_SEPARATOR.'idx';
		if (file_exists($path)) {
			$a=array_flip(file($path));
			unset($a[$value]);
			unset($a['!'.$value]);
			file_put_contents_perm($path,implode("",array_keys($a)));
		}
	}
	
	private function int2str($int) {
		$d=array(
			'0'=>'a','1'=>'b','2'=>'c','3'=>'d','4'=>'e','5'=>'f','6'=>'g','7'=>'h',
			'8'=>'i','9'=>'j','a'=>'k','b'=>'l','c'=>'m','d'=>'n','e'=>'o','f'=>'p',
			'g'=>'q','h'=>'r','i'=>'s','j'=>'t','k'=>'u','l'=>'v','m'=>'w','n'=>'x',
			'o'=>'y','p'=>'z');
		$id=strrev(str_pad(strtr(base_convert($int,10,26),$d),self::levels,'a',STR_PAD_LEFT));
		return $id;
	}
}


?>
