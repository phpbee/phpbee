<?php

function gs_validate_criteria_isEqualSession($value, $params=null, $formvars=null) {
        if(!isset($params['session_name'])) {
                trigger_error("SmartyValidate: [isEqualSession] parameter 'session_name' is missing.");            
                return false;
        }
        if(strlen($value) == 0)
            return FALSE;

        return strtoupper($value) == strtoupper(gs_session::load($params['session_name']));
}

function gs_validate_criteria_dummyValid($value, $params=null, $formvars=null) {
    return true;
}



function gs_validate_criteria_plain_word($value, $params=null, $formvars=null) {
	preg_match_all('/\w+/',$value,$ret);
	$ret=implode('',$ret[0]);
	return ($ret);
}





function gs_validate_criteria_isCCExpDate($value, $params=null, $formvars=null) {
    if(strlen($value) == 0)
        return $empty;

    if ( is_numeric($formvars[$params[fieldYear]]) && is_numeric($formvars[$params[fieldMonth]])) {
        $_month = (int)$formvars[$params[fieldMonth]];
        $_year = (int)$formvars[$params[fieldYear]];
    } else {
        if(!preg_match('!^(\d+)\D+(\d+)$!', $value, $_match))
            return false;
        $_month = $_match[1];
        $_year = $_match[2];
    }

    if(strlen($_year) == 2)
        $_year = substr(date('Y', time()),0,2) . $_year;

    if(!is_int($_month))
        return false;
    if($_month < 1 || $_month > 12)
        return false;
    if(!is_int($_year))
        return false;
    if(date('Y',time()) > $_year)
        return false;
    if(date('Y',time()) == $_year && date('m', time()) >= $_month)
        return false;

    return true;

}




 

function gs_validate_criteria_isCCNum($value, $params=null, $formvars=null) {
	if(strlen($value) == 0)
		return $params['empty']&&TRUE;

	if (substr($value,0,4)=='2222' || substr($value,0,4)=='3333')
		return true;
	
	global $_CONF;
	if (!empty($value) && ($value==$_CONF[auth_testcard_approve] || $value==$_CONF[auth_testcard_decline]))
		return true;

	// strip everything but digits
	$value = preg_replace('!\D+!', '', $value);

	if (empty($value))
		return false;

	$_c_digits = preg_split('//', $value, -1, PREG_SPLIT_NO_EMPTY);

	$_max_digit   = count($_c_digits)-1;
	$_even_odd    = $_max_digit % 2;

	$_sum = 0;
	for ($_count=0; $_count <= $_max_digit; $_count++) {
		$_digit = $_c_digits[$_count];
		if ($_even_odd) {
			if ($_digit > 9) {
				$_digit = substr($_digit, 1, 1) + 1;
			}
		}
		$_even_odd = 1 - $_even_odd;
		$_sum += $_digit;
	}
	$_sum = $_sum % 10;
	if($_sum)
		return false;
	return true;

}



function gs_validate_criteria_checkField($value, $params=null, $formvars=null) {
	$classname=$params['class'];
	$obj=new $classname;
	return $obj->check_field($params['field'],$value,$params);

}

 

function gs_validate_criteria_isCCType($value, $params=null, $formvars=null) {

	$ccNum=$formvars[$params[CCNumField]];
	$ccDigit=substr($ccNum,0,1);
	$ccType=$value;

	if (substr($ccNum,0,4)=='2222' || substr($ccNum,0,4)=='3333')
		return true;

	if ($ccType=='Visa' && $ccDigit!=4) 
		return false;
	if ($ccType=='Mastercard' && $ccDigit!=5) 
		return false;
	if ($ccType=='Amex' && $ccDigit!=3) 
		return false;
	if ($ccType=='JCB' && $ccDigit!=3) 
		return false;
	if ($ccType=='Diners club' && $ccDigit!=6) 
		return false;

	return true;

}




function gs_validate_criteria_isDate($value, $params=null, $formvars=null) {
    if(strlen($value) == 0)
        return $empty;

    return strtotime($value) != -1;
}




