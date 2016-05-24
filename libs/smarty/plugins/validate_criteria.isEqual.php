<?php

function smarty_validate_criteria_isEqual($value, $empty, &$params, &$formvars)
{

        if (!isset($params['value']) && !isset($params['field2'])) {
                trigger_error("SmartyValidate: [isEqual] parameter 'field2' or 'value' is missing.");
                return false;
        }

        if (strlen($value) == 0) {
                return $empty;
        }

        if (isset($params['field2'])) {
                return $value == $_POST[$params['field2']];
        }
        return $value == $params['value'];
}

