<?php


class gs_parser {
	
	private $data;
	private $registered_handlers=NULL;
	private $current_handler;

	static function &get_instance($data=null,$gspgtype=null)
	{
		static $instance;
		if (!isset($instance)) {
			$instance = new gs_parser();
		}
		if ($data) $instance->prepare($data,$gspgtype);
		return $instance;
	}
	
	function __construct($data=null)
	{
		if($data) $this->data=$data;


		
		$this->registered_handlers=gs_cacher::load('handlers','config');
		if (!$this->registered_handlers) {
			mlog('WILL REHASH HANDLERS');
			$init=new gs_init('auto');
			$init->load_modules();
			$this->registered_handlers=$this->get_handlers();
			//mlog($this->registered_handlers);
			gs_cacher::save($this->registered_handlers,'config','handlers');
		}

		if ($data) {
			$this->prepare($data);
		}
	}

    function apply_wrappers(&$result) {
        if (!isset($this->registered_handlers['wrapper'])) return;
		$wrapper=$this->find_handler($this->registered_handlers['wrapper'],$this->data['gspgid']);

        foreach($wrapper['handler'] as $handler) {
            $val=$this->parse_val($handler);
            $template=$this->registered_handlers['template'][$val['method_name']];
            if (!$template) continue;
            $template=$template['handlers'];
            $variables=$val['params'];
            $variables['continue']='continue_'.preg_replace('/[^a-zA-Z0-9]/','_',$val['method_name']);
            $template=array_var_replace($template,$variables);
            $newresult=array();
            foreach ($template as $tk=>$tv) {
                if (stripos($tv,'$original_handlers')!==FALSE) {
                    $newresult=array_merge($newresult,$result['handler']);
                    continue;
                }
                $newresult[$tk]=$tv;
            }
            $result['handler']=$newresult;

        }
       /* 
        md($wrapper,1);
        md($result,1);
        die();
        */

    }

	function prepare($data,$gspgtype=null) {
		$data['gspgid']=trim($data['gspgid'],'/');
		$this->data=$data;

        if (!$gspgtype && isset($data['gspgtype'])) $gspgtype=$data['gspgtype'];
        if (!$gspgtype) $gspgtype='get';
		gs_var_storage::save('gspgtype',$gspgtype);

		$result=$this->find_handler($this->registered_handlers[$gspgtype],$data['gspgid']);

        if ($gspgtype=='get') $this->apply_wrappers($result);

		$this->current_handler=$result['handler'];
	
		$data['handler_key']=$result['key'];
		gs_var_storage::save('handler_key',$data['handler_key']);
		if (!gs_var_storage::load('top_handler_key')) gs_var_storage::save('top_handler_key',$data['handler_key']);

		$len=count(explode('/',$result['key']));
		$data['gspgid_v']=implode('/',array_slice(explode('/',$data['gspgid']),$len));
		$data['gspgid_va']=explode('/',$data['gspgid_v']);
		$data['gspgid_vp']=array();
		for($i=0;$i<count($data['gspgid_va']);$i+=2) {
            $keyname=$data['gspgid_va'][$i];
            if (empty($keyname)) continue;
			$data['gspgid_vp'][$keyname]=isset($data['gspgid_va'][$i+1]) ?  $data['gspgid_va'][$i+1] : NULL;
		}

		$data['gspgid_a']=explode('/',$data['gspgid']);
		$this->data=$data;
	}
        function url_compare($g,$h) {
                $u=$h['splits'];
                #if (count($g)<$h['depth']) return -1;

                $cnt=0;
                for ($k=0;$k<$h['depth'];$k++) {
                        if($u[$k]=='*') {
                            $cnt++;
                            continue;
                        }
                        if ($u[$k]!=$g[$k]) return -1;
                        $cnt+=10;
                }
                return $cnt;
        }
        function find_handler($urls,$gspgid) {
                $result=array('key'=>null,'handler'=>array());
                if ($gspgid=='' && isset($urls[''])) {
                        $result['key']='';
                        $result['handler']=$urls['']['handlers'];
                        return $result;
                }
                $max_c=-1;
                $g=explode('/',trim($gspgid,'/'));

                foreach ($urls as $url=>$h) {
                        if (count($g)<$h['depth']) continue;
                        $c=$this->url_compare($g,$h);
                        if ($c>$max_c) {
                                $max_c=$c;
                                $result['key']=$url;
                                $result['handler']=$h['handlers'];
                        }
                }
                return $result;
        }
        function find_handler_cached($urls,$gspgid) {
                $route=gs_cacher::load('url_handler_routes','config');
                if (!is_array($route)) {
                    $route=array();
                    gs_cacher::save($route,'config','url_handler_routes');
                }
                if (isset($route[$gspgid])) return $route[$gspgid];


                $result=array('key'=>null,'handler'=>array());
                if ($gspgid=='' && isset($urls[''])) {
                        $result['key']='';
                        $result['handler']=$urls[''];
                        return $result;
                }
                $max_c=-1;
                foreach ($urls as $url=>$h) {
                        $c=$this->url_compare(trim($gspgid,'/'),trim($url,'/'));
                        if ($c>$max_c) {
                                $max_c=$c;
                                $result['key']=$url;
                                $result['handler']=$h;
                        }
                }
                $route[$gspgid]=$result;
                gs_cacher::save($route,'config','url_handler_routes');

                return $result;
        }

	
	function get_current_handler() {
		return $this->parse_val(reset($this->current_handler));
	}

