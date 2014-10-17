<?php

class gs_widget_validate_exception extends gs_exception {}
class gs_widget_skipdata_exception extends gs_exception {}
interface gs_widget_interface {
	function __construct($fieldname,$data);
	function html();
	function js();
	function clean();
	function validate();
}
abstract class gs_widget implements gs_widget_interface {
	function __construct($fieldname,$data,$params=array(),$form=NULL) {
		$this->form=$form;
		$this->validate_errors=NULL;
		$this->fieldname=$fieldname;
		$this->value=is_string($fieldname) && isset($data[$fieldname]) ? $data[$fieldname] : NULL;
		$this->params=$params;
		$this->data=$data;
		$this->tpl=gs_tpl::get_instance();
		$this->interact=(isset($this->params['interact']) && $this->params['interact']) ? ' fInteract' : '';
		$this->placeholder=isset($this->params['placeholder']) ? 'placeholder="'.$this->params['placeholder'].'"' : '';
		$this->rows=isset($this->params['rows']) ? 'rows="'.$this->params['rows'].'"' : '';
	}
	function clean() {
		if (!$this->validate()) throw new gs_widget_validate_exception($this->fieldname);
		return $this->value;
	}
	function validate() {
		return true;
	}
	function js() {
		return $this->html();
	}
	function html() {
		$css=isset($this->params['cssclass']) ? $this->params['cssclass'] : 'widget';
		if ($this->form->get_error($this->fieldname)) $css.=" ".$this->form->css_error_class;
		return sprintf('<input class="%s" type="text" name="%s" value="%s" %s  %s>', 
			$css,
			$this->fieldname,trim($this->value),(isset($this->params['readonly']) && $this->params['readonly']) ? 'disabled="disabled"' : '', $this->placeholder);
	}
}

class gs_widget_label extends gs_widget {
	function html() {
		return $this->value;
	}
	function clean() {
		return null;
	}
}
class gs_widget_input extends gs_widget {
	var $type="text";
	function html() {
		$value=$this->value;
		if (is_string($this->value)) $value=htmlspecialchars(trim($this->value));
		return sprintf('<input class="%s" type="%s" name="%s" value="%s" %s>',
					   isset($this->params['cssclass']) ? $this->params['cssclass'] : 'fString',
						$this->type,
					   $this->fieldname,$value, $this->placeholder
					   );
	}
}
class gs_widget_date extends gs_widget_input {
	var $type="date";
}
class gs_widget_number extends gs_widget {
	function html() {
		return sprintf('<input class="%s" type="text" name="%s" value="%s">',
					   isset($this->params['cssclass']) ? $this->params['cssclass'] : 'fString fNumber',
					   $this->fieldname,htmlspecialchars(trim($this->value)));
	}
}

class gs_widget_int extends gs_widget {
	function html() {
		return sprintf('<input class="fInt" type="text" name="%s" value="%d">', $this->fieldname,trim($this->value));
	}
}


class gs_widget_password2 extends gs_widget {
	var $htmlstr='<input class="fPassword %s" type="password" name="%s" value="%s" %s %s></td><td class="helper_tr_error"></td></tr><tr class="helper_tr"><td class="helper_tr_title">%s</td><td class="helper_tr_field"><input class="fPassword %s" type="password" name="%s_repeat" value="%s" %s %s>';
	function html() {
		$f2=$this->fieldname.'_repeat';
		$v2=isset($this->data[$f2]) ? $this->data[$f2] : '';
		return sprintf($this->htmlstr,
		isset($this->params['cssclass']) ? $this->params['cssclass'] : '',
		$this->fieldname,
		trim($this->value),
		$this->params['readonly'] ? 'disabled="disabled"' : '',
		$this->placeholder,
		//$this->params['verbose_name2']? $this->params['verbose_name2'] : gs_dict::get('REPEAT_PASSWORD'),
		isset($this->params['verbose_name2']) ? $this->params['verbose_name2'] : '',
		isset($this->params['cssclass']) ? $this->params['cssclass'] : '',
		$this->fieldname,
		($this->value==$v2) ? $this->value : '',
		$this->params['readonly'] ? 'disabled="disabled"' : '',
		isset($this->params['placeholder2']) ? 'placeholder="'.$this->params['placeholder2'].'"' : ''
		);
	}

	function validate() {
		$f1=$this->fieldname;
		$f2=$this->fieldname.'_repeat';
		return (isset($this->data[$f2]) && $this->data[$f1]==$this->data[$f2]);
	}
}

class gs_widget_password2_inline extends gs_widget_password2 {
	//var $htmlstr='<input class="fPassword %s" type="password" name="%s" value="%s" %s %s></td><td class="helper_tr_error"><input class="fPassword %s" type="password" name="%s_repeat" value="%s" %s %s>';
	var $htmlstr='<input class="fPassword %s" type="password" name="%s" value="%s" %s %s>
				%s
				<input class="fPassword %s" type="password" name="%s_repeat" value="%s" %s %s>';
}


