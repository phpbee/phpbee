<?php
class field_interface {
	static function init($arr,$init_opts) {
		$structure =array('fields'=>array(),
				'recordsets'=>array(),
				'htmlforms'=>array(),
				'fkeys'=>array(),
				'indexes'=>array(),
				);
		$ret=array();
		$arr=string_to_params($arr);
		foreach ($arr as $k=>$r) {
			if(!isset($r['required'])) $r['required']='true';
			if (!isset($r['readonly'])) $r['readonly']=false;
			if (!isset($r['index']) || $r[0]=='lOne2One') $r['index']=false;
			if(!isset($r['multilang'])) $r['multilang']=false;
			$r['func_name']=$r[0];
			if (in_array($r['func_name'],array('lMany2Many','lMany2One','lOne2One'))) {
				$r['linked_recordset']=$r[1];
				if (!isset($r['hidden'])) $r['hidden']=!(isset($r['verbose_name']) || isset($r[2])) ;
				if (!isset($r['verbose_name'])) $r['verbose_name']=isset($r[2]) ? $r[2] : $k;
				$r['counter'] = isset($r['counter']) && (!$r['counter'] || strtolower($r['counter'])=='false') ? false : true; // by default: on
				//$r['counter'] = (isset($r['counter']) && $r['counter'] && strtolower($r['counter'])!='false') ? true : false; // by default: off

			} else {
				if (!isset($r['hidden'])) $r['hidden']=!(isset($r['verbose_name']) || isset($r[1])) ;
				if (!isset($r['verbose_name'])) $r['verbose_name']=isset($r[1]) ? $r[1] : $k;
			}
			$ret[$k]=$r;
		}
		foreach ($ret as $k => $r) {
			if (!method_exists('field_interface',$r['func_name']))
				throw new gs_exception("field_interface: no method '".$r['func_name']."'");

			self::$r['func_name']($k,$r,$structure,$init_opts);
			if (isset($r['default']) && !isset($structure['fields'][$k]['default'])) $structure['fields'][$k]['default']=$r['default'];
			if (isset($r['default']) && !isset($structure['htmlforms'][$k]['default'])) $structure['htmlforms'][$k]['default']=$r['default'];
			if (isset($r['trigger'])) {
				$structure['triggers']['before_insert'][$k]=$r['trigger'];
				$structure['triggers']['before_update'][$k]=$r['trigger'];
			}
			if ($r['index'] && !isset($structure['indexes'][$k])) $structure['indexes'][$k]=$k;
		}
		return $structure;
	}
	

	static function fString($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'varchar','options'=>isset($opts['max_length']) ? (int)$opts['max_length'] : 255);
		$structure['fields'][$field]['multilang']=$opts['multilang'];
		$structure['htmlforms'][$field]=array(
			'type'=>'input', 
			'hidden'=>$opts['hidden'],
			'readonly'=>$opts['readonly'],
			'index'=>isset($opts['index']) ? $opts['index'] : 0,
			'keywords'=>isset($opts['keywords']) ? $opts['keywords'] : 0,
			'verbose_name'=>$opts['verbose_name'], 
			'options'=>isset($opts['options']) ? $opts['options'] : NULL,
			);
		if (strtolower($opts['required'])=='false') {
			$structure['htmlforms'][$field]['validate'][]='dummyValid';
		} else {
			$structure['htmlforms'][$field]['validate'][]='isLength';
			$structure['htmlforms'][$field]['validate_params']['min']=isset($opts['min_length']) ? (int)($opts['min_length']) : 1;
			$structure['htmlforms'][$field]['validate_params']['max']=isset($opts['max_length']) ? (int)($opts['max_length']) : $structure['fields'][$field]['options'];
			if (isset($opts['validate_regexp'])) {
				$structure['htmlforms'][$field]['validate'][]='isRegexp';
				$structure['htmlforms'][$field]['validate_params']['validate_regexp']=$opts['validate_regexp'];
			}
		}
		if (isset($opts['unique']) && strtolower($opts['unique'])!='false' && $opts['unique']) {
			if(!is_array($structure['htmlforms'][$field]['validate'])) {
				$structure['htmlforms'][$field]['validate']=array($structure['htmlforms'][$field]['validate']);
			}
			$structure['htmlforms'][$field]['validate'][]='checkUnique';
			$structure['htmlforms'][$field]['validate_params']['class']=$init_opts['recordset'];
			$structure['htmlforms'][$field]['validate_params']['field']=$field;
		}
		if (isset($opts['default'])) {
			$structure['htmlforms'][$field]['default']=$opts['default'];
		}
		if (isset($opts['validate'])) {
			foreach (explode(',',$opts['validate']) as $v) {
				$structure['htmlforms'][$field]['validate'][]=$v;
			}
		}
		if (isset($opts['widget'])) $structure['htmlforms'][$field]['widget']=$opts['widget'];
		if (isset($opts['cssclass'])) $structure['htmlforms'][$field]['cssclass']=$opts['cssclass'];
		if (isset($opts['helper_text'])) $structure['htmlforms'][$field]['helper_text']=$opts['helper_text'];
	}
	static function fPassword($field,$opts,&$structure,$init_opts) {
		self::fString($field,$opts,$structure,$init_opts);
		$structure['password_fields'][]=$field;
		$structure['triggers']['before_update'][]='trigger_fPassword_encode';
		$structure['triggers']['after_insert'][]='trigger_fPassword_encode';
	}
	static function fCheckbox($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'int','default'=>isset($opts['default']) ? (int)$opts['default'] : 0);
		$structure['fields'][$field]['multilang']=$opts['multilang'];
		$structure['htmlforms'][$field]=array(
			'type'=>'checkbox',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'index'=>isset($opts['index']) ? $opts['index'] : 0,
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'isNumber'
		);
		if (isset($opts['default'])) $structure['htmlforms'][$field]['default']=(bool)$opts['default'];
		if (isset($opts['widget'])) $structure['htmlforms'][$field]['widget']=$opts['widget'];
		if (isset($opts['cssclass'])) $structure['htmlforms'][$field]['cssclass']=$opts['cssclass'];
		if (isset($opts['helper_text'])) $structure['htmlforms'][$field]['helper_text']=$opts['helper_text'];
	}
	static function serial($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'serial');
	}
	static function fInt($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'int');
		$structure['fields'][$field]['multilang']=$opts['multilang'];
		$structure['htmlforms'][$field]=array(
			'type'=>'number',
			'hidden'=>$opts['hidden'],
			'index'=>isset($opts['index']) ? $opts['index'] : 0,
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'isNumber'
		);
		if (isset($opts['widget'])) $structure['htmlforms'][$field]['widget']=$opts['widget'];
		if (isset($opts['cssclass'])) $structure['htmlforms'][$field]['cssclass']=$opts['cssclass'];
		if (isset($opts['helper_text'])) $structure['htmlforms'][$field]['helper_text']=$opts['helper_text'];
	}
	
	static function fFloat($field,$opts,&$structure,$init_opts) {
		self::fInt($field,$opts,$structure,$init_opts);
		$structure['fields'][$field]=array('type'=>'float');
		if (isset($opts['widget'])) $structure['htmlforms'][$field]['widget']=$opts['widget'];
		if (isset($opts['cssclass'])) $structure['htmlforms'][$field]['cssclass']=$opts['cssclass'];
		if (isset($opts['helper_text'])) $structure['htmlforms'][$field]['helper_text']=$opts['helper_text'];
	}
	
	static function fEmail($field,$opts,&$structure,$init_opts) {
		self::fString($field,$opts,$structure,$init_opts);
		$structure['fields'][$field]=array('type'=>'varchar','options'=>isset($opts['max_length']) ? $opts['max_length'] : 255);
		$structure['htmlforms'][$field]['type']='email';
		if (isset($opts['cssclass'])) $structure['htmlforms'][$field]['cssclass']=$opts['cssclass'];
		if (isset($opts['helper_text'])) $structure['htmlforms'][$field]['helper_text']=$opts['helper_text'];
	}
	static function fDateTime($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'date');
		$structure['fields'][$field]['multilang']=$opts['multilang'];
		$structure['htmlforms'][$field]=array(
			'type'=>'datetime',
			'hidden'=>$opts['hidden'],
			'index'=>isset($opts['index']) ? $opts['index'] : 0,
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'isDate'
		);
		if (isset($opts['widget'])) $structure['htmlforms'][$field]['widget']=$opts['widget'];
		if (isset($opts['cssclass'])) $structure['htmlforms'][$field]['cssclass']=$opts['cssclass'];
		if (isset($opts['helper_text'])) $structure['htmlforms'][$field]['helper_text']=$opts['helper_text'];
	}
    static function fTimestamp ($field,$opts,&$structure,$init_opts) {
        self::fDateTime($field,$opts,$structure,$init_opts);
        $structure['fields'][$field]['type']='timestamp';
        $structure['fields'][$field]['default']=0;
    }
	static function fText($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'text');
		$structure['fields'][$field]['multilang']=$opts['multilang'];
		$structure['htmlforms'][$field]=array(
			'type'=>'text',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'index'=>isset($opts['index']) ? $opts['index'] : 0,
			'keywords'=>isset($opts['keywords']) ? $opts['keywords'] : 0,
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty'
		);
		if (isset($opts['widget'])) $structure['htmlforms'][$field]['widget']=$opts['widget'];
		if (isset($opts['cssclass'])) $structure['htmlforms'][$field]['cssclass']=$opts['cssclass'];
		if (isset($opts['helper_text'])) $structure['htmlforms'][$field]['helper_text']=$opts['helper_text'];
		if (isset($opts['images_key'])) $structure['htmlforms'][$field]['images_key']=$opts['images_key'];
	}
    static function fObject($field,$opts,&$structure,$init_opts) {
        self::fText($field,$opts,$structure,$init_opts);
        $structure['htmlforms'][$field]['type']='object';
    }


	
	static function fFile($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field.'_filename']=array('type'=>'varchar','options'=>255);
		$structure['fields'][$field.'_data']=array('type'=>'longblob');
		$structure['fields'][$field.'_mimetype']=array('type'=>'varchar','options'=>16);
		$structure['fields'][$field.'_size']=array('type'=>'bigint');
		$structure['fields'][$field.'_width']=array('type'=>'int');
		$structure['fields'][$field.'_height']=array('type'=>'int');
		$structure['htmlforms'][$field]=array(
			'type'=>'file',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty'
		);
		if (isset($opts['widget'])) $structure['htmlforms'][$field]['widget']=$opts['widget'];
		if (isset($opts['cssclass'])) $structure['htmlforms'][$field]['cssclass']=$opts['cssclass'];
		if (isset($opts['helper_text'])) $structure['htmlforms'][$field]['helper_text']=$opts['helper_text'];
	}
	
	static function fCoords($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field.'_x']=array('type'=>'float');
		$structure['fields'][$field.'_y']=array('type'=>'float');
		$structure['htmlforms'][$field]=array(
			'type'=>'coords',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty'
		);
		/*$structure['htmlforms'][$field.'_x']=array(
			'type'=>'input',
			'hidden'=>false,
		);
		$structure['htmlforms'][$field.'_y']=array(
			'type'=>'input',
			'hidden'=>false,
		);*/
		
			
		if (isset($opts['widget'])) $structure['htmlforms'][$field]['widget']=$opts['widget'];
		if (isset($opts['cssclass'])) $structure['htmlforms'][$field]['cssclass']=$opts['cssclass'];
		if (isset($opts['helper_text'])) $structure['htmlforms'][$field]['helper_text']=$opts['helper_text'];
	}
	
	static function fSelect($field,$opts,&$structure,$init_opts) {
		$options=isset($opts['values']) ? explode(',',$opts['values']) : (isset($opts['options']) ? explode(',',$opts['options']) : array());
		$structure['fields'][$field]=array('type'=>'varchar','options'=>isset($opts['max_length']) ? (int)$opts['max_length'] : 255);
		$structure['htmlforms'][$field]=array(
			'type'=>'Select',
			'hidden'=>$opts['hidden'],
			'index'=>isset($opts['index']) ? $opts['index'] : 0,
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty',
			'options'=>$options ? array_combine($options,$options) : array(),
		);
		$structure['indexes'][$field]=$field;
		if (isset($opts['widget'])) $structure['htmlforms'][$field]['widget']=$opts['widget'];
		if (isset($opts['cssclass'])) $structure['htmlforms'][$field]['cssclass']=$opts['cssclass'];
		if (isset($opts['helper_text'])) $structure['htmlforms'][$field]['helper_text']=$opts['helper_text'];
	}
	static function fSet($field,$opts,&$structure,$init_opts) {
		self::fSelect($field,$opts,$structure,$init_opts);
		$structure['fields'][$field]['type']='set';
		$structure['fields'][$field]['options']=$structure['htmlforms'][$field]['options'];
		$structure['htmlforms'][$field]['type']='Set';
	}
	static function f___dummy($field,$opts,&$structure,$init_opts) {
		$structure['fields'][$field]=array('type'=>'varchar','options'=>255);
		$structure['htmlforms'][$field]=array(
			'type'=>'input',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty'
		);
		if (isset($opts['widget'])) $structure['htmlforms'][$field]['widget']=$opts['widget'];
		if (isset($opts['cssclass'])) $structure['htmlforms'][$field]['cssclass']=$opts['cssclass'];
		if (isset($opts['helper_text'])) $structure['htmlforms'][$field]['helper_text']=$opts['helper_text'];
	}
	static function lOne2One($field,$opts,&$structure,$init_opts) {
		$fname=isset($opts['local_field_name']) ? $opts['local_field_name'] : $field.'_id';
		if (!isset($structure['fields'][$fname])) $structure['fields'][$fname]=array('type'=>'int');
		if (isset($opts['mode']) && $opts['mode']=='link') {
			$structure['fields'][$fname.'_hash']=array('type'=>'varchar','options'=>16);
			$structure['htmlforms'][$fname.'_hash']=array(
			'type'=>'hidden',
			'widget'=>'private',
			'validate'=>'dummyValid'
		);
		}
		$structure['htmlforms'][$fname]=array(
			'type'=>'lOne2One',
			'linkname'=>$field,
			'hidden'=>$opts['hidden'] && strtolower($opts['hidden'])!='false',
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty',
			'nulloption'=>(isset($opts['nulloption']) && $opts['nulloption'] && strtolower($opts['nulloption'])!='false') ? explode(':',$opts['nulloption']) : false ,
		);
		$structure['indexes'][$fname]=$fname;
		$structure['recordsets'][$field]=array(
			'recordset'=>$opts['linked_recordset'],
			'local_field_name'=>$fname,
			'foreign_field_name'=>isset($opts['foreign_field_name']) ? $opts['foreign_field_name'] : 'id',
			'update_recordset'=>$opts['linked_recordset'],
			'mode'=>isset($opts['mode']) ? $opts['mode'] : null,
			);
		$structure['htmlforms'][$fname]['options']=$structure['recordsets'][$field];
		$structure['fkeys'][]=array('link'=>$field,'on_delete'=>'RESTRICT','on_update'=>'CASCADE');
		if (isset($opts['widget'])) $structure['htmlforms'][$fname]['widget']=$opts['widget'];
		if (isset($opts['cssclass'])) $structure['htmlforms'][$field]['cssclass']=$opts['cssclass'];
		if (isset($opts['helper_text'])) $structure['htmlforms'][$field]['helper_text']=$opts['helper_text'];
	}
	
	static function lMany2One($field,$opts,&$structure,$init_opts) {
		list($rname,$linkname)=explode(':',$opts['linked_recordset']);
				// если в init_opts такой же рекордсет что и в $opts['linked_recordset'] - то не надо создавать новый объект, иначе скрипт уходит в рекурсию
		if(!class_exists($rname)) return;		
		if(!$linkname) return;
		if ($init_opts['recordset']!=$rname) {
			$obj=new $rname();
			$obj_rs=$obj->structure['recordsets'][$linkname];
		} else {
			$obj_rs=$structure['recordsets'][$linkname];
		}
		//if(isset($init_opts['skip_many2many'])) return;
		//$obj=new $rname(array('skip_many2many'=>true));
		$structure['recordsets'][$field]=array(
			'recordset'=>$rname,
			'local_field_name'=>isset($opts['local_field_name']) ? $opts['local_field_name'] : 'id',
			'foreign_field_name'=>$obj_rs['local_field_name'],
			'type'=>'many',
			'mode'=>isset($obj_rs['mode']) ? $obj_rs['mode'] : null,
			);
		if (isset($opts['index_field_name'])) $structure['recordsets'][$field]['index_field_name']=$opts['index_field_name'];
		$structure['htmlforms'][$field.'_hash']=array(
			'type'=>'hidden',
			'widget'=>'private',
			'validate'=>'dummyValid'
		);
		if($opts['counter']) {
			$counter_fieldname='_'.$field.'_count';
			$structure['recordsets'][$field]['counter_fieldname']=$counter_fieldname;
			$structure['recordsets'][$field]['counter_linkname']=$linkname;
			$structure['fields'][$counter_fieldname]=array('type'=>'int','default'=>0);
			$structure['htmlforms'][$counter_fieldname]=array( 'type'=>'fInt', 'hidden'=>'true',);
		}
		$structure['htmlforms'][$field]=array(
			'type'=>'lMany2One',
			'linkname'=>$field,
			'hidden'=>$opts['hidden'],
			'widget'=>isset($opts['widget']) ? $opts['widget'] : '',
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty',
			'nulloption'=>(isset($opts['nulloption']) && $opts['nulloption'] && strtolower($opts['nulloption'])!='false') ? true : false ,
			'options'=>$structure['recordsets'][$field],
		);
		if (isset($opts['widget'])) $structure['htmlforms'][$field]['widget']=$opts['widget'];
		if (isset($opts['widget_params'])) $structure['htmlforms'][$field]['widget_params']=$opts['widget_params'];
		if (isset($opts['cssclass'])) $structure['htmlforms'][$field]['cssclass']=$opts['cssclass'];
		if (isset($opts['helper_text'])) $structure['htmlforms'][$field]['helper_text']=$opts['helper_text'];
	}
	static function lMany2Many($field,$opts,&$structure,$init_opts) {
		@list($rname,$table_name,$foreign_field_name)=explode(':',$opts['linked_recordset']);
		if(!$table_name) {
			$a=array($init_opts['recordset'],$rname);
			sort($a);
			$table_name='m2m_'.implode('_',$a);
		}
		/*
		new gs_rs_links($init_opts['recordset'],$rname,$table_name);	
		нужно переопределить lazy_load в _short чтобы он для rs_links вызывал хитрый конструктор.

		*/
		$structure['htmlforms'][$field]=array(
			'type'=>'lMany2Many',
			'hidden'=>$opts['hidden'],
			'verbose_name'=>$opts['verbose_name'],
			'validate'=>strtolower($opts['required'])=='false' ? 'dummyValid' : 'notEmpty',
		);


		$structure['recordsets'][$field]=array(
			'recordset'=>$table_name,
			'rs1_name'=>$init_opts['recordset'],
			'rs2_name'=>$rname,
			'rs_link'=>false,
			'local_field_name'=>isset($opts['local_field_name']) ? $opts['local_field_name'] : 'id',
			'foreign_field_name'=>$foreign_field_name ? $foreign_field_name : $init_opts['recordset'].'_id',
			'type'=>'many',
			);
		$structure['recordsets']['_'.$field]=$structure['recordsets'][$field];
		$structure['recordsets']['_'.$field]['rs_link']=true;

		if ($opts['counter']) {
			$counter_fieldname='_'.$field.'_count';
			$structure['fields'][$counter_fieldname]=array('type'=>'int','default'=>0);
			$structure['htmlforms'][$counter_fieldname]=array( 'type'=>'fInt', 'hidden'=>'true',);
			$structure['recordsets'][$field]['counter_fieldname']=$counter_fieldname;
		}
		$structure['htmlforms'][$field]['options']=$structure['recordsets'][$field];
		if (isset($opts['widget'])) $structure['htmlforms'][$field]['widget']=$opts['widget'];
		if (isset($opts['cssclass'])) $structure['htmlforms'][$field]['cssclass']=$opts['cssclass'];
		if (isset($opts['helper_text'])) $structure['htmlforms'][$field]['helper_text']=$opts['helper_text'];


		//$structure['fkeys'][]=array('link'=>$field,'on_delete'=>'CASCADE','on_update'=>'CASCADE');
	}
	function install()  {

	}
}

