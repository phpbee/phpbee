<?php
function smarty_function_handler_block($params, &$smarty)
{
                    //$smarty->trigger_error("html_image: extra attribute '$_key' cannot be an array", E_USER_NOTICE);
		    $blocks=$smarty->getTemplateVars('_blocks');
		    $block=$blocks[$params['id']];
		    return $block->show();
}
?>