class gs_widget_password extends gs_widget {
	function html() {
		/*
		return sprintf('<input class="fPassword %s" type="password" name="%s" value="%s" %s>',
			isset($this->params['cssclass']) ? $this->params['cssclass'] : '',
			$this->fieldname,htmlspecialchars(trim($this->value)), $this->placeholder);
		*/
		return sprintf('<input class="fPassword %s" type="password" name="%s"  %s>',
			isset($this->params['cssclass']) ? $this->params['cssclass'] : '',
			$this->fieldname, $this->placeholder);
	}

	function clean() {
		$value=parent::clean();
		if (empty($value)) throw new gs_widget_skipdata_exception($this->fieldname);
		return $value;
	}

}
class gs_widget_hidden extends gs_widget {
	function html() {
		return sprintf('<input class="fHidden" type="hidden" name="%s" value="%s">', $this->fieldname,htmlspecialchars(trim($this->value)));
	}
}
class gs_widget_text extends gs_widget {
	function html() {
		return sprintf('<textarea class="%s" name="%s" %s %s>%s</textarea>',
					   isset($this->params['cssclass']) ? $this->params['cssclass'] : 'fText',
					   $this->fieldname,
					   $this->placeholder,
					   $this->rows,
					   trim($this->value)
					   );
	}
}

class gs_widget_plaintext  extends gs_widget_text {
	function clean() {
		return strip_tags(parent::clean());
	}
}

class gs_widget_fetch extends gs_widget_text{
	function clean() {
		$url=parent::clean();
		if ($url && parse_url($url,PHP_URL_SCHEME)=='http') {
			$r=file_get_contents($url);
			if ($r!==FALSE) return $r;
		}
		return $url;
	}
}

class gs_widget_file extends gs_widget {
	function html() {
		return sprintf('<input class="fFile" type="file" name="%s" >', $this->fieldname);
	}
	function clean() {
		if (!isset($this->value['tmp_name'])) return array();
		$ret=array(
				$this->fieldname.'_data'=>file_get_contents($this->value['tmp_name']),
				$this->fieldname.'_filename'=>$this->value['name'],
				$this->fieldname.'_mimetype'=>$this->value['type'],
				$this->fieldname.'_size'=>$this->value['size'],
				//$this->fieldname=>$this->get_id(),
			 );
		if (stripos($this->value['type'],'image')===0) {
			list($ret[$this->fieldname.'_width'],$ret[$this->fieldname.'_height'])=getimagesize($this->value['tmp_name']);
		}
		return $ret;
	}
}

class gs_widget_coords extends gs_widget {
	function html() {
		return sprintf('<input type="text" class="coords" name="%s[X]" id="coord_x" value="%d">:<input type="text" class="coords" name="%s[Y]" id="coord_y" value="%d"><br><div id="coords_map" class="main29_map"></div>', $this->fieldname,$this->data[$this->fieldname.'_x'], $this->fieldname,$this->data[$this->fieldname.'_y']);
	}
	function clean() {
		parent::clean();
		return array(
				   $this->fieldname.'_x'=>$this->value['X'],
				   $this->fieldname.'_y'=>$this->value['Y'],
			   );
	}

	function validate() {
		return (!empty($this->value['X']) && !empty($this->value['Y']));
	}
}


class gs_widget_yandex_coords extends gs_widget {
	function html() {
		$x=$y=0;
		
		if (isset($this->params['variants']) && $this->params['variants']) {
			$x=$this->params['variants']['x'];
			$y=$this->params['variants']['y'];
		}
			//list($x,$y)=$this->params['variants'];
		return sprintf('<input type="hidden" class="coords" name="%s[X]" id="coord_x" value="%.9f">:<input type="hidden" class="coords" name="%s[Y]" id="coord_y" value="%.9f"><br><div id="coords_map" ></div><script type="text/javascript">if (%.2f!=0) {ymaps.ready(function() {window.placemark.geometry.setCoordinates([%.9f,%.9f]);window.myMap.setCenter([%.9f,%.9f],11);});}</script>', $this->fieldname,$x, $this->fieldname,$y,$x,$x,$y,$x,$y);
	}
	function clean() {
		parent::clean();
		return array(
				   $this->fieldname.'_x'=>$this->value['X'],
				   $this->fieldname.'_y'=>$this->value['Y'],
			   );
	}

	/*function validate() {
		return (!empty($this->value['X']) && !empty($this->value['Y']));
	}*/
}