class gs_rs_links extends gs_recordset{
		public $handler_cache_status=2;
        public $id_field_name='id';
		private $links=array();
        public $structure=array(
                'fields'=>array(
                        'id'=>array('type'=>'serial'),
			),
		);

	function __construct($rs1,$rs2,$table_name,$rs_link=false,$link_name='') { 
		$this->table_name=$table_name;
		$this->link_name=$link_name;
		$this->rs1_name=$rs1;
		$this->rs2_name=$rs2;
		$this->rs_link=$rs_link;
		reset(cfg('gs_connectors'));
		$this->gs_connector_id=key(cfg('gs_connectors'));

		$f1=$rs1.'_id';
		$f2=$rs1!=$rs2 ? $rs2.'_id': 'id2';
		if ($f1=='tw_tours_id') $f1='tw_trip_id';

		$this->structure['fields'][$f1]=array('type'=>'int');
		$this->structure['fields'][$f2]=array('type'=>'int');

		$this->structure['indexes'][$f1]=$f1;
		$this->structure['indexes'][$f2]=$f2;

		$this->structure['recordsets']['parents']=array('recordset'=>$rs1,'local_field_name'=>$f1,'foreign_field_name'=>'id','update_recordset'=>$rs2,'update_link'=>$link_name);
		$this->structure['recordsets']['childs']=array('recordset'=>$rs2,'local_field_name'=>$f2,'foreign_field_name'=>'id','update_recordset'=>$rs1,'update_link'=>$link_name);

		$this->structure['fkeys'][]=array('link'=>'parents','on_delete'=>'CASCADE','on_update'=>'CASCADE');
		$this->structure['fkeys'][]=array('link'=>'childs','on_delete'=>'CASCADE','on_update'=>'CASCADE');

		return parent::__construct($this->gs_connector_id,$table_name);

	}
	public function find($opts,$linkname=null) {
		return $this->first()->get_recordset()->find($opts,$linkname);
	}
	public function find_records($options=null,$fields=null,$index_field_name=null) {
		parent::find_records($options,$fields,$index_field_name);
		parent::load_records();
		if ($this->rs_link) {
			$this->links=$this->array;
			return $this;
		}
		if (isset($this->parent_record)) {
			$idname=$this->structure['recordsets']['childs']['local_field_name'];
			$ids=array();
			foreach ($this as $t) $ids[]=$t->$idname;
			$rsname=$this->structure['recordsets']['childs']['recordset'];
			$rs=new $rsname();
			$rs=$rs->find_records(array('id'=>$ids))->load_records();
			$rs->parent_recordset=$this;
			$links=array();
			foreach ($this->array as $l) {
				$links[$l->$idname]=$l;
			}
			$this->links=$links;
			$this->array=$rs->array;
		}
		return $this;
	}
	function implode($d=':') {
		return implode($d,$this->array_keys());
	}
	function array_keys() {
		return array_keys($this->array);
	}

