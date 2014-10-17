<?php
interface g_forms_interface {
	function __construct($rs);
	function as_table();
	function as_list();
	function as_labels();
	function clean();
	function get_data($name=NULL);
}

abstract class g_forms implements g_forms_interface{
	public $error_template='<span class="label label-warning">%s</span>';
	public $clean_data=array();
	public $validate_errors=array();
    public $params=array();
	public $rec;
	public $css_error_class="";

	function __toString() {
		return "";
	}

	function __construct($h=array(),$params=array(),$data=array()) {
		$this->rec=new gs_null(GS_NULL_XML);

		if (!is_array($data)) $data=array();
		$this->params=$params;
		$form_default=array();
		foreach ($h as $k=>$ih) {
			if(isset($ih['hidden']) && $ih['hidden']) {
				unset($h[$k]);
			}
			if(isset($ih['default'])) $form_default[$k]=$ih['default'];
		}
		if (count($form_default)>0) $data=array_merge($form_default,$data);
		$this->data=$data;
		$this->htmlforms=$h;
		$this->view = new gs_glyph('helper',array('class'=>'dl'));
		$this->addNode(array_keys($h));
	}
	function addNode($name) {
		$this->view->addNode('helper',array('class'=>'dt'),$name);
	}

	function replace_validator($field,$value,$params='') {
        if (!isset($this->htmlforms[$field])) return $this;
		if (isset($this->htmlforms[$field]['validate'])) unset($this->htmlforms[$field]['validate']);
		return $this->add_validator($field,$value,$params);
	}
		
	function add_validator($field,$value,$params='') {
        if (!isset($this->htmlforms[$field])) return $this;

		$params=string_to_params($params);
		if (isset($this->htmlforms[$field]['validate']) && !is_array($this->htmlforms[$field]['validate'])) {
			$this->htmlforms[$field]['validate']=array($this->htmlforms[$field]['validate']);
		}
		$this->htmlforms[$field]['validate'][]=$value;
		if (!isset($this->htmlforms[$field]['validate_params']) || !is_array($this->htmlforms[$field]['validate_params'])) $this->htmlforms[$field]['validate_params']=array();
		$this->htmlforms[$field]['validate_params']=array_merge($this->htmlforms[$field]['validate_params'],$params);

		return $this;
	}
	function add_field($name,$params,$options=null) {
		if (!is_array($params)) $params=string_to_params($params);
		if (isset($this->field_options[$name])) $params=array_merge_recursive($this->field_options[$name],$params);
        if ($options!==null) $params['options']= is_array($options) ? $options : string_to_params($options);

		$this->htmlforms[$name]=$params;
		$this->addNode(array($name));
		return $this;
	}
	function get_field($name) {
		return isset($this->htmlforms[$name]) ? $this->htmlforms[$name] : NULL;
	}
	function get_fields() {
		return $this->htmlforms;
	}
	function get_values() {
		return $this->data;
	}
	function remove_field($name) {
		unset($this->htmlforms[$name]);
	}
	function set_values($arr) {
		foreach ($arr as $name=>$value) $this->set_value($name,$value);
	}
	function set_value($name,$value) {
		if (isset($this->htmlforms[$name]) && !is_a($value,'gs_null')) $this->data[$name]=$value;
		if (strpos($name,'_repeat')) {
			$name2=str_replace('_repeat','',$name);
			if (isset($this->htmlforms[$name2]) && $this->htmlforms[$name2]['widget']=='password2' && !is_a($value,'gs_null')) $this->data[$name]=$value;
		}
	}
	function set_default_value($name,$value) {
		if ($this->get_value($name)===NULL) return $this->set_value($name,$value);
	}
	function force_set_value($name,$value) {
		$this->data[$name]=$value;
	}
	function set_variants($name,$variants) {
		$this->set_option($name,'variants',$variants);
	}
	function get_option($field,$option) {
		return (isset($this->htmlforms[$field][$option])) ? $this->htmlforms[$field][$option] : NULL;
	}