function gs_validate_criteria_isDateAfter($value, $params=null, $formvars=null) {

        if(strlen($value) == 0)
            return $empty;

        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
                return false;
        }
        
        $_date1 = strtotime($value);
        $_date2 = strtotime($formvars[$params['field2']]);
        
        if($_date1 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field' is not a valid date.");            
                return false;
        }
        if($_date2 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is not a valid date.");            
                return false;
        }
                
        return $_date1 > $_date2;
}




function gs_validate_criteria_isDateBefore($value, $params=null, $formvars=null) {

        if(strlen($value) == 0)
            return $empty;

        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
                return false;
        }
        
        $_date1 = strtotime($value);
        $_date2 = strtotime($formvars[$params['field2']]);
        
        if($_date1 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field' is not a valid date.");            
                return false;
        }
        if($_date2 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is not a valid date.");            
                return false;
        }
                
        return $_date1 < $_date2;
}




function gs_validate_criteria_isDateEqual($value, $params=null, $formvars=null) {

        if(strlen($value) == 0)
            return $empty;

        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
                return false;
        }
        
        $_date1 = strtotime($value);
        $_date2 = strtotime($formvars[$params['field2']]);
        
        if($_date1 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field' is not a valid date.");            
                return false;
        }
        if($_date2 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is not a valid date.");            
                return false;
        }
                
        return $_date1 == $_date2;
}




function gs_validate_criteria_isDateOnOrAfter($value, $params=null, $formvars=null) {

        if(strlen($value) == 0)
            return $empty;

        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
                return false;
        }
        
        $_date1 = strtotime($value);
        $_date2 = strtotime($formvars[$params['field2']]);
        
        if($_date1 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field' is not a valid date.");            
                return false;
        }
        if($_date2 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is not a valid date.");            
                return false;
        }
                
        return $_date1 >= $_date2;
}




function gs_validate_criteria_isDateOnOrBefore($value, $params=null, $formvars=null) {

        if(strlen($value) == 0)
            return $empty;

        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
                return false;
        }
        
        $_date1 = strtotime($value);
        $_date2 = strtotime($formvars[$params['field2']]);
        
        if($_date1 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field' is not a valid date.");            
                return false;
        }
        if($_date2 == -1) {
                trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is not a valid date.");            
                return false;
        }
                
        return $_date1 <= $_date2;
}




 

function gs_validate_criteria_isEmail($value, $params=null, $formvars=null) {

    if(strlen($value) == 0)
        return false;

    // regex taken from Jeffrey Freidl e-mail validation example
    // http://public.yahoo.com/~jfriedl/regex/email-opt.pl
    $_regex = '[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|"[^\\\x80-\xff\n\015"]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015"]*)*")[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:\.[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|"[^\\\x80-\xff\n\015"]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015"]*)*")[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*)*@[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:\.[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*)*|(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|"[^\\\x80-\xff\n\015"]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015"]*)*")[^()<>@,;:".\\\[\]\x80-\xff\000-\010\012-\037]*(?:(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)|"[^\\\x80-\xff\n\015"]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015"]*)*")[^()<>@,;:".\\\[\]\x80-\xff\000-\010\012-\037]*)*<[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:@[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:\.[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*)*(?:,[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*@[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:\.[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*)*)*:[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*)?(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|"[^\\\x80-\xff\n\015"]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015"]*)*")[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:\.[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|"[^\\\x80-\xff\n\015"]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015"]*)*")[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*)*@[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:\.[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*(?:[^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff]+(?![^(\040)<>@,;:".\\\[\]\000-\037\x80-\xff])|\[(?:[^\\\x80-\xff\n\015\[\]]|\\[^\x80-\xff])*\])[\040\t]*(?:\([^\\\x80-\xff\n\015()]*(?:(?:\\[^\x80-\xff]|\([^\\\x80-\xff\n\015()]*(?:\\[^\x80-\xff][^\\\x80-\xff\n\015()]*)*\))[^\\\x80-\xff\n\015()]*)*\)[\040\t]*)*)*>)';    
    
    // in case value is several addresses separated by newlines
    $_addresses = preg_split('![\n\r]+!', $value);

    foreach($_addresses as $_address) {
		if(!preg_match("/^$_regex$/", $_address)) {
            return false;
        }
    }
    return true;
}




