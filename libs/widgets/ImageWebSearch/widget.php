<?php
load_file(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'MultiPowUpload'.DIRECTORY_SEPARATOR.'widget.php');
class gs_widget_ImageWebSearch extends gs_widget_MultiPowUpload {
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

        /*
        $tpl=gs_tpl::get_instance();
	$tpls=$tpl->template_dir;
	$tpls[]=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates';
	$tpl->setTemplateDir($tpls);
    */

        $tpl=new gs_tpl();
		$tpl=$tpl->init();
        $tpl->addTemplateDir(dirname(__FILE__).DIRECTORY_SEPARATOR.'templates');

        $params=array();
        $params['recordset']=$this->params['options']['recordset'];
        $params['linkname']=$this->params['linkname'];
        $params['foreign_field_name']=$this->params['options']['foreign_field_name'];
        $params['rid']=$rid;
        $params['hash']=$hash;
        $params[$params['linkname'].'_hash']=$hash;

        $tpl->assign('params',$params);

        $images=$r->find_records($find)->orderby('group_key');
        $g_images=array();
        foreach($images as $i) {
            $key=$i->group_key;
            if (!$key) $key='nogrp';
            $g_images[$key][]=$i;
        }
        $tpl->assign('images',$images);
        $tpl->assign('g_images',$g_images);

        return $tpl->fetch('widget_ImageWebSearch.html');

    }
    function clean() {
        return array();
    }
}

class gs_widget_ImageWebSearch_module extends gs_base_module implements gs_module {
    function __construct() {}
    function install() {}
    function get_menu() {}
    static function get_handlers() {
        $data=array(
                  'handler'=>array(
                      '/widgets/ImageWebSearch/action'=>array(
                          'gs_widget_ImageWebSearch_handler.action',
                          'gs_base_handler.redirect',
                      ),
                  ),
                  'get'=>array(
                      '/widgets/ImageWebSearch/upload'=>array(
                          'gs_widget_ImageWebSearch_handler.upload',
                      ),
                      '/widgets/ImageWebSearch/search'=>array(
                          'gs_widget_ImageWebSearch_handler.search',
                      ),
                    '/libs/widgets/ImageWebSearch/'=>'gs_widget_ImageWebSearch_handler.public_html',
                  ),
              );
        return self::add_subdir($data,dirname(__file__));
    }
}
class gs_widget_ImageWebSearch_handler extends gs_widget_MultiPowUpload_handler {
	function public_html() {
		$fname=dirname(__FILE__).DIRECTORY_SEPARATOR.'public_html'.DIRECTORY_SEPARATOR.trim($this->data['gspgid_v'],DIRECTORY_SEPARATOR);
		$fname=realpath($fname);
		if(!$fname) return NULL;
		//if(isset($this->params['content-type'])) header('Content-type:'.$this->params['content-type']);
		if (pathinfo($fname, PATHINFO_EXTENSION)=='css') header('Content-type:text/css');
		if (pathinfo($fname, PATHINFO_EXTENSION)=='js') header('Content-type:application/javascript');
		readfile($fname);
	}
	function upload() {
		$ret=array();
        $params=array(
                    $this->data['gspgid_va'][1]=>$this->data['gspgid_va'][2],
                );
        if ($this->data['gspgid_va'][2]==0) {
            $params[$this->data['gspgid_va'][1].'_hash']=$this->data['gspgid_va'][3];
        }

        $rs=new $this->data['gspgid_va'][0];
        $img=$rs->new_record($params);
        $file=$img->File->first(true);
		$file->fill_values($img->File->fetch_image($this->data['src']));
        if($file->File_size) {
			$img->commit();
			return $this->li($img);
		}
		/*
		$ret['src']=$img->src1('small');
		echo(json_encode($ret));
		 */
	}
	function li($f) {
		$tpl=gs_tpl::get_instance();
		$tpl->template_dir=cfg('lib_dir').DIRECTORY_SEPARATOR.'widgets'.DIRECTORY_SEPARATOR.'ImageWebSearch'.DIRECTORY_SEPARATOR.'templates';
		$tpl->assign('i',$f);
		echo $tpl->fetch('li_image_ImageWebSearch.html');
	}

    function search() {
		echo(json_encode($this->search_bing()));
    }
	function search_bing() {
		$ret=false;
		$url="http://www.bing.com/images/search?q=%s";
		$url=sprintf($url,urlencode($this->data['search']));
		$html=html_fetch($url);
		$html=tidy_html($html);
		$this->xml = simplexml_load_string($html);
		if (!$this->xml) return $ret;
		$result = $this->xml->xpath('/html/body//a[@m]');
        if (!$result) {
            return $this->search_notfound();
        }
        foreach ($result as $r) {
			$img=array();
			$src=array();
			$res=explode(",",reset($r['m']));
			list($v,$src)=explode(":",$res[4],2);
			$src=trim($src,"\"");
			$img['tbUrl']=trim($r->img['src2']);
			$img['url']=$src;
			$ret['results'][]=$img;
        }
        return $ret;
	}

	function search_google() {
	   $ret=false;
		$url="http://www.google.ru/search?q=%s";
		$url=sprintf($url,urlencode($this->data['search']));
		$url.="&tbs=isz:m&hl=ru&newwindow=1&prmd=imvns&source=lnms&tbm=isch&sa=X&oi=mode_link&ct=mode&cd=2&biw=1536&bih=827";
		$html=html_fetch($url);
		$html=tidy_html($html);
		$this->xml = simplexml_load_string($html);
		if (!$this->xml) return $ret;
		$result = $this->xml->xpath('/html/body//a[starts-with(@href,"/imgres")]');
        if (!$result) {
            return $this->search_notfound();
        }
		foreach ($result as $r) {
			$img=array();
			$src=array();
			$img['tbUrl']=trim($r->img['src']);
			parse_str(trim($r['href']),$src);
			$img['url']=reset($src);
			$ret['results'][]=$img;
		}
        return $ret;
	}
	function search_notfound(){
            $img=array();
            $ret=array();
            $img['tbUrl']='http://ts2.mm.bing.net/th?id=H.4822764155635017&w=136&h=141&c=7&rs=1&pid=1.7';
            $img['url']='http://ts2.mm.bing.net/th?id=H.4822764155635017&w=136&h=141&c=7&rs=1&pid=1.7';
            $ret['results'][]=$img;
            return $ret;
        }
}
