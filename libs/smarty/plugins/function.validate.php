<?php

/**
 * Project:     SmartyValidate: Form Validator for the Smarty Template Engine
 * File:        function.validate.php
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

function smarty_function_validate($params, &$smarty) {
    global $_POST;
    $formvars=$_POST;
    if (empty($formvars) || $formvars[_skipvalidate]) return;

    
	    $url=parse_url($_SERVER[HTTP_REFERER]);
	    $requrl=parse_url($_SERVER[REQUEST_URI]);


	    if ($url[path]!=$requrl[path]) {
		    $smarty->_validate_processed=0;
		    return;
	    }
    
    if (strlen($params['field']) == 0) {
        $smarty->trigger_error("validate: missing 'field' parameter");
        return;
    }
    if (strlen($params['criteria']) == 0) {
        $smarty->trigger_error("validate: missing 'criteria' parameter");
        return;
    }
    if(strlen($params['criteria']) == 0) {        
            $smarty->trigger_error("validate: parameter 'criteria' missing.");
            return;                
    }
    //mydump($params);
    $criteria=$params['criteria'];
    $_func_name = 'smarty_validate_criteria_' . $criteria;

    if(!function_exists($_func_name)) {
	$smarty->smarty->include_plugin('validate_criteria',$criteria);
	/*
	if($_plugin_file = $smarty->_get_plugin_filepath('validate_criteria', $criteria)) {
	    include_once($_plugin_file);
	    } 
	*/
    }
    $value=$smarty->getTemplateVars($params[field]);
    $smarty->_validate_processed=1;
    if (!$_func_name($value, $empty, $params, $formvars)) {
	$smarty->_validate_error=1;
	$smarty->_validate_error_fields.=$params[field]." | ";
	return $params[message];
    }

    
      
}

?>
