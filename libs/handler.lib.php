<?php


abstract class gs_handler {
    protected $blocks;
    protected $data;
    protected $params;
    public function __construct($data=null,$params=null) {
        $this->data=$data;
        $this->params=$params;
        if (isset($this->data['handler_params']) && is_array($this->data['handler_params'])) $this->params=array_merge($this->data['handler_params'],$this->params);
    }
	function va($i) {
		return $this->data['gspgid_va'][$i];
	}
	private $set_module_tpldir=0;

	protected function set_module_tpldir($tpl) {
		if ($this->set_module_tpldir++) return;
        if (! isset($this->params['module_name'])) return;

		$classes=gs_cacher::load('classes','config');
		if (! isset($classes[$this->params['module_name']])) return;

		$filename=$classes[$this->params['module_name']];
		$subdir=trim(str_replace(cfg('lib_modules_dir'),'',dirname($filename).'/'),'/');
		$www_subdir=trim(cfg('www_dir').$subdir.'/','/');
		$www_subdir=$www_subdir ? "/$www_subdir/" : '/';
		$subdir=$subdir ? "/$subdir/" : '';


		$td=cfg('tpl_data_dir').'modules'.$subdir;
		$tpl->addTemplateDir($td);

		$this->tpl_dir=dirname($filename).DIRECTORY_SEPARATOR.'templates';
		$tpl->addTemplateDir($this->tpl_dir);

        $this->subdir=$subdir;
        $this->www_subdir=$www_subdir;
        $tpl->assign('_module_subdir',$subdir);
        $tpl->assign('subdir',$subdir);
        $tpl->assign('www_subdir',$www_subdir);

	}
}
class gs_base_handler extends gs_handler {
	var $subdir='';
	var $www_subdir='';

    public function __construct($data=null,$params=null) {
        parent::__construct($data,$params);
		$tpl=gs_tpl::get_instance();
        $config=gs_config::get_instance();

        $tpl->assign('tpl',$this);
        $tpl->assign('_gssession',gs_session::load());
        $tpl->assign('root_dir',cfg('root_dir'));






        //$this->register_blocks();
    }

	function add_message($r) {
		gs_session::add_message($this->params['message']);
        return $r['last'];
	}

    function nop($r) {
        return $r['last'];
    }

    function get_data($name=null) {
        return $name ? $this->data[$name] : $this->data ;
    }

    function is_post() {
        return $this->data['gspgtype']==GS_DATA_POST;
    }

    function register_blocks() {
        $this->assign('_blocks',$this->blocks);
    }
    function assign($name,$value=NULL) {
        $tpl=gs_tpl::get_instance();
        if (is_array($name)) {
            return $tpl->assign($name);
        }
        return $tpl->assign($name,$value);
    }
    function fetch($data) {
        if (empty($this->params['name'])) throw new gs_exception('gs_base_handler.fetch: empty params[name]');
        $tpl=gs_tpl::get_instance();
		$this->set_module_tpldir($tpl);
        $tplname=file_exists($this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name']) ? $this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name'] : $this->params['name'];
        $tpl->assign('_gsdata',$this->data);
        $tpl->assign('_gsparams',$this->params);
        $tpl->assign('_gsstack',$data);
        if(isset($this->params['hkey'])) $tpl->assign('hdata',$data[$this->params['hkey']]);
        mlog($tplname);
        if (!$tpl->templateExists($tplname)) {
            mlog($tpl->getTemplateDir());
            throw new gs_exception('gs_base_handler.fetch: can not find template file for '.$tplname);
        }
        return $tpl->fetch($tplname);
    }

    function validate_gl() {
        $name=$this->params['name'];
        if (isset($this->params['gl'])) $name=$this->params['gl'];
        $ret=call_user_func($this->params['module_name'].'::gl',$name,$this->data['gspgid_v'],$this->data['gspgid']);
        if ($ret instanceof gs_record) return $ret;
        $url=trim($ret,'/');
        
        return ($url==$this->data['gspgid'] || $url==trim($_SERVER['REQUEST_URI'],'/') || $ret===TRUE );
    }

    function show404($ret) {
        header("HTTP/1.0 404 Not Found");
        return $this->show($ret);
    }

    function flush($str) {
	if (is_array($str) && isset($str['last'])) $str=$str['last'];    
        while (ob_get_level()) ob_end_clean();
        echo $str;
        die();
    }

