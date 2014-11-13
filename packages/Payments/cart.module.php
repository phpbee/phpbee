<?php

class payments_cart_handler extends gs_handler {
    private $person;
    private $cart;
    public function __construct($data=null,$params=null) {
        parent::__construct($data,$params);

		$this->tpl_dir= dirname(__FILE__).DIRECTORY_SEPARATOR.'___templates';
		if (!file_exists($this->tpl_dir)) $this->tpl_dir=dirname(__FILE__).DIRECTORY_SEPARATOR.'templates';
        $this->tpl=gs_tpl::get_instance();
        $this->tpl->addTemplateDir($this->tpl_dir);

        $this->person=person();
        $cart = $this->person->cart;
        if (!is_a($cart,'payment_cart')) $cart=new payment_cart();

        $this->cart=$cart;

    }
	function get_cart() {
		return $this->cart;
	}
    function __destruct() {
        $this->person->cart=$this->cart;
    }

	function add_record($rec,$qnt=1) {
        $this->cart->add($rec,$qnt);
        $this->person->cart=$this->cart;
		return $this->person->cart;
	}

	function set_currency($Currency_id=1) {
		$this->cart->set_currency($Currency_id);
	}

    function add($d) {
		return $this->add_record($d['last']);
    }
    function add_once($d) {
        $this->cart->add_once($d['last']);
        $this->person->cart=$this->cart;
		return $this->person->cart;
    }
    function remove($d) {
        $this->cart->remove($d['last']);
        $this->person->cart=$this->cart;
    }
    function remove_discount($d) {
        $this->cart->remove_discount($this->data['gspgid_va'][0]);
        $this->person->cart=$this->cart;
        return $d;
    }
    function empty_cart($d) {
        $this->cart->empty_cart();
		$this->person->cart=$this->cart;
        return $d['last'];
    }
    function add_discount_code($d) {

        $rec=record_by_field('code',$this->data['code'],'discount_codes');
        if (!$rec) return array('msg'=>'DISCOUNT_CODE_NOTFOUND','rec'=>$rec);
        if (strtotime('now') > strtotime($rec->expdate)) return array('msg'=>'DISCOUNT_CODE_EXPIRED','rec'=>$rec);
        $this->cart->add_discount_code($rec);
        $this->person->cart=$this->cart;
        return $rec;
    }

    function add_discount_code_form($d) {
        $this->load_template('template');

        $this->form=new g_forms_html();

        $form_html=$this->fetch('template'); //needed to ensure form modifications from template, i.e. $form->add_validator
        $this->form->set_values($this->data);
        if ($this->data['gspgtype']==GS_DATA_GET) return $form_html;

        $validate=$this->form->validate();
        if ($validate!==TRUE && $validate['STATUS']!==TRUE) return $this->fetch('template');
        $clean=$this->form->clean();

        $rec=record_by_field('code',$clean['code'],'discount_codes');
        if (!$rec) {
            $this->form->trigger_error('FORM_ERROR','REC_NOTFOUND');
            return $this->fetch('template');
        }
        if (strtotime('now') > strtotime($rec->expdate)) {
            $this->form->trigger_error('FORM_ERROR','DISCOUNT_CODE_EXPIRED');
            return $this->fetch('template');
        }
        $this->cart->add_discount_code($rec);
        $this->person->cart=$this->cart;
        return $rec;
    }
    protected function fetch($name) {
        if (!isset($this->templates[$name])) throw new gs_exception('form_handler.fetch : can not find template file for '.$name);
        $this->tpl->assign('form',$this->form);
        return $this->tpl->fetch($this->templates[$name]);
    }
    protected function load_template($name) {
        if (!isset($this->params[$name])) return;
        $fname=pathinfo($this->params[$name],PATHINFO_BASENAME);
        $this->templates[$name]=$fname;
    }
}

class payment_cart {
    private $item=array('class'=>null, 'id'=>null,'Currency_id'=>null,'title'=>null,'qnt'=>0,'price'=>0,'total_amount'=>0);
    private $format="%d";

    private $items=array();
    private $discounts=array();
    private $total_items=0;
    private $total_price=0;
    private $total_discount=null;
    private $Currency_id=1;

	function empty_cart() {
		$this->items=array();
		$this->discounts=array();
		$this->total_items=0;
		$this->total_price=0;
		$this->total_discount=null;
		$this->Currency_id=1;
	}

