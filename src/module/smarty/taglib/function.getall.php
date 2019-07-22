<?php
function smarty_function_getall($params, &$smarty)
{
	if(!isset($params['from']))
	{
		echo 'No table name!';
		return false;
	}
    if(!isset($params['_name']))
	{
		echo 'No record variable name!';
		return false;
	}
	$php = SPF\App::getInstance();;
	$select = new SPF\SelectDB($php->db);
	$select->call_by = 'func';
	if(!isset($params['order'])) $params['order'] = 'id desc';
	$select->put($params);
	if(isset($params['page']))
	{
		$select->paging();
        $pager = $select->pager;
		$smarty->assign("pager",array('total'=>$pager->total,'render'=>$pager->render()));
	}
	$records = $select->getall();
	$smarty->_tpl_vars[$params['_name']] = $records;
}