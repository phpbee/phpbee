<?php
class gs_filter {
	function __construct($data) {
		$this->data=$data;
		$this->params=$data['handler_params'];
		$this->name=$this->params['name'];
		if (isset($this->params['alias'])) {
            $this->alias=$this->params['alias'];
            $this->loadValues($this->params['alias']);
        } else {
            $this->loadValues();
        }
		if (isset($this->params['default_value']) && empty($this->value)) {
			$this->value=$this->params['default_value'];
		}
	}
	function get_data_url() {
		$ds=new gs_data_driver_sef();
		$arr=$ds->import();
		unset($arr['gspgtype']);
		return $arr;
	}
	function get_data_get() {
		$ds=new gs_data_driver_get();
		$arr=$ds->import();
		unset($arr['gspgtype']);
		return $arr;
	}
	function loadValues($get_name=false) {
		$arr=array();
		if (!$get_name) $get_name=$this->name;
		switch ($this->data['handler_params']['urltype']) {
			case 'get': 
				$arr=$this->get_data_get();
				break;
			case 'session': 
				$arr=$this->get_data_get();
				if(isset($arr[$this->name])) {
                    if (function_exists('person')) person()->{'filter_'.$this->name}=$arr[$get_name];
						else gs_session::save($arr[$get_name],'filter_'.$this->name);
				} else {
                    if (function_exists('person')) {
							$sess=person()->{'filter_'.$this->name};
							if (!is_a($sess,'gs_null')) $arr[$this->name]=$sess;
					} else $arr[$this->name]=gs_session::load('filter_'.$this->name);
				}
				break;
			default:
				/*
				$d=$this->data['gspgid_handler_va'];
				*/
				$url=$this->data['gspgid_root'];
				$h_kr=$this->data['handler_key_root'];
				$h_kr=str_replace('*','\*',$h_kr);
				$url=preg_replace('|^'.$h_kr.'/|','',$url);
				$d=explode('/',$url);
				for($i=0;$i<count($d);$i+=2) {
					$j=$i+1;
					if (isset($d[$j])) $arr[$d[$i]]=$d[$j];
				}
		}
		$this->va=$arr;
		$this->value=isset($arr[$get_name]) ? $arr[$get_name] :(isset($this->params['default']) ? $this->params['default'] : null);
	}
	function applyFilter($options,$rs) {
		return $options;
	}
	function getHtmlBlock($ps) {
		foreach ($this->va as $fname=>$fvalue) {
			if (strpos('_page',$fname)) continue;
			$filter_name=str_replace('_page','',$fname);
			$filter=gs_filters_handler::get($filter_name);
			if (is_a($filter,'gs_filter_offset'))  unset($this->va[$fname]);
		}
		return $this->name;
	}

	function get_search_field_type() {
		return 'fText';
	}

    function get_value() {
        return isset($this->real_value) ? $this->real_value : $this->value;
    }
    function get_name() {
        return isset($this->alias) ? $this->alias: $this->name;
    }
    function get_urltype() {
        return $this->params['urltype'];
    }
}

class gs_filter_firstletters extends gs_filter_like {
	function __construct($data) {
		parent::__construct($data);
		$this->case=isset($this->params['case']) ? $this->params['case'] : 'STARTS';
		$this->real_value=$this->value;
		if ($this->value=='digits' || $this->value=='0-9')  {
			$this->case='NOTREGEXP';
			$this->value='^[[:alpha:]а-яА-Я[:space:]\'"]';
		}
	}
	function getHtmlBlockNonExlusive($ps) {
		$curr_value=$this->value;
		$this->value=$this->real_value;
		$ret=parent::getHtmlBlockNonExlusive($ps);
		$this->value=$curr_value;
		return $ret;
	}
}
class gs_filter_range extends gs_filter_like {
	function __construct($data) {
		parent::__construct($data);
		$this->real_value=$this->value;
        $this->range=preg_split('/\s*-\s*/',$this->value,2);
	}
	function applyFilter($options,$rs) {
        if (count($this->range)<2) return parent::applyFilter($options,$rs);

		$to=array(
			'type'=>'condition',
			'condition'=>'OR',
		);

		foreach ($this->fields as $field) {
			$t=array(
                    'type'=>'condition',
                    'condition'=>'AND',
                    );

            if ($this->range[0] !== '') {

                    $t[]=array(
					'type'=>'value',
					'field'=>$field,
					'value'=>$this->range[0],
					'case'=>'>=',
					);
            };

            if ($this->range[1] !== '') {
                    $t[]=array(
					'type'=>'value',
					'field'=>$field,
					'value'=>$this->range[1],
					'case'=>'<=',
					);
            };

            $to[]=$t;
		}
		$options[$this->name]=$to;
        gs_eventer::send('gs_filter_apply_'.$this->get_name(), $this);
		return $options;


    }
}

