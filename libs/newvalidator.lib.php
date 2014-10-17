<?php

class gs_validate {
		function get_name () {
				return str_replace('gs_validate_','',get_class($this));
		}
		function description() {
				return $this->get_name();
		}
}

class gs_validate_dummyValid  extends gs_validate {
		function description() {
				return 'always true (dummyValid)';
		}
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				return true;
		}
}



class gs_validate_plain_word  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				preg_match_all('/\w+/',$value,$ret);
				$ret=implode('',$ret[0]);
				return ($ret);
		}
}





class gs_validate_isCCExpDate  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				if(strlen($value) == 0)
						return false;

				if ( is_numeric($data[$params[fieldYear]]) && is_numeric($data[$params[fieldMonth]])) {
						$_month = (int)$data[$params[fieldMonth]];
						$_year = (int)$data[$params[fieldYear]];
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
}






class gs_validate_isCCNum  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				if(strlen($value) == 0)
						return$params['empty']&&TRUE;

				if (substr($value,0,4)=='2222' || substr($value,0,4)=='3333')
						return true;

				// strip everything but digits
				$value = preg_replace('!\D+!', '', $value);
				return $this->validate_cc_number($value);

		}

		function validate_cc_number($cc_number) {
				/* Validate; return value is card type if valid. */
				$false = false;
				$card_type = "";
				$card_regexes = array(
								"/^4\d{12}(\d\d\d){0,1}$/" => "visa",
								"/^5[12345]\d{14}$/"       => "mastercard",
								"/^3[47]\d{13}$/"          => "amex",
								"/^6011\d{12}$/"           => "discover",
								"/^30[012345]\d{11}$/"     => "diners",
								"/^3[68]\d{12}$/"          => "diners",
								);

				foreach ($card_regexes as $regex => $type) {
						if (preg_match($regex, $cc_number)) {
								$card_type = $type;
								break;
						}
				}

				if (!$card_type) {
						return $false;
				}

				/*  mod 10 checksum algorithm  */
				$revcode = strrev($cc_number);
				$checksum = 0; 

				for ($i = 0; $i < strlen($revcode); $i++) {
						$current_num = intval($revcode[$i]);  
						if($i & 1) {  /* Odd  position */
								$current_num *= 2;
						}
						/* Split digits and add. */
						$checksum += $current_num % 10; if
								($current_num >  9) {
										$checksum += 1;
								}
				}

				if ($checksum % 10 == 0) {
						return $card_type;
				} else {
						return $false;
				}
		}
}



class gs_validate_checkField  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				$classname=$params['class'];
				$obj=new $classname;
				return $obj->check_field($params['field'],$value,$params,$params['rec_id']);

		}
}

class gs_validate_checkUnique extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				if (empty($value)) return true;
				$classname=$params['class'];
				$obj=new $classname;
				$func_name=isset($params['func']) ? $params['func'] : 'check_unique';
				return $obj->$func_name($params['field'],$value,$params,$params['rec_id'],$data);

		}
}


class gs_validate_isCCType  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {

				$ccNum=$data[$params[CCNumField]];
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
}




class gs_validate_isDate  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				if(strlen($value) == 0)
						return false;

				return strtotime($value) != -1;
		}
}




class gs_validate_isDateAfter  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {

				if(strlen($value) == 0)
						return false;

				if(!isset($params['field2'])) {
						trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
						return false;
				}

				$_date1 = strtotime($value);
				$_date2 = strtotime($data[$params['field2']]);

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
}




class gs_validate_isDateBefore  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {

				if(strlen($value) == 0)
						return false;

				if(!isset($params['field2'])) {
						trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
						return false;
				}

				$_date1 = strtotime($value);
				$_date2 = strtotime($data[$params['field2']]);

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
}




class gs_validate_isDateEqual  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {

				if(strlen($value) == 0)
						return false;

				if(!isset($params['field2'])) {
						trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
						return false;
				}

				$_date1 = strtotime($value);
				$_date2 = strtotime($data[$params['field2']]);

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
}




class gs_validate_isDateOnOrAfter  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {

				if(strlen($value) == 0)
						return false;

				if(!isset($params['field2'])) {
						trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
						return false;
				}

				$_date1 = strtotime($value);
				$_date2 = strtotime($data[$params['field2']]);

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
}




class gs_validate_isDateOnOrBefore  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {

				if(strlen($value) == 0)
						return false;

				if(!isset($params['field2'])) {
						trigger_error("SmartyValidate: [isDateAfter] parameter 'field2' is missing.");            
						return false;
				}

				$_date1 = strtotime($value);
				$_date2 = strtotime($data[$params['field2']]);

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
}






class gs_validate_isEmail  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {

				if(strlen($value) == 0)
						return true;

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
}