	private function get_parent_linkname() {
		return $this->structure['recordsets']['parents']['update_link'];
	}
	private function get_child_fieldname() {
		return $this->structure['recordsets']['childs']['local_field_name'];
	}

	private function modified_links_add($l) {
			$this->modified_links_action('new_links',$l);
	}
	private function modified_links_remove($l) {
			$this->modified_links_action('removed_links',$l);
	}
	 private function modified_links_action($type,$l) {
			if ($this->parent_record) $this->parent_record->set_modified_links($this->get_parent_linkname(),$type,$l,$this->get_child_fieldname()) ;
	}

	public function new_record($data=null,$id=NULL) {
		if ($data) {
			$arr=array(
				$this->structure['recordsets']['parents']['local_field_name']=>$this->parent_record->get_id(),
				$this->structure['recordsets']['childs']['local_field_name']=>$data
				);
			$nr=parent::new_record($arr);
		} else {
			$nr=parent::new_record($data);
		}
		$this->links[]=$nr;
		$this->modified_links_add($nr);
		return $nr;
	}
	public function unlink($id) {
		$fname=$this->structure['recordsets']['childs']['local_field_name'];
		if (is_object($id) && is_a($id,'gs_record')) $id=$id->get_id();
		if (!is_numeric($id)) return false;
		if (isset($this->links)) foreach ($this->links as $k=>$l) {
			if ($l->$fname==$id)  {
				$this->modified_links_remove($l);
				$l->delete();
			}
		}
	}