class gs_filter_coords extends gs_filter {
	function __construct($data) {
		parent::__construct($data);
		if (!isset($this->params['fields'])) $this->params['fields']='';
		$this->fields=$this->params['fields'];
		$this->radius=$this->params['radius'];
	}
	function getHtmlBlock($ps) {
		parent::getHtmlBlock($ps);
		if (isset($ps['exlusive']) && $ps['exlusive']) return $this->getHtmlBlockExlusive($ps);
		return $this->getHtmlBlockNonExlusive($ps);
	}
	function getHtmlBlockNonExlusive($ps) {
		$tpl=gs_tpl::get_instance();
		$tpl->assign('current',$this->value);
		$tpl->assign('keyname',$this->name);
		$tpl->assign('params',$ps);
        if(isset($ps['options'])) $tpl->assign('options',string_to_params($ps['options']));
		$tplname=isset($ps['tpl']) ? $ps['tpl'] : str_replace('gs_filter_','',get_class($this)).'.html';
		$out=$tpl->fetch('filters'.DIRECTORY_SEPARATOR.$tplname);
		return $out;
	}
	function applyFilter($options,$rs) {
		if (empty($this->value)) return $options;
		list($x,$y)=explode(',',$this->value);
		$to=array(
				'type'=>'function',
				'function'=>sprintf('coords(%s,%.15f,%.15f)',$this->fields,(float)$x,(float)$y),
				'value'=>$this->radius/1000,
				'case'=>'<=',
				);
		$options[$this->name]=$to;
		return $options;
	}
}

class gs_filter_like_by_link extends gs_filter {
	function __construct($data) {
		parent::__construct($data);
		list($rsname,$fname)=explode('.',$this->params['link']);
		$this->rsname=$rsname;
		$this->fieldname=$fname;
		if (isset($this->params['alias'])) $this->loadValues($this->params['alias']);
	}
	function getHtmlBlock($ps) {
		parent::getHtmlBlock($ps);
		if (isset($ps['exlusive']) && $ps['exlusive']) return $this->getHtmlBlockExlusive($ps);
		return $this->getHtmlBlockNonExlusive($ps);
	}
	function getHtmlBlockNonExlusive($ps) {
		$tpl=gs_tpl::get_instance();
		$tpl->assign('current',$this->value);
		$tpl->assign('keyname',$this->name);
		$tpl->assign('params',$ps);
		$tpl->assign('filter',$this);
		if(isset($ps['options'])) $tpl->assign('options',string_to_params($ps['options']));
		$tplname=isset($ps['tpl']) ? $ps['tpl'] : str_replace('gs_filter_','',get_class($this)).'.html';
		$out=$tpl->fetch('filters'.DIRECTORY_SEPARATOR.$tplname);
		return $out;
	}
	function applyFilter($options,$rs) {
		if (empty($this->value)) return $options;
		$rn=$this->rsname;
		$link=$rs->structure['recordsets'][$this->rsname];
		if ($link['type']=='many') {
			$rsname=$link['rs2_name'];
			$ors=new $rsname;
			$opts[$this->fieldname]=array(
				'type'=>'value',
				'field'=>$this->fieldname,
				'value'=>$this->value,
				'case'=>'LIKE',
			);
			$link_ids=array_keys($ors->find_records($opts)->get_values('id'));
			$lrs=new $link['recordset'];
			$lf=$lrs->structure['recordsets'][$link['rs2_name']]['local_field_name'];
			$real_ids=$lrs->find_records(array($lf=>$link_ids))->get_values($link['foreign_field_name']);
			$ids=array();
			foreach ($real_ids as $rid) {
				$ids[]=$rid[$link['foreign_field_name']];
			}
			$to=array(
				'type'=>'value',
				'field'=>$rs->id_field_name,
				'value'=>$ids,
			);
			$options[$this->name]=$to;
		} else {
			//$rsname=$link['recordset'];
			// надо сделать
			$rsname=$link['recordset'];
			$ors=new $rsname;
			$opts[$this->fieldname]=array(
				'type'=>'value',
				'field'=>$this->fieldname,
				'value'=>$this->value,
				'case'=>'LIKE',
			);
			$real_ids=$ors->find_records($opts)->get_values($link['foreign_field_name']);
			$ids=array();
			foreach ($real_ids as $rid) {
				$ids[]=$rid[$link['foreign_field_name']];
			}
			$to=array(
				'type'=>'value',
				'field'=>$rs->id_field_name,
				'value'=>$ids,
			);
			$options[$link['local_field_name']]=$to;
		}
		
		return $options;
	}
}