    function show($ret) {


        if (isset($this->params['gl'])) {
            if ($this->validate_gl()!==TRUE) {
                unset($this->params['gl']);
                $this->params['name']='404.html';
                return $this->show404();
            }
        }

        $tpl=gs_tpl::get_instance();
		$this->set_module_tpldir($tpl);


        if (empty($this->params['name'])) {
            $this->params['name']=basename($this->data['handler_key']).'.html';
        }
        
        $this->params['name']=trim($this->params['name']);

		$tplname=$this->params['name'];
		foreach($tpl->getTemplateDir() as $tpldir) {
			$testtplname=$tpldir.DIRECTORY_SEPARATOR.$tplname;
			if (file_exists($testtplname)) {
				$tplname=$tpl->multilang(realpath($testtplname));
				break ;
			}
		}

        mlog($tplname);
        $tplname=$tpl->multilang($tplname);



        $tpl->assign('_gsdata',$this->data);
        $tpl->assign('_gsparams',$this->params);
        $tpl->assign('_gsstack',$ret);

        if (isset($this->data['handler_params'])) {
            try {
                mlog($tplname);
                $html=$tpl->fetch($tplname);
                echo $html;
                return;
            } catch (gs_exception $e) {
                var_dump($this->params);
                var_dump($this->data);
                throw $e;
            }
        }
        $txt=ob_get_contents();
        mlog($tplname);
        $html=$tpl->fetch($tplname);
        echo $html;
        if (function_exists('memory_get_peak_usage')) mlog(sprintf('memory usage: %.4f / %.4f Mb ',memory_get_usage(TRUE)/pow(2,20),memory_get_peak_usage(TRUE)/pow(2,20)));
        $pool=gs_connector_pool::get_instance();
        $db_conn=$pool->get_connector('mysql');
        if ($db_conn) mlog($db_conn->get_stats());

		gs_eventer::send('gs_base_handler_show',$this);

        if (DEBUG) {
            $g=gs_logger::get_instance();
            $g->console();
        }
    }
    protected function get_form() {
        $params=$this->params;
        $data=$this->data;
        if (!isset($params['classname']) && isset($this->data['handler_params']['classname'])) $params['classname']=$this->data['handler_params']['classname'];
        if (isset($params['classname'])) {
            $id=isset($data['gspgid_va'][1]) ? $data['gspgid_va'][1] : null;
            $classname=$params['classname'];
            $obj=new $classname;
            $fields=array_keys($obj->structure['fields']);

            $options=array();
            foreach($data['handler_params'] as $hk=>$hv) {
                if (isset($obj->structure['fields'][$hk])) $options[$hk]=$hv;
            }

            if ($id!==NULL) {
                $options[$obj->id_field_name]=$id;
                $rec=$obj->find_records($options,$fields)->first(true);
            } else {
                $rec=$obj->new_record();
            }
            $rec->fill_values($options);
            $f=self::get_form_for_record($rec,$this->params,$this->data);
        } else {
            $form_class_name=isset($params['form_class']) ? $params['form_class'] : 'g_forms_html';
            $f=new $form_class_name(array(),$params,$data);
            if(isset($data['handler_params']['_default'])) {
                $default_values=string_to_params($data['handler_params']['_default']);
                $f->set_values($default_values);
            }
        }
        $this->showform($f); //needs to have changes from form's template!
        return $f;
    }

    static function minus_fields($hh_fields,$params,$data,$hh) {
        $custom_fields=NULL;
        if (isset($params['fields'])) $custom_fields=$params['fields'];
        if (isset($data['handler_params']['fields'])) $custom_fields=$data['handler_params']['fields'];
        if ($custom_fields) $custom_fields=explode(',',$custom_fields);
        if (count($custom_fields)) {
            $fields_minus=array_filter($custom_fields,create_function('$a','return substr($a,0,1)=="-";'));
            $fields_plus=array_diff($custom_fields,$fields_minus);
            $fields_minus=array_map(create_function('$a','return substr($a,1);'),$fields_minus);
            if(count($fields_plus)) $hh_fields=$fields_plus;
            foreach ($fields_minus as $name) unset($hh_fields[array_search($name,$hh_fields)]);
        }
        return $hh_fields;
    }