	public function flush($data) {
		$fname=$this->structure['recordsets']['childs']['local_field_name'];
		if (isset($this->links)) foreach ($this->links as $k=>$l) {
			if (!array_key_exists($l->$fname,$data))  {
				$this->modified_links_remove($l);
				$l->delete();
			}
		}
	}
	public function commit() {
		foreach ($this->structure['recordsets'] as $l=>$st) {
			$prec=new $st['recordset'];
			$update_link=$st['update_link'];
			if (isset($prec->structure['recordsets'][$update_link])) {
				$counter_fieldname=$prec->structure['recordsets'][$update_link]['counter_fieldname'];
			} else {
				foreach ($prec->structure['recordsets'] as $rs) {
					if ($rs['recordset']==$this->table_name) {
						$counter_fieldname=$rs['counter_fieldname'];
						break;
					}
				}
			}
			$ids=array();
			foreach ($this->links as $a) {
				$ids[]=$a->{$st['local_field_name']};
			}
			/* // Otorvalu counters, vse ravno ne rabotaet
			$prec->find_records(array($st['foreign_field_name']=>array_unique($ids)));
			$counter_arr=array();
			if (isset($this->links)) foreach ($this->links as $link) {
				$id=$link->{$st['local_field_name']};
				if ($link->recordstate & RECORD_NEW) $counter_arr[$id]=$counter_arr[$id]+1;
				if ($link->recordstate & RECORD_DELETED) $counter_arr[$id]=$counter_arr[$id]-1;
			}
			foreach ($counter_arr as $id=>$cnt) {
				if (isset($counter_fieldname)) $prec[$id]->$counter_fieldname+=$cnt;
			}
			$prec->commit();
			*/
		}
		$ret=parent::commit();
		if (isset($this->links)) foreach ($this->links as $l) $l->commit();

		return $ret;
	}
    function html_select_options($data=array()) {
        $variants=array();
        foreach ($this->parent_record->{$this->link_name} as $vrec) $variants[$vrec->get_id()]=trim($vrec);
        return $variants;
    }
}
class gs_recordset_short extends gs_recordset {
	public $sortkey=0;
	public $no_urlkey=1;
	public $no_ctime=0;
	function __construct($s=false,$init_opts=false) {
		$this->init_fields=$s;
		$this->init_opts=$init_opts;
		$this->init_opts['recordset']=get_class($this);
		//if (!$s || !is_array($s)) throw new gs_exception('gs_recordset_short :: empty init values on '.get_class($this));
		if (!$this->table_name) $this->table_name=get_class($this);
		if (!$this->id_field_name) $this->id_field_name='id';
		$config=gs_config::get_instance();
		if (!$this->gs_connector_id) $this->gs_connector_id=key(cfg('gs_connectors'));
		$this->structure['fields'][$this->id_field_name]=array('type'=>'serial');
		$this->selfinit($s);
		if(!isset($this->no_ctime) || !$this->no_ctime) {
			$this->structure['fields']['_ctime']=array('type'=>'date');
			$this->structure['fields']['_mtime']=array('type'=>'date');
		}
		if(isset($this->sortkey) && $this->sortkey) {
			$this->structure['fields']['sortkey']=array('type'=>'float','default'=>0);
			$this->structure['indexes']['sortkey']='sortkey';
			$this->structure['triggers']['after_insert'][]='trigger_sortkey';
			$this->structure['triggers']['before_update'][]='trigger_sortkey';
		}
		if(!isset($this->no_urlkey) || !$this->no_urlkey) {
			$this->structure['fields']['urlkey']=array('type'=>'varchar','options'=>128);
			$this->structure['triggers']['before_insert'][]='trigger_urlkey';
			$this->structure['htmlforms']['urlkey']=array(
				'type'=>'input', 
				'verbose_name'=>'Urlkey', 
				'validate'=>'checkUnique',
				'validate_params'=>array(
					'class'=>$this->init_opts['recordset'],
					'field'=>'urlkey',
					'func'=>'check_unique_urlkey',
					),

				);
		}
		parent::__construct($this->gs_connector_id,$this->table_name);


		$classname='i18n_'.get_class($this);
		if (!class_exists($classname,true)) return;


		$ml=0;
		foreach ($this->structure['fields'] as $k=>$h) {
			if(isset($h['multilang']) && $h['multilang']) {
				$ml=1;
				break;
			}
		}

		if (!$ml) return;

		$lng=languages();
		if (count($lng)<2) return;
		$default_lang=key($lng);
		//array_shift($lng); // all_languages_in_form



		$hf=array();
		foreach ($this->structure['htmlforms'] as $k=>$h) {
			$hf[$k]=$h;
			if (isset($this->structure['fields'][$k]) 
				&& isset($this->structure['fields'][$k]['multilang']) 
				&& $this->structure['fields'][$k]['multilang']) {

				foreach($lng as $l=>$lname) {
					$new_h=$h;
					$new_h['verbose_name'].="/$lname";
					$hf['Lang:'.$l.':'.$k]=$new_h;
				}
				//unset($hf[$k]); // all_languages_in_form


			}
		}


		$this->structure['htmlforms']=$hf;

		$ml_opts=array(
			'required' => true,
			'readonly' => false,
			'index' => 0,
			'multilang' => 0,
			'func_name' => 'lMany2One',
			'local_field_name' => $this->id_field_name,
			'linked_recordset' => $classname.':Parent',
			'hidden' => 1,
			'verbose_name' => false,
			'counter' => 0,
			'widget'=>'hidden',

		);
		field_interface::lMany2One('Lang',$ml_opts,$this->structure,$this->init_opts);
		$this->structure['recordsets']['Lang']['index_field_name']='lang';



	}
	public function md() {
		return md($this->get_values(),1);
	}
	function install() {
		$ret=parent::install();
		$this->i18n_install();
		$this->sortkey_install();
		return $ret;
	}

