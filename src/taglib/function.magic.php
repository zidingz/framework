<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
function smarty_function_magic($params, &$smarty)
{
	if(empty($params['func'])) exit(new SPF\Error(509));
	$func = "cms_".$params['func'];
	return $func($params, $smarty);
}