	function get_registered_handlers() {
		return $this->registered_handlers;
	}


	
	public function _get_handler()
	{
		return $this->current_handler;
	}

	function parse_val($val)
	{
		$params=array();
		$parts=explode(':',str_replace(array("{","}"),"",$val));
		$len=count($parts);
		if ($len>2) for ($i=1;$i<$len;$i+=2) {
			$params[$parts[$i]]=$parts[$i+1];
		}

		$ret=array(
			'name'=>$parts[0],
			'params'=>$params,
		);
		list($ret['class_name'],$ret['method_name'])=explode('.',$ret['name']);
		return $ret;
	}

	function process() {
		mlog($this->data['gspgid']);
		$config=gs_config::get_instance();
		$ret=array();
		$ret['last']= new gs_null(GS_NULL_XML);
		$handler_array=$this->current_handler;
		reset($handler_array);
		while($handler=current($handler_array)) {
			$h_key=trim(key($handler_array));
			$handler=$this->parse_val(trim($handler));
			if ($handler['name']=='end') return $ret['last'];
			if (!class_exists($handler['class_name'],TRUE)) {
				load_file($config->lib_handlers_dir.$handler['class_name'].'.class.php');
			}
			if (!class_exists($handler['class_name'],TRUE)) throw new gs_exception('gs_parser.process: Handler class not exists '.$handler['class_name']);
			if (!method_exists($handler['class_name'],$handler['method_name'])) throw new gs_exception('gs_parser.process: Handler class method not exists '.$handler['class_name'].'.'.$handler['method_name']);
			$module_name=$handler['params']['module_name'];

			$s_data=$this->data;
			// --------------------- 
            /*
			if(call_user_func(array($module_name, 'admin_auth'),$this->data,$handler['params'])===false) {
				return false;
			}

			if (method_exists($handler['params']['module_name'],'auth')) {
				
				$ret['last']=$ret[$h_key]=call_user_func(array($module_name, 'auth'),$this->data,$handler['params']);
				if ($ret['last']===false) return false;
			}
            */
			// ----------------------
			mlog($handler['params']['module_name'].':'.$handler['name']);
			$o_h=new $handler['class_name']($this->data,$handler['params']);

			$ret['last']=$ret[$h_key]=$o_h->{$handler['method_name']}($ret);

			$this->data=$s_data;

			$condition=isset($handler['params']['return']) ? $handler['params']['return'] : 'gs_record';
			preg_match('/([^\&\^]+)([\&]([^\&\^]+))?([\^]([^\&\^]+))?/',$condition,$cond);
			$condition=$cond[1];
			$cond_true= isset($cond[3]) ? $cond[3] : false;
			$cond_false= isset($cond[5]) ? $cond[5] : false;


			/*
			var_dump('========'.$this->data['gspgid'].':'.$handler['class_name'].'.'.$handler['method_name']);
			echo "<pre>";
			var_dump($condition);
			var_dump($cond_true);
			var_dump($cond_false);
			var_dump($this->continue_if($condition,$ret));
			var_dump($handler['params']);
			*/




			if($this->continue_if($condition,$ret['last'])) {
				if ($cond_true && array_key_exists($cond_true,$handler_array)) {
					reset($handler_array);
					while ((string)(key($handler_array))!=(string)$cond_true) next($handler_array);
					continue;
				}
			} else  {
				if ($cond_false && array_key_exists($cond_false,$handler_array)) {
					reset($handler_array);
					while ( (string)(key($handler_array))!=(string)$cond_false)  next($handler_array);
					continue;
				}
				return $ret['last'];
			}
			next($handler_array);
		}
		return $ret['last'];
	}
	function continue_if($type,$result) {
		//var_dump($type); var_dump($result);
		switch (strtolower($type)) {
			case 'true': 
				return $result===TRUE;
			case 'false': 
				return $result===FALSE;
			case 'not_false': 
			case 'notfalse': 
				return $result!==FALSE;
			case 'array':
				return is_array($result);
			case 'gs_record':
				return is_object($result) && is_a($result,'gs_record') && $result->get_id()!==NULL;
			case 'gs_recordset':
				return is_object($result) && is_a($result,'gs_recordset');
			case 'g_forms':
				return is_object($result) && is_a($result,'g_forms');

		}
		return false;
	}
	
