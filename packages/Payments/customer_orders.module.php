<?php
require_once('___module.xphp');
class customer_orders extends orders {
    public $table_name='orders';


	function __construct($s=false,$init_opts=false) {
        parent::__construct($s,$init_opts);
        $this->structure['recordsets']['Items']['rs1_name']='orders';
        $this->structure['recordsets']['Items']['foreign_field_name']='orders_id';


    }

	public function fill_values($obj,$data) {
        $this->check_order_expire($obj);    
        $this->make_dl_links($obj);
	}
    static function check_order_expire($o) {
        $o->expired=1;
        if ($o->Status!='approved') return;

        $expdate='1 month';
        $expcfg=record_by_field('key','order_expire_date','lps_config');
        if ($expcfg->value) $expdate=$expcfg->value;

        $pdate=$o->_ctime;

        $p=$o->Payments->find('status=approved')->first();
        if ($p) {
            $pdate=$p->_ctime;
        }


        $o->payment_date=strtotime($pdate);
        $o->expire_date=strtotime($pdate.' +'.$expdate);
        $o->expired=$o->expire_date < strtotime('now');
    }

    function make_dl_links($o) {
        //if($o->expired) return;
        foreach ($o->Items as $i) {
            $mp=array();
            preg_match('/\((\d+( part of (\d+)))\)/',$i->filename,$mp);
            $parts=1;
            if (isset($mp[3]) && $mp[3]>1) $parts=$mp[3];
            $l=array();
            for ($part=1; $part<=$parts;$part++) {
                        $link=array();
                        $link['link']=sprintf("dl/%d/%d/%d",$o->get_id(),$i->get_id(),$part);
                        $link['fullname']=str_replace($mp[1],$part.$mp[2],$i->filename);
                        $link['filename']=basename($link['fullname']);
                        $l[]=$link;

            }
            $i->dl_links=$l;
        }

    }

}
