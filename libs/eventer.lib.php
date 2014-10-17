<?php

class gs_eventer {
    private $path;
    private $events=array();
    private $modified=false;
    private $performed_events=array();

    function __construct() {
        $this->path=dirname(__FILE__).DIRECTORY_SEPARATOR.'eventer.lib.txt';
        if (class_exists('gs_cacher')) {
            $this->events=gs_cacher::load('events','eventer');
            $this->performed_events=gs_cacher::load('performed_events','eventer');
        } else if (file_exists($this->path)) {
            $this->events=unserialize(file_get_contents($path));
        }
        $this->init_performed_events=$this->performed_events;
    }

    function __destruct() {
        if ($this->modified) {
            if (class_exists('gs_cacher')) {
                $this->events=gs_cacher::save($this->events,'eventer','events');
            } else {
                file_put_contents($this->path,serialize($this->events));
            }
        }
	if (sizeof($this->performed_events)>sizeof($this->init_performed_events)) {
                $this->events=gs_cacher::save($this->performed_events,'eventer','performed_events');
                $this->events=gs_cacher::save(implode("\n",$this->performed_events),'eventer','performed_events.txt');
	}
    }

    static function &get_instance()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new gs_eventer();
        }
        return $instance;
    }

    public function make_event($event_name,$data,$generator) {
	$this->performed_events[$event_name]=$event_name;

        if (isset($this->events[$event_name])) {
            foreach ($this->events[$event_name] as $method) {

                mlog(sprintf(" %s %s",
                        $event_name,
                        is_object($data) ? 
                            is_a($data,'gs_record') ? $data->get_recordset_name() : get_class($data) 
                            : gettype($data)));


                switch ($method['type']) {
                case 'function':
                    call_user_func($method['method'],$data,$event_name,$generator);
                    break;
                case 'static':
                    call_user_func(array($method['classname'],$method['method']),$data,$event_name,$generator);
                    break;
                }
            }
        }
    }

    static public function send($event,$generator=null,$data=null) {
       //mlog("eventer.send start $event");
        if (!$data) $data=$generator;

        $ev=gs_eventer::get_instance();
        $ev->make_event($event,$data,$generator);

        if (!is_object($generator)) return;

        $classes=class_parents($generator);
        $classes[]=get_class($generator);
        foreach ($classes as $class) {
            $event_name=$class.'_'.$event;
            $ev->make_event($event_name,$data,$generator);
        }
        //mlog("eventer.send finished $event");
    }

    static public function subscribe ($event_name,$method) {
        $ev=gs_eventer::get_instance();
        $ev->subscribe_event($event_name,$method);
    }

    static public function unsubscribe ($event_name,$method) {
        $ev=gs_eventer::get_instance();
        $ev->unsubscribe_event($event_name,$method);
    }
    static function clean_subscribes() {
        $ev=gs_eventer::get_instance();
        $ev->_clean_subscribes();
    }
    function _clean_subscribes() {
        $this->modified=true;
        $this->events=array();
    }


    public function subscribe_event($event_name,$method) {
        $this->modified=true;
        $this->events[$event_name][$method]=$this->_parse_method_name($method);
    }

    public function unsubscribe_event($event_name,$method) {
        $this->modified=true;
        unset($this->events[$event_name][$method]);
    }

    private function _parse_method_name($method) {
        $dynamic=explode(".",$method);
        $static=explode("::",$method);

        if (count($static)==2) {
            return array('type'=>'static','classname'=>$static[0],'method'=>$static[1]);
        }
        if (count($dynamic)==2) {
            return array('type'=>'dynamic','classname'=>$dynamic[0],'method'=>$dynamic[1]);
        }
        return array('type'=>'function','classname'=>null,'method'=>$method);
    }
}

/*
echo "Events lib<br><br>";

gs_eventer::subscribe('numerator_generate','operator:sum');
gs_eventer::subscribe('digits_generate','operator:subtraction');

$n=new numerator();
$n->generate();

class digits {
}

class numerator extends digits {
    function generate() {
        $a=rand(10,99);
        $b=rand(100,200);
        printf("A=%d B=%d<br>",$a,$b);
        gs_eventer::send('generate',$this,array($a,$b));
    }
}

class operator {
    static function sum($event_name,$data) {
        $a=$data[0];
        $b=$data[1];
        echo "Sum ($event_name)=".($a+$b);
        echo "<br>";
    }

    static function subtraction($event_name,$data) {
        $a=$data[0];
        $b=$data[1];
        echo "Sub=".($a-$b);
        echo "<br>";
    }
}
*/
