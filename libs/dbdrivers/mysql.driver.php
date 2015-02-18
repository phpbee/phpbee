<?php 
class gs_dbdriver_mysql extends gs_prepare_sql implements gs_dbdriver_interface {
	private $cinfo;
	private $db_connection;
	private $_res;
	private $_id;
	private $stats;
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
    function get_stats() {
		return sprintf('gs_dbdriver_mysql total time: %.05fs queries: %d rows: %d',$this->stats['total_time'],$this->stats['total_queries'],$this->stats['total_rows']);
    }

	function escape_value($v,$c=null) {
		if (is_float($v)) {
			return sprintf('truncate(%s,5)',str_replace(',','.',sprintf('%.05f',$v)));
		//}else if (is_numeric($v) && intval($v)==$v) {
		}else if (is_int($v)) {
			return $v;
		} else if (is_null($v)) {
			return 'NULL';
		} else if ($c=='SET' && is_array($v) ) {
			return $this->escape_value(implode(',',$v));
		} else if (is_array($v)) {
			$arr=array();
			foreach($v as $k=>$l) {
				$arr[]=$this->escape_value($l);
			}
			return sprintf('(%s)',implode(',',$arr));
		} else if (is_a($v,'DateTime')) {
			return sprintf("'%s'",$v->format('Y-m-d'));	
		} else if ($c=='LIKE' || $c=='NOTLIKE' || $c=='STRONGLIKE' || $c=='STARTS' || $c=='ENDS') {
			return sprintf('%s',mysql_real_escape_string($v,$this->db_connection));
		} else {
			return sprintf("'%s'",mysql_real_escape_string($v,$this->db_connection));
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


		$cinfo=$this->cinfo;
		if(!function_exists('mysql_connect')) throw new gs_dbd_exception(get_class($this).': undefined function mysql_connect() ');
		$this->db_connection=@mysql_connect($cinfo['db_hostname'].':'.$cinfo['db_port'],$cinfo['db_username'],$cinfo['db_password'],TRUE);
		if ($this->db_connection ===FALSE) {
			throw new gs_dbd_exception(get_class($this).': '.mysql_error());
		}
		if (mysql_select_db($cinfo['db_database'],$this->db_connection)===FALSE) {
			throw new gs_dbd_exception(get_class($this).': '.mysql_error());
		}
        mlog('connected to '.$cinfo['db_database']);
		if (isset($cinfo['codepage']) && !empty($cinfo['codepage'])) {
            $this->set_connection_charset($cinfo['codepage']);
		}
	}

    function set_connection_charset($codepage) {
			$this->query(sprintf('SET NAMES %s COLLATE %s_general_ci',$codepage,$codepage));
    }


	function query($que='') {
		$t=microtime(true);

		if (!mysql_ping($this->db_connection)) $this->connect();

		mlog($que);



		$this->_res=mysql_query($que,$this->db_connection);
		
		if ($this->_res===FALSE) {
			//throw new gs_dbd_exception(get_class($this).': '.mysql_error().' in query '.$que. ' connector:'.implode('|',$this->cinfo));
			throw new gs_dbd_exception(get_class($this).': '.mysql_error().' in query '.$que);
		}
		$t=microtime(true)-$t;
		$rows=mysql_affected_rows($this->db_connection);
		mlog(sprintf("%.03f secounds, %d rows",$t, $rows));
		$this->stats['total_time']+=$t;
		$this->stats['total_queries']+=1;
		$this->stats['total_rows']+=$rows;
		return $this->_res;

	}
	function get_insert_id() {
		$id=mysql_insert_id($this->db_connection) ;
        return $id ? $id : FALSE;
	}
	public function get_table_names() {
		$que=sprintf("SHOW TABLES");
		$this->query($que);
		$ret=array();
		$t=$this->fetchall();
		foreach ($t as $row) {
			$ret[]=reset($row);
		}
		return $ret;

	}
	public function table_exists($tablename) {
		$que=sprintf("SHOW TABLES LIKE '%s'",$tablename);
		$this->query($que);
		return $this->fetch();
	}

	public function get_table_fields($tablename) {
		$que=sprintf("SHOW FIELDS from `%s`",$tablename);
		$this->query($que);
		$r=array();
		while ($a=$this->fetch()) { 
			$r[$a['Field']]=$a['Field'];
		}
		return $r;
	}
	public function get_fields_info($tablename) {
		$this->query(sprintf("SHOW FIELDS from `%s`",$tablename));
		$ret=array();
		while($r=$this->fetch()) {
			$ret[$r['Field']]=array(
				'Field'=>$r['Field'],
				'Type'=>$r['Type'],
				'Null'=>$r['Null'],
				'Key'=>$r['Key'],
				'Default'=>$r['Default'],
				'Extra'=>$r['Extra'],
			);
		}
		return $ret;
	}

	public function get_table_keys($tablename) {
		$que=sprintf("SHOW KEYS from `%s`",$tablename);
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

		$add_fields=array_diff(array_keys($table_fields),array_keys($old_fields));
		foreach($add_fields as $k=>$v) {
			$tf[]="ADD ".str_ireplace('AUTO_INCREMENT PRIMARY KEY','',$table_fields[$v]);
		}

		$mod_fields=array_intersect(array_keys($old_fields),array_keys($table_fields));
		foreach($mod_fields as $k=>$v) {
			if (!isset($options['fields'][$k]['type']) && $options['fields'][$v]['type']!='serial') 
				$tf[]="MODIFY ".str_ireplace('AUTO_INCREMENT PRIMARY KEY','',$table_fields[$v]);
		}

		$drop_fields=array_diff(array_keys($old_fields),array_keys($table_fields));
		foreach($drop_fields as $k=>$v) {
			if (!isset($options['fields'][$v]['type']) || $options['fields'][$v]['type']!='serial') 
				$tf[]="DROP `$v`";
		}

		return sprintf ('%s',implode(",",$tf));
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
						throw new gs_dbd_exception('gs_dbdriver_mysql.construct_altertable: can not find definition for _index_types_'.$index['type']);
					}
					if (isset($old_keys[$name])) {
						//$que=sprintf('ALTER TABLE %s DROP %s KEY',$tablename,$old_keys[$name]);
						//$this->query($que);
					} else {
						$que=sprintf('CREATE %s INDEX `%s` ON `%s`(`%s%s`)',$this->_index_types[$index['type']],$name,$tablename,$name,isset($index['options'])?$index['options']:'');
						$this->query($que);
					}
				}
	}

	public function construct_droptable($tablename) {
			$que=sprintf('DROP TABLE IF EXISTS  `%s`',$tablename);
			return $this->query($que);
	}
	public function construct_altertable($tablename,$structure) {
		switch (isset($structure['type']) ? $structure['type'] : '') {
		case 'view':
			$this->construct_droptable($tablename);
			return $this->construct_createtable($tablename,$structure);
		break;
		default:
			$construct_fields=$this->construct_altertable_fields($tablename,$structure);
			$que=sprintf('ALTER TABLE  %s %s',$tablename, $construct_fields);
			$this->query($que);
			$this->construct_indexes($tablename,$structure);
			break;
		}
	}
	public function construct_createtable($tablename,$structure) {
		switch (isset($structure['type']) ? $structure['type'] : '') {
		case 'view':
			foreach($structure['recordsets'] as $rs_name) {
				$obj[$rs_name]=new $rs_name;
				if (isset($obj[$rs_name]->structure['keys'])) foreach ($obj[$rs_name]->structure['keys'] as $key) {
					if ($key['type']=='foreign' && in_array($key['recordset'],$structure['recordsets'] )) {
						$que=sprintf('CREATE VIEW %s AS SELECT * FROM `%s` LEFT JOIN  `%s` ON (%s.%s=%s.%s)',
								$tablename,$obj[$key['recordset']]->table_name,$obj[$rs_name]->table_name,
								$obj[$rs_name]->table_name, $key['local_field_name'],
								$obj[$key['recordset']]->table_name,$key['foreign_field_name']);

						var_dump($que);
						$this->query($que);
						return TRUE;

					}
				}

			}
				
		break;
		default:
			$construct_fields=$this->construct_createtable_fields($structure);
			$this->construct_droptable($tablename);
			$que=sprintf('CREATE TABLE  `%s` %s ENGINE=MyISAM CHARACTER SET=%s',$tablename, $construct_fields,$this->cinfo['codepage']);
			$this->query($que);
			$this->construct_indexes($tablename,$structure);
			break;
		}
	}
	public function insert($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$fields=$values=array();
		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ( $st['type']!='serial' && $record->is_modified($fieldname)) {
				$fields[]=$fieldname;
				if ($st['type']=='set') {
					$values[]=$this->escape_value($record->$fieldname,'SET');
				} else {
					$value=$record->$fieldname;
					if ($record->get_field_type($fieldname)=='object') $value=serialize($value);
					$values[]=$this->escape_value($value);
				}
			}
		}
		$que=sprintf('INSERT INTO `%s` (`%s`) VALUES  (%s)',$rset->db_tablename,implode('`,`',$fields),implode(',',$values));
		$this->query($que);
		return $this->get_insert_id();

	}
	public function update($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$fields=array();
		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			
			if ($record->is_modified($fieldname)) {
				if ($st['type']=='set') {
				$fields[]=sprintf('`%s` = %s',$fieldname,$this->escape_value($record->$fieldname,'SET'));
				} else {
					$value=$record->$fieldname;
					if ($record->get_field_type($fieldname)=='object') $value=serialize($value);
					$fields[]=sprintf('`%s`=%s',$fieldname,$this->escape_value($value));
				}
				//$fields[]=$this->escape($fieldname,'=',$record->$fieldname);
			}
		}
		if (sizeof($fields)==0) return;
		$idname=$rset->id_field_name;
		$que=sprintf('UPDATE `%s` SET %s WHERE `%s`=%s',$rset->db_tablename,implode(',',$fields),$idname,$this->escape_value($record->get_old_value($idname)));
		return $this->query($que);

	}
	public function delete($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$idname=$rset->id_field_name;
		$que=sprintf('DELETE FROM `%s`  WHERE %s=%s',$rset->db_tablename,$idname,$this->escape_value($record->get_old_value($idname)));
		return $this->query($que);

	}
	function fetchall() {
		$ret=array();
		if (!$this->_que) {
			while ($r=mysql_fetch_assoc($this->_res)) $ret[]=$r;
			return $ret;
		}
		if (!isset($this->_cache[$this->_que])) {
			while ($r=mysql_fetch_assoc($this->_res)) $ret[]=$r;
			$this->_cache[$this->_que]=$ret;
		}
		$ret=$this->_cache[$this->_que];
		$this->_que=null;
		return $ret;
	}
	function fetch() {
		return mysql_fetch_assoc($this->_res);
	}

	function to_unions($options) {

		$ands=array();
		$ors=array();
		$unions=array('options'=>$options,'unions'=>array());

		$or_counter=0;
		foreach ($options as $k=>$o) {
			if (isset($o['condition']) && strtoupper($o['condition'])=='OR' && $or_counter==0) {
				$or_counter++;
				$ors=$o;
			} else {
				$ands[$k]=$o;
			}
		}
		if ($or_counter==0) return $unions;

		foreach ($ors as $k=>$o) {
			if (!is_array($o)) continue;
			$union=$ands;
			$union[]=$o;
			$ret=$this->to_unions($union);
			if ($ret['unions']) {
				$unions['unions']=array_merge($unions['unions'],$ret['unions']);
			} else {
				$unions['unions'][]=$ret['options'];
			}
		}

		return $unions;

		

	}


	function count($rset,$options) {
		/*
		array_unshift($options,array(
					'type'=>'condition',
					'condition'=>'or',
					array('type'=>'value','field'=>'transactionType','value'=>'sale'),
					array('type'=>'value','field'=>'transactionType','value'=>'refund'),
					));
		*/

		$unions=$this->to_unions($options);
		if ($unions['unions'] && count($unions['unions'])>1) {
			$uq=array();
			foreach($unions['unions'] as $u) {
				$q=$this->construct_where($u);
				$uq[]=sprintf("SELECT `%s`  FROM `%s` WHERE %s ",$rset->id_field_name, $rset->db_tablename,$q);

			}
			$q=implode(" UNION DISTINCT ",$uq);
			$que=sprintf("SELECT count(*) as count  FROM (%s) as a",$q);

		} else {
			$where=$this->construct_where($options);
			$que=sprintf("SELECT count(*) as count  FROM `%s` ",$rset->db_tablename);
			if (!empty($where)) $que.=sprintf(" WHERE %s", $where);
		}
		$this->_que=md5($que);
		if(isset($this->_cache[$this->_que])) {
			return true;
		}

		return $this->query($que);
	}
	function select($rset,$options,$fields=NULL) {
		$fields = is_array($fields) ? array_filter($fields) : array_keys($rset->structure['fields']);
		foreach ($fields as $k=>$f) {
			if ($rset->structure['fields'][$f]['type']=='float') {
				$fields[$k]=sprintf("`%s`*1 AS `%s`",$f,$f);
			} else {
				$fields[$k]='`'.$f.'`';
			}
		}
		//$que=sprintf("SELECT `%s` FROM `%s` ", implode('`,`',$fields), $rset->db_tablename);
		$unions=$this->to_unions($options);
		if ($unions['unions'] && count($unions['unions'])>1) {
			$uq=array();
			foreach($unions['unions'] as $u) {
				$q=$this->construct_where($u);
				//$uq[]=sprintf("SELECT %s  FROM `%s` WHERE %s ", implode(',',$fields), $rset->db_tablename,$q);
				$uq[]=sprintf("SELECT *  FROM `%s` WHERE %s ", $rset->db_tablename,$q);

			}
			$q=implode(" UNION DISTINCT ",$uq);
			$que=sprintf("SELECT %s  FROM (%s) as a", implode(',',$fields),$q);

		} else {
			$where=$this->construct_where($options);
			$que=sprintf("SELECT %s FROM `%s` ", implode(',',$fields), $rset->db_tablename);
			if (!empty($where)) $que.=sprintf(" WHERE %s", $where);
		}
		if (is_array($options)) foreach($options as $o) {
			if (isset($o['type'])) switch($o['type']) {
				case 'limit':
					$str_limit=sprintf(' LIMIT %d ',intval($o['value']));
					break;
				case 'offset':
					$str_offset=sprintf(' OFFSET %d ',intval($o['value']));
					break;
				case 'orderby':
					$str_orderby=sprintf(' ORDER BY %s ',mysql_real_escape_string($o['value'],$this->db_connection));
					break;
				case 'groupby':
					$str_groupby=sprintf(' GROUP BY %s ',mysql_real_escape_string($o['value'],$this->db_connection));
					break;
			}
		}
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

	function dbimport($table) {
		$ret=array();
		$fields=$this->get_fields_info($table);
		foreach ($fields as $f) {
			$r=array(
				'name'=>$f['Field'],
				'verbose_name'=>$f['Field'],
			);

			if($f['Null']=='NO') $r['required']=1;
			if($f['Default']) $r['default_value']=$f['Default'];
			if($f['Key']) $r['index']=1;

			if ($f['Extra']=='auto_increment') {
				$r['type']='serial';
				$r['verbose_name']=null;

			} else if(strpos($f['Type'],'int(')===0) {
				$r['type']='fInt';

			} else if($f['Type']=='float') {
				$r['type']='fFloat';

			} else if(strpos($f['Type'],'varchar(')===0) {
				$r['type']='fString';
				$r['options']=reset(sscanf($f['Type'],'varchar(%d)'));

			} else if(strpos($f['Type'],'enum(')===0) {
				$r['type']='fSelect';
				preg_match('/enum\((.*)\)/',$f['Type'],$options);
				$r['options']=$options[1];

			} else if($f['Type']=='datetime') {
				$r['type']='fDateTime';

			} else if($f['Type']=='text') {
				$r['type']='fText';

			}	

			$ret[]=$r;
		}

		return $ret;

	}


}
?>