    function get_items($class=null) {
        $ret=array();
        if ($class) {
            if (isset($this->items[$class])) return $this->items[$class];
        } else {
            foreach ($this->items as $rs_name=>$rs_items) {
                foreach ($rs_items as $i) {
                    $ret[]=$i;
                }
            }
        }
        return $ret;
    }
    function get_item($class,$id) {
		if (is_numeric($class)) return $this->get_item_record($class);
        $ret=array();
        $items=$this->get_items($class);
        if (isset($items[$id])) {
            $ret=$items[$id];
        }
        return $ret;
    }
	function get_item_record($num) {
		$items=$this->get_items();
		if (!isset($items[$num])) return new gs_null(GS_NULL_XML);
		$item=$items[$num];
		$rec=record_by_id($item['id'],$item['class']);
		$rec->cart_item=$item;
		return $rec;
	}
    function get_discounts() {
        return $this->discounts;
    }
    function count() {
        return $this->get_total_items();
    }
    function get_total_items() {
        return $this->total_items;
    }
    function get_price() {
        return $this->price;
    }
    function get_total_price() {
        return $this->total_price;
    }
    function get_total_discount() {
        return $this->total_discount;
    }
    function get_currency() {
        return $this->Currency_id;
    }
	function get_currency_record() {
		return record_by_id($this->get_currency(),'Currency');
	}
	function set_currency($Currency_id=1) {
		$this->Currency_id=$Currency_id;
	}
    function get_cart() {
        return $this->items;
    }
    function recalculate() {
        $this->total_items=0;
        $this->price=0;
        $this->total_price=0;

        foreach  ($this->get_items() as $i) {
           $this->total_items+=$i['qnt'];
           $this->price+=$i['total_amount'];
        }

        $discount_percent=null;
        $discount_amount=null;
        foreach  ($this->discounts as $i) {
            if ($i['type']=='PERCENT') $discount_percent=max($discount_percent,$i['discount']);
            if ($i['type']=='AMOUNT') $discount_amount+=$i['discount'];
        }


        $this->total_price=$this->price;

        if ($discount_percent!==null) $this->total_price*=0.01*(100-$discount_percent);
        if ($discount_amount!==null)  $this->total_price-=min($this->items['total_price'],$discount_amount);

        $this->total_price=sprintf($this->format,$this->total_price);
        $this->price=sprintf($this->format,$this->price);
        $this->total_discount=sprintf($this->format,$this->price-$this->total_price);
    }

    function add($rec,$qnt=1) {
       gs_eventer::send('cart_add',$rec);
       $i=$this->items[$rec->get_recordset_name()][$rec->get_id()];
       if (!$i) $i=$this->item;

       $i['class']=$rec->get_recordset_name();
       $i['id']=$rec->get_id();
       $i['Currency_id']=$this->items['Currency_id'];
       //$i['item']=$rec;
       $i['title']=trim($rec);
       $i['qnt']+=$qnt;
       $i['price']=$rec->price;
       $i['total_amount']=$i['price']*$i['qnt'];

        $i['price']=sprintf($this->format,$i['price']);
        $i['total_amount']=sprintf($this->format,$i['total_amount']);

       $this->items[$rec->get_recordset_name()][$rec->get_id()]=$i;

       $this->recalculate();
    }

    function add_discount_code($rec) {
       if (isset($this->discounts[$rec->get_id()])) return;
       $i=array();
       $i['id']=$rec->get_id();
       $i['title']=$rec->code;
       $i['expdate']=$rec->expdate;
       $i['amount']=sprintf("%s%s",$rec->discount,$rec->discount_type);
       $i['discount']=$rec->discount;
       $i['type']=$rec->discount_type=='$' ? 'AMOUNT' : 'PERCENT';

       $this->discounts[$rec->get_id()]=$i;

       $this->recalculate();
    }
    function remove_discount($id) {
        if (isset($this->discounts[$id])) {
            unset($this->discounts[$id]);
            $this->recalculate();
        }
    }
    function add_once($rec) {
       if (isset($this->items[$rec->get_recordset_name()][$rec->get_id()])) return ;
       return $this->add($rec);
    }
    function remove($rec) {
       if (!isset($this->items[$rec->get_recordset_name()][$rec->get_id()])) return ;
       $i=$this->items[$rec->get_recordset_name()][$rec->get_id()];

       $i['qnt']--;
       $i['price']=$rec->price;
       $i['total_amount']=$i['price']*$i['qnt'];

        $i['price']=sprintf($this->format,$i['price']);
        $i['total_amount']=sprintf($this->format,$i['total_amount']);


        if ($i['qnt']<1) unset($this->items[$rec->get_recordset_name()][$rec->get_id()]);

        $this->recalculate();
    }
}