	function sortkey_install() {
		if (!isset($this->structure['fields']['sortkey'])) return;
		$sr=$this->find_records(array('sortkey'=>0));
		foreach ($sr as $rec) {
			if (isset($this->structure['fields']['cnt'])) $rec->sortkey=$rec->cnt;
			else $rec->sortkey=$rec->get_id();
		}
		$sr->commit();

	}
	function i18n_install () {
		$s=$this->init_fields;
		$lng=languages();
		if (count($lng)<2) return;
		$classname='i18n_'.get_class($this);
		$ml_options=array();
		$ml_options['lang']='fString';
		$ml_options['Parent']='lOne2One '.get_class($this);
		foreach ($this->structure['fields'] as $k=>$f) {
			if (isset($f['multilang']) && $f['multilang']) {
				$ml_options[$k]=$s[$k];

			}
		}
		$ml_options=str_replace('multilang=true','',$ml_options);
		if(count($ml_options)<=2) return;

		$tpl= '<?php
class %s extends gs_recordset_i18n {
	public $no_urlkey=1;
	function __construct($init_opts=false) { parent::__construct(
		%s
		,$init_opts);
		$this->structure["fkeys"]=array(
			array("link"=>"Parent","on_delete"=>"CASCADE","on_update"=>"CASCADE"),
			);
	}
}
?>';


		$classstr=sprintf($tpl, $classname ,var_export($ml_options,true));

		$fname=cfg('lib_modules_dir').'i18n'.DIRECTORY_SEPARATOR.$classname.'.module.php';
		check_and_create_dir(dirname($fname));
		file_put_contents_perm($fname,$classstr);
		include_once($fname);
		$rs=new $classname;
		$rs->install();

	}

