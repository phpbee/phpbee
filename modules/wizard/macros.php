<?php

class wz_macros extends g_forms_html{
	function escape($txt) {
		$tpl=gs_tpl::get_instance();
		$txt=str_replace(array('{','}'),array($tpl->left_delimiter,$tpl->right_delimiter),$txt);
		return ($txt);
	}
}

class wz_macros_handler extends wz_macros {
	function __construct($hh,$params=array(),$data=array()) {
		$module=record_by_id($data['gspgid_va'][0],'wz_modules');
		$rs=new wz_urls();
		$rs->find_records(array('Module_id'=>$module->get_id(),
					array('field'=>'gspgid_value','case'=>'!=','value'=>''),
					array('type'=>'orderby','value'=>'gspgid_value'),
					));
		$urls=array();
		foreach ($rs as $r) {
			$url=$r->gspgid_value;
			if (substr($url,0,1)!='/') $url='/'.$module->name.'/'.$url;
			$urls[$url]=$url;
		}
		$hh=array(
		    'gspgid_value' => Array
			(
			    'verbose_name'=>'gspgid',
			    'type' => 'select',
			    'options' => $urls,
			    'widget'=>'select_enter',
			),
		    'extra_params' => Array
			(
			    'type' => 'input',
			    'validate' => 'dummyValid',
			),

		);
		return parent::__construct($hh,$params,$data);
	}
	function macros() {
		$d=$this->clean();

		$str='{handler gspgid="%s" %s}';

		return $this->escape(sprintf($str,$d['gspgid_value'],$d['extra_params']));

	}
}

class wz_macros_controller_foreach extends wz_macros {
	function __construct($hh,$params=array(),$data=array()) {
		$module=record_by_id($data['gspgid_va'][0],'wz_modules');
		//md($module->recordsets->recordset_as_string_array());
		$opts=array();
		foreach($module->recordsets as $rs) {
			$opts[trim($rs)]=$rs->Fields->recordset_as_string_array();
		}

		$hh=array(
			/*
		    'recordset' => Array
			(
			    'type' => 'select',
			    //'options'=>class_members('gs_recordset_short'),
			    'options'=>$module->recordsets->recordset_as_string_array()
			),
			*/
		    'type' => Array
			(
			    'type' => 'select',
			    'options'=>',table,tr,li',
			    'validate'=>'notEmpty',
			),
		    'fields' => Array
			(
			    'type' => 'multiselect',
			    //'options'=>$module->recordsets->first()->Fields->recordset_as_string_array(),
			    'options'=>$opts,
			    'validate'=>'notEmpty',
			),

		);
		return parent::__construct($hh,$params,$data);
	}
	function macros() {
		$d=$this->clean();
		$fields=new wz_recordset_fields();
		$fields->find_records(array('id'=>$d['fields']));
		$recordset=$fields->first()->Recordset->first();

		$names=array();
		foreach($fields as $f) {
			$names[$f->name]=$f->verbose_name;
		}
		$values=array();
		foreach($names as $name=>$v) {
			$values[$name]=sprintf('{$i_%s.%s}',$recordset,$name);
		}


		$cont=sprintf('{controller _class="%s" _assign=%s}',$recordset,$recordset);

		$str=$this->{"view_".$d['type']}($recordset,$names,$values);
		$cont.=$str;
		return $this->escape($cont);
	}
	function iac($arr,$pattern) {
		$ret=array();
		foreach ($arr as $a) {
			$ret[]=sprintf($pattern,$a);
		}
		return implode($ret,"\n");
	}
	function view_table($rsname,$names,$values) {
		$names_str=$this->iac($names,'<td>%s</td>');
		$values_str=$this->iac($values,'<td>%s</td>');
		$str='
			<table>
			<thead>
			<tr>

			%s

			</tr>
			</thead>
			<tbody>
			{foreach from=$%s item=i_%s}

			<tr>
			%s
			</tr>

			{/foreach}
			</tbody>
			</table>
		';
		$str=sprintf($str,$names_str,$rsname,$rsname,$values_str);
		return $str;

	}
	function view_tr($rsname,$names,$values) {
		$values_str=$this->iac($values,'<td>%s</td>');
		$str='
			{foreach from=$%s item=i_%s}
			<tr>
			%s
			</tr>
			{/foreach}
		';
		$str=sprintf($str,$rsname,$rsname,$values_str);
		return $str;

	}
	function view_li($rsname,$names,$values) {
		$values_str=$this->iac($values,'<span>%s</span>');
		$str='
			<ul>
			{foreach from=$%s item=i_%s}

			<li>
			%s
			</li>

			{/foreach}
			</ul>
		';
		$str=sprintf($str,$rsname,$rsname,$values_str);
		return $str;

	}
}
class wz_macros_extends extends wz_macros {
	function __construct($hh,$params=array(),$data=array()) {
		$extends=array_map(basename,glob(cfg('tpl_data_dir')."*"));
		array_unshift($extends,'');
		$hh=array(
		    'extends' => Array
			(
			    'type' => 'select_enter',
			    'options' => array_combine($extends,$extends),
			),
		);
		return parent::__construct($hh,$params,$data);
	}
	function macros() {
		$d=$this->clean();

		$str='{extends file="%s"}';

		return $this->escape(sprintf($str,$d['extends']));

	}
}


?>
