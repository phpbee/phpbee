<?php
class gs_dbdriver_sphinx extends gs_dbdriver_mysql implements gs_dbdriver_interface {

    function __construct($cinfo){
            parent::__construct($cinfo);
            $this->_escape_case['FULLTEXT']=array('FLOAT'=>'`{f}`={v}','NUMERIC'=>'`{f}`={v}','STRING'=>" MATCH ('@{f} {v}') ",'NULL'=>'FALSE');
    }
    function set_connection_charset($codepage) {
			$this->query(sprintf("SET NAMES '%s'",$codepage));
    }
	function query($que='') {
        mlog($que);
        return parent::query($que);
    }
	function escape_value($v,$c=null) {
		if (is_float($v)) {
			return sprintf('truncate(%s,5)',str_replace(',','.',sprintf('%.05f',$v)));
		}else if (is_numeric($v)) {
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
		} else if ($c=='LIKE' || $c=='STRONGLIKE' || $c=='STARTS' || $c=='ENDS' || $c=='FULLTEXT') {
			return sprintf('%s',mysql_escape_string($v));
		} else {
			return sprintf("'%s'",mysql_escape_string($v));
		}
	}

	public function insert($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
        $max_id=$this->fetch($this->query(sprintf('SELECT id from `%s` order by id desc limit 1 ',$rset->db_tablename)));
        $max_id=isset($max_id['id']) ? $max_id['id']+1 : 1;
        $fields=array('id'); $values=array($max_id);

		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ( $st['type']!='serial' && $record->is_modified($fieldname)) {
				$fields[]=$fieldname;
				if ($st['type']=='set') {
					$values[]=$this->escape_value($record->$fieldname,'SET');
				} else {
					$values[]=$this->escape_value($record->$fieldname);
				}
			}
		}
		$que=sprintf('INSERT INTO `%s` (`%s`) VALUES  (%s)',$rset->db_tablename,implode('`,`',$fields),implode(',',$values));
		$r=$this->query($que);
		return $r ? $max_id : FALSE;

	}
	public function update($record) {
		$this->_cache=array();
		$rset=$record->get_recordset();
        $fields=$values=array();

		foreach ($rset->structure['fields'] as $fieldname=>$st) {
			if ( $st['type']!='serial') {
				$fields[]=$fieldname;
				if ($st['type']=='set') {
					$values[]=$this->escape_value($record->$fieldname,'SET');
				} else {
					$values[]=$this->escape_value($record->$fieldname);
				}
			}
		}
        if (!$fields) return true;
        array_unshift($fields,'id');
        array_unshift($values,$record->get_id());

		$que=sprintf('REPLACE INTO `%s` (`%s`) VALUES  (%s)',$rset->db_tablename,implode('`,`',$fields),implode(',',$values));
		return $this->query($que);
	}
	
	function select($rset,$options,$fields=NULL) {
		$where=$this->construct_where($options);
		$fields = is_array($fields) ? array_filter($fields) : array_keys($rset->structure['fields']);
		$que=sprintf("SELECT * FROM `%s` ", $rset->db_tablename);
		if (is_array($options)) foreach($options as $o) {
			if (isset($o['type'])) switch($o['type']) {
				case 'limit':
					$str_limit=sprintf(' LIMIT %d ',$this->escape_value($o['value']));
					break;
				case 'offset':
					$str_offset=sprintf(' OFFSET %d ',$this->escape_value($o['value']));
					break;
				case 'orderby':
					$str_orderby=sprintf(' ORDER BY %s ',mysql_escape_string($o['value']));
					break;
				case 'groupby':
					$str_groupby=sprintf(' GROUP BY %s ',mysql_escape_string($o['value']));
					break;
			}
		}
		$where=trim($where,'()');
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
	
	function count($rset,$options) {
        $cnt=0;
        $options[]=array('type'=>'limit','value'=>10000);
        $ret=$this->select($rset,$options);
        if (!is_bool($ret))  $cnt=mysql_num_rows($ret);
        return $this->query(sprintf(" select %d as `count` from `%s` limit 1",$cnt,$rset->table_name));
	}
	
	function construct_where($options,$type='AND') {
		$tmpsql=array();
		$counter_or=0;
		$fulltext=array();
		if (is_array($options)) foreach ($options as $kkey=>$value) {
			if (!is_array($value) || !isset($value['value'])) {
				$value=array('type'=>'value', 'field'=>$kkey,'case'=>'=','value'=>$value);
			}
			if (!isset($value['case'])) $value['case']='=';
			if (!isset($value['type'])) $value['type']='value';


			switch ($value['type']) {
			case 'value':
				if ($value['case']=='FULLTEXT') {
					$fulltext[]='@'.$value['field'].' '.$value['value'];
				}
				//$txt=$this->escape($value['field'],$value['case'],$value['value']);
				break;
			case 'field':
				$txt=sprintf("`%s` %s `%s`",$value['field'],$value['case'],$value['value']);
				break;
			}
			if (!empty($txt)) $tmpsql[]=$txt;
			$txt='';
		}
		$tmpsql[]=sprintf(" MATCH ('%s') ",implode(' ',$fulltext));
		$ret=sizeof($tmpsql)>0 ? sprintf ( $counter_or ? '(%s)' : ' %s ',implode(" $type ",$tmpsql)) : '';
		$this->_where=$ret;
		return $ret;
	}
}
