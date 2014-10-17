<?php
DEFINE ('GS_DATA_STDIN','stdin');
DEFINE ('GS_DATA_POST','post'); // operator
DEFINE ('GS_DATA_GET','get'); // operator
DEFINE ('GS_DATA_SEF','sef'); // Search Engines Frendly URI
DEFINE ('GS_DATA_CLI','cli');
DEFINE ('GS_DATA_SESSION','session');
DEFINE ('GS_DATA_COOKIE','cookie');
DEFINE ('GS_NULL_XML',"<null></null>");

DEFINE ('GS_SESSION_COOKIE','gs_session_1');
DEFINE ('GS_DEFAULT_MESSAGE','GS_DEFAULT_MESSAGE');

class gs_null extends SimpleXMLElement implements arrayaccess {
	public function get_id() {
		return $this;
	}
	public function __get($name) {
		return $this;
	}
	public function offsetGet($offset) {
		return $this;
	}
	public function first() {
		return $this;
	}
	public function get_recordset() { return $this; }
	public function find() { return $this; }
	public function commit() { return $this; }

	public function count() {
		return 0;
	}
	public function get_values() {
		return array();
	}
	    public function offsetSet($offset, $value) {
	    }
	    public function offsetExists($offset) {
	    }
	    public function offsetUnset($offset) {
	    }
	     public function __call($name, $arg) {
		     return $this;
		     //throw new gs_dbd_exception('trying call '.$name.' in gs_null object',DBD_GSNULL_CALL);
	     }
	     function html_list() {
		     return '';
	     }
}
class gs_data {
	
	private $data;
	private $data_drivers;
	
	public function __construct()
	{
		$this->data_drivers=array(
			GS_DATA_COOKIE,
			GS_DATA_SESSION,
			GS_DATA_GET,
			GS_DATA_SEF,
			GS_DATA_CLI,
			GS_DATA_POST,
			GS_DATA_STDIN,
		);
		$this->data=array('gspgid'=>'','gspgtype'=>'');
		$config=gs_config::get_instance();
		foreach ($this->data_drivers as $key => $class_name)
		{
			load_file($config->lib_data_drivers_dir.$class_name.'.lib.php');
			$s_name='gs_data_driver_'.$class_name;
			$c=new $s_name;
			if ($c->test_type())
			{
				$this->data=array_merge($this->data,$c->import());

			}
		}
		if($this->data['gspgtype']==GS_DATA_POST && isset($this->data['gspgid_form']) 
					&& $this->data['gspgid_form']!=$this->data['gspgid']) {
			$gspgid_form=$this->data['gspgid_form'];
			$gspgid=$this->data['gspgid'];
			$c=new gs_data_driver_get;
			$this->data=$c->import();
			$this->data['gspgid']=$gspgid;
			$this->data['gspgid_form']=$gspgid_form;
		}
	}
	

	public function get_data()
	{
		return $this->data;
	}
}

interface gs_data_driver {

	function test_type();
	
	function import();
}

interface gs_module {
	function install();
	static function get_handlers();
	//static function register();
}

class gs_iterator implements Iterator, arrayaccess {
    public $array = array();  


    function add_element(&$element, $id=NULL) {
	    if ((is_subclass_of($element,'gs_record') || get_class($element)=='gs_record') && ($id!==NULL || $element->get_id() ) ) {
		    return $this->array[ $id!==NULL ? $id : $element->get_id()]=$element;
	    }
	    $this->array[]=$element;
		return $this;	
    }

    function shuffle() {
        shuffle($this->array);
    }

    function remove_element($element) {
	    if (get_class($element)=='gs_record') {
		    foreach ($this->array as $k=>$el) {
			    if ($el===$element) {
				    unset($this->array[$k]);
				    break;
			    }
		    }
	    } else {
		    if (isset($this->array[$element])) {
			    unset($this->array[$element]);
		    }
	    }
	    return $this;
    }

    function add($elements,$id=NULL) {
	    if (is_subclass_of($elements,'gs_iterator') || is_array($elements)) {
		    foreach($elements as $e) {
			    $this->add_element($e,$id);
		    }
		    return $this;
	    }
	    return $this->add_element($elements,$id);
    }
    function replace($elements) {
	    $this->reset();
	    $this->array=(array)$elements;
    }
    function reset() {
	    $this->array=array();
	    $this->rewind();
    }

    function shift() {
	    $ret=$this->first();
	    reset($this->array);
	    $key=key($this->array);
	    unset($this->array[$key]);
	    return $ret;
    }

    function rewind() {
	    reset($this->array);
    }
    function first() {
	    $this->rewind();
	    return $this->current();
    }
    function reverse() {
	    $values=$this->array;
	    $keys=array_keys($values);
	    if($keys && $values) $this->array=array_combine(array_reverse($keys),array_reverse($values));
	    return $this;
    }
    function current() {
	    return current($this->array);
    }

    function array_keys() {
	    return array_keys($this->array);
    }

    function key() {
        return key($this->array);
    }

