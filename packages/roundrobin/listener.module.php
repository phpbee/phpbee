<?php

class roundrobin_listener {
    static function add($rec,$event) {
        $cfg=new roundrobin_cfg;
        foreach ($cfg->find_records(array('event_name'=>$event)) as $c) {
            self::add_value($c,$rec);
        }
    }

    static function add_value($cfg,$rec) {
        $options=array(
                    'Config_id'=>$cfg->get_id(),
                    'recordset_name'=>$rec->get_recordset_name(),
                    'record_id'=>$rec->get_id(),
                    'slot'=>roundrobin_handler::slot($cfg),
        );
        $rr=new roundrobin;
        $r=$rr->find_records($options)->first();
        if (!$r) {
            $r=$rr->new_record($options);

            $old_rs=new roundrobin;
            $old_options=$options;
            $old_options['slot']=array('field'=>'slot','case'=>'<','value'=>$options['slot']);
            $old_r=$old_rs->find_records($old_options)->first();

            $r->counter=$old_r->counter;


        }

        if ($cfg->rs_intvalue_field) $r->add_intvalue=$rec->{$cfg->rs_intvalue_field};
        if ($cfg->rs_stringvalue_field) $r->add_stringvalue=$rec->{$cfg->rs_stringvalue_field};

        $r->name=$cfg->name;            
        $r->counter++;
        $r->commit();
    }


}