class gs_data_widget_yandex_coords {
	function gd($rec,$k,$hh,$params,$data) {
		$hh[$k]['variants']=array('x'=>$rec[$k.'_x'],'y'=>$rec[$k.'_y']);
		return $hh;
	}
}


class gs_widget_image extends gs_widget_file {
	function html() {
		//md($this->fieldname,1);
		//md($this->data,1);
		//if ($this->value) return sprintf('img src="%s">', $this->value);
		return parent::html();
	}
}

class gs_widget_dateMonthYear extends gs_widget {
	function html() {
		$this->data[$this->fieldname.'[Month]']= $this->data[$this->fieldname]['Month'];
		$this->data[$this->fieldname.'[Year]']= $this->data[$this->fieldname]['Year'];
		$this->params['options']=array(''=>'');
		foreach (range(1,12) as $i)  $this->params['options'][$i]=sprintf("%02d",$i);
		$w_sel=new gs_widget_select($this->fieldname.'[Month]',$this->data,$this->params,$this->form);
		$out=$w_sel->html();
		$min_year=date('Y',strtotime($this->params['min_year']));
		$max_year=date('Y',strtotime($this->params['max_year']));
		$this->params['options']=array(''=>'');
		foreach (range($min_year,$max_year) as $i)  $this->params['options'][$i]=$i;
		$w_sel=new gs_widget_select($this->fieldname.'[Year]',$this->data,$this->params,$this->form);
		$out.=$w_sel->html();
		return $out;
	}

	function clean() {
		parent::clean();
		return array(
				$this->fieldname.'_Month'=>$this->value['Month'],
				$this->fieldname.'_Year'=>$this->value['Year'],
			   );
	}
	function validate() {
		return (!empty($this->value['Month']) && !empty($this->value['Year']));
	}

}

class gs_widget_datetime extends gs_widget {
	function html() {
		return sprintf('<input class="%s fDateTime" type="text" name="%s" value="%s" %s>',
			isset($this->params['cssclass']) ? $this->params['cssclass'] : ' ',
			$this->fieldname,htmlspecialchars(trim($this->value)), $this->placeholder);
	}
	function clean() {
		if (empty($this->value)) return '';
		$s=strtotime($this->value);
		return $s>0 ?  date('Y-m-d H:i:s',$s) : '';

		//return date('Y-m-d H:i:s',strtotime(!empty($this->value) ? $this->value : 'now' ));
	}
}

class gs_widget_email extends gs_widget {
	function validate() {
		$v=new gs_validate_isEmail();
		return $v->validate($this->fieldname,$this->value);
	}
}
class gs_widget_select extends gs_widget {
	function js() {
		$ret="<select class=\"fSelect\" name=\"".$this->fieldname."\">\n";
		foreach ($this->params['options'] as $v) {
			$ret.="<option value=\"$v\" <% if (t.values.".$this->fieldname."==\"$v\") { %> selected=\"selected\" <% } %> >$v</option>\n";
		}

		$ret.="</select>\n";
		return $ret;
	}
	function html($multi=false) {
		$ret=sprintf('<select class="%s %s"  name="%s%s" %s>',
					 isset($this->params['cssclass']) ? $this->params['cssclass'] : ($multi ? 'fMultiSelect' : 'fSelect'),
					 $this->interact,
					 $this->fieldname,
					 ($multi ? '[]' : ''),
					 ($multi ? 'multiple="on"' : '')
					);
		if (isset($this->params['variants']) && $this->params['variants']) $this->params['options']=$this->params['variants'];
		if (!is_array($this->params['options'])) $this->params['options']=array_combine(explode(',',$this->params['options']),explode(',',$this->params['options']));

		foreach ($this->params['options'] as $v=>$l) {
			if (is_array($l)) {
				$ret.=sprintf("<optgroup label=\"%s\">\n",htmlspecialchars($v));
				foreach ($l as $vv=>$vl) {
					$ret.=$this->option_string($vv,$vl);
				}
				$ret.="</optgroup>\n";
			} else {
				$ret.=$this->option_string($v,$l);
			}
		}

		$ret.="</select>\n";
		return $ret;
	}
	function option_string($v,$l) {
		/*
		var_dump(is_array($this->value));
		var_dump($this->value->array_keys());
		$sel=is_array($this->value) ? in_array($v,array_keys($this->value)) : trim($this->value)==$v;
		*/

		$sel=false;
		if(trim($this->value)==$v) $sel=TRUE;
		if (is_array($this->value) && in_array($v,array_keys($this->value))) $sel=TRUE;
		if ($this->value instanceof gs_recordset && in_array($v,$this->value->array_keys())) $sel=TRUE;

		return sprintf("<option value=\"%s\" %s>%s</option>\n", htmlspecialchars($v), $sel ? 'selected="selected"' : '', $l);
	}
}
class gs_data_widget_select {
	function gd($rec,$k,$hh,$params,$data) {
		if (method_exists($rec->get_recordset(),'gs_data_widget_select')) {
			$variants=array();
			$vrecs=$rec->get_recordset()->gs_data_widget_select($rec,$k);
			foreach ($vrecs as $key=>$vrec) {
				if (is_array($vrec) || is_string($vrec)) {
					$variants[$key]=$vrec;
				} else {
					$variants[trim($vrec)]=trim($vrec);
				}
			}
			$hh[$k]['variants']=$variants;
			return $hh;
		}
	}
}
class gs_widget_multiselect extends gs_widget_select {
	function html() {
		return parent::html(TRUE);
	}
	function clean() {
		if (!$this->validate()) throw new gs_widget_validate_exception($this->fieldname);
		$ret=array();
		//$ret=is_array($this->value) && count($this->value)>0 ? array_combine(array_values($this->value),array_values($this->value)) : array();
		if (is_array($this->value)) foreach ($this->value as $k=>$v) {
			if (is_string($v) || is_numeric($v)) $ret[$v]=$v;
		}
		return array($this->fieldname=>$ret);
	}
}

