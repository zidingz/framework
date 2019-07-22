<?php
function smarty_block_select($params, $body, &$smarty)
{
	if (is_null($body)) {
		return;
	}
	$php = SPF\App::getInstance();;
	$select = new SPF\SelectDB($php->db);
	$select->put($params);
	return SwooleTemplate::parse_loop($select->getall(),$body);
}