class gs_filter_like extends gs_filter {
	var $label=null;
	function __construct($data) {
		parent::__construct($data);
		if (!isset($this->params['fields'])) $this->params['fields']='';
		if (!isset($this->params['strong'])) $this->params['strong']='';

		$this->fields=array_map('trim',array_filter(explode(',',$this->params['fields'])));
		$this->strong=array_map('trim',array_filter(explode(',',$this->params['strong'])));
		$this->case=isset($this->params['case']) ? $this->params['case'] : 'LIKE';

	}
	function getHtmlBlock($ps) {
		parent::getHtmlBlock($ps);
		if (isset($ps['exlusive']) && $ps['exlusive']) return $this->getHtmlBlockExlusive($ps);
		return $this->getHtmlBlockNonExlusive($ps);
	}
	function getHtmlBlockNonExlusive($ps) {
		$tpl=gs_tpl::get_instance();
		$tpl->assign('current',$this->value);
		$tpl->assign('keyname',$this->name);
		$tpl->assign('prelabel',isset($ps['prelabel']) ? $ps['prelabel'] : null);
		$tpl->assign('label',isset($ps['label']) ? $ps['label'] : $this->label);
		if(isset($ps['values'])) $tpl->assign('values',string_to_params($ps['values']));
		$tpl->assign('params',$ps);
		if(isset($ps['options'])) $tpl->assign('options',string_to_params($ps['options']));

		$tpl->assign('filter',$this);

		$tplname=isset($ps['tpl']) ? $ps['tpl'] : str_replace('gs_filter_','',get_class($this)).'.html';
		$out=$tpl->fetch('filters'.DIRECTORY_SEPARATOR.$tplname);
		return $out;
	}
	function applyFilter($options,$rs) {
		if (empty($this->value)) return $options;
		$to=array(
			'type'=>'condition',
			'condition'=>'OR',
		);

		foreach ($this->fields as $field) {
			$to[]=array(
					'type'=>'value',
					'field'=>$field,
					'value'=>$this->value,
					'case'=>in_array($field,$this->strong) ? '=' : $this->case,
					);
		}
		$options[$this->name]=$to;
		gs_eventer::send('gs_filter_apply_'.$this->get_name(), $this);
		return $options;
	}
}

class gs_filter_fulltext extends gs_filter_like {
	function applyFilter($options,$rs) {
		if (empty($this->value)) return $options;
		$options[$this->name]=array(
					'type'=>'value',
					'field'=>$this->params['fields'],
					'value'=>$this->value,
					'case'=>'FULLTEXT',
					);
		return $options;
	}
}


class gs_filter_switch extends gs_filter {
	function __construct($data) {
		parent::__construct($data);
		if (!isset($this->params['fields'])) $this->params['fields']='';
		$this->fields=$this->params['fields'];
		$this->value=$this->params['value'];
	}
	
	function applyFilter($options,$rs) {
		if (empty($this->value)) return $options;
		
		$to=array(
			'type'=>'condition',
			'condition'=>'OR',
		);
		
		$fields=explode(',',$this->params['fields']);
		
		foreach ($fields as $field) {
			$to[]=array(
					'type'=>'value',
					'field'=>$field,
					'value'=>$this->value,
					'case'=>'=',
					);
		}
		$options[$this->name]=$to;
		return $options;
	}
}


