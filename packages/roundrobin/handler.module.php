<?php

class roundrobin_exception extends gs_exception {}

class roundrobin_handler extends gs_handler {

    static function slot($cfg,$num=0) {
        if (!is_a($cfg,'gs_record') && is_numeric($cfg)) {
            $cfg=record_by_id($cfg,'roundrobin_cfg');
        }
        if (!is_a($cfg,'gs_record') || !$cfg ||  $cfg->get_recordset_name()!='roundrobin_cfg')  throw new roundrobin_exception('RoundRobin config not found ');

        $slottime= strtotime($cfg->timeslot)-time();
        $slot=ceil(time()/$slottime);

        return $slot+$num;

    }

    function rotate($d) {

        $cfg=new roundrobin_cfg;
        foreach ($cfg->find_records(array()) as $c) {
            $this->rotate_cfg($c);
        }
    }

    function rotate_cfg($cfg) {
        $rr=new roundrobin;
        $rr->find_records(array(
                'Config_id'=>$cfg->get_id(),
                'slot'=>0,
                ));
        foreach ($rr as $r) {
            if ($r->_ctime && strtotime($cfg->timeslot, strtotime($r->_ctime))<time()) {
                $this->rotate_rr($r,$cfg);
            }
        }
    }

    function rotate_rr($rb,$cfg) {
        $slottime= strtotime($cfg->timeslot)-time();
        $options=array(
            'Config_id'=>$cfg->get_id(),
            'recordset_name'=>$rb->recordset_name,
            'record_id'=>$rb->record_id,
            );

        $rr=new roundrobin;
        $rr->find_records($options)->orderby('slot desc');
        foreach ($rr as $r) {
            if ($r->counter>0)  break;
            $r->delete();
        }
        foreach ($rr as $r) {
            $slot=floor((time()-strtotime($r->_ctime))/$slottime);
            $r->slot=$slot;
            if ($r->slot>=$cfg->slotcount) $r->delete();
        }
        $rr->commit();
        $rr->find_records($options)->orderby('slot desc');
        if ($rr->count()>0) {
            $r=$rr->new_record($options);
            $r->name=$cfg->name;
            $r->slot=0;
            $rr->commit();
        }
    }


    function stack($d) {

        $cfg=new roundrobin_cfg;
        foreach ($cfg->find_records(array()) as $c) {
            $this->stack_cfg($c);
            $this->flush_rr($c);
        }

    }

    private function stack_cfg($cfg) {
        foreach ($cfg->Stack as $s) {
            $this->stack_rr($s,$cfg);
        }
    }

    private function stack_rr($stack,$cfg) {

        $rs=new roundrobin_stack;
        $gs_connector=$rs->get_connector();


        $lslot=-($stack->slotcount+$stack->startslot);
        $fslot=-$stack->startslot;
        $que=sprintf("call rr_stack(%d,%d,'%s',%d,%d);",$cfg->get_id(),$stack->get_id(),$stack->name,self::slot($cfg,$fslot),self::slot($cfg,$lslot));

        $gs_connector->query($que);
        md($que,1);

        return;

    }

    private function flush_rr($cfg) {
        $rs=new roundrobin_stack;
        $gs_connector=$rs->get_connector();
        $fslot=-$stack->startslot;

        $que=sprintf("delete from roundrobin_stack where Config_id=%d and slot < %d",$cfg->get_id(),self::slot($cfg,-$cfg->slotcount));
        $gs_connector->query($que);
        md($que,1);

        $que=sprintf("delete from roundrobin where Config_id=%d and slot < %d",$cfg->get_id(),self::slot($cfg,-$cfg->slotcount));
        $gs_connector->query($que);
        md($que,1);

        return;


    }
}