	function selfinit($arr) {
		$id=get_class($this);
		$struct=gs_var_storage::load($id);
		if (!$struct) {
			$struct=field_interface::init($arr,$this->init_opts);
			gs_var_storage::save($id,$struct);
		}
		foreach ($struct as $k=>$s)
			$this->structure[$k]=isset($this->structure[$k]) ? array_merge($this->structure[$k],$struct[$k]) : $struct[$k];
	}
	function commit() {
		foreach ($this->structure['recordsets'] as $l=>$st) {
			// Block for commit preloaded linked "Many2One" records 
			if (isset($st['type']) && $st['type']=='many' && isset($st['mode']) && $st['mode']=='link') {
				$id_name=$st['foreign_field_name'];
				$root_name=$l.'_hash';
				$hash_name=$st['foreign_field_name'].'_hash';

				/*foreach ($this as $record) {
					$ret=$record->find_childs($l,array($hash_name=>$record->$root_name,$id_name=>0));
				}*/
				foreach ($this as $record) {
					$record->$l->find_records(array($hash_name=>$record->$root_name,$id_name=>0))->bind();
				}
			}
			// End block
			if(isset($st['update_recordset'])) {
				$prec=new $st['update_recordset'];
				foreach ($prec->structure['recordsets'] as $pl=>$pst) {
					if (isset($pst['counter_linkname']) && $pst['counter_linkname']==$l) {
						foreach ($this as $rlink) {
							$old_id=$rlink->get_old_value($st['local_field_name']);
							$new_id=$rlink->{$st['local_field_name']};

							if ($rlink->recordstate & RECORD_NEW) {
									$plink=$prec->get_by_id($new_id);
									$plink->{$pst['counter_fieldname']}++;
									$plink->commit(1);
							} else if ($rlink->recordstate & RECORD_DELETED) {
									$plink=$prec->get_by_id($old_id);
									$plink->{$pst['counter_fieldname']}--;
									$plink->commit(1);
							} else if ($old_id!=$new_id) {
									$plink=$prec->get_by_id($new_id);
									$plink->{$pst['counter_fieldname']}++;
									$plink->commit(1);
									$plink=$prec->get_by_id($old_id);
									$plink->{$pst['counter_fieldname']}--;
									$plink->commit(1);
							}
						}
					}
					
				}
			}
		}
		$ret=parent::commit();
		return $ret;
	}
	function html_list() {
		return trim($this);
	}
	function html_fields() {
		$v=$this->structure['htmlforms'];
		$v=array_keys(array_filter($v,create_function('$a','return $a["type"]!="hidden" && (!isset($a["hidden"]) || $a["hidden"]!="true");')));
		return $v;
	}