class gs_filter_var extends gs_filter {
	function __construct($data) {
		parent::__construct($data);
		$this->tpl=gs_tpl::get_instance();
	}
	function getHtmlBlock($ps) {
		parent::getHtmlBlock($ps);
		if (isset($ps['exlusive']) && $ps['exlusive']) return $this->getHtmlBlockExlusive($ps);
		return $this->getHtmlBlockNonExlusive($ps);
	}
	function getHtmlBlockNonExlusive($ps) {
		$this->tpl->assign('current',$this->value);
		$this->tpl->assign('keyname',$this->name);
		$this->tpl->assign($ps);
		$tplname=isset($ps['tpl']) ? $ps['tpl'] : str_replace('gs_filter_','',get_class($this)).'.html';
		$out=$this->tpl->fetch('filters'.DIRECTORY_SEPARATOR.$tplname);
		return $out;
	}
}
class gs_filter_limit extends gs_filter_var {
	function __construct($data) {
		parent::__construct($data);
		$this->values=explode(',',$this->params['values']);
		$this->default_value=isset($this->params['default_value']) ? $this->params['default_value'] : reset($this->values);
		if(!$this->value) $this->value=$this->default_value;
		if ($this->values && !in_array($this->value,$this->values)) $this->value=$this->default_value;
	}
	function applyFilter($options,$rs) {
		if (empty($this->value)) return $options;
		$options[]=array('type'=>'limit','value'=>$this->value);
		return $options;
	}
	function getHtmlBlockNonExlusive($ps) {
		$this->tpl->assign('params',$ps);
		$this->tpl->assign('values',$this->values);
		return parent::getHtmlBlockNonExlusive($ps);
	}
}
class gs_filter_offset extends gs_filter_var {
	function __construct($data) {
		$filter=gs_filters_handler::get($data['handler_params']['limit']);
		$limit=$filter ? $filter->value : (int)($data['handler_params']['limit']);
		if (!$limit) $limit=1;
		$this->limit=$limit;
		parent::__construct($data);
		if(!$this->value) $this->value=0;
	}
	function applyFilter($options,$rs) {
		$options[]=array('type'=>'limit','value'=>$this->limit);
		if (empty($this->value)) return $options;
		$options[]=array('type'=>'offset','value'=>$this->value);
		return $options;
	}
	function get_data_get() {
		$ds=new gs_data_driver_get();
		$arr=$ds->import();
		if (isset($arr[$this->name.'_page'])) {
			$cp=$arr[$this->name.'_page'];
			$cp=max((int)($cp),1);
			$arr[$this->name]=($cp-1)*$this->limit;
		}
		return $arr;
	}
	function getHtmlBlockNonExlusive($ps) {
		$limit=$this->limit;
		$recordset=$ps['recordset'];
		$count=$recordset->count_records();

		$pages=$this->pages=max(ceil($count/$limit),1);
		$current_page=$this->current_page=floor($this->value/$limit)+1;
		$this->previous_page=max($current_page-1,1);
		$this->next_page=min($current_page+1,$pages);


		$this->tpl->assign('recordset',$recordset);
		$this->tpl->assign('count',$count);
		$this->tpl->assign('limit',$limit);
		$this->tpl->assign('pages',$pages);
		$this->tpl->assign('current_page',max(min($current_page,$pages),1));
		$this->tpl->assign('previous_page',$this->previous_page);
		$this->tpl->assign('next_page',$this->next_page);
		return parent::getHtmlBlockNonExlusive($ps);
	}
}

class gs_filter_sort extends gs_filter {
	function __construct($data) {
		parent::__construct($data);
		$this->fields=array_map('trim',array_filter(explode(',',$this->params['fields'])));
	}
	function getHtmlBlock($ps) {
		parent::getHtmlBlock($ps);
		if (isset($ps['exlusive']) && $ps['exlusive']) return $this->getHtmlBlockExlusive($ps);
		return $this->getHtmlBlockNonExlusive($ps);
	}
	function getHtmlBlockNonExlusive($ps) {
		$tpl=gs_tpl::get_instance();
		$tpl->assign('current',$this->value);
		$tpl->assign('keyname',$this->name);
		$tpl->assign('field',isset($ps['field']) ? $ps['field'] : '');
		$tpl->assign('prelabel',isset($ps['prelabel']) ? $ps['prelabel'] : '');
		$tpl->assign('label',isset($ps['label']) ? $ps['label'] : '');
		$tpl->assign('values',$this->fields);
		$tplname=isset($ps['tpl']) ? $ps['tpl'] : str_replace('gs_filter_','',get_class($this)).'.html';
		$out=$tpl->fetch('filters'.DIRECTORY_SEPARATOR.$tplname);
		return $out;
	}
	function applyFilter($options,$rs) {
        /*
		if (empty($this->value)) {
			$this->value=reset($this->fields);
		}
        */
		if (empty($this->value)) return $options;
		$value=str_replace(":"," ",$this->value);
		$options['orderby']=array('type'=>'orderby','value'=>$value);

		return $options;
	}
}

