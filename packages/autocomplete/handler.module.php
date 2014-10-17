<?php

class autocomplete_handler extends gs_handler {

    function input_jquery() {
        $data=array();
        $data['search']=$this->data['term'];
        return $data;
    }

    function process($d) {
        $ret=array();
        $s=$d['input']['search'];
        $cfg=$d['cfg'];
        $options=array();
        $fields=string_to_params(str_replace(',',' ',$cfg->fields));

        if (!$fields) {
            foreach($cfg->Fields as $f) {
                $fields[$f->name]=$f->name;
            }
        }
        foreach ($fields as $f) {
            $options['OR'][]=array(
                'field'=>$f,
                //'case'=>$cfg->fulltext ? 'FULLTEXT' : 'LIKE',
                'case'=>$cfg->searchtype,
                'value'=>$s,
            );
        }

        if (!$options) return $ret;

        $rsname=$cfg->Recordset->first()->name;
        $rs=new $rsname;
        $rs->find_records($options);
        if ($cfg->limit) $rs->limit($cfg->limit);

        $values=$rs->get_values($fields);

        foreach ($values as $id=>$val) {
            //$ret[$id]=implode(' ',$val);
            $ret[]=array('label'=>implode(' ',$val),'value'=>$id);
        }

        return $ret;


    }

    function array_values($d) {
        $ret=array();
        foreach ($d['last'] as $a) {
            $ret[]=$a['label'];
        }
        return $ret;

    }

    function output_json($d) {

        echo json_encode($d['last']);
        return true;

    }

}