    static function apply_data_widgets($f,$hh,$params,$data) {
        $params['form']=$f;
        $rec=$f->rec;
        foreach ($f->htmlforms as $k=>$v) {
		if (!isset($hh[$k])) continue;
            $v=$hh[$k];
            $hhh=array($k=>$v);
            switch($v['type']) {
            case 'lMany2Many':
                if (method_exists($rec->get_recordset(),'form_variants_'.$k)) {
                    $vrecs=call_user_func(array($rec->get_recordset(),'form_variants_'.$k),$rec,$data['handler_params']);
                } else {
                    $vrecs=$rec->form_variants($k,$data['handler_params']);
                    /*
                    $options=array();
                    foreach ($data['handler_params'] as $hp_k=>$hp_v) {
                        if(!strpos($hp_k,'__')) continue;
                        list($hp_link,$hp_field) = explode ('__',$hp_k);
                        if ($hp_link==$k) $options[$hp_field]=explode(':',$hp_v);
                    }
                    $rsl=$rec->init_linked_recordset($k);
                    $rsname=$rsl->structure['recordsets']['childs']['recordset'];
                    $rs=new $rsname();
                    $vrecs=$rs->find_records($options);
                    */
                }
                $variants=array();
                foreach ($vrecs as $vrec) $variants[$vrec->get_id()]=trim($vrec);
                $hhh[$k]['variants']=$variants;
                break;
            case 'lOne2One':
                if ($hh[$k]['hidden']!='false' && $hh[$k]['hidden']) break;
                if (isset($v['widget'])) {
                    $dclass='gs_data_widget_'.$v['widget'];
                    if (class_exists($dclass)) {
                        $d=new $dclass();
                        $hhh=$d->gd($rec,$k,$hhh,$params,$data);

                    }
                }
                break;
            case 'lMany2One':
                if ($v['hidden']=='true') break;
                if (isset($v['widget'])) {
                    $dclass='gs_data_widget_'.$v['widget'];
                    if (class_exists($dclass)) {
                        $d=new $dclass();
                        $hhh=$d->gd($rec,$k,$hhh,$params,$data);
                    }
                }
                if (isset($v['options']['mode']) && $v['options']['mode']=='link') {
                    $f->add_field($v['linkname'].'_hash', array('type'=>'private','validate'=>'dummyValid'));
                }
                if (!empty($v['widget'])) {
                    break;
                }
                break;
            default:
                break;
            }
            if (isset($v['widget'])) {
                $dclass='gs_data_widget_'.$v['widget'];
                if (class_exists($dclass)) {
                    $d=new $dclass();
                    $hhh=$d->gd($rec,$k,$hhh,$params,$data,$f);
                }
            }
            if (isset($hhh[$k]['variants']))  $f->set_variants($k,$hhh[$k]['variants']);
            /*
            foreach ($hhh as $k=>$p) {
            	if (!isset($hh[$k])) {
            		$f->add_field($k,$p);
            	}
            }
            */
        }
    }

    static function get_form_for_record($rec,$params,$data) {


        $default_values=array();
        $rec_default_values=array();
        if(isset($data['handler_params']['_default'])) {
            $default_values=string_to_params($data['handler_params']['_default']);
        }
        $rec->fill_values($default_values);


        $form_class_name=isset($params['form_class']) ? $params['form_class'] : 'g_forms_html';
        $f=new $form_class_name(array(),$params,$data);
        $f->rec=$rec;
        $f->force_set_value($rec->get_recordset()->id_field_name,$rec->get_id());

        $hh=$rec->get_recordset()->structure['htmlforms'];
        $hh_fields=array_keys($hh);
        $hh_fields=self::minus_fields($hh_fields,$params,$data,$hh);

        if (!count($f->htmlforms)) foreach ($hh_fields as $name) {
            $params=$hh[$name];
            if (!(isset($params['hidden']) && $params['hidden']) && !isset($data['handler_params'][$name])) {
            //if (!isset($data['handler_params'][$name])) {
                $f->add_field($name,$params);
                if(isset($params['default'])) $rec_default_values[$name]=$params['default'];
            }
        }



		$langs=languages();
		cfg_set('languages',array());
        
        self::apply_data_widgets($f,$hh,$params,$data);

		cfg_set('languages',$langs);

        $fields=$rec->get_recordset()->id_field_name.','.implode(',',$hh_fields);

        $rec_values=$rec->get_values($fields);
        if (isset($rec_values['Lang'])) {
            $langs=languages();
            $default_lang=key($langs);
            $rec_values['Lang'][$default_lang]=$rec->get_values();
        }
        


        $f->set_values($rec_default_values);
        $f->set_values($default_values);
        $f->set_values($rec_values);
        $f->set_values(self::implode_data($rec_values));
        $f->set_values($data);


        return $f;

    }


    function showform($f=null) {
        $tpl=gs_tpl::get_instance();
		$this->set_module_tpldir(gs_tpl::get_instance());
        if (!$f) $f=$this->get_form();
        $tpl->assign('formfields',$f->show());
        $tpl->assign('handler_params',$this->data['handler_params']);
        /*
        $tpl->assign('forminputs',$f->get_inputs());
        $tpl->assign('formerrors',$f->validate_errors['FIELDS']);
        */
        $tpl->assign('form',$f);
		//if (isset($this->data['handler_params']['name'])) $this->params['name']=$this->data['handler_params']['name'];
        if(!isset($this->params['name'])) $this->params['name']=$this->data['handler_params']['name'];
        if(!isset($this->params['name'])) $this->params['name']='form_empty.html';

        $tplname=file_exists($this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name']) ? $this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name'] : $this->params['name'];
        $tplname=$tpl->multilang($tplname);
        mlog($tplname);
        $ret=$tpl->fetch($tplname);
        return $ret;
    }
    function validate($f=NULL) {
        if(isset($this->data['handler_params']['form_class'])) {
            $this->params['form_class']=$this->data['handler_params']['form_class'];
        }
        if (!$this->is_post()) {
            return $this->showform();
        }

        $tpl=gs_tpl::get_instance();
        if (!$f || !is_object($f) || !is_a($f,'g_forms')) $f=$this->get_form();
        if (isset($this->data['gsform_interact'])) {
            $this->flush($f->interact($this->data['gsform_interact']));
        }
        $validate=$f->validate();
        if ($validate['STATUS']===true) {
            return $f;
        }
        $tpl->assign('formfields',$f->show($validate));
        $tpl->assign('form',$f);
        if(!$this->params['name']) $this->params['name']='form_empty.html';
        $tplname=file_exists($this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name']) ? $this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name'] : $this->params['name'];
        mlog($tplname);
        $ret=$tpl->fetch($tplname);
        return $ret;
    }
    function post($ret) {
        if (!$this->is_post()) {
            gs_session::save(cfg('referer_path'),'post_referer_'.$this->data['gspgid']);
        }
        $f=$this->validate();
        if (!is_object($f) || !is_a($f,'g_forms')) return $f;
        $cleandata=$f->clean();
        $f->rec->fill_values(self::explode_data($cleandata));

        foreach ($f->htmlforms as $fieldname=>$field) {
            if ($field['type']=='lMany2Many') {
                //if (isset($cleandata[$fieldname])) {
                if (array_key_exists($fieldname,$cleandata)) {
                    //$data[$k]=(is_array($data[$k])) ? array_combine($data[$k],$data[$k]) : array();
                    $f->rec->$fieldname->flush($cleandata[$fieldname]);
                }
            }
        }

        $f->rec->get_recordset()->commit();

        /*
        if (isset($this->data['save_return'])) {
        		$this->params['gl']='save_return';
        		$this->redirect_if($ret);
        }
         */

		//die();

        return $f->rec;
    }