class gs_widget_multiselect_chosen extends gs_widget_multiselect {
	function __construct($fieldname,$data,$params=array(),$form=null) {
		parent::__construct($fieldname,$data,$params,$form);
		if (!isset($this->params['cssclass'])) $this->params['cssclass']='';
		$this->params['cssclass'].=' chzn-select chosen';
	}
}
class gs_widget_select_chosen extends gs_widget_select {
	function __construct($fieldname,$data,$params=array(),$form=null) {
		parent::__construct($fieldname,$data,$params,$form);
		$this->params['cssclass'].=' chosen';
	}
}


class gs_data_widget_select_enter extends gs_data_widget_select {}

class gs_widget_select_enter extends gs_widget_select {
	function html() {
		if (!is_array($this->value)) $this->value=array('select'=>trim($this->value),'enter'=>trim($this->value));
		$ret="<select onChange=\"$('input[selname=".$this->fieldname."]').val(this.value);\" class=\"fSelect\"  name=\"".$this->fieldname."[select]\"><option></option>\n";
		if (($this->params['variants'])) $this->params['options']=$this->params['variants'];
		if (!is_array($this->params['options'])) $this->params['options']=array_combine(explode(',',$this->params['options']),explode(',',$this->params['options']));
		foreach ($this->params['options'] as $v=>$l) {
			if (is_array($l)) {
				$ret.=sprintf("<optgroup label=\"%s\">\n",htmlspecialchars($v));
				foreach ($l as $vv=>$vl) {
					$ret.=$this->option_string($vv,$vl);
				}
				$ret.="</optgroup>\n";
			} else {
				$ret.=$this->option_string($v,$l);
			}
		}
		$ret.="</select>\n";
		$ret.=sprintf("</label><label><input class=\"fSelect\"  name=\"%s[enter]\" selname=\"%s\" value=\"%s\">",
					  $this->fieldname,
					  $this->fieldname,
					  trim($this->value['enter'])
					 );

		return $ret;
	}
	function clean() {
		$ret=isset($this->value['enter']) && !empty($this->value['enter']) ? $this->value['enter'] : $this->value['select'];
		return $ret;
	}
}

class gs_widget_checkboxes extends gs_widget_multiselect {
	function html($multi=false) {
		$ret='<input type="hidden" name="'.$this->fieldname.'[]" value="">';
		if (!is_array($this->params['options'])) $this->params['options']=array_combine(explode(',',$this->params['options']),explode(',',$this->params['options']));
		foreach ($this->params['options'] as $v=>$l) {
			if (is_array($l)) {
				$ret.=sprintf("<fieldset><legend>%s</legend>\n",htmlspecialchars($v));
				foreach ($l as $vv=>$vl) {
					$ret.=$this->option_string($vv,$vl);
				}
				$ret.="</fieldset>\n";
			} else {
				$ret.=$this->option_string($v,$l);
			}
		}
		return $ret;
	}
	function option_string($v,$l) {
		$sel=is_array($this->value) ? in_array($v,$this->value) : trim($this->value)==$v;
		return sprintf('<label class="%s"><input type="checkbox" name="%s[]" value="%s" %s>%s</label>%s'.PHP_EOL, 
				isset($this->params['cssclass']) ? $this->params['cssclass'] : 'fSelect',
				$this->fieldname,
				htmlspecialchars($v), 
				$sel ? 'checked="checked"' : '', 
				$l,
				 isset($this->params['delimiter']) ? $this->params['delimiter'] : ''
				);
	}
	function clean() {
		$d=parent::clean();
		if (is_array($d)) foreach ($d as $k=>$dd)  {
			if (is_array($dd)) $d[$k]=array_filter($dd);
		}
		return $d;
	}
}
class gs_widget_Set extends gs_widget_checkboxes {
	function html($multi=false) {
		if (!is_array($this->value)) $this->value=array_map('trim',explode(',',$this->value));
		return parent::html($multi);
	}
}