class gs_filter_calendar extends gs_filter_like {
    function __construct($data) {
        parent::__construct($data);
        if (isset($this->params['dates']) && ($this->value || isset($this->va[$this->name]))) {
            $this->real_value=$this->value;
            $this->value=$this->params['dates'];
        }
    }
	function applyFilter($options,$rs) {
		if (empty($this->value)) return $options;
		$dates=array_map('trim',explode(' - ',$this->value));
		$date1=array_shift($dates);
		$date2=$dates ? array_shift($dates) : $date1;

		$to=array(
			'type'=>'condition',
			'condition'=>'OR',
		);
		foreach ($this->fields as $field) {
			$to[]=array(
				'type'=>'condition',
				'condition'=>'AND',

				array(
					'type'=>'value',
					'field'=>$field,
					'value'=>date(DATE_ATOM,strtotime($date1)),
					'case'=>'>=',
				),
				array(
					'type'=>'value',
					'field'=>$field,
					'value'=>date(DATE_ATOM,strtotime("$date2 +1day")),
					'case'=>'<',
				),
			);

		}
		$options[$this->name]=$to;
		return $options;
	}
	function get_search_field_type() {
		return 'fTimestamp';
	}
}
class gs_filter_verbose_calendar extends gs_filter_like {
	var $values=array (
		'Today'=>'today:today',
		'Yesterday'=>'yesterday:yesterday',
		'Week'=>'-1 week:today',
		'Month'=>'-1 month:today',
		'Prevmonth'=>'-2 month:-1 month',
		'All'=>'',
		);
	function __construct($data) {
		parent::__construct($data);
		if (isset($this->params['dates'])) {
			$dates=string_to_params(str_replace(',',' ',$this->params['dates']));
			$values=array();
			foreach ($dates as $d) {
					if (isset($this->values[$d])) $values[$d]=$this->values[$d];
			}
			$this->values=$values;
		}
	}
	function applyFilter($options,$rs) {
		if (empty($this->value)) return $options;


		$date1=$date2=date('Y-m-d',strtotime($this->value));

		if (isset($this->values[$this->value])) {
			$dates=array_map('trim',explode(':',$this->values[$this->value]));
			$date1=array_shift($dates);
			$date2=$dates ? array_shift($dates) : $date1;
		}

		if ($this->value=='Interval') {
			if (isset($this->data[$this->name.'_from']) && $this->data[$this->name.'_from']) $date1=$this->data[$this->name.'_from'];
			if (isset($this->data[$this->name.'_to']) && $this->data[$this->name.'_to']) $date2=$this->data[$this->name.'_to'];
			
			if (!isset($date2) && isset($date1)) $date2=$date1;
			if (!isset($date1) && isset($date2)) $date1=$date2;
		}


		$this->date1=date('Y-m-d',strtotime($date1));
		$this->date2=date('Y-m-d',strtotime($date2));
		if ($this->value=='Interval') {
			$this->label=sprintf("%s &ndash; %s",
							date("M d, Y",strtotime($date1)),
							date("M d, Y",strtotime($date2))
							);
		}


		$to=array(
			'type'=>'condition',
			'condition'=>'OR',
		);
		foreach ($this->fields as $field) {
			$to[]=array(
				'type'=>'condition',
				'condition'=>'AND',

				array(
					'type'=>'value',
					'field'=>$field,
					'value'=>date(DATE_ATOM,strtotime($date1)),
					'case'=>'>=',
				),
				array(
					'type'=>'value',
					'field'=>$field,
					'value'=>date(DATE_ATOM,strtotime($date2.' +1 day')),
					'case'=>'<',
				),
			);

		}
		$options[$this->name]=$to;
		return $options;
	}
}