	function set_option($field,$option,$value) {
		if (isset($this->htmlforms[$field])) {
			$this->htmlforms[$field][$option]=$value;
		}
	}
	function remove_option($field,$option) {
		if (isset($this->htmlforms[$field])) {
			unset ($this->htmlforms[$field][$option]);
		}
	}
	function set_option_allfields($option,$value) {
		foreach ($this->htmlforms as $field=>$h) {
			$this->set_option($field,$option,$value);
		}
	}
	function set_error_template($str) {
		$this->error_template=$str;
	}
	function set_error_message_field($field,$type,$message) {
		$this->error_field_messages[$field][$type]=$message;
	}
	function set_error_message($type,$message) {
		$this->error_messages[$type]=$message;
	}
	function get_value($name=null,$default=null) {
		$ret=$this->get_data($name);
		if ($ret===NULL && $default!==NULL) return $default;
		return $ret;
	}
	function get_data($name=null) {
		if ($name===NULL) return $this->data;
		return isset($this->data[$name]) ? $this->data[$name] : NULL;
	}
	function show_arr($validate=array(),$view=NULL) {
		if (!$validate) $validate=$this->validate_errors;
		$arr=array();
		$this->_prepare_inputs();
		if($view===NULL) $view=$this->view;
		$hclass='helper_'.$view->class;
		$helper=new $hclass();
		$str='';
		foreach ($view->children() as $k=>$e) {
			if($e->getName()=='helper') { 
				$value=array('label'=>(string)$e->label,'input'=>$this->show($validate,$e));
				$arr[]=$helper->show($value['label'],$value['input']);
			} else {
				$name=(string)$e->name;
				if (!isset($this->htmlforms[$name])) continue;
				$field=$this->htmlforms[$name];
				$value=isset($this->_inputs[$name]) ? $this->_inputs[$name] : null;
				if (isset($field['type']) && $field['type']=='private') continue;

				if ((isset($field['type']) && $field['type']=='hidden') || (isset($field['widget']) && $field['widget']=='hidden')) {
					$arr[]=$value['input'];
				} else {
					$arr[]=$helper->show($value['label'],$value['input'],isset($validate['FIELDS'][$name]) ? $validate['FIELDS'][$name] : NULL,$field);
				}
			}
		}
		return $arr;
	}
	function show ($validate=array(),$view=NULL)  {
		$delimiter="\n";
		return implode($delimiter,$this->show_arr($validate,$view));
	}
	function clean($name=null) {
		return $name ? $this->clean_data[$name] : $this->clean_data;
	}
	protected function error(&$ret, $k,$m,$err_array=null) {
		if(is_array($err_array)) {
			$ret=array_merge_recursive($ret,$err_array);
			return;
		}
		$ret['STATUS']=false;
		$ret['ERRORS'][]=array('FIELD'=>$k,'ERROR'=>$m);
		$ret['FIELDS'][$k][]=$m;
	}

	function validate() {
		$this->clean_data=array();
		$ret=array(
			'STATUS'=>true,
			'ERRORS'=>array(),
			'FIELDS'=>array(),
			);
		$readonly=isset($this->params['readonly']) ? explode(',',$this->params['readonly']) : array();
		foreach ($this->htmlforms as $field=>$h) {
			if (in_array($field,$readonly)) continue;
			$k=$field;
			$wclass=(isset($h['widget']) ? $h['widget'] : $h['type']);
			if(empty($wclass)) continue;
			$wclass='gs_widget_'.$wclass;
			$h['gs_form_params']=$this->params;
			$w =new $wclass($k,$this->data,$h,$this);
			$value=null;
			try {
				$value=$w->clean();
				if (is_array($value) && $value && !is_numeric(key($value))) {
					foreach ($value as $vk=>$vv) {
						$this->clean_data[$vk]=$this->postfilter($k,$vv);
					}
				} else {
					$this->clean_data[$k]=$this->postfilter($k,$value);
				}
			} catch (gs_widget_validate_exception $e) {
				$this->error($ret, $k,$e->getMessage(),$w->validate_errors);
			} catch (gs_widget_skipdata_exception $e) {
			}

			if (!isset($h['validate'])) $h['validate']='notEmpty';
			$validate=is_array($h['validate']) ? $h['validate'] : array($h['validate']);
			//$h['validate_params']['rec_id']=$this->params['rec_id'];
			$h['validate_params']['rec_id']= ($this->rec) ? $this->rec->get_id() : null; 
			foreach ($validate as $v) {
				$vname='gs_validate_'.$v;
				$val=new $vname();
				if (!$val->validate($k,$value,$this->data,isset($h['validate_params'])?$h['validate_params'] : array())) {
					$this->error($ret, $k,$vname);
				}

			}
		}
		if(is_array($ret['STATUS'])) $ret['STATUS']=!in_array(FALSE,$ret['STATUS']);
		$this->validate_errors=$ret;
		return $ret;

	}
	function as_url() {
		$arr=array();
		foreach($this->htmlforms as $k=>$f) {
			$arr[$k]=$this->data[$k];
		}
		return http_build_query($arr);
	}
	function get_inputs() {
		$this->_prepare_inputs();
		$inputs=$this->_inputs;
		if (isset($this->validate_errors['FIELDS'])) foreach ($this->validate_errors['FIELDS'] as $f=>$e) {
			$inputs[$f]['errors']=$e;
		}
		return($inputs);
	}
	function get_row($field) {
		$ret=sprintf('<label>%s %s</label>',$this->get_label($field), $this->get_input($field));
		if ($this->get_error($field)) $ret.=sprintf('<span class="error">%s</span>',implode($this->get_error($field)));
		return $ret;
	}
	function get_tr($field) {
		$e='';
		if ($this->get_error($field)) $e=sprintf('<span class="error">%s</span>',implode($this->get_error($field)));
		$ret=sprintf('<tr><td>%s</td><td>%s%s</td></tr>',$this->get_label($field), $this->get_input($field),$e);
		return $ret;
	}
	