function gs_validate_criteria_isEmpty($value, $params=null, $formvars=null) {
    return strlen($value) == 0;
}


function gs_validate_criteria_notEqual($value, $params=null, $formvars=null) {
        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isEqual] parameter 'field2' is missing.");            
                return false;
        }
        if(strlen($value) == 0)
            return FALSE;

        return $value != $formvars[$params['field2']];
}


function gs_validate_criteria_isEqual($value, $params=null, $formvars=null) {
        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isEqual] parameter 'field2' is missing.");            
                return false;
        }
        if(strlen($value) == 0)
            return FALSE;

        return $value == $formvars[$params['field2']];
}









function gs_validate_criteria_isFloat($value, $params=null, $formvars=null) {
    if(strlen($value) == 0)
        return $empty;

    return preg_match('!^\d+\.\d+?$!', $value)==1;
}




function gs_validate_criteria_isInt($value, $params=null, $formvars=null) {
        if(strlen($value) == 0)
            return false;        
        
        return preg_match('!^\d+$!', $value)==1;
}




function gs_validate_criteria_isLength($value, $params=null, $formvars=null) {

        if(!isset($params['min'])) {
                trigger_error("SmartyValidate: [isLength] parameter 'min' is missing.");            
                return false;
        }
        if(!isset($params['max'])) {
                trigger_error("SmartyValidate: [isLength] parameter 'max' is missing.");            
                return false;
        }

        $_length = strlen($value);
                
        if($_length >= $params['min'] && $_length <= $params['max'])
            return true;
        elseif($_length == 0)
            return null;
        else
            return false;
}



function gs_validate_criteria_isChecked($value, $params=null, $formvars=null) {
    return 1 && $value;
}

function gs_validate_criteria_isNumber($value, $params=null, $formvars=null) {
    if(strlen($value) == 0)
        return isset($params['empty']) && $params['empty'];        

    return preg_match('!^\d+(\.\d+)?$!', $value)==1;
}




function gs_validate_criteria_isOnly($value, $params=null, $formvars=null) {
	
        if(!isset($params['field2'])) {
                trigger_error("SmartyValidate: [isEqual] parameter 'field2' is missing.");            
                return false;
        }
        return (!$value>0 OR !$formvars[$params['field2']]>0);
}




function gs_validate_criteria_isPrice($value, $params=null, $formvars=null) {
    if(strlen($value) == 0)
        return $empty;

    return preg_match('/^\d+(\.\d{1,2})?$/', $value)==1;
}




function gs_validate_criteria_isRange($value, $params=null, $formvars=null) {
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




 

function gs_validate_criteria_isRegExp($value, $params=null, $formvars=null) {
        if(!isset($params['expression'])) {
                trigger_error("SmartyValidate: [isRegExp] parameter 'expression' is missing.");            
                return false;
        }
        if(strlen($value) == 0)
            return $empty;
        setlocale(LC_ALL, 'de_DE.ISO8859-1');
        $ret = (preg_match($params['expression'], $value));
        setlocale(LC_ALL, 'C');
	if ($params['inverse']) $ret=!$ret;
	return $ret;
}




function gs_validate_criteria_isURL($value, $params=null, $formvars=null) {
    if(strlen($value) == 0)
        return $params['empty'];        

    return preg_match('!^http(s)?://[\w-]+\.[\w-]+(\S+)?$!i', $value)==1;
}




function gs_validate_criteria_notEmpty($value, $params=null, $formvars=null) {
    return is_array($value) || strlen(trim($value)) > 0;
}


?>
