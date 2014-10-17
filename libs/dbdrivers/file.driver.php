<?php 
define('GS_DB_FILE_ID_LENGTH',4);
class gs_dbdriver_file extends gs_prepare_sql implements gs_dbdriver_interface {
	private $cinfo;
	private $db_connection;
	private $_res;
	private $_id;
	private $stats;
	private $d=array( '0'=>'a','1'=>'b','2'=>'c','3'=>'d','4'=>'e','5'=>'f','6'=>'g','7'=>'h',
			'8'=>'i','9'=>'j','a'=>'k','b'=>'l','c'=>'m','d'=>'n','e'=>'o','f'=>'p',
			'g'=>'q','h'=>'r','i'=>'s','j'=>'t','k'=>'u','l'=>'v','m'=>'w','n'=>'x',
			'o'=>'y','p'=>'z');
	function __construct($cinfo) {
		parent::__construct();
		$this->cinfo=$cinfo;
		$this->_id=rand();
		$this->_cache=array();
		$this->_que=null;
		$this->stats['total_time']=0;
		$this->stats['total_queries']=0;
		$this->stats['total_rows']=0;
		$this->connect();
	}
	
	function __destruct() {
		if (DEBUG) {
			//var_dump($this->stats);
		}
	}

	function connect() {
		$cinfo=$this->cinfo;
		check_and_create_dir($cinfo['db_root']);
		$this->root=$cinfo['db_root'];
		$this->www_root=isset($cinfo['www_root']) ? $cinfo['www_root'] : '';
	}


	function query($que='') {
		$t=microtime(true);
		if (DEBUG) {
			md($que);
		}
		$this->_res=mysql_query($que,$this->db_connection);
		
		if ($this->_res===FALSE) {
			throw new gs_dbd_exception('gs_dbdriver_mysql: '.mysql_error().' in query '.$que);
		}
		$t=microtime(true)-$t;
		$rows=mysql_affected_rows($this->db_connection);
		if (DEBUG) {
			md(sprintf("%.03f secounds, %d rows",$t, $rows));
		}
		$this->stats['total_time']+=$t;
		$this->stats['total_queries']+=1;
		$this->stats['total_rows']+=$rows;
		return $this->_res;

	}
	public function table_exists($tablename) {
		$fname=$this->root.DIRECTORY_SEPARATOR.$tablename;
		return is_dir($fname);
	}

	public function get_table_names() {
		return array();
	}

	public function get_fields_info($tablename) {
		return array();
	}

	public function get_table_fields($tablename) {
		$r=array();
		$fname=$this->root.DIRECTORY_SEPARATOR.$tablename.DIRECTORY_SEPARATOR.'fields';
		if (file_exists($fname)) $r=unserialize(file_get_contents($fname));
		return $r;
	}

	public function get_table_keys($tablename) {
		$r=array();
		return $r;
	}

	function construct_createtable_fields($options) {
		$table_fields=$this->construct_table_fields($options);
		return sprintf ('(%s)',implode(",",$table_fields));
	}
	function construct_altertable_fields($tablename,$options) {
		$fname=$this->root.DIRECTORY_SEPARATOR.$tablename;
		$tf=array();
		$table_fields=$this->construct_table_fields($options);
		$old_fields=$this->get_table_fields($tablename);
		$add_fields=array_diff(array_keys($table_fields),array_keys($old_fields));
		$mod_fields=array_intersect(array_keys($old_fields),array_keys($table_fields));
		$drop_fields=array_diff(array_keys($old_fields),array_keys($table_fields));
		$mask=DIRECTORY_SEPARATOR.'?'.str_repeat(DIRECTORY_SEPARATOR.'*',GS_DB_FILE_ID_LENGTH-1);
		foreach($drop_fields as $k=>$v) {
			$files=glob($fname.$mask.DIRECTORY_SEPARATOR.$v);
			foreach ($files as $del_fname) unlink ($del_fname);
		}
	}
	function construct_indexes($tablename,$structure) {
		$construct_indexes=isset($structure['indexes']) && is_array($structure['indexes']) ? $structure['indexes'] : array();
		$old_keys=$this->get_table_keys($tablename);
		foreach ($construct_indexes as $name=>$index) {
			if (!is_array($index)) {
			$name=$index;
			$index=array();
			}
			if (!isset($index['type'])) $index['type']='key';
			if (!isset($this->_index_types[$index['type']])) {
				throw new gs_dbd_exception('gs_dbdriver_file.construct_altertable: can not find definition for _index_types_'.$index['type']);
			}
			if (isset($old_keys[$name])) {
			} else {
				$fname=$this->root.DIRECTORY_SEPARATOR.$tablename.DIRECTORY_SEPARATOR.'indx'.DIRECTORY_SEPARATOR.$name;
				$bt=new b_tree($fname);
				mlog(sprintf('File query: CREATE INDEX b_tree %s(%s)',$tablename,$name));
			}
		}
	}
	