	function get_input($name) {
		return (isset($this->_inputs[$name])) ? $this->_inputs[$name]['input'] : null;
	}
	function get_label($name) {
		return (isset($this->_inputs[$name])) ? $this->_inputs[$name]['label'] : null;
	}
	function get_error($field) {
		return isset($this->validate_errors['FIELDS'][$field]) ? $this->validate_errors['FIELDS'][$field] :array();
	}
	function trigger_error($field,$error) {
		$this->error($this->validate_errors,$field,$error);
	}

	function get_error_template($field, $only_first_error=TRUE) {
		return $this->print_error($field, $only_first_error);
	}
	function print_error($field, $only_first_error=TRUE,$error_template=NULL) {
		$e=$this->get_error($field);
		$ret='';
		if ($error_template===NULL) $error_template=$this->error_template;
		if ($e && $error_template) {

			if ($only_first_error) $e=array(reset($e));

			foreach($e as $t) {
				$ret.=' ';
				if (isset($this->error_field_messages[$field][$t])) { $ret.=$this->error_field_messages[$field][$t]; continue;}
				if (isset($this->error_messages[$t])) { $ret.=$this->error_messages[$t]; continue;}
				$ret.=$t;
			}

			return sprintf($error_template,$ret);
		}
		return implode($e);
	}
	function add_helper_clone($fieldname) {
		$posts=$this->view->find("name",$fieldname);
		if($posts) {
			$ids=array();
			foreach (array_keys($this->data) as $data_field_name) {
				if (strpos($data_field_name,$fieldname)===0) {
					preg_match("/$fieldname:(-?\d+):/",$data_field_name,$id);
					$ids[$id[1]]=$id[1];
				}
			}

			$helper=new gs_glyph('helper',array('class'=>'clone'));
			$posts[0]->replaceNode($helper);
			$helper=$helper->addNode('helper',array('class'=>'dl','label'=>$fieldname))->addNode('helper',array('class'=>'dt'));
			$this->view->removeNode($posts);
			foreach($posts as $p) {
				$helper->addChild($p);
				$this->htmlforms[$p->name]['clonable']=TRUE;
			}
		
			$first_id=reset($ids);
			foreach ($ids as $id) {
				foreach($posts as $p) {
					$newname=str_replace("$fieldname:$first_id","$fieldname:$id",$p->name);
					if ($p->name!=$newname) {
						$this->htmlforms[$newname]=$this->htmlforms[$p->name];
					}
				}
			}

		}
	}



	function interact($interact,$old_ret=array()) {
		$actions=$this->interact[$interact];
		$actions=preg_replace(array_keys(form_interact::$interact_regexps),form_interact::$interact_regexps,$actions);
		$interact=new form_interact($this,$interact,$actions);
		$ret=$interact->i($old_ret);
		foreach ($ret as $r) {
			$i_ret=json_decode($this->interact($r['field'],$ret));
			$ret=array_merge($ret,$i_ret);
		}
		return json_encode($ret);
	}

	function postfilter($field,$value) {
		$hf=$this->htmlforms[$field];
		if (!isset($hf['postfilter'])) return $value;
		$filters=array_unique(array_filter(array_map('trim',explode(',',$hf['postfilter']))));
		foreach ($filters as $fname) {
			$fname='g_forms_postfilter_'.$fname;
			$f=new $fname();
			$value=$f->filter($value);
		}
		return $value;
	}
	function css_error_class($c) {
		$this->css_error_class=trim($c);
	}
}