class gs_widget_checkbox extends gs_widget {
	function html() {
		$s=sprintf('<input type="hidden" name="%s" value="0">', $this->fieldname);
		$s.=sprintf('<input class="%s %s" type="checkbox" name="%s" id="%s" value="1" %s>',
					isset($this->params['cssclass']) ? $this->params['cssclass'] : 'fCheckbox', $this->interact,
					$this->fieldname,$this->fieldname,trim($this->value) ? 'checked="checked"' : '');
		return $s;
	}
	function js() {
		$s=sprintf('<input type="hidden" class="fCheckbox" name="%s" value="0">', $this->fieldname);
		$s.="<input type=\"checkbox\" name=\"$this->fieldname\" value=\"1\" <%if(t.values.$this->fieldname == 1) { %> checked=\"checked\"i<% } %> >";
		return $s;
	}
}
class gs_widget_radio extends gs_widget {
	function html() {
		$s="";
		if (!is_array($this->params['options'])) $this->params['options']=array_combine(explode(',',$this->params['options']),explode(',',$this->params['options']));
		foreach ($this->params['options'] as $v=>$l) {
			$s.=sprintf('<label class="radio"><input class="%s %s" type="radio" name="%s" value="%s" %s> %s </label>%s',
				 isset($this->params['cssclass']) ? $this->params['cssclass'] : 'fRadio',
				 $this->interact,
				 $this->fieldname,
				 htmlspecialchars($v),
				 trim($this->value)==$v || (isset($this->params['default']) && $v==$this->params['default']) ? 'checked="checked"' : '',
				 $l,
				 isset($this->params['delimiter']) ? $this->params['delimiter'] : ''
				 );
		}
		return $s;
	}
}

class gs_widget_lMany2Many extends gs_widget_multiselect {}
class gs_data_widget_lMany2Many {
	function gd($rec,$k,$hh,$params,$data) {
		$v=$hh[$k];
		$rname=$v['options']['rs2_name'];
		$vro=new $rname;
		$options=array();
		$vrecs=$vro->find_records($options);

		$variants=array();
		//$variants[0]='';
		foreach ($vrecs as $vrec) {
			$id=$vrec->get_id();
			if ($idname) $id=$vrec->$idname;
			$variants[$id]=trim($vrec);
		}
		$hh[$k]['variants']=$variants;
		return $hh;
	}
}

class gs_widget_lMany2Many_chosen extends gs_widget_multiselect_chosen {}
class gs_data_widget_lMany2Many_chosen extends gs_data_widget_lMany2Many {}

class gs_widget_lOne2One extends gs_widget {
	function js() {
		$ret=sprintf("<select class=\"%s\" name=\"".$this->fieldname."\">\n",
				isset($this->params['cssclass']) ? $this->params['cssclass'] : 'lOne2One');
		$ret.="<% for (vid in t.values.".$this->fieldname.".variants) { %>
			  <option value=\"<%=vid%>\" <% if (t.values.".$this->fieldname.".selected == vid) { %> selected=\"selected\" <% } %>  ><%=t.values.".$this->fieldname.".variants[vid]%></option>
			  <% } %>
			  ";
		$ret.="</select>\n";
		return $ret;
	}
	function option_string($fieldname) {
		return sprintf("<select  class=\"%s fInteract\" name=\"%s\">\n",
				isset($this->params['cssclass']) ? $this->params['cssclass'] : 'lOne2One',
				$fieldname);
	}
	function html() {
		$ret=$this->option_string($this->fieldname);
		if ($this->params['nulloption']) $ret.=sprintf('<option value="%s">%s</option>',$this->params['nulloption'][0],isset($this->params['nulloption'][1]) ? $this->params['nulloption'][1] : '' );
		if (isset($this->params['variants']) && is_array($this->params['variants'])) foreach ($this->params['variants'] as $k=>$v) {
			$ret.=sprintf("<option value=\"%s\" %s>%s</option>\n", ($k==0 && empty($v)) ? '' : $k, ($this->value==$k) ? 'selected="selected"' : '',$v);
		}
		$ret.="</select>\n";
		return $ret;
	}
}
class gs_widget_form_add extends gs_widget {
	function html() {
		if ($this->value) return $this->form_add_ok($this->value);
		$idname=$this->fieldname.'_'.md5(rand());
		$s=sprintf('<input type="hidden" name="%s" id="%s" value="%s">', $this->fieldname,$idname,$this->value);
		$s.=sprintf('<iframe src="%sform_add/%s/%s" style="width:100%%; border: 0px;"></iframe>', $this->tpl->getTemplateVars('www_subdir'), $this->params['options']['recordset'],$idname);
		return $s;
	}
	function clean() {
		return $this->value;
	}
	function form_add_ok($value=false) {
		if ($value) {
			$s=$this->params['options']['recordset'];
			$rec=new $s;
			$rec=$rec->get_by_id($value);
			return trim($rec);
		}
		$data=$this->fieldname;
		$rec=new $data['gspgid_va'][1];
		$rec=$rec->get_by_id($data['gspgid_va'][0]);
		printf("%s<script type=\"text/javascript\">window.top.document.getElementById('%s').value=%d;</script>",$rec,$data['gspgid_va'][2],$data['gspgid_va'][0]);
	}
}

