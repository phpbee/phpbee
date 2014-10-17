<?php
function smarty_function_controller($params, &$smarty)
{
	$use_find=false;
    if (isset($params['_params'])) {
		$params=array_merge($params,$params['_params']);
	}
	
	if (isset($params['_class'])) {
        $obj=new $params['_class'];
	    if (isset($params['_id'])) $params[$obj->id_field_name]=$params['_id'];
	    if (isset($params['_assign_type']) && $params['_assign_type']=='class') {
		    $obj->new_record(array());
		    $smarty->assign($params['_assign'],$obj->current());
		    return;
	    }
    }

    if (isset($params['_object'])) {
        $obj=$params['_object'];
        $use_find=true;
    }

	$vars=array();
	foreach ($params as $k=>$v) {
		if ($v!==FALSE && $v!==NULL && (array_key_exists($k,$obj->structure['fields']) || strpos($k,'_')!==0)) {
			if (is_array($v)) {
				$vars[]=array($k,$v);
			} else {
				/*
				$vv=explode(',',$v);
				foreach ($vv as $val) {
					$vars[]=array($k,$val);
				}
				*/
				$vars[]=array($k,$v);
			}
		}
	}

	if (isset($params['_search_options'])) {
	$_search_options=unserialize(base64_decode($params['_search_options']));
	if (is_array($_search_options)) {
		$fields=$obj->structure['fields'];
		foreach ($_search_options as $k=>$v) {
			if ($v!==FALSE && isset($obj->structure['fields'][$k])) $vars[]=array($k,$v);
		}
	}
	}
	$options=array();
	foreach ($vars as $val) {
		list($k,$v)=$val;
		if (isset($params['_skip_null_options']) && $params['_skip_null_options'] && empty($v)) continue;
		if (!is_array($v) && preg_match('/^(LIKE|NOTLIKE|STRONGLIKE|STARTS|ENDS|!=|<=|>=|<|>|!IN|IN|FULLTEXT|BETWEEN|REGEXP|NOTREGEXP)(.*)$/',$v,$matches) ) {
			if (isset($params['_skip_null_options']) && $params['_skip_null_options'] && empty($matches[2])) continue;
			if ($matches[1]=='IN') {
				$options[]=array('type'=>'value', 'field'=>$k,'case'=>'=','value'=>explode(':',$matches[2]));
			} elseif ($matches[1]=='!IN') {
				$options[]=array('type'=>'value', 'field'=>$k,'case'=>'!=','value'=>explode(':',$matches[2]));
			} elseif ($matches[1]=='BETWEEN') {
				$options[]=array('type'=>'value', 'field'=>$k,'case'=>'BETWEEN','value'=>explode(':',$matches[2]));
			} else {
				$options[]=array('type'=>'value', 'field'=>$k,'case'=>$matches[1],'value'=>$matches[2]);
			}

		
		/*
		} else if (!is_array($v) && preg_match('/^(BETWEEN)\s*(.*)\s+(.*)/',$v,$matches) ) {
			$options[]=array('type'=>'value', 'field'=>$k,'case'=>'>=','value'=>$matches[2]);
			$options[]=array('type'=>'value', 'field'=>$k,'case'=>'<=','value'=>$matches[3]);
		*/	
		} elseif (!is_array($v) && preg_match('/^(SET)(.*)/',$v,$matches)) {
					$options[]=array('type'=>'value', 'field'=>$k,'case'=>'=','value'=>explode(':',$matches[2]));
		} elseif (!is_array($v) && preg_match('/^(!SET)(.*)/',$v,$matches)) {
					$options[]=array('type'=>'value', 'field'=>$k,'case'=>'!=','value'=>explode(':',$matches[2]));
		} else {
			$options[]=array('type'=>'value','field'=>$k,'value'=>$v);
		}
	}
	if (isset($params['_options']) && is_array($params['_options'])) {
		 $options=array_merge($options,$params['_options']);
	}


	if (isset($params['_offset'])) $_offset=(int)$params['_offset'];

	if (isset($params['_filters'])) {
		$filternames=array_filter(array_map('trim',explode(',',isset($params['_filters'])?$params['_filters'] : '')));
		$filters=gs_var_storage::load('filters');
		foreach ($filternames as $f) {
			$filter=$filters[$f];
			$options=$filter->applyFilter($options,$obj);
		}
	}

	if (!empty($params['_paging'])) {
		if (!isset($_offset)) {
			$get=$smarty->getTemplateVars('_gsdata');
			$_offset=isset($get[$params['_assign'].'_paging']) ? (int)$get[$params['_assign'].'_paging'] : 0;
		}

		list($_paging_type,$_paging_itemsperpage)=sscanf($params['_paging'],"%[A-Za-z]:%d");
		//require_once('function.controller.paging.php');
		require_fullpath(__FILE__,'function.controller.paging.php');

		$rows_count=$obj->count_records($options);
		$pages=gs_controller_paging($params['_assign']."_paging", $_paging_type,$rows_count,$_paging_itemsperpage,$_offset);
		$smarty->assign($params['_assign']."_paging",$pages);
		$smarty->assign($params['_assign']."_count",$rows_count);
	}


	if (isset($params['_limit'])) $options[]=array('type'=>'limit','value'=>$params['_limit']);
	else if (isset($params['_paging'])) sscanf($params['_paging'],'%[A-Za-z]:%d',$tmp,$limit) && $limit && $options[]=array('type'=>'limit','value'=>$limit);
	if (isset($_offset)) $options[]=array('type'=>'offset','value'=>$_offset);
	if (isset($params['_orderby']) && trim($params['_orderby'])) $options['orderby']=array('type'=>'orderby','value'=>$params['_orderby']);

	$fields=(isset($params['_fields'])) ? (!is_array( $params['_fields'])) ? explode(",", $params['_fields']) :  $params['_fields'] : NULL;
	
	if (!isset($params['_count'])) {
			if (!isset($params['_index_field_name'])) {
				$ret=(!$use_find) ? $obj->find_records($options,$fields) : $obj->find($options);
			} else {
				$ret=$obj->find_records($options,$fields,$params['_index_field_name']);
			}
	} else {
		$ret=$obj->count_records($options);
	}
	//$vars=$ret->get_values();
	if (isset($params['_assign_type']) && $params['_assign_type']=='plain') {
		$ret=$ret->current();

		if (!$ret) return;

		$vars=$ret->get_values();
		if (isset($params['_skip_filled']) && $params['_skip_filled'] && is_array($vars)) {
			$tpl_vars=$smarty->getTemplateVars();
			foreach ($vars as $k=>$v) {
				if (isset($tpl_vars[$k])) unset($vars[$k]);
			}
		}
		$smarty->assign($vars);
		if (isset($params['_assign'])) {
			//$smarty->assign($params['_assign'],$vars);
			$smarty->assign($params['_assign'],$ret);
		}
		return;

	}

	if (isset($params['_assign_type']) && $params['_assign_type']=='first') {
		$ret=$ret->current();
		if (isset($params['_assign'])) $smarty->assign($params['_assign'],$ret);
		return;
	}
	$smarty->assign($params['_assign'],$ret);
	return;
}
?>
