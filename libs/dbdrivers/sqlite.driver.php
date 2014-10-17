<?php 
class gs_dbdriver_sqlite extends gs_prepare_sql implements gs_dbdriver_interface {
	private $cinfo;
	private $db_connection;
	private $_res;
	private $_id;
	private $stats;
	function __construct($cinfo) {
		parent::__construct();
		$this->_field_types['serial']='INTEGER PRIMARY KEY';
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

	function escape_value($v,$c=null) {
		if (is_float($v)) {
			return sprintf('round(%s,5)',str_replace(',','.',sprintf('%.05f',$v)));
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
			return sprintf('%s',$this->db_connection->escapeString($v));
		} else {
			return sprintf("'%s'",$this->db_connection->escapeString($v));
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


	function connect() {


		if(!class_exists('SQLite3')) throw new gs_dbd_exception('gs_dbdriver_sqlite : undefined class SQLite3 ');
		$this->db_connection=new SQLite3($this->cinfo['db_file']);
		if (!$this->db_connection) {
			throw new gs_dbd_exception('gs_dbdriver_sqlite: can not open database '.$this->cinfo['db_file']);
		}
	}


	function query($que='') {
		$t=microtime(true);

		mlog($que);

		$this->_res=$this->db_connection->query($que);
		
		if ($this->_res===FALSE) {
			throw new gs_dbd_exception('gs_dbdriver_sqlite: '.$this->db_connection->lastErrorMsg().' in query '.$que);
		}
		$t=microtime(true)-$t;
		$rows=count($this->_res);
		mlog(sprintf("%.03f secounds, %d rows",$t, $rows));
		$this->stats['total_time']+=$t;
		$this->stats['total_queries']+=1;
		$this->stats['total_rows']+=$rows;
		return $this->_res;

	}
	function exec($que='') {
		$t=microtime(true);
		$que=trim($que,';').';';
		mlog($que);
		$this->_res=$this->db_connection->exec($que);
		if (!$this->_res) {
			throw new gs_dbd_exception('gs_dbdriver_sqlite: '.$this->db_connection->lastErrorMsg().' in query '.$que);
		}
		$t=microtime(true)-$t;
		$rows=0;
		mlog(sprintf("%.03f secounds, %d rows",$t, $rows));
		$this->stats['total_time']+=$t;
		$this->stats['total_queries']+=1;
		$this->stats['total_rows']+=$rows;
		return $this->_res;

	}
	function get_insert_id() {
		return $this->db_connection->lastInsertRowID();
	}
	public function get_table_names() {
		$que=sprintf("SELECT name FROM sqlite_master WHERE type = 'table'");
		$this->query($que);
		return $this->fetchall();
	}
	public function get_fields_info ($tablename)  {
		return array();
	}
	public function table_exists($tablename) {
		$que=sprintf("SELECT name FROM sqlite_master WHERE type = 'table' and name='%s'",$tablename);
		$this->query($que);
		return $this->fetch();
	}

	public function get_table_fields($tablename) {
		$que=sprintf("PRAGMA table_info(%s)",$tablename);
		$this->query($que);
		$r=array();
		while ($a=$this->fetch()) { 
			$str=$a['name']." ".$a['type'];
			if($a['notnull']) $str.=" NOT NULL";
			if($a['pk']) $str.=" PRIMARY KEY";
			if($a['dflt_value']!==NULL) $str.=" DEFAULT ".$a['dflt_value'];
			$r[$a['name']]=$str;
		}
		return $r;
	}

	public function get_table_keys($tablename) {
		$que=sprintf("PRAGMA index_list(%s)",$tablename);
		$this->query($que);
		$r=array();
		while ($a=$this->fetch()) { 
			$r[$a['Column_name']]=$a['Key_name'];
		}
		return $r;
	}

	function construct_createtable_fields($options) {
		$table_fields=$this->construct_table_fields($options);
		return sprintf ('(%s)',implode(",",$table_fields));
	}
	function construct_altertable_fields($tablename,$options) {
		$tf=array();
		$table_fields=$this->construct_table_fields($options);

		$old_fields=$this->get_table_fields($tablename);

		if($table_fields==$old_fields) return $tf;

		$old_keys=array_keys($old_fields);
		$new_keys=array_keys($table_fields);
		$copy_s=implode(',',array_intersect($old_keys,$new_keys));
		$new_s=implode(',',$new_keys);
		$new_def=implode(',',$table_fields);


		$tf[]="
		BEGIN TRANSACTION;
		CREATE TEMPORARY TABLE _backup_$tablename($new_def);
		INSERT INTO _backup_$tablename ($copy_s)  SELECT $copy_s FROM $tablename;
		DROP TABLE $tablename;
		CREATE TABLE $tablename($new_def);
		INSERT INTO $tablename ($copy_s)  SELECT $copy_s FROM _backup_$tablename;
		DROP TABLE _backup_$tablename;
		COMMIT;
		";


		/*
		var_dump($tablename);
		var_dump($old_fields);
		var_dump($table_fields);
		md($tf,1);
		*/

		return $tf;

	}
	function construct_indexes($tablename,$structure) {
			$construct_indexes=isset($structure['indexes']) && is_array($structure['indexes']) ? $structure['indexes'] : array();
			/*
			if (is_array($structure['fields'])) foreach ($structure['fields'] as $key=>$field) {
				if (isset($field['type']) && $field['type']=='serial') {
					 $construct_indexes[$key]=array('type'=>'serial');
					 break;
				}
			}
			*/
			$old_keys=$this->get_table_keys($tablename);
			//mlog($construct_indexes);
			foreach ($construct_indexes as $name=>$index) {
					if (!is_array($index)) {
					$name=$index;
					$index=array();
					}
					if (!isset($index['type'])) $index['type']='key';
					if (!isset($this->_index_types[$index['type']])) {
						throw new gs_dbd_exception('gs_dbdriver_sqlite.construct_altertable: can not find definition for _index_types_'.$index['type']);
					}
					if (!isset($old_keys[$name])) {
						$que=sprintf('CREATE %s INDEX IF NOT EXISTS  %s ON %s(%s%s)',$this->_index_types[$index['type']],$name,$tablename,$name,isset($index['options'])?$index['options']:'');
						$this->query($que);
					}
				}
	}

	public function construct_droptable($tablename) {
			if ($this->table_exists($tablename)) {
				$que=sprintf('DROP TABLE IF EXISTS %s',$tablename);
				return $this->exec($que);
			}
	}
	public function construct_altertable($tablename,$structure) {
		$construct_fields=$this->construct_altertable_fields($tablename,$structure);
		if ($construct_fields) foreach ($construct_fields as $af) {
			$this->exec($af);
			$this->construct_indexes($tablename,$structure);
		}
	}
	public function construct_createtable($tablename,$structure) {
		$construct_fields=$this->construct_createtable_fields($structure);
		$this->construct_droptable($tablename);
		$que=sprintf('CREATE TABLE  %s %s ',$tablename, $construct_fields);
		$this->exec($que);
		$this->construct_indexes($tablename,$structure);
	}
	public function insert($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$fields=$values=array();
		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ( $st['type']!='serial' && $record->is_modified($fieldname)) {
				$fields[]=$fieldname;
				$values[]=$this->escape_value($record->$fieldname);
			}
		}
		$que=sprintf('INSERT INTO %s (%s) VALUES  (%s)',$rset->db_tablename,implode(',',$fields),implode(',',$values));
		$this->query($que);
		return $this->get_insert_id();

	}
	public function update($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$fields=array();
		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ($record->is_modified($fieldname)) {
				$fields[]=sprintf('%s=%s',$fieldname,$this->escape_value($record->$fieldname));
			}
		}
		if (sizeof($fields)==0) return;
		$idname=$rset->id_field_name;
		$que=sprintf('UPDATE %s SET %s WHERE %s=%s',$rset->db_tablename,implode(',',$fields),$idname,$this->escape_value($record->get_old_value($idname)));
		return $this->query($que);

	}
	public function delete($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$idname=$rset->id_field_name;
		$que=sprintf('DELETE FROM %s  WHERE %s=%s',$rset->db_tablename,$idname,$this->escape_value($record->get_old_value($idname)));
		return $this->query($que);

	}
	function fetchall() {
		$ret=array();
		if (!$this->_que) {
			while ($r=$this->_res->fetchArray(SQLITE3_ASSOC)) $ret[]=$r;
			return $ret;
		}
		if (!isset($this->_cache[$this->_que])) {
			while ($r=$this->_res->fetchArray(SQLITE3_ASSOC)) $ret[]=$r;
			$this->_cache[$this->_que]=$ret;
		}
		$ret=$this->_cache[$this->_que];
		$this->_que=null;
		return $ret;
	}
	function fetch() {
		$res=$this->_res->fetchArray(SQLITE3_ASSOC);
		return $res;
	}
	function count($rset,$options) {
		$where=$this->construct_where($options);
		$que=sprintf("SELECT count(*) as count  FROM %s ",$rset->db_tablename);
		if (!empty($where)) $que.=sprintf(" WHERE %s", $where);
		$this->_que=md5($que);
		if(isset($this->_cache[$this->_que])) {
			return true;
		}
		return $this->query($que);
	}
	function select($rset,$options,$fields=NULL) {
		$where=$this->construct_where($options);
		//md($rset->structure['fields'],1);
		$fields = is_array($fields) ? array_filter($fields) : array_keys($rset->structure['fields']);
		$que=sprintf("SELECT %s FROM %s ", implode(',',$fields), $rset->db_tablename);
		if (is_array($options)) foreach($options as $o) {
			if (isset($o['type'])) switch($o['type']) {
				case 'limit':
					$str_limit=sprintf(' LIMIT %d ',$this->escape_value($o['value']));
					break;
				case 'offset':
					$str_offset=sprintf(' OFFSET %d ',$this->escape_value($o['value']));
					break;
				case 'orderby':
					$str_orderby=sprintf(' ORDER BY %s ',$this->db_connection->escapeString($o['value']));
					break;
				case 'groupby':
					$str_groupby=sprintf(' GROUP BY %s ',$this->db_connection->escapeString($o['value']));
					break;
			}
		}
		if (!empty($where)) $que.=sprintf(" WHERE %s", $where);
		if (!empty($str_groupby)) $que.=$str_groupby;
		if (!empty($str_orderby)) $que.=$str_orderby;
		if (!empty($str_limit)) $que.=$str_limit;
		if (!empty($str_offset)) $que.=$str_offset;

		$this->_que=md5($que);
		if(isset($this->_cache[$this->_que])) {
			return true;
		}

		return $this->query($que);
	}


}
?>
