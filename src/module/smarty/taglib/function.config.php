<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */
function smarty_function_config($params, &$smarty)
{
	$php = SPF\App::getInstance();;
	if(!is_object($php->model->Config)) $php->createModel('Config');
	return $php->model->Config[$params['name']];
}
?>