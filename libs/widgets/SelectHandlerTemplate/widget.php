<?php

class gs_widget_SelectHandlerTemplate extends gs_widget_select {}
class gs_data_widget_SelectHandlerTemplate {
	function gd($rec,$k,$hh,$params,$data) {
        $handlers=gs_cacher::load('handlers','config');
        if (isset($handlers['template'])) {
            $variants=array_keys($handlers['template']);
            array_unshift($variants,'');
        }
		$hh[$k]['variants']=array_combine($variants,$variants);
		return $hh;
	}
}