	public function construct_droptable($tablename) {
		$fname=$this->root.DIRECTORY_SEPARATOR.$tablename;
		return rmdir($fname);
	}
	public function construct_altertable($tablename,$structure) {
		switch (isset($structure['type']) ? $structure['type'] : '') {
		case 'view':
			$this->construct_droptable($tablename);
			return $this->construct_createtable($tablename,$structure);
		break;
		default:
			$this->construct_altertable_fields($tablename,$structure);
			$table_fields=$this->construct_table_fields($structure);
			$this->construct_indexes($tablename,$structure);
			$fname=$this->root.DIRECTORY_SEPARATOR.$tablename;
			file_put_contents_perm($fname.DIRECTORY_SEPARATOR.'fields',serialize($table_fields));
		}
	}
	public function construct_createtable($tablename,$structure) {
		switch (isset($structure['type']) ? $structure['type'] : '') {
		case 'view':
			throw new gs_dbd_exception('gs_dbdriver_file.construct_createtable: view have not implemented for file dbdriver');
		break;
		default:
			$fname=$this->root.DIRECTORY_SEPARATOR.$tablename;
			check_and_create_dir($fname);
			$table_fields=$this->construct_table_fields($structure);
			file_put_contents_perm($fname.DIRECTORY_SEPARATOR.'fields',serialize($table_fields));
			break;
		}
	}
	function get_id($tablename) {
		$cname=$this->root.DIRECTORY_SEPARATOR.$tablename.DIRECTORY_SEPARATOR.'counter';
		check_and_create_dir(dirname($cname));
		$counter=file_exists($cname) ?  file_get_contents($cname)+1 : 0;

		$r_id=$this->_get_id($tablename,$counter);
		while (file_exists($r_id)) {
			$counter++;
			$r_id=$this->_get_id($tablename,$counter);
		}
		file_put_contents_perm($cname,$counter);
		return $r_id;
	}

	function id2int($id) {
		$d=array_flip($this->d);
		$id=intval(strtr($id,$d),26);
		return($id);
	}

	function int2id($id) {
		if(is_numeric($id)) {
			$id=str_pad(strtr(base_convert($id,10,26),$this->d),GS_DB_FILE_ID_LENGTH,'a',STR_PAD_LEFT);
		}
		return $id;
	}
	
	function count($rset,$options) {
		$this->_que=md5(serialize($options));
		$this->_res=array(array('count'=>0));
		/*$where=$this->construct_where($options);
		$que=sprintf("SELECT count(*) as count  FROM %s ",$rset->db_tablename);
		if (!empty($where)) $que.=sprintf(" WHERE %s", $where);
		$this->_que=md5($que);
		if(isset($this->_cache[$this->_que])) {
			return true;
		}
		return $this->query($que);
		*/

		
	}
	
	function _get_id($tablename,$id) {
		$id=trim($id);
		//$id=$this->int2id($id);
		$id=$this->split_id($id);
		$ret=$this->root.DIRECTORY_SEPARATOR.$tablename.DIRECTORY_SEPARATOR.$id;
		return $ret;
	}
	function split_id($id,$no_fs=false) {
		
		if (is_numeric($id)) {
			$id=$this->int2id($id);
			$id=str_split($id,1);
			for($i=1;$i<GS_DB_FILE_ID_LENGTH;$i++) {
				$id[$i]=$id[$i-1].$id[$i];
			}
		} else {
			$id=str_split($id,ceil(strlen($id) / GS_DB_FILE_ID_LENGTH));
		}
		return implode(($no_fs==true) ? '/' : DIRECTORY_SEPARATOR,$id);
	}
	
	function fid2path($id) {
		$id=str_split($id,1);
		for($i=1;$i<GS_DB_FILE_ID_LENGTH;$i++) {
			$id[$i]=$id[$i-1].$id[$i];
		}
		return implode(DIRECTORY_SEPARATOR,$id);
	}
	