	static function get_handlers()
	{
		$config=gs_config::get_instance();
		$data=array();
		$modules=$config->get_registered_modules();
		if (is_array($modules)) foreach ($modules as $module_name) {
			$handlers=call_user_func(array($module_name,'get_handlers'));
			if(is_array($handlers)) {


				if (isset($handlers['get_post'])) { //get_post DEPRECATED
					$handlers['get']=isset($handlers['get']) ? array_merge($handlers['get_post'],$handlers['get']) : $handlers['get_post'];
					$handlers['post']=isset($handlers['post']) ? array_merge($handlers['get_post'],$handlers['post']) : $handlers['get_post'];
				}

				foreach ($handlers as $k=>$h) {
					foreach ($h as $kk=>$hv) {
						if (!is_array($hv)) $hv=array($hv);
						$hv_arr=array();
						foreach ($hv as $handler_key=>$handler_value) {
							$hv_arr[$handler_key]=$handler_value.":module_name:$module_name";
						}
						$handlers[$k][$kk]=$hv_arr;
					}
				}
				$data=array_merge_recursive($data,$handlers);
			}
		}
		krsort ($data['get']);
		krsort ($data['post']);


        $cdata=array();
        foreach ($data as $key=>$kdata) {
            $cdata[$key]=self::examine_handlers($kdata);
        }


		return $cdata;
	}

    private static function examine_handlers($data) {
        $out=array();
        foreach ($data as $url=>$handlers) {
            $url=trim($url,'/');
            $u=explode('/',$url);
            $d=array('depth'=>count($u),
                     'url'=>$url,
                     'splits'=>$u,
                     'handlers'=>$handlers,
                     );
                $out[$url]=$d;
        }

        return $out;
    }


	
	
	private function parse_handlers_data($data)
	{
		$ret=array();
		foreach (array('get','post','handler') as $type) {
			$root=new gs_node('root');
			$this->parse_handler_for_type($root,$type,$data[$type]);
			$ret[$type]=$root;
		}
		return $ret;
	}
	
	private function parse_handler_for_type(&$node,$type,$data)
	{
		if (is_array($data)) foreach ($data as $url => $item) {
			new gs_recurseparser($node,$url,$item,$type);
		}
	}
}


?>
