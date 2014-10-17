<?php 
class gs_dbdriver_mongo extends gs_prepare_sql implements gs_dbdriver_interface {
	private $cinfo;
	private $db_connection;
	private $_res;
	private $_id;
	private $stats;
	function __construct($cinfo) {
		parent::__construct();
		$this->_escape_case=array(
		                        '='=>'$in',
		                        '!='=>'$ne',
		                        '>'=>'$gt',
		                        '>='=>'$gte',
		                        '<'=>'$lt',
		                        '<='=>'$lte',
		                        'STRONGLIKE'=>'$regex',
		                        'LIKE'=>'$regex',
		                        'STARTS'=>'$regex',
		                        'ENDS'=>'$regex',
		                        'FULLTEXT'=>'$regex',
		                        'REGEXP'=>'$regex',
		                        'NOTREGEXP'=>'$regex',
		                        'BETWEEN'=>'$and',
		                    );
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



	function connect() {


		if(!class_exists('Mongo')) throw new gs_dbd_exception('gs_dbdriver_mongo: undefined class Mongo');
		$mongo=new Mongo(sprintf("mongodb://%s:%s@%s/%s",
						$this->cinfo['db_username'],
						$this->cinfo['db_password'],
						$this->cinfo['db_hostname'],
						$this->cinfo['db_database']
						));
		if (!$mongo) {
			throw new gs_dbd_exception('gs_dbdriver_mongo: can not open database '.$this->cinfo['db_hostname']);
		}
		$this->db_connection=$mongo->selectDB($this->cinfo['db_database']);
	}


	function query($que='') {
		$this->_res=$que;
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
		return $this->db_connection->listCollections();
	}
	public function get_fields_info ($tablename)  {
		return array();
	}
	public function table_exists($tablename) {
		$tables=$this->get_table_names();
		return in_array($tablename,$tables);
	}

	public function get_table_fields($tablename) {
		return array();
	}

	public function get_table_keys($tablename) {
		$c=$this->db_connection->selectCollection($tablename)->getIndexInfo();
		return $c;
	}

	function construct_createtable_fields($options) {
	}
	function construct_altertable_fields($tablename,$options) {
	}
	function construct_indexes($tablename,$structure) {
	}

	public function construct_droptable($tablename) {
		return $this->db_connection->dropCollection($tablename);
	}
	public function construct_altertable($tablename,$structure) {
	}
	public function construct_createtable($tablename,$structure) {
		return $this->db_connection->createCollection($tablename);
	}
	public function insert($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$fields=$values=array();
		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ( $st['type']!='serial' && $record->is_modified($fieldname)) {
				$values[$fieldname]=$this->escape_value($record->$fieldname);
			}
		}
		$this->recordset=$rset;
		$this->db_connection->selectCollection($rset->db_tablename)->insert($values);
		return trim($values['_id']);

	}
	public function update($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$values=array();
		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ($record->is_modified($fieldname)) {
				//$fields[]=sprintf('%s=%s',$fieldname,$this->escape_value($record->$fieldname));
				$values[$fieldname]=$this->escape_value($record->$fieldname);
			}
		}
		$idname=$rset->id_field_name;
		$theObjId = new MongoId($record->get_old_value($idname)); 
		return $this->db_connection->selectCollection($rset->db_tablename)->update(
									//array('_id'=>$record->get_old_value($idname)),
									array('_id'=>$theObjId),
									array('$set' => $values)
									);

	}
	public function delete($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
		$idname=$rset->id_field_name;
		return $this->db_connection->selectCollection($rset->db_tablename)->remove(array('_id'=>new MongoId($record->get_old_value($idname))));

	}
	function fetchall() {
		$ret=array();
		$idfieldname=isset($this->recordset) ? $this->recordset->id_field_name : null;
		if (!$this->_que) {
			foreach ($this->_res as $r) {

				if (is_array($r) && isset($r['_id']) && $idfieldname) {
					$r[$idfieldname]=trim($r['_id']);
					unset($r['_id']);
				}
				$ret[]=$r;
			}
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
		$count=$this->db_connection->selectCollection($rset->db_tablename)->count($where);
		return $this->query(array(array('count'=>$count)));
	}
	function select($rset,$options,$fields=NULL) {
        mlog($options);
		$this->recordset=$rset;
		$col=$this->selectCollection($rset);
        
		$where=$this->construct_where($options);

		/*
		md('-------------------',1);
		md($this->recordset->get_recordset_name(),1);
		md($options,1);
		md($where,1);
		*/


		if ($fields) $ret=$col->find($where,$fields);
			else $ret=$col->find($where);
		


		
		if (is_array($options)) foreach($options as $o) {
			if (isset($o['type'])) switch($o['type']) {
				case 'limit':
					$ret->limit($this->escape_value($o['value']));
					break;
				case 'offset':
					$ret->skip($this->escape_value($o['value']));
					break;
				case 'orderby':
					$val=explode(',',$o['value']);
					$sort_arr=array();
					foreach ($val as $v) {
						$f=explode(' ',$v,2);
						$sort_arr[$f[0]]= (isset($f[1]) && stripos($f[1],'DESC')!==FALSE) ? -1 : 1;
					}
					$ret->sort($sort_arr);
					break;
				case 'groupby':
					break;
			}
		}
		return $this->query($ret);	
	}
	private function selectCollection($rset) {
		return $this->db_connection->selectCollection($rset->db_tablename);
	}

	function  construct_where($options,$type='AND') {
		$tmpsql=array();
		$counter_or=0;
		if (is_array($options)) foreach ($options as $kkey=>$value) {
					//$txt=$this->escape($value['field'],$value['case'],$value['value']);
				if (isset($value['type']) && $value['type']=='condition') {
					$condition=$value['condition'];
					unset($value['type']);
					unset($value['condition']);
					$casesql=$this->construct_where($value,$condition);
					$tmpsql[]=$casesql;
					continue;
				} 

				if (!is_array($value) || !isset($value['value'])) {
					$value=array('type'=>'value', 'field'=>$kkey,'case'=>'=','value'=>$value);
				}
				if (!isset($value['case'])) $value['case']='=';
				if (!isset($value['type'])) $value['type']='value';



				if ($value['type']=='value') {

					if ($value['field']==$this->recordset->id_field_name) {
						$value['field']='_id';
						if (is_array($value['value'])) {
							foreach ($value['value'] as $k=>$v) {
								$value['value'][$k]=new MongoId($v);
							}
						} else {
							$value['value']=new MongoId($value['value']);
						}
					}

					if ($value['case']=='=') {
						if (is_array($value['value'])) {
							$tmpsql[][$value['field']]['$in']=$this->escape_value($value['value'],$value['case']);	
						} else {
							$tmpsql[][$value['field']]=$this->escape_value($value['value'],$value['case']);	
						}
					} else {
						$tmpsql[][$value['field']][$this->escape_case($value['case'])]=$this->escape_value($value['value'],$value['case']);	
					}

				}

		}
		if (!$tmpsql) return array();
		return array($type=='OR' ? '$or' : '$and'  => $tmpsql);

	}
	function escape_case($case) {
		$ret=$this->_escape_case[$case];
		return $ret;
	}
	function escape_value($v,$c=null) {
		if ($c=='LIKE') return ".*$v.*";
		return $v;
	}


}
?>