class gs_filter_select_by_links extends gs_filter {
	/*
	 {handler gspgid="/filter/" class="select_by_links" link="reports.status:name" name="status"}
										 ^имя поля в линке, по которому фильтруем
	 */
	function __construct($data) {
		parent::__construct($data);
		$this->options=null;
		list($recordsetname,$linkname)=explode('.',$this->params['link']);
		list($this->linkname,$this->fieldname)=explode(':',$linkname);
		$rs=new $recordsetname();
		$this->recordset=$rs;
		$this->link=$rs->structure['recordsets'][$this->linkname];
		$this->value_record=new gs_null(GS_NULL_XML);


		$this->recordset=$rs;

		$this->find_rs_links();


		if (empty($this->value) && isset($this->params['default'])) $this->value=$this->rs_links->array_keys();
		if (empty($this->value)) return;

		if (!is_array($this->value)) $this->value=explode(',',$this->value);

		$fieldname=$this->fieldname;
		$link=$this->link;

		if (isset($link['type']) && $link['type']=='many') {

			$rec_rs_name=$link['rs2_name'];
			$rec_rs=new $rec_rs_name();

			$backlink='_'.$rs->get_backlink_name($this->linkname);
			$link_ids=array();
			foreach($rec_rs->find_records(array($fieldname=>$this->value)) as $filter_rec) {
				foreach ($filter_rec->$backlink as $a) $link_ids[]=$a[$link['foreign_field_name']];
			}
			$this->options=array(
					'type'=>'value',
					'field'=>$rs->id_field_name,
					'value'=>$link_ids,
					);
			 $this->links=$link_ids;
		} else {
			$rec_rs_name=$link['recordset'];
			$rec_rs=new $rec_rs_name();
			$values=array();
			if ($rec_rs->structure['fields'][$fieldname]['multilang']) {
				foreach ($rec_rs->find_records(array()) as $rec) {

					if (stripos($rec->$fieldname,$this->value)!==FALSE) {
						$values[]=$rec->{$link['foreign_field_name']};
                        $this->value_record=$rec;
					} else {
						foreach ($rec->Lang as $l) {
							if (stripos($l->$fieldname,$this->value)!==FALSE) {
								$values[]=$rec->{$link['foreign_field_name']};
                                $this->value_record=$rec;
								break;
							} 
						}
					}
				}
			} else {
				foreach ($rec_rs->find_records(array($fieldname=>$this->value)) as $rec) {
					$values[]=$rec->{$link['foreign_field_name']};
				    $this->value_record=$rec;
				}
			}
			$this->options=array(
					'type'=>'value',
					'field'=>$link['local_field_name'],
					'value'=>$values,
					);
			    $this->links=$values;
		}



	}
	private function find_rs_links() {
				$recordsetname=$this->link['recordset'];
				if (isset($this->link['type']) && $this->link['type']=='many') $recordsetname=$this->link['rs2_name'];

				$rec_rs=new $recordsetname();
				$options=array();
				if (isset($this->params['options'])) $options=string_to_params($this->params['options']);
				if (isset($this->params['options_arr'])) $options=array(string_to_params($this->params['options_arr']));
				if (isset($this->params['options'])) $options=string_to_params($this->params['options']);
				if (isset($this->recordset->query_options['options'])) foreach ($this->recordset->query_options['options'] as $o) {
					if (isset($o['field']) && isset($rec_rs->structure['fields'][$o['field']]) && $o['field']!=$rec_rs->id_field_name) {
						$options[]=$o;
					}
				}
				$rec_rs=$rec_rs->find_records($options);
				$this->rs_links=$rec_rs;
	}
    function get_options() {
        return $this->options;
    }
    function get_values() {
        return $this->links;
    }
    function get_record() {
        return $this->value_record;
    }
	function applyFilter($mainoptions,$rs) {
		$options=$this->get_options();
        if (!$options) return $mainoptions;
        if (isset($this->params['field_name'])) {
            $options['field']=$this->params['field_name'];
        }
        if (isset($this->params['alias']) && ($al=$this->params['alias']) && isset($mainoptions[$al]) && is_array($mainoptions[$al])) {
            $mainoptions[$al][]=$options;
        } else {
            $mainoptions[]=$options;
        }
        return $mainoptions;

	}
	function getHtmlBlock($ps) {
		parent::getHtmlBlock($ps);
		if (isset($ps['exlusive']) && $ps['exlusive']) return $this->getHtmlBlockExlusive($ps);
		return $this->getHtmlBlockNonExlusive($ps);
	}

	