    function next() {
	    return next($this->array);
    }
    function rand() {
        $this->load_records();
	    $rnd=rand(0,$this->count()-1);
	    $this->rewind();
	    for ($i=0;$i<$rnd;$i++) $this->next();
	    return $this->current();
    }
    function prev($cnt=null) {
	    if ($cnt===null) return prev($this->array);
	    $ret=$this->current();
	    for($i=0;$i<$cnt;$i++) {
		    $ret=$this->prev();
		    if ($ret===FALSE) {
			    return $this->first();
		    }
	    }
	    return $ret;
    }
    function end() {
	    return end($this->array);
    }

    function pop() {
	    return array_pop($this->array);
    }

    function count() {
	    return count($this->array);
    }

    function valid() {
		return current($this->array);
    }
    public function offsetSet($offset, $value) {
        $this->array[$offset] = $value;
    }
    public function offsetExists($offset) {
        return isset($this->array[$offset]);
    }
    public function offsetUnset($offset) {
        unset($this->array[$offset]);
    }
    public function offsetGet($offset) {
        return isset($this->array[$offset]) ? $this->array[$offset] : new gs_null(GS_NULL_XML);
    }

    function sort($flag=1) {
	    if (is_string($flag)) {
		    if (substr($flag,0,1)=='-') {
		    $field=substr($flag,1);
			    usort($this->array,create_function('$a,$b','return -strcmp($a->'.$field.',$b->'.$field.');'));
		    } else {
			    usort($this->array,create_function('$a,$b','return strcmp($a->'.$flag.',$b->'.$flag.');'));
		    }


	    } 
	    else if ($flag<0) krsort($this->array);
	    else ksort($this->array);
	    return $this;
    }
}


class gs_cacher {

	static function save($data,$subdir='.',$id=NULL) {
		$dirname=cfg('cache_dir').DIRECTORY_SEPARATOR.$subdir.DIRECTORY_SEPARATOR;
		check_and_create_dir($dirname);
		if (!$id) {
			$fn=tempnam($dirname,'');
			$id=basename($fn);
		} else {
			$fn=$dirname.$id;
		}
		try {
			if (!is_a($data,'gs_null')) file_put_contents_perm($fn,serialize($data));
		} catch (Exception $e) {
		}
		return $id;
	}
	static function load($id,$subdir='.') {
		$dirname=cfg('cache_dir').DIRECTORY_SEPARATOR.$subdir.DIRECTORY_SEPARATOR.$id;
		if (!file_exists($dirname)) return FALSE;
		$ret=unserialize(file_get_contents($dirname));
		return $ret;
	}
	static function clear($id,$subdir='.') {
		$dirname=cfg('cache_dir').DIRECTORY_SEPARATOR.$subdir.DIRECTORY_SEPARATOR.$id;
		return file_exists($dirname) && unlink($dirname);
	}
	static function cleardir($subdir=false) {
		if (!$subdir) return false;
		$dirname=cfg('cache_dir').DIRECTORY_SEPARATOR.$subdir;
		foreach (glob($dirname.DIRECTORY_SEPARATOR."*") as $filename) {
			unlink($filename);
		}
		return file_exists($dirname) && is_dir($dirname) && rmdir($dirname);
	}

}

class gs_session {

	static function add_message($obj,$name=GS_DEFAULT_MESSAGE) {
		$msg=self::load($name);
		if (!is_array($msg)) $msg=array();
		$msg[]=$obj;
		return self::save($msg,$name);
	}

	static function get_messages($name=GS_DEFAULT_MESSAGE,$flush=true) {
		$msg=self::load($name);
		if (!is_array($msg)) $msg=array();
		if ($flush) self::save(array(),$name);	
		return $msg;
	}

	static function get_messages_keys($name=GS_DEFAULT_MESSAGE,$flush=true) {
		$msg=self::get_messages($name,$flush);
		$ret=array();
		foreach ($msg as $k=>$m) {
			if (is_string($m)) $ret[$m]=$m;
		}
		return $ret;
	}


	static function save($obj,$name) {
		if (!isset($_COOKIE[GS_SESSION_COOKIE]) || gs_session::load()===FALSE) {
			$new_id=$_COOKIE[GS_SESSION_COOKIE]=gs_cacher::save(array(),GS_SESSION_COOKIE);
		} else {
			$new_id=$_COOKIE[GS_SESSION_COOKIE];
		}

		gs_setcookie(GS_SESSION_COOKIE,$new_id);
			

		$data=gs_session::load();
		$data[$name]=$obj;
		return gs_cacher::save($data,GS_SESSION_COOKIE,$new_id);
	}

	static function get_id() {
		$id=isset($_COOKIE[GS_SESSION_COOKIE]) ? $_COOKIE[GS_SESSION_COOKIE] : NULL;
		if (!$id) $id=self::save('_get_id','_get_id');
		return ($id);
	}

	static function load($name=NULL) {
		if (!isset($_COOKIE[GS_SESSION_COOKIE])) return FALSE;
		$ret=gs_cacher::load($_COOKIE[GS_SESSION_COOKIE],GS_SESSION_COOKIE);
		if ($name===NULL) return $ret;
		return isset($ret[$name]) ? $ret[$name] : NULL;
	}

	static function clear($name=NULL) {
		if (!isset($_COOKIE[GS_SESSION_COOKIE])) return FALSE;
		return gs_cacher::clear($_COOKIE[GS_SESSION_COOKIE],GS_SESSION_COOKIE);
		//return isset($ret[$name]) ? $ret[$name] : $ret;
	}


}






?>