	public function insert($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$fields=$values=array();
		if ($record->get_id()) {
			$id=$this->root.DIRECTORY_SEPARATOR.$rset->db_tablename.DIRECTORY_SEPARATOR.$this->split_id($record->get_id());
		} else {
			$id=$this->get_id($rset->db_tablename);
		}
		mlog(sprintf('File query: insert %s record  %s',$rset->db_tablename,$id));
		check_and_create_dir($id);
		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ( $st['type']!='serial' && $record->is_modified($fieldname)) {
				$value=$record->$fieldname;
				if ($record->get_field_type($fieldname)=='object') $value=serialize($value);
				file_put_contents_perm($id.DIRECTORY_SEPARATOR.escapeshellcmd($fieldname),$value);
			}
		}
		
		$insert_id=basename($id);
		
		$construct_indexes=isset($rset->structure['indexes']) && is_array($rset->structure['indexes']) ? $rset->structure['indexes'] : array();
		foreach ($construct_indexes as $index => $name) {
			$fname=$this->root.DIRECTORY_SEPARATOR.($rset->db_tablename).DIRECTORY_SEPARATOR.'indx'.DIRECTORY_SEPARATOR.$index;
			$bt=new b_tree($fname);
			$bt->add($record->$index,$insert_id);
		}
		return $this->id2int($insert_id);

	}
	public function update($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$id=$this->root.DIRECTORY_SEPARATOR.$rset->db_tablename.DIRECTORY_SEPARATOR.$this->split_id($record->get_id());
		$construct_indexes=isset($rset->structure['indexes']) && is_array($rset->structure['indexes']) ? $rset->structure['indexes'] : array();
		foreach ($construct_indexes as $index => $name) {
			$fname=$this->root.DIRECTORY_SEPARATOR.($rset->db_tablename).DIRECTORY_SEPARATOR.'indx'.DIRECTORY_SEPARATOR.$index;
			$bt=new b_tree($fname);
			if($record->get_old_value($index)!=$record->$index) {
				$bt->delete($record->get_old_value($index),$record->get_old_value($rset->id_field_name));
				$bt->add($record->$index,$record->get_id());
			}
		}

		$fields=array();
		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ($record->is_modified($fieldname)) {
				//file_put_contents_perm($id.DIRECTORY_SEPARATOR.escapeshellcmd($fieldname),$record->$fieldname);
				$value=$record->$fieldname;
				if ($record->get_field_type($fieldname)=='object') $value=serialize($value);
				file_put_contents_perm($id.DIRECTORY_SEPARATOR.escapeshellcmd($fieldname),$value);
			}
		}
	}
	public function delete($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		md('---------------',1);
		md($record->get_id(),1);
		$id=$this->root.DIRECTORY_SEPARATOR.$rset->db_tablename.DIRECTORY_SEPARATOR.$this->split_id($record->get_id());
		$files=glob($id.DIRECTORY_SEPARATOR.'*');
		
		$construct_indexes=isset($rset->structure['indexes']) && is_array($rset->structure['indexes']) ? $rset->structure['indexes'] : array();
		foreach ($construct_indexes as $index => $name) {
			$fname=$this->root.DIRECTORY_SEPARATOR.($rset->db_tablename).DIRECTORY_SEPARATOR.'indx'.DIRECTORY_SEPARATOR.$index;
			$bt=new b_tree($fname);
			$bt->delete($record->$index,$record->get_id());
		}
		
		foreach($files as $f) {
			unlink($f);
		}
		
		rmdir($id);
	}
	function fetchall() {
		return $this->_res;
	}
	function fetch() {
		return next($this->_res);
	}
	function select($rset,$options,$fields=NULL) {
		$t=microtime(true);
		$this->_res=array();
		$fields = is_array($fields) ? $fields : array_keys($rset->structure['fields']);
		$this->root=rtrim($this->root,DIRECTORY_SEPARATOR);
		$fname=$this->root.DIRECTORY_SEPARATOR.$rset->db_tablename;
		$where=$this->construct_where($options);
		$use_id=false;
		$mask=null;
		if (isset($options[$rset->id_field_name]) ) {
			$use_id=true;
			if (trim($options[$rset->id_field_name])!=='') {
				$mask=DIRECTORY_SEPARATOR.$this->split_id($options[$rset->id_field_name]);
			}
		} else {
			$mask=DIRECTORY_SEPARATOR.'?'.str_repeat(DIRECTORY_SEPARATOR.'*',GS_DB_FILE_ID_LENGTH-1);
			foreach ($options as $o) {
				if (isset($o['field']) && $o['field']==$rset->id_field_name) {
					$mask=DIRECTORY_SEPARATOR.$this->split_id($o['value']);
					$use_id=true;
					continue;
				}
			}
		}
		$files=array();
		// If options not empty and index for this fileds created - use index
		if (!empty($options) && !$use_id) {
			$construct_indexes=isset($rset->structure['indexes']) && is_array($rset->structure['indexes']) ? $rset->structure['indexes'] : array();
			$idxs=array();
			//mlog($options);
			foreach ($options as $index => $value) {
				if (is_numeric($index) && $options[$index]['type']=='value') {
					$index=$value['field'];
					$value=$value['value'];
				}
				if (isset($construct_indexes[$index])) {
					mlog('====================');
					$idxs[]=$index.'='.$value;
					$iname=$this->root.DIRECTORY_SEPARATOR.($rset->db_tablename).DIRECTORY_SEPARATOR.'indx'.DIRECTORY_SEPARATOR.$index;
					mlog($iname);
					$bt=new b_tree($iname);
					if (!is_array($value)) $value=array($value);
					$ids=array();
					foreach ($value as $val) {
						$ids=array_merge($ids,$bt->find($val));
					}
					foreach ($ids as $id) {
						//$rid=$this->id2int($id);
						$path=rtrim(trim($fname.DIRECTORY_SEPARATOR.$this->fid2path($id)),DIRECTORY_SEPARATOR);
						$r=glob($path);
						if ($r) {
							$files=array_merge($files,$r);
						}
					}
				}
			}
			$mask='indexes('.implode(',',$idxs).')';
		} else {
			$files=$mask ? glob($fname.$mask): array();
		}
		
		foreach ($files as $f) {
			$rid=basename($f);
			if ($rset->structure['fields'][$rset->id_field_name]['type']=='varchar') {
				$rid=str_replace('/','',str_replace($fname,'',$f));
			}
			$d=array(
				$rset->id_field_name=>(strlen($rid)==GS_DB_FILE_ID_LENGTH) ? $this->id2int($rid) : $rid,
				);
			foreach ($fields as $field) {
				if (!isset($d[$field])) $d[$field]=file_exists($f.DIRECTORY_SEPARATOR.$field) ? file_get_contents($f.DIRECTORY_SEPARATOR.$field) : NULL;
			}
			$this->_res[$rid]=$d;
		}

		mlog(sprintf('File query on %s: %s fields: %s records: %s (%.06f sec)',$rset->db_tablename,$mask,implode(',',$fields),count($this->_res),(microtime(1)-$t)));
		return $this->_res;
	}

	function escape_value($v,$c=null) {
		if (is_float($v)) {
			return sprintf('truncate(%s,5)',str_replace(',','.',sprintf('%.05f',$v)));
		}else if (is_numeric($v)) {
			return $v;
		} else if (is_null($v)) {
			return 'NULL';
		} else if (is_array($v)) {
			$arr=array();
			foreach($v as $k=>$l) {
				$arr[]=$this->escape_value($l);
			}
			return sprintf('(%s)',implode(',',$arr));
		} else if ($c=='LIKE') {
			return sprintf('%s',mysql_escape_string($v));
		} else {
			return sprintf("'%s'",mysql_escape_string($v));
		}
	}

	function escape($f,$c,$v) {
		$v_type='STRING';
		if (is_float($v)) {$v_type='FLOAT';}
		else if (is_numeric($v)) {$v_type='NUMERIC';}
		else if (is_array($v)) {$v_type=!empty($v) ? 'ARRAY' : 'NULL';}
		else if (is_null($v)) {$v_type='NULL';}


		$escape_pattern=$this->_escape_case[$c][$v_type];
		$ret=$this->replace_pattern($escape_pattern,$v,$f,$c);
		return $ret;

	}

	function replace_pattern($escape_pattern,$v,$f=null,$c=null) {
		preg_match_all('/{v/',$escape_pattern,$value_replaces);
		if (sizeof($value_replaces[0])>1) {
			$ret=str_replace('{f}',$f,$escape_pattern);
			for ($i=0; $i<sizeof($value_replaces[0]); $i++) {
				$ret=str_replace("{v$i}",$this->escape_value($v[$i]),$ret);
			}
		} else {
			$v=$this->escape_value($v,$c);
			$ret=str_replace(array('{f}','{v}'),array($f,$v),$escape_pattern);
		}
		return $ret;
	}


}
?>