	function getHtmlBlockNonExlusive($ps) {


		$rec_rs=$this->rs_links;
		
		if (!isset($ps['params']) || empty($ps['params'])) $ps['params']=array();
		
		$links=array();
		$count_all=0;


		foreach ($rec_rs as $rec) {
			$arr=$this->va;
			$key=$rec->{$this->fieldname};
			if (isset($this->link['type']) && $this->link['type']=='many') {
				$field=$this->recordset->id_field_name;
				$backlink='_'.$this->recordset->get_backlink_name($this->linkname);
				$id=array();
				foreach ($rec->$backlink as $a) $id[]=$a[$this->link['foreign_field_name']];
			} else {
				$field=$this->link['local_field_name'];
				$id=$rec->{$this->link['foreign_field_name']};
			}
			if ($ps['recordset']) {
				$rs=$ps['recordset'];
				$count_array=$rs->query_options['options'];
				foreach ($count_array as $ca_key=>$ca) {
					if ($ca_key===$this->link['local_field_name'] 
						|| (is_array($ca) && isset($ca['field']) && $ca['field']==$this->link['local_field_name'])
						) {

						unset($count_array[$ca_key]);
					}
				}
				$count_array_all=$count_array;
				$count_array[]=array('type'=>'value',
							    'field'=>$field,
							    'value'=>$id);

				$rsname=$ps['recordset']->get_recordset_name();
				$rs=new $rsname();
				$count=$rs->count_records($count_array);
			}

			$fname=$rec->get_recordset()->get_name_field();
			$name=$rec->$fname;
			$arr[$this->name]=$key;


			$links[]=array('name'=>$name,'keyname'=>$this->name,'key'=>$key,'count'=>$count, 'va'=>$arr,'rec'=>null,'rec_id'=>$rec->get_id());
		}
		$count_all= isset($rs) ? $rs->count_records($count_array_all) : 0;
		
		$current_names=array();

		$link_all='';
		$count_all='';
		foreach($links as $key=>$l) {
			ksort($l['va']);
			switch ($this->data['handler_params']['urltype']) {
				case 'get':
					$link=$this->data['gspgid_root'].'?'.http_build_query($l['va']);	
					unset($l['va'][$this->name]);
					$link_all=$this->data['gspgid_root'].'?'.http_build_query($l['va']);	
					break;
				default:
					$link=$this->data['handler_key_root'];
					foreach ($l['va'] as $k=>$v) $link.="/$k/$v";

					unset($l['va'][$this->name]);
					$link_all=$this->data['handler_key_root'];
					foreach ($l['va'] as $k=>$v) $link_all.="/$k/$v";
			}
			$l['href']=$link;
			unset($l['va']);
			$links[$key]=$l;
			if (is_array($this->value) && in_array($l['key'],$this->value)) $current_names[]=$l;
		}
		$link_all_array=array('name'=>'all','key'=>'all','href'=>$link_all,'count'=>$count_all, 'va'=>null,'rec'=>null);
		$tpl=gs_tpl::get_instance();
		$tpl->assign('link_all',$link_all_array);
		$tpl->assign('links',$links);
		$tpl->assign('current',$this->value);
		$tpl->assign('current_names',$current_names);
		$tpl->assign('current_name',count($current_names)==1 ? $current_names[0]['name'] : '');
		$tpl->assign('params',$ps);
		$tpl->assign('filter_params',$ps['params']);
		$tpl->assign('title',isset($ps['title']) ? $ps['title'] : '');
		$tpl->assign('filter',$this);
        
		$tplname=isset($ps['tpl']) ? $ps['tpl'] : str_replace('gs_filter_','',get_class($this)).'.html';
		$out=$tpl->fetch('filters'.DIRECTORY_SEPARATOR.$tplname);
		return $out;
	}
}
class gs_filter_search_by_links extends gs_filter_select_by_links {
	function getHtmlBlockNonExlusive($ps) {
		$tpl=gs_tpl::get_instance();
		$tpl->assign('current',$this->value);
		$tpl->assign('keyname',$this->name);
		$tpl->assign('prelabel',isset($ps['prelabel']) ? $ps['prelabel'] : null);
		$tpl->assign('label',isset($ps['label']) ? $ps['label'] : null);
		if(isset($ps['values'])) $tpl->assign('values',string_to_params($ps['values']));
		$tpl->assign('params',$ps);
		if(isset($ps['options'])) $tpl->assign('options',string_to_params($ps['options']));
		$tplname=isset($ps['tpl']) ? $ps['tpl'] : str_replace('gs_filter_','',get_class($this)).'.html';
		$out=$tpl->fetch('filters'.DIRECTORY_SEPARATOR.$tplname);
		return $out;
	}
}

