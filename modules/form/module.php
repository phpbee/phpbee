<?php
gs_dict::append(array(
            ));

class module_form extends gs_base_module implements gs_module {
    function __construct() {
    }
    function install() {
        foreach(array(
                    ) as $r){
            $this->$r=new $r;
            $this->$r->install();
        }
    }

    function get_menu() {
        $ret=array();
        $item=array();
        $item[]='<a href="/admin/form/">form</a>';
        $ret[]=$item;
        return $ret;
    }

    static function get_handlers() {
        $data=array(
                'handler'=>array(
                    ''=>array(
                        'form_handler.process_form', 
                        ),
                    'record'=>array(
                        'form_handler.record', 
                        ),
                    'record/redirect'=>array(
                        'form_handler.record', 
                        'gs_base_handler.redirect', 
                        ),
                    'record/redirect/up'=>array(
                        'form_handler.record', 
                        'gs_base_handler.redirect_up:level:2', 
                        ),
                    'record/redirect/up/1'=>array(
                        'form_handler.record', 
                        'gs_base_handler.redirect_up:level:1', 
                        ),
                    'record/redirect/gl'=>array(
                        'form_handler.record', 
                        'form_handler.redirect_gl', 
                        ),
                    ),
            );
        return self::add_subdir($data,dirname(__file__));
    }

    static function gl($alias,$rec,$data) {
        $fname=dirname(__FILE__).DIRECTORY_SEPARATOR.'gl.php';
        if (file_exists($fname)) {
            $x=include($fname);
            return $x;
        }
        return parent::gl($alias,$rec,$data);
    }

    /*
       static function gl($alias,$rec) {
       if(!is_object($rec)) {
       $obj=new tw_form;
       $rec=$obj->get_by_id(intval($rec));
       }
       switch ($alias) {
       case '___show____':
       return sprintf('/form/show/%s/%d.html',
       date('Y/m',strtotime($rec->date)),
       $rec->get_id());
       break;
       }
       }
     */
}
/*
   class handler_form extends gs_base_handler {
   }
 */






?>