class g_forms_html extends g_forms {
	function _prepare_inputs(){
		$arr=array();
		foreach($this->htmlforms as $field => $v) {
			$wclass=isset($v['widget']) ? $v['widget'] : $v['type'];
			if (!$wclass) continue;
			$wclass="gs_widget_$wclass";
			$v['gs_form_params']=$this->params;
			$v['interact']=isset($this->interact[$field]);
			$w =new $wclass($field,$this->data,$v,$this);
			if(isset($v['type']) && $v['type']=='label') {
				$arr[$field]=array('input'=>$v['verbose_name']);
				continue;
			}
			$arr[$field]=array('label'=>isset($v['verbose_name']) ? $v['verbose_name']:$field,
						'input'=>$w->html()
						);
		}
		$this->_inputs=$arr;
		//return $arr;
	}
	function as_dl($delimiter="\n",$validate=array(),$inputs=null,$outstr='<dl class="row"><dt><label for="%s">%s%s</label></dt> <dd><div>%s</div>%s</dd> </dl>'){
		$arr=array();
		if($inputs===null) {
			$this->_prepare_inputs();
			$inputs=$this->_inputs();
		}
		foreach($inputs as $field=>$v)  {
			$e="";
			if (isset($validate['FIELDS'][$field])) {
				$e='<div class="error">Error: '.implode(',',$validate['FIELDS'][$field]).'</div>';
			}
			if ($this->htmlforms[$field]['type']=='private') {
			} else if ($this->htmlforms[$field]['type']=='hidden' || $this->htmlforms[$field]['widget']=='hidden') {
				$arr[]=$v['input'];
			} else {
				if(is_array($v['input'])) {
					if ($this->htmlforms[$field]['widget_params']=='inline') {
						$arr[]=$this->as_dl($delimiter,$validate,$v['input'],$outstr);
					} else {
						$v['input']=$this->as_dl($delimiter,$validate,$v['input'],$outstr);
						$arr[]=sprintf($outstr,$field,$v['label'],$v['label']?':':'',$v['input'],$e);
					}
				} else {
					$arr[]=sprintf($outstr,$field,$v['label'],$v['label']?':':'',$v['input'],$e);
				}
			}
		}
		return implode($delimiter,$arr);
	}
	function as_table($delimiter="\n"){
		$arr=array();
		$this->_prepare_inputs();
		foreach($this->_inputs as $field=>$v) 
			$arr[]=sprintf('<tr><td><label for="%s">%s</label></td><td>%s</td></tr>',$field, $v['label'],$v['input']);

		return implode($delimiter,$arr);
	}
	function as_list(){}
	function as_labels($delimiter="<br/>\n",$suffix=':',$validate=array()){
		$arr=array();
		$this->_prepare_inputs();
		foreach($this->_inputs as $field=>$v) {
			$e="";
			if (isset($validate['FIELDS'][$field])) {
				$e='<div class="error">Поле '.$v['label'].' не может быть пустым.</div>';
			}
			$arr[]=sprintf('<label>%s%s %s<br>%s</label>', $v['label'],trim($v['label']) ? $suffix : null ,$v['input'],$e);
		}

		return implode($delimiter,$arr);
	}
	
	function as_inline($delimiter=" \n",$validate=array()){
		$arr=array();
		$this->_prepare_inputs();
		foreach($this->_inputs as $field=>$v) 
			$arr[]=sprintf('<div class="inline"><div>%s</div>%s</div>',$v['label'],$v['input']);

		return implode($delimiter,$arr);
	}


}
class g_forms_jstpl extends g_forms_html {
	function _prepare_inputs(){
		$arr=array();
		foreach($this->htmlforms as $field => $v) {
			$wclass="gs_widget_".(isset($v['widget']) ? $v['widget'] : $v['type']);
			$w =new $wclass($field,array($field=>"<%=t.values.$field%>"),$v);
			$arr[$field]=array('label'=>isset($v['verbose_name']) ? $v['verbose_name']:$field,
						/*'input'=>$w->html($field,"<%=t.values.$field%>",$v)*/
						'input'=>$w->js()
						);
		}
		$this->_inputs=$arr;
		//return $arr;
	}
}