/*
* {controller _class=$_gsdata.gspgid_va.0 _params=$params _assign="list"}
* output form: {handler gspgid="$subdir/form/`$_gsdata.gspgid_va.0`" _params=$params}
*/
class gs_widget_lMany2One extends gs_widget {
	function clean() {
		//md($this->data,1);
		return array('fake'=>true);
	}
	function html() {
		$e_data=gs_base_handler::explode_data($this->data);
		$rs=new $this->params['options']['recordset'];
		$options=array($rs->id_field_name=>isset($e_data[$this->fieldname]) ? array_keys($e_data[$this->fieldname]) : array());
		$links=$rs->find_records($options);
		$rid_name=$this->params['options']['local_field_name'];
		$rid=isset ($this->data[$rid_name]) ? $this->data[$rid_name] : 0;
		$hash=isset($this->data[$this->params['linkname'].'_hash']) ? $this->data[$this->params['linkname'].'_hash'] : time().rand(10,99);
		$s=sprintf('<a href="/admin/many2one/%s/%s/%d/%s" target="_blank" onclick="window.open(this.href,\'_blank\',\'width=800,height=400,scrollbars=yes, resizable=yes\'); return false;" id="lMany2One_%s">%s</a>',
				   $this->params['options']['recordset'],
				   $this->params['options']['foreign_field_name'],
				   $rid,
				   $hash,
				   $this->params['linkname'],
				   gs_dict::get('LOAD_RECORDS'));
		$s.=sprintf('<input type="hidden" name="%s" value="%s">', $this->params['linkname'].'_hash',$hash);
		return $s;
	}
}

class gs_widget_gallery extends gs_widget {
	function clean() {
		$hash_field_name=$this->params['linkname'].'_hash';
		return array($hash_field_name=>$this->data[$hash_field_name]);
		//return array('fake'=>true);
	}
	function html() {
		$hash_field_name=$this->params['linkname'].'_hash';
		$hash=isset($this->data[$hash_field_name]) ? $this->data[$hash_field_name] : time().rand(10,99);
		$rid_name=$this->params['options']['local_field_name'];
		$rid=isset ($this->data[$rid_name]) ? $this->data[$rid_name] : 0;
		$r=new $this->params['options']['recordset'];

		$images=$r->find_records(array(
				$this->params['options']['foreign_field_name']=>0,
				array('field'=>'_ctime','case'=>'<=','value'=>date(DATE_ATOM,strtotime('now -1 day'))),
				));
		$images->delete();
		$images->commit();

		$find=array();
		if (isset ($this->data[$rid_name])) {
			$find[$this->params['options']['foreign_field_name']]=$this->data[$rid_name];
		} else {
			$find[$this->params['options']['foreign_field_name'].'_hash']=$hash;
		}
			
		$images=$r->find_records($find);
		//$images=$images->get_values();

		$s='<ul class="many2one_gallery" id="gallery_'.$hash.'">';
			$images=$images->img('admin');
			if(count($images)) $s.=sprintf('<li>%s</li>',implode('</li><li>',$images));
		$s.='</ul><div class="clear"></div>';



		$s.=sprintf('<a href="/admin/many2one/%s/%s/%d/%s/as_gallery" target="_blank" onclick="window.open(this.href,\'_blank\',\'width=800,height=400,scrollbars=yes, resizable=yes\'); return false;" id="lMany2One_%s">%s</a>',$this->params['options']['recordset'],$this->params['options']['foreign_field_name'],$rid,$hash,$this->params['linkname'],gs_dict::get('LOAD_RECORDS'));
		$s.=sprintf('<input type="hidden" name="%s" value="%s">', $this->params['linkname'].'_hash',$hash);
		return $s;
	}
}



class gs_widget_parent_list extends gs_widget_lOne2One {}