class gs_validate_isEmpty  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				return strlen($value) == 0;
		}
}


class gs_validate_notEqual  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				if(!isset($params['field2'])) {
						trigger_error("SmartyValidate: [isEqual] parameter 'field2' is missing.");            
						return false;
				}
				if(strlen($value) == 0)
						return FALSE;

				return $value != $data[$params['field2']];
		}
}


class gs_validate_isEqual  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				if(!isset($params['field2']) && isset($params['field'])) {
						$params['field2']=$params['field'];
				}
				if(!isset($params['field2'])) {
						trigger_error("SmartyValidate: [isEqual] parameter 'field2' is missing.");            
						return false;
				}
				if(strlen($value) == 0) return FALSE;

				return $value == $data[$params['field2']];
		}
}
class gs_validate_isEqualPassword  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				if(!isset($params['classname'])) {
						trigger_error("SmartyValidate: [isEqualPassword] parameter 'classname' is missing.");            
						return false;
				}
				if(!isset($params['field'])) {
						trigger_error("SmartyValidate: [isEqualPassword] parameter 'field' is missing.");            
						return false;
				}
				if(!isset($params['rec_id'])) {
						trigger_error("SmartyValidate: [isEqualPassword] parameter 'rec_id' (should be auto seted) is missing.");            
						return false;
				}
				if(strlen($value) == 0) return FALSE;

				$rec=record_by_id($params['rec_id'],$params['classname']);
				$encoded_value=$rec->get_recordset()->encode_password($rec,$value);
				return $encoded_value===$rec->{$params['field']};

		}
}









class gs_validate_isFloat  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				if(strlen($value) == 0)
						return false;

				return preg_match('!^\d+\.\d+?$!', $value)==1;
		}
}




class gs_validate_isInt  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				if(strlen($value) == 0)
						return false;        

				return preg_match('!^\d+$!', $value)==1;
		}
}




class gs_validate_isLength  extends gs_validate {
		function description() {
				return parent::description().' min=X max=Y';
		}
		function validate($field,$value,$data=array(),$params=array(),$record=null) {

				if(!isset($params['min'])) {
						trigger_error("SmartyValidate: [isLength] parameter 'min' is missing.");            
						return false;
				}
				if(!isset($params['max'])) {
						trigger_error("SmartyValidate: [isLength] parameter 'max' is missing.");            
						return false;
				}

				if (isset($params['required']) && $params['required']===false && empty($value)) return true;

				$_length = strlen($value);

				if($_length >=$params['min'] && $_length <=$params['max'])
						return true;
				elseif($_length == 0)
						return null;
				else
						return false;
		}
}



class gs_validate_isChecked  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				return 1 && $value;
		}
}

class gs_validate_isNumber  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				if(strlen($value) == 0)
						return isset($params['empty']) &&$params['empty'];        

				return preg_match('!^\d+(\.\d+)?$!', $value)==1;
		}
}




class gs_validate_isOnly  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {

				if(!isset($params['field2'])) {
						trigger_error("SmartyValidate: [isEqual] parameter 'field2' is missing.");            
						return false;
				}
				return (!$value>0 OR !$data[$params['field2']]>0);
		}
}




class gs_validate_isPrice  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				if(strlen($value) == 0)
						return false;

				return preg_match('/^\d+(\.\d{1,2})?$/', $value)==1;
		}
}




class gs_validate_isRange  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				if (isset($params['min'])) $params['low']=$params['min'];
				if (isset($params['max'])) $params['high']=$params['max'];

				if(!isset($params['low']) && !isset($params['high'])) {
						trigger_error("SmartyValidate: [isRange] parameter 'low/high' is missing.");            
						return false;
				}

				if(strlen($value) == 0) return false; 
				if (isset($params['low']) && $value < $params['low']) return false;	
				if (isset($params['high']) && $value > $params['high']) return false;	

				return true;
		}
}






class gs_validate_isRegExp  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				if(!isset($params['validate_regexp'])) {
						trigger_error("SmartyValidate: [isRegExp] parameter 'expression' is missing.");            
						return false;
				}
				if(strlen($value) == 0)
						return false;
				setlocale(LC_ALL, 'de_DE.ISO8859-1');
				$ret = (preg_match($params['validate_regexp'], $value));
				setlocale(LC_ALL, 'C');
				if (isset($params['validate_regexp_inverse'])) $ret=!$ret;
				return $ret;
		}
}




class gs_validate_isURL  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				if(strlen($value) == 0)
						return true;

				return preg_match('!^http(s)?://[\w-]+\.[\w-]+(\S+)?$!i', $value)==1;
		}
}




class gs_validate_notEmpty  extends gs_validate {
		function validate($field,$value,$data=array(),$params=array(),$record=null) {
				return is_array($value) ? count($value)>0 : strlen(trim($value)) > 0;
		}
}