    function postform() {

        if ($this->data['gspgtype']==GS_DATA_GET) return $this->showform();

        $tpl=gs_tpl::get_instance();
		$this->set_module_tpldir($tpl);
        $f=$this->get_form();
        $validate=$f->validate();
        if ($validate['STATUS']===true) {
            $f->rec->fill_values(self::explode_data($f->clean()));
            $f->rec->get_recordset()->commit();
            if (isset($this->params['href'])) {
                $href=$this->params['href'];
                if (strpos($this->params['href'],'/')!==0) {
                    $href=$this->subdir.$href;
                }
                return html_redirect($href,array(
                                         'id'=>$f->rec->get_id(),
                                         'classname'=>get_class($f->rec->get_recordset()),
                                     ));
            }
            return html_redirect($this->data['gspgid_handler']);
        }
        $tpl->assign('formfields',$f->show($validate));
        $tpl->assign('form',$f);
        $tplname=file_exists($this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name']) ? $this->tpl_dir.DIRECTORY_SEPARATOR.$this->params['name'] : $this->params['name'];
        mlog($tplname);
        return $tpl->fetch($tplname);
    }
    function displayform() {
        $tpl=gs_tpl::get_instance();
        $tpl->assign('gspgid_form',$this->data['gspgid']);
        $tpl->assign('gspgid_handler',$this->data['gspgid']);
        echo $this->postform();
    }
    function deleteform() {
		$this->set_module_tpldir(gs_tpl::get_instance());

        if ($this->data['gspgtype']==GS_DATA_GET) return $this->showform();
        $f=$this->get_form();
        $f->rec->delete();
        $f->rec->commit();

        if (isset($this->params['href'])) return html_redirect($this->subdir.$this->params['href'].'/'.$f->rec->get_id().'/'.get_class($f->rec->get_recordset()).'/'.$this->data['gspgid_v']);
        return html_redirect($this->data['gspgid_handler']);
    }
    /**
    * NEVER use this handler on client side, use delete_link instead
    **/
    function delete() {
        $id=$this->data['gspgid_va'][0];
        $rs=new $this->params['classname'];
        $rec=$rs->get_by_id($id);
        $rec->delete();
        $rec->commit();
        return $rec;
    }
    /**
    *	'gs_base_handler.check_login:return:gs_record^redirect:classname:customers:assign:customer'
    *	'gs_base_handler.delete_link:{link:customer.Shipping_address}'
    *	'redirect'=>'gs_base_handler.redirect'
    **/
    function delete_link() {
        list($classname,$linkname)=explode('.',$this->params['link']);
        $rec=gs_var_storage::load($classname);
        if (!$rec) return;
        $id=$this->data['gspgid_va'][0];
        $links=$rec->$linkname;
        if ($links && $links[$id]) {
            $links[$id]->delete();
            $links->commit();
        }
        return $rec;
    }
    
    function delete_rec ($data) {
        $rec=$this->hpar($data);
        $rec->delete();
        $rec->commit();
        return $rec;
    }
    
    function set_value($data) {
        $rec=$this->hpar($data);
        if (!$rec) return $rec;
        $name=$this->params['name'];
        $rec->$name=$this->data['gspgid_va'][0];
        $rec->commit();
        return $rec;
    }
    function copy() {
        $id=$this->data['gspgid_va'][0];
        $rs=new $this->params['classname'];
        $rec=$rs->get_by_id($id);
        /*
        $rec->delete();
        $rec->commit();
        */
        $values=$rec->get_values();
        unset($values[$rs->id_field_name]);
        unset($values['_ctime']);
        unset($values['_mtime']);
        unset($values['urlkey']);
        $newrec=$rs->new_record($values);
        $newrec->commit();
        $newrec->urlkey=$newrec->get_id();
        $newrec->commit();
        return $rec;
    }
    function save_file_public_html($data) {
        $txt=trim($this->hpar($data));
        $filename=$this->params['filename'];
        $filename=cfg('document_root').trim($filename,DIRECTORY_SEPARATOR);
        $dirname=realpath(dirname($filename));
        if (strpos($dirname,realpath(cfg('document_root')))!==0) return false;
        if (!file_put_contents_perm($filename,$txt)) return false;
        return $txt;
    }
    function dump($data) {
        $txt=trim($this->hpar($data));
        echo $txt;
        return $txt;
    }
    function xml2txt($ret) {
        $x=xml_print($ret['last']->asXML());
        return $x;
    }

    function xml_dump($ret) {
        $x=xml_print($ret['last']->asXML());
        md($x,1);
        return $x;
    }
    function xml_show($ret) {
        return $this->xml_print($ret);
    }
    function xml_print($ret) {
        $x=xml_print($ret['last']->asXML());
        header('Content-type: text/xml');
        echo $x;
        return $x;
    }
    function xml_save_file($ret) {
	$x=$ret['last'];
	if(is_object($x) && method_exists($x,'asXML')) $x=$x->asXML();
        $x=xml_print($x);
        $filename=str_replace('/','_',$this->data['gspgid']).'.xml';

	while (ob_get_level()) ob_end_clean();
        header('Content-type: text/xml;charset=utf8');
        header('Content-disposition: attachment; filename='.$filename);
        echo $x;
	die();

    }
    function xml_export() {
        $id=$this->data['gspgid_va'][0];
        $rs=new $this->params['classname'];
        $rec=$rs->get_by_id($id);
        if (!$rec) return $rec;
        return $rec->xml_export();
    }
    function xml_clone() {
        $xml=$this->xml_export();
        $newrs=xml_import($xml);
        $newrs->commit();
        return $newrs->first();
    }
    function json_print($ret) {
        $x=json_encode($ret['last']);
        echo $x;
        return $x;
    }
    function save_file($x,$contenttype='text/plain',$extension='.txt',$filename=null) {
	if (is_array($x) && isset($x['last'])) $x=$x['last'];
        if(!$filename) $filename=str_replace('/','_',$this->data['gspgid']).$extension;

	while (ob_get_level()) ob_end_clean();

        header('Content-type: '.$contenttype.';charset=utf8;');
        header('Content-disposition: attachment; filename='.$filename);
        echo $x;
	die();

    }
    function json_save_file($ret) {
	$x=$ret['last'];    
        $x=json_encode($x);
        $filename=str_replace('/','_',$this->data['gspgid']).'.json';

	while (ob_get_level()) ob_end_clean();

        header('Content-type: text/json;charset=utf8');
        header('Content-disposition: attachment; filename='.$filename);
        echo $x;
	die();

    }
	function fix_gl($ret) {
		$href=null;
        if (isset($this->params['gl'])) {
			$href=call_user_func('module_'.$this->params['module'].'::gl',$this->params['gl'],$ret['last'],$this->data);
			$href=trim($href,'/');
		}

		if ($href!=$this->data['gspgid']) {
			$this->params['href']=$href;
			return $this->redirect();
		}
		return $ret['last'];
    }
	function redirect_gl($ret) {
		if (isset($this->params['gl'])) {
			$this->params['href']=call_user_func($this->params['module_name'].'::gl',$this->params['gl'],$ret['last'],$this->data);
		} else if (isset($this->params['href'])) {
			$this->params['href']=trim($this->params['href'],'/').'/'.$ret['last']->get_id();
		}
		return $this->redirect($ret);
	}
	function redirect_v() {
		$href=$this->data['gspgid_v'];
		return html_redirect($href,array(),'302',$this->params['clean_get']);
	}


    function redirect_if($ret) {
        if (!isset($this->data[$this->params['gl']])) return true;
        $this->params['href']=call_user_func($this->params['module_name'].'::gl',$this->params['gl'],$ret['last'],$this->data);
        $this->redirect();
        return false;
    }
    function redirect($ret) {
		if (isset($this->params['clean_get']) && $this->params['clean_get']!='false') {
			$this->params['clean_get']=true;
		} else {
			$this->params['clean_get']=false;
		}

		$href=null;
		if(isset($this->params['href'])) $href=$this->params['href'];
		if($href=='current_url')  {
			$href=$this->data['gspgid'];
			if (isset($this->data['gspgid_root'])) $href=$this->data['gspgid_root'];
		}
		if (!isset($this->params['target'])) $this->params['target']=null;

        return html_redirect($href,array(),'302',$this->params['clean_get'], $this->params['target']);
    }

    function get_record($data) {
        return record_by_id($this->data['gspgid_va'][$this->params['key']],$this->params['rs']);
    }
    
    function get_record_a($data) {
        return record_by_id($this->data['gspgid_a'][$this->params['key']],$this->params['rs']);
    }

    function set_record($data) {
        $rec=$this->hpar($data);
        if (!$rec) {
            $rec=record_by_id($this->data['gspgid_va'][$this->params['key']],$this->params['rs']);
        }
        $f=$this->params['field'];
        $v=$this->params['value'];
        $rec->$f=$v;
        $rec->commit();
        return $rec;
    }

    /**
    * Analogue "redirect" but put in sprintf pattern of "href" field`s value of record from previous handler
    **/
    function redirect_rs_hkey($data) {
        $rec=$this->hpar($data);
        return html_redirect(sprintf($this->params['href'],$this->params['field'] ?  $rec-> {$this->params['field']} : $rec->get_id()));
    }

    function redirect_up() {
        $level=isset($this->params['level'])? intval($this->params['level']) :1;
        $href=$this->data['gspgid_root'];
        for($i=0; $i<$level; $i++) $href=dirname($href);
        return html_redirect($href);
        //return (isset($this->data['gspgid_va'][1])) ? html_redirect($href) : html_redirect();
    }

    function save_prev_url($ret) {
        gs_session::save(cfg('referer_path'),$this->params['name']);
        return TRUE;
    }
    function redirect_saved_url($ret) {
        $url=gs_session::load($this->params['name']);
        if ($url) {
            $this->params['href']=$url;
            $this->redirect($ret);
            return TRUE;
        }
        return FALSE;
    }


    function many2one() {
        if (isset($this->data['gspgid_va'][4]) && $this->data['gspgid_va'][4]=='delete') {
            $rid=intval($this->data['gspgid_va'][5]);
            $rs_name=$this->data['gspgid_va'][0];
            $rs=new $rs_name;
            $rec=$rs->get_by_id($rid);
            if ($rec) {
                $rec->delete();
                $rec->commit();
            }
            $res=preg_replace("|/delete/\d+|is","//",$this->data['gspgid']);
            return html_redirect($res);
        }
        $params=array(
                    $this->data['gspgid_va'][1]=>$this->data['gspgid_va'][2],
                );
        $url=$this->data['gspgid_va'][0].'/'.$this->data['gspgid_va'][1].'/'.$this->data['gspgid_va'][2].'/'.$this->data['gspgid_va'][3];
        if ($this->data['gspgid_va'][2]==0) {
            $params[$this->data['gspgid_va'][1].'_hash']=$this->data['gspgid_va'][3];
        }
        $tpl=gs_tpl::get_instance();
        $tpl->assign('url',$url);
        $tpl->assign('params',$params);
        $this->show();
    }

    static function implode_data($data,$prefix='') {
        $newdata=array();
        foreach ($data as $k=>$v) {
            if(is_array($v)) {
                $newdata=array_merge($newdata,self::implode_data($v,$prefix.':'.$k));
            } else {
                $newdata[trim("$prefix:$k",':')]=$v;
            }
        }
        return $newdata;
    }
    static function explode_data($data) {
        $newdata=array();
        foreach ($data as $k=>$v) {
            $s=explode(':',$k);
            while (($i=array_pop($s))!==NULL) {
                $dd=array();
                $dd[$i]=$v;
                $v=$dd;
            }
            $newdata=array_merge_recursive_distinct($newdata,$v);
        }
        $ret=array_merge($data,$newdata);
        return $ret;
    }
    static function process_handler($params,$smarty) {
        $smarty=gs_tpl::get_instance();
        $smarty->assign($params);
        $params['gspgid']=trim($params['gspgid'],'/');
        $s_data=$data=$smarty->getTemplateVars('_gsdata');
        $s_gspgid_form=$smarty->getTemplateVars('gspgid_form');
        $s_gspgid=cfg('s_gspgid');
        cfg_set('s_gspgid',$params['gspgid']);

        $s_handler_cnt=cfg_set('s_handler_cnt',cfg('s_handler_cnt')+1);

        if (isset($params['_params']) && is_array($params['_params'])) $params=array_merge($params,$params['_params']);

        if (isset($data['gspgid_form']) && $data['gspgid_form']==$params['gspgid']) {
            $gspgid_form=$data['gspgid_form'];
            $c=new gs_data_driver_post;
            $data=$c->import();
            $data['gspgid_form']=$gspgid_form;
            $data['gspgid']=$params['gspgid'];
        }

        if (cfg('use_handler_cache') && $data['gspgtype']!==GS_DATA_POST) {
            $hh=new tw_handlers_cache();
            $h=$hh->find_records(array('md5'=>md5($params['gspgid']),'gspgid'=>$params['gspgid']),'text')->first();
            if ($h) {
                mlog('RETRUN '.$params['gspgid'].' data from cache');
                return $h->text;
            }
        }

        cfg_set('handler_cache_status',0);

        if (!isset($data['gspgid_root'])) {
            $data['gspgid_root']=$s_data['gspgid'];
            $data['handler_key_root']=$s_data['handler_key'];
        }
        $data['gspgid_handler']=isset($data['gspgid']) ? $data['gspgid'] : '';
        $data['gspgid_handler_va']=isset($data['gspgid_va']) ? $data['gspgid_va'] : '';
        $data['gspgid']=$params['gspgid'];
        $data['handler_params']=$params;


        $tpl=gs_tpl::get_instance();
        $tpl->assign($params);

        if (isset($params['_record'])) {
            $tpl->assign('_record',$params['_record']);
        }
        $assign=array();
        $assign['gspgdata_form']=$data;
        $assign['gspgid_form']=$data['gspgid'];
        $assign['gspgid_handler']=$data['gspgid_handler'];
        $assign['gspgid_root']=$data['gspgid_root'];
        $assign['handler_params']=$params;

        $tpl->assign($assign);

        if (isset($params['gspgtype'])) $data['gspgtype'] = $params['gspgtype'] ;
        $o_p=gs_parser::get_instance($data,isset($params['gspgtype']) ? $params['gspgtype'] : 'handler');
        if (isset($params['scope'])) {
            $hndl=$o_p->get_current_handler();
            if ($hndl['params']['module_name']!=$params['scope']) {
                return '';
            }
        }
        ob_start();
        try {
            $ret=$o_p->process();
        } catch (gs_dbd_exception $e) {
            throw $e;
        } catch (gs_exception $e) {
            throw $e;
        }
        $ret_ob=ob_get_contents();
        ob_end_clean();
        $smarty->assign('_gsdata',$s_data);
        $tpl->assign('gspgid_form',$s_gspgid_form);
        cfg_set('s_gspgid',$s_gspgid);
        $ret=$ret_ob.$ret;

        if(cfg('use_handler_cache') &&  $s_handler_cnt==cfg('s_handler_cnt') && cfg('handler_cache_status')==2 &&  $data['gspgtype']!==GS_DATA_POST) {
            $h=$hh->find_records(array('md5'=>md5($params['gspgid'])))->first(true);
            $h->gspgid=$params['gspgid'];
            $h->text=$ret;
            $hh->commit();
        }

        return $ret;

    }

function hpar($data,$name='hkey',$default=null) {
        if ($name=='hkey' && !isset($this->params[$name])) {
            return $data['last'];
        }
        return isset($this->params[$name]) ? $data[$this->params[$name]] : $default;
    }

    function send_email($data) {
        $to=$this->hpar($data,'email',array());
        $to=$this->hpar($data,'hkey',$to);

        $txt=$this->hpar($data,'txt','');

        $subj='lalala';

        pmail($to,$subj,$txt);

    }

    /**
    * Send email with data from record
    * If field $this->params['email'] contains @ - get address from her, else not contains - use her as name of record`s field with address
    **/
    function email4record($data) {
        $rec=$data['last'];
        if(isset($this->params['email']) && strpos($this->params['email'],'@')!==false) {
            $to=$this->params['email'];
        } else {
            $to=$rec-> {$this->params['email']};
        }
        // if email incorrect - don`t send letter
        if(empty($to) || strpos($to,'@')===false) return false;
				
				if (cfg('disable_email')) return $rec;

        $tpl=gs_tpl::get_instance();
        $tpl->assign('rec',$rec);
        $tpltitle= (isset($this->params['template_title']) && $this->params['template_title']) ? $this->params['template_title'] : str_replace(".html","_title.html",$this->params['template']);
        $subj=$tpl->fetch($tpltitle);
        $txt=$tpl->fetch($this->params['template']);
        //bee_mail($to,$subj,$txt,cfg('support_email_address'));
        //bee_mail($to,$subj,$txt,'info@sevenpay.com');
        //pmail($to,$txt,$subj,$txt);
        return $rec;
    }

    function test_id($data) {
        //$code=$this->hpar($data);
        $code=$this->data['gspgid_va'][0];
        $res=preg_match("|(\d+)a(.*)|is",$code,$out);
        if (count($out)<2) return false;
        if (md5($out[1])!=$out[2]) return false;
        $id=intval($out[1]);
        return record_by_id($id,$this->params['rs']);
    }


    function check_login($data) {
        if(!isset($this->params['classname']) && isset($this->data['handler_params']['classname'])) $this->params['classname']=$this->data['handler_params']['classname'];

        if(function_exists('person') && isset($this->params['role'])) {
			$rec=person($this->params['role']);
		} else {
			$id=gs_session::load('login_'.$this->params['classname']);
			$rec=record_by_id($id,$this->params['classname']);
		}

        foreach ($this->params as $n=>$v) {
            if (isset($rec->get_recordset()->structure['fields'][$n])) {
				if ($rec->$n!=$v) {
					return new gs_null(GS_NULL_XML);
				}
			}
        }
	
        if(isset($this->data['handler_params']['assign'])) {
            gs_var_storage::save($this->data['handler_params']['assign'],$rec);
        }
        if(isset($this->params['assign'])) {
            gs_var_storage::save($this->params['assign'],$rec);
        }
        return $rec;
    }
    function post_logout($data) {
        $h=new handler_registry;
        $rec=$this->check_login($data);
        if($rec) $h->before_logout($rec);
        //gs_session::clear('login_'.$this->params['classname']);
        gs_session::save(NULL,'login_'.$this->params['classname']);

        if(function_exists('person') && isset($this->params['role'])) {
			$roles=explode(',',$this->params['role']);
			foreach ($roles as $role) person()->remove_role($role);
		}
        return true;
    }


    function post_login($data) {

		$rec=null;

		if (isset($data['last'])) $rec=$data['last'];
		if (!$rec) $rec=$this->post_find_record($data);



		if (is_object($rec) && is_a($rec,'g_forms')) return $this->showform($rec);
        if (!is_object($rec) || !is_a($rec,'gs_record')) return $rec;
        gs_session::save($rec->get_id(),'login_'.$this->params['classname']);

        if(function_exists('person') && isset($this->params['role'])) person()->add_role($this->params['role'],$rec);

        $h=new handler_registry;
        $h->after_login($rec);
        return $rec;
    }
    function post_find_record_form($data) {
        $rec=$this->post_find_record($data);
        if (is_object($rec) && is_a($rec,'g_forms')) return $this->showform($rec);
        return $rec;
    }
    function post_find_record($data) {
        $bh=new gs_base_handler($this->data,$this->params);
        $f=$bh->get_form();
        foreach($f->htmlforms as $k=>$v) {
            if(!is_array($v['validate'])) continue;
            $u=array_search('checkUnique',$v['validate']);
            if ($u!==FALSE) unset($f->htmlforms[$k]['validate'][$u]);
        }
        $fv=$bh->validate($f);

        if (!is_object($fv) || !is_a($fv,'g_forms')) return $f;

        $d=$f->clean();

        if(!isset($this->params['classname']) && isset($this->data['handler_params']['classname'])) $this->params['classname']=$this->data['handler_params']['classname'];
        $rsname=$this->params['classname'];
        $rs=new $rsname;

		$password_fields=array();


		foreach ($d as $n=>$v) {
				if ($rs->is_password_field($n)) {
						$password_fields[$n]=$d[$n];
						unset($d[$n]);
				}
		}

        foreach ($this->data['handler_params'] as $n=>$v) {
            if (isset($rs->structure['fields'][$n])) $d[$n]=$v;
        }
        $e=array_filter($d);
        if (!$e) {
            $f->trigger_error('FORM_ERROR','EMPTY_SEARCH');
			return $f;
            //return $this->showform($f);

        }
        $rec=$rs->find_records($d)->first();

        if (!$rec) {
            $f->trigger_error('FORM_ERROR','REC_NOTFOUND');
			return $f;
            //return $this->showform($f);
        }

	foreach ($password_fields as $n=>$v) {
		if ($rec->$n == $v) {
			$rec->$n=FALSE;
			$rec->$n=$v;
			$rec->commit();
		}
		if ($rec->$n != $rs->encode_password($rec,$v)) {
				$f->trigger_error('FORM_ERROR','REC_NOTFOUND');
				return $f;
				//return $this->showform($f);
		}
	}


        return $rec;
    }

	function rec_by_urlkey($ret) {
        $id=null;
        if (isset($this->data['gspgid_handler_va'])) $id=reset($this->data['gspgid_handler_va']);
        if (!$id) $id=reset($this->data['gspgid_va']);
        //$rec=record_by_urlkey(end($this->data['gspgid_handler_va']),$this->params['classname']);
        $rec=record_by_urlkey($id,$this->params['classname']);
        return $rec;
    }
	function rec_by_fieldname($ret) {
        $rec=$this->hpar($data);
        $id=null;
        if (!$rec) {
            if (isset($this->data['gspgid_handler_va'])) $id=reset($this->data['gspgid_handler_va']);
            if (!$id) $id=reset($this->data['gspgid_va']);
        } else {
            $id=$rec->get_id();
        }
		$rec=record_by_field($this->params['fieldname'],$id,$this->params['classname']);
        return $rec;
    }
    function rec_by_id($ret) {
		$id=null;
        if (isset($this->data['gspgid_handler_va'])) $id=reset($this->data['gspgid_handler_va']);
        if (!$id) $id=reset($this->data['gspgid_va']);

        if(!$id) return false;

        $rec=record_by_id($id,$this->params['classname']);
		gs_eventer::send('gs_base_handler_rec_by_id',$rec);
        return $rec;
    }
    function rec_by_handler_id($ret) {
		$id=null;
        $id=reset($this->data['gspgid_va']);
        if(!$id) return false;
        $rec=record_by_id($id,$this->params['classname']);
        return $rec;
    }

}

class gs_tpl_block {
    protected $tpl_filename;
    protected $data;
    function __construct($data=null,$tpl_filename='default/empty_block.html') {
        $this->data=$data;
        $this->tpl_filename=$tpl_filename;
    }
    function show() {
        $tpl=gs_tpl::get_instance();
        return $tpl->fetch($this->tpl_filename);
    }
}
?>