class gs_data_widget_parent_list{
	function gd($rec,$k,$hh,$params,$data) {
		$v=$hh[$k];
		$idname=null;
		if (isset($v['options']['foreign_field_name'])) $idname=$v['options']['foreign_field_name'];

		if (method_exists($rec->get_recordset(),'form_variants_'.$v['linkname'])) {
			$vrecs=call_user_func(array($rec->get_recordset(),'form_variants_'.$v['linkname']),$rec,$data);
		} else {
			$rname=get_class($rec->init_linked_recordset($v['linkname']));
			$vro=new $rname;
			$options=array();
			foreach($data['handler_params'] as $o=>$v) {
				if (isset($data['handler_params']['_alllinks'])) {
					if (in_array($k,array_map('trim',explode(',',$data['handler_params']['_alllinks'])))) {
						continue;
					}
				}
				if (isset($vro->structure['fields'][$o])) $options[$o]=$v;
			}
			$vrecs=$vro->find_records($options);
		}
		$variants=array();
		$variants[0]='';
		foreach ($vrecs as $vrec) {
			$id=$vrec->get_id();
			if ($idname) $id=$vrec->$idname;
			$variants[$id]=trim($vrec);
		}
		$hh[$k]['variants']=$variants;
		return $hh;
	}
}
class gs_widget_parent_list_notnull extends gs_widget_parent_list {}

class gs_data_widget_parent_list_notnull extends gs_data_widget_parent_list {
	function gd($rec,$k,$hh,$params,$data) {
		$hh=parent::gd($rec,$k,$hh,$params,$data);
		if (isset($hh[$k]['variants'][0]) && !$hh[$k]['variants'][0]) unset($hh[$k]['variants'][0]);
		return $hh;
	}
}

class gs_widget_121_radio extends gs_widget_lOne2One {
	function html() {
		$ret="";
		foreach ($this->params['variants'] as $k=>$v) {
			$ret.=sprintf("<div class=\"%s\"><label><input type=\"radio\" name=\"%s\" value=\"%s\" %s>%s</label></div>\n",
			isset($this->params['cssclass']) ? $this->params['cssclass'] : 'radio_121 radio',
			$this->fieldname, $k, ($this->value==$k) ? 'checked="checked"' : '',$v);
		}
		$ret.='<div class="radio_121_end"></div>';
		return $ret;
	}
}
class gs_data_widget_121_radio extends gs_data_widget_parent_list {}

class gs_widget_121_radio_notnull extends gs_widget_121_radio{}
class gs_data_widget_121_radio_notnull extends gs_data_widget_parent_list_notnull{}


