<?php


class search_handler extends gs_handler {
	function search($data) {
        $options=array();
		$cfg=record_by_field('alias',$this->data['gspgid_va'][0],'search_config');

        $rs_name='sidx_'.$cfg->alias;
        $rs=new $rs_name;

        $filters=array();
        foreach ($cfg->Filters as $filter) {
            $filters[$filter->group_name]['class_name']=$filter->filter_classname;
        }
        md($filters,1);


        foreach ($filters as $k=>$f) {
            $this->data['handler_params']['name']=$k;
            $this->data['handler_params']['urltype']='get';
            $this->data['handler_params']['fields']=$k;

            $filter=new $f['class_name']($this->data);
            $options=$filter->applyFilter($options,$rs);

        }
        return $options;
        /*
        md($options,1);
        $rs->find_records($options);
        md($rs->get_values(),1);
        return $rs;
        */
	}

	function commit($d) {
		//$search_config=record_by_field('alias',$this->data['gspgid_va'][0],'search_config');
        $searches = new search_config;
        $searches->find_records(array());

        foreach ($searches as $search_config) {
            $fields=array();
            foreach ($search_config->Filters as $f) {
                $flobj=new $f->filter_classname(null);
                $type=$flobj->get_search_field_type();
                $fields[$f->group_name]['type']=$type;
                $fields[$f->group_name]['filter_classname']=$f->filter_classname;
            }
            $search_config->search_fields=$fields;
        }



		$tpl=new gs_tpl();
		$tpl=$tpl->init();
		$tpl->left_delimiter='<*';
		$tpl->right_delimiter='*>';

		$tpl->assign('searches',$searches);


        $filters=array();
        $rs=new search_config_filter;
        $rs->find_records(array());
        foreach ($rs as $f) {
            $filters[$f->Recordset->first()->name][$f->Parent->first()->alias][$f->group_name]=$f;
        }
        $tpl->assign('filters',$filters);


		$fname=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.'create_index.html';
		$out=$tpl->fetch('string:'.file_get_contents($fname));

		$out=beautify($out);

        md($out,1);
        /*
        die();
        */

        $fname=dirname(__FILE__).DIRECTORY_SEPARATOR.'sidx.module.php';


        file_put_contents($fname,$out);

	}

    function dropindex($d) {
        $sname='sidx_'.$this->data['gspgid_va'][0];
        md($sname,1);
        $sidx=new $sname;
        $sidx->find_records(array())->limit(10000)->delete()->commit();
    }


    function index($d) {
        $rs=new $this->data['gspgid_va'][0];

        $rs->find_records(array());
        $sidx=new sidx_index;
        foreach ($rs as $rec) {
            $sidx->sidx_update_index($rec);
        }
    }
}