	function check_unique_urlkey($field,$value,$params,$rec_id) {
		$recs=$this->find_records(array($field=>$value));
		if ($recs->count()==0) return true;
		return $recs->first()->get_id()===$params['rec_id'];
	}

    function check_unique($field,$value,$params,$record=null,$data=null) {
		$recs=$this->find_records(array($field=>$value));
		if ($recs->count()==0) return true;
		return $recs->first()->get_id()===$params['rec_id'];
    }

	function trigger_urlkey($rec,$type) {
		if(!trim($rec->urlkey)) $rec->urlkey=string_to_safeurl(trim($rec));
	}
	function trigger_sortkey($rec,$type) {
		if (!$rec->sortkey) $rec->sortkey=$rec->get_id();
        if (!is_numeric($rec->sortkey)) $rec->sortkey=sprintf('%u',crc32($rec->sortkey));
	}

	function trigger_fPassword_encode($rec) {
		foreach ($this->structure['password_fields'] as $field) {
			if ($rec->is_modified($field)) $rec->$field=$this->encode_password($rec,$rec->$field);
		}
	}
	function is_password_field($n) {
		return isset($this->structure['password_fields']) && in_array($n,$this->structure['password_fields']);
	}
	function encode_password($rec,$v) {
		return md5(md5($rec->get_id()).$v);
	}


	function get_backlink_class($linkname) {
		return $this->structure['recordsets'][$linkname]['rs2_name'];
	}
	function get_backlink_name($linkname) {
		$link=$this->structure['recordsets'][$linkname];

		$l_rs=new $link['rs2_name'];

		foreach($l_rs->structure['recordsets'] as $backlink=>$rs_link) {
			if (substr($backlink,0,1)!='_' && $rs_link['recordset']==$link['recordset']) return $backlink;
		}
		return null;
	}
	function set_fkey($name,$on_delete='RESTRICT',$on_update='CASCADE') {
		$k=array_key_recursive($this->structure['fkeys'],'link',$name);
		if ($k!==FALSE) unset($this->structure['fkeys'][$k]);
		$fkey=array('link'=>$name);
		if($on_delete) $fkey['on_delete']=$on_delete;
		if($on_update) $fkey['on_update']=$on_update;
		$this->structure['fkeys'][]=$fkey;
	}

    function html_select_options($rec,$link,$data=array()) {
        $variants=array();
        foreach ($this as $vrec) $variants[$vrec->get_id()]=trim($vrec);
        return $variants;
    }

    function form_variants($rec,$link,$data=array()) {
                $options=array();
                foreach ($data as $hp_k=>$hp_v) {
                    if(!strpos($hp_k,'__')) continue;
                    list($hp_link,$hp_field) = explode ('__',$hp_k);
                    if ($hp_link==$link) $options[$hp_field]=explode(':',$hp_v);
                }
                $rsl=$rec->init_linked_recordset($link);
                $rsname=$rsl->structure['recordsets']['childs']['recordset'];
                $rs=new $rsname();
                $vrecs=$rs->find_records($options);
                return $vrecs;
    }

}

abstract class gs_recordset_i18n extends gs_recordset_short {
    public $orderby = "id";
	function offsetGet($offset) {
		$langs=languages();
		$default_lang=key($langs);
		if ($langs) {
			array_shift($langs);
			if ($default_lang==$offset) {
				$this->parent_record->disable_multilang=1;
				return $this->parent_record;
			}
		}
		$ret=parent::offsetGet($offset);
		//if (!$ret) $ret=$this->parent_record->Lang->new_record(array('lang'=>$offset));
		return $ret;
	}
}

