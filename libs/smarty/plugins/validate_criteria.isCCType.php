<?php

/**
 * Project:     SmartyValidate: Form Validator for the Smarty Template Engine
 * File:        validate_criteria.isCCNum.php
 * Author:      Monte Ohrt <monte@ispi.net>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @link http://www.phpinsider.com/php/code/SmartyValidate/
 * @copyright 2001-2004 ispi of Lincoln, Inc.
 * @author Monte Ohrt <monte@ispi.net>
 * @package SmartyValidate
 * @version 2.3-dev
 */
 
 /**
 * test if a value is a valid credit card checksum
 *
 * @param string $value the value being tested
 * @param boolean $empty if field can be empty
 * @param array params validate parameter values
 * @param array formvars form var values
 */
function smarty_validate_criteria_isCCType($value, $empty, &$params, &$formvars) {

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

?>