class g_forms_table extends  g_forms_html {
    function __construct($h,$data=array(),$rec=null)  {
         parent::__construct($h,$data,$rec);
         $this->view = new gs_glyph('helper',array('class'=>'table'));
	 $this->addNode(array_keys($h));
    }
    function addNode($name) {
	    $this->view->addNode('helper',array('class'=>'tr'),$name);
    }
}
class g_forms_inline extends  g_forms_html {
    function __construct($h,$data=array(),$rec=null)  {
         parent::__construct($h,$data,$rec);
         $this->view = new gs_glyph('helper',array('class'=>'empty'));
	 $this->addNode(array_keys($h));
    }
    function addNode($name) {
	    $this->view->addNode('helper',array('class'=>'inline'),$name);
    }
}
class g_forms_empty extends  g_forms_html {
    function __construct($h,$data=array(),$rec=null)  {
         parent::__construct($h,$data,$rec);
         $this->view = new gs_glyph('helper',array('class'=>'empty'));
         $this->addNode(array_keys($h));
    }
    function addNode($name) {
	    $this->view->addNode('helper',array('class'=>'empty'),$name);
    }
}
class g_forms_table_submit extends  g_forms_html {
    function __construct($h,$data=array(),$rec=null)  {
         parent::__construct($h,$data,$rec);
         $this->view = new gs_glyph('helper',array('class'=>'table_submit'));
         $this->addNode(array_keys($h));
    }
    function addNode($name) {
	    $this->view->addNode('helper',array('class'=>'tr'),$name);
    }
}
class g_forms_divbox extends  g_forms_html {
    function __construct($h,$data=array(),$rec=null)  {
         parent::__construct($h,$data,$rec);
         $this->view = new gs_glyph('helper',array('class'=>'empty'));
         $this->addNode(array_keys($h));
    }
    function addNode($name) {
	    $this->view->addNode('helper',array('class'=>'divbox'),$name);
    }
}
class g_forms_label extends  g_forms_html {
    function __construct($h,$data=array(),$rec=null)  {
         parent::__construct($h,$data,$rec);
         $this->view = new gs_glyph('helper',array('class'=>'empty'));
         $this->addNode(array_keys($h));
    }
    function addNode($name) {
	    $this->view->addNode('helper',array('class'=>'label_br'),$name);
    }
}


class gs_glyph {
	private $tagName;
	private $parent=NULL;
	private $children=array();
	private $attributes=array();
	function __construct($name='',$attributes=array()) {
		$this->tagName=$name;
		foreach ($attributes as $k=>$v) {
			$this->addAttribute($k,$v);
		}
	}
	function setParent($obj) {
		$this->parent=$obj;
	}
	function addNode($name,$attributes=array(),$childs=array()) {
		$node=$this->addChild($name);
		foreach ($attributes as $k=>$v) {
			$node->addAttribute($k,$v);
		}
		foreach ($childs as $k=>$v) {
			$node->addNode('field',array('name'=>$v));
		}
		return $node;
	}
	function addAttribute($k,$v) {
		$this->attributes[$k]=$v;
	}
	function addChild($name) {
		$c= is_object($name) && is_a($name,'gs_glyph') ? $name :  new gs_glyph($name);
		$c->setParent($this);
		$this->children[]=$c;
		return $c;
	}
	function replaceNode($new) {
		$this->parent->replaceChild($this,$new);
	}
	function replaceChild($old,$new) {
		$k=array_search($old,$this->children);
		if ($k) {
			$this->children[$k]=$new;
			$new->setParent($this);
		}
	}
	function removeNode(&$nodes) {
		if (!is_array($nodes)) $nodes=array($nodes);
		foreach ($this->children as $k=>$c) {
			$c->removeNode($nodes);
			if (in_array($c,$nodes)) {
				$c->setParent(NULL);
				unset($this->children[$k]);
			}
		}
		return $nodes;
	}
	function __get($name) {
		return isset($this->attributes[$name]) ? $this->attributes[$name] : NULL;
	}
	function getName() {
		return $this->tagName;
	}
	function children() {
		return $this->children;
	}
	function find($name,$value) {
		$ret=array();
		if (strpos($this->$name,$value)===0) $ret[]=&$this;
		foreach ($this->children as $c) {
			$ret=array_merge($ret,$c->find($name,$value));
		}
		return $ret;
	}
}
abstract class g_forms_postfilter {
	function filter($value) {
		return $value;
	}
}

class g_forms_postfilter_strip_tags extends g_forms_postfilter {
	function filter($value) {
		return strip_tags($value);
	}
}

?>
