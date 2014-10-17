<?php
function smarty_validate_criteria_isRange($value, $empty, &$params, &$formvars) {
		if (isset($params['min'])) $params['low']=$params['min'];
		if (isset($params['max'])) $params['high']=$params['max'];
        if(!isset($params['low'])) {
                trigger_error("SmartyValidate: [isRange] parameter 'low' is missing.");            
                return false;
        }
        if(!isset($params['high'])) {
                trigger_error("SmartyValidate: [isRange] parameter 'high' is missing.");            
                return false;
        }
        if(strlen($value) == 0)
            return $empty;
        
        return ($value >= $params['low'] && $value <= $params['high']);
}