class gs_filter_select_records extends gs_filter {
	function __construct($data) {
		parent::__construct($data);

		$rs=$this->params['recordset'];
		if (is_object($rs) && is_subclass_of($rs,'gs_recordset')) {
			$this->recordset=$rs;
			$this->recordset->preload();
		} else {
			$this->recordset= new $rs;
			$this->recordset->find_records(array())->preload();
		}
		$this->default_value = isset($this->params['default_value']) ? $this->params['default_value'] : NULL;
	}
	function current() {
		if ($this->value &&  $this->recordset[$this->value])  return  $this->recordset[$this->value] ;
		if ($this->default_value &&  $this->recordset[$this->default_value])	return  $this->recordset[$this->default_value];
		return $this->recordset->first();
	}
	function applyFilter($options,$rs) {
		$options[]=array(
				'type'=>'value',
				'field'=>$this->recordset->id_field_name,
				'value'=>$this->value,
				);
		return $options;
	}
	function getHtmlBlock($ps) {
		parent::getHtmlBlock($ps);
		if (isset($ps['exlusive']) && $ps['exlusive']) return $this->getHtmlBlockExlusive($ps);
		return $this->getHtmlBlockNonExlusive($ps);
	}
	function getHtmlBlockNonExlusive($ps) {
		$tpl=gs_tpl::get_instance();	
		//$links=$this->recordset->recordset_as_string_array();
		$links=array();
		foreach($this->recordset as $rec) {
			$links[$rec->get_id()]=$rec;
		}
		$tpl->assign('filter',$this);
		$tpl->assign('links',$links);
		$tpl->assign('current',$this->value);
		$tpl->assign('keyname',$this->name);
		$tpl->assign('prelabel',$ps['prelabel']);
		$tpl->assign('label',$ps['label']);
		$tpl->assign('params',$ps);
		$tplname=isset($ps['tpl']) ? $ps['tpl'] : str_replace('gs_filter_','',get_class($this)).'.html';
		$out=$tpl->fetch('filters'.DIRECTORY_SEPARATOR.$tplname);
		return $out;
	}
}


class gs_filters_handler extends gs_handler {
	function init() {
		$classname="gs_filter_".$this->data['handler_params']['class'];
		$filter=new $classname($this->data);
		$filters=gs_var_storage::load('filters');
		if (!$filters) $filters=array();
		$filters[$filter->name]=$filter;
		gs_var_storage::save('filters',$filters);
		return;
	}
	function show() {
		$filter=self::get($this->data['handler_params']['name']);
		if ($filter) return $filter->getHtmlBlock($this->data['handler_params']);
	}
	static function get($name) {
		$filters=gs_var_storage::load('filters');
		return isset($filters[$name]) ? $filters[$name] : new gs_null(GS_NULL_XML);
	}
	static function set($name,$value) {
		if ($value===NULL) return FALSE;
		$f=self::get($name);
		if (!$f) return FALSE;
		return $f->value=$value;
	}
	static function value($name) {
		$f=self::get($name);
		return $f ? $f->value : NULL;
	}
	static function url($baseurl) {
        $d=parse_url($baseurl);
        $get=array();
        parse_str($d['query'],$get);
        $url=array();
		$filters=gs_var_storage::load('filters');
        foreach ($filters as $f) {
            $value=$f->get_value();
            if (empty($value)) continue;
            switch ($f->get_urltype()) {
                case 'get':
                    $get[$f->get_name()]=$value;
                    break;
                case 'url':
                    $url[$f->get_name()]=$f->get_name().'/'.$value;
                    break;
            }
        }
        $d['path'].=implode('/',$url);
        $d['query']=http_build_query($get);
        return http_build_url($d);
	}
}