class gs_widget_121_segmented extends gs_widget_parent_list_notnull{
	function html() {
		$ret=sprintf ('
					<div class="btn-group 121_segmented" data-toggle="buttons-radio">
					<input type="hidden" name="%s" value="%s">
				', $this->fieldname, $this->value);

		foreach ($this->params['variants'] as $k=>$v) {
			$ret.=sprintf('<button type="button" class="%s %s" value="%s">%s</button>',
								isset($this->params['cssclass']) ? $this->params['cssclass'] : 'btn',
								($this->value==$k) ? 'active' : '',
								$k, $v);
		}
		$ret.="</div>

			<script>
				$(document).ready(function() {
					var sel=$('div.121_segmented button');
					sel.live('click',function() {
						var inp=$(this).closest('div.121_segmented').find('input');
						inp.val(this.value);
						inp.change();
					});
				});
			</script>	
		
		";

		return $ret;
	}
}
class gs_data_widget_121_segmented extends gs_data_widget_parent_list_notnull {}


class gs_widget_include_form extends gs_widget {}

class gs_data_widget_include_form {
	function gd($rec,$k,$hh,$params,$data) {
		if (isset($hh[$k]) && $hh[$k]['type']=='lOne2One') {
			$l_k=$hh[$k]['linkname'];
			$nrs=$rec->$l_k;
		} else {
			$l_k=$k;
			$nrs=$rec->$k;
		}


		if (!is_object($nrs) || !$nrs) return $hh;
		$nrs->first(true);

		foreach($nrs as $nobj) {
			$f=gs_base_handler::get_form_for_record($nobj,$params,$data);
			$forms=$f->htmlforms;
			$i=intval($nobj->get_id());
			foreach($forms as $fk=>$fv) {
				$key="$l_k:$fk";
				$pfx_key=$hh[$k]['type']=='lOne2One' ? $key : "$l_k:$i:$fk";

				$hh[$pfx_key]=$fv;
				if(isset($data['handler_params'][$key])) {
					$data['handler_params'][$pfx_key]=$data['handler_params'][$key];
				}

				if ($params['form']) {
					$params['form']->add_field($pfx_key,$fv);
				}

			}
		}
		if ($params['form']) {
			$params['form']->remove_field($k);
		}
		unset($hh[$k]);
		return $hh;
	}
}

class gs_widget_window_form extends gs_widget {
	function clean() {
		return array('fake'=>true);
	}
	function html() {
		$rid_name=$this->params['options']['local_field_name'];
		$rid=isset ($this->data[$rid_name]) ? $this->data[$rid_name] : 0;
		$r=new $this->params['options']['recordset'];
		$find=array();
		$find[$this->params['options']['foreign_field_name']]=$rid;
		$hash=isset($this->data[$this->params['linkname'].'_hash']) ? $this->data[$this->params['linkname'].'_hash'] : time().rand(10,99);
		$images=$r->find_records($find);
		$images=$images->get_values();


		$s='';
		$s.=sprintf('<a href="/admin/window_form/%s/%s/%d/%s" target="_blank" onclick="window.open(this.href,\'_blank\',\'width=800,height=400,scrollbars=yes, resizable=yes\'); return false;" id="lMany2One_%s">%s</a>',$this->params['options']['recordset'],$this->params['options']['foreign_field_name'],$rid,$hash,$this->params['linkname'],gs_dict::get('LOAD_RECORDS'));
		$s.=sprintf('<input type="hidden" name="%s" value="%s">', $this->params['linkname'].'_hash',$hash);
		return $s;
	}
}

class gs_widget_iframe_gallery extends gs_widget {
	function clean() {
		return array('fake'=>true);
	}
	function html() {
		$rid_name=$this->params['options']['local_field_name'];
		$rid=isset ($this->data[$rid_name]) ? $this->data[$rid_name] : 0;
		$hash=isset($this->data[$this->params['linkname'].'_hash']) ? $this->data[$this->params['linkname'].'_hash'] : time().rand(10,99);

		$s='<div class="many2one_gallery" id="gallery_'.$hash.'">';
		if ($rid>0) {
			$r=new $this->params['options']['recordset'];
			$find=array();
			$find[$this->params['options']['foreign_field_name']]=$rid;
			$images=$r->find_records($find);
			$s.=(string)$images;
			/*
							$images=$images->get_values();
					if (count($images)) {foreach ($images as $im) {
							$s.=sprintf('<img src="/img/h/%s/100/%d.jpg" title="%s">',$this->params['options']['recordset'],$im['id'],$im['name']);
					}}
						*/
		}
		$s.='<div class="clear"></div></div>';

		$s.=sprintf('<a href="/admin/news/iframe_gallery/%s/%s/%d/%s" target="gal_%s" id="lMany2One_%s">%s</a>',
					$this->params['options']['recordset'],
					$this->params['options']['foreign_field_name'],
					$rid,
					$hash,
					$hash,
					$this->params['linkname'],
					$this->params['linkname'],
					gs_dict::get('GALLERY_MANAGE_RECORDS'));
		$s.='<iframe name="gal_'.$hash.'" class="gallery_ifr" id="gal_'.$hash.'" frameBorder="0"></iframe>';
		$s.=sprintf('<input type="hidden" name="%s" value="%s">', $this->params['linkname'].'_hash',$hash);
		return $s;
	}
}
class gs_widget_alex_gal extends gs_widget_iframe_gallery {}


class gs_widget_private extends gs_widget {
	function html() {
		return '';
	}
}


class gs_widget_lMany2Many_checkboxes extends gs_widget {
	function html() {
		$e_data=gs_base_handler::explode_data($this->data);
		if (isset($e_data[$this->fieldname]) && is_array($e_data[$this->fieldname]) && !empty($e_data[$this->fieldname])) {
			$this->value=array_combine(array_keys($e_data[$this->fieldname]),array_keys($e_data[$this->fieldname]));
		}
		$ret="<input type=\"hidden\" name=\"".$this->fieldname."\" value=\"0\">";
		$ret.="<span>";
		if (isset($this->params['variants'])) foreach ($this->params['variants'] as $k=>$v) {
			$ret.=sprintf("<label class=\"lMany2Many_checkbox\"><input type=\"checkbox\" name=\"%s[]\" value=\"%d\" %s>%s</label>\n",$this->fieldname,$k, (is_array($this->value) && (in_array($k,$this->value) || array_key_exists($k,$this->value))) ? 'checked="checked"' : '',$v);
		}
		$ret.="</span>";
		return $ret;
	}
	function clean() {
		if (!$this->validate()) throw new gs_widget_validate_exception($this->fieldname);
		$ret=is_array($this->value) && count($this->value)>0 ? array_combine(array_values($this->value),array_values($this->value)) : array();
		return $ret;
	}
}
class gs_widget_lMany2Many_checkboxes_all extends gs_widget_lMany2Many_checkboxes {
	function html() {
		$ret=parent::html();
		$ret.="<br><label><input type=\"checkbox\" onChange=\"$('.lMany2Many_checkbox :checkbox').attr('checked',this.checked);\">all</label>\n";
		return $ret;
	}

}


?>
