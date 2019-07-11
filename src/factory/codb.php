<?php
$app = SPF\App::getInstance();
if (empty($php->config['db'][$php->factory_key]))
{
    throw new SPF\Exception\Factory("codb->{$php->factory_key} is not found.");
}
$codb = new SPF\Client\CoMySQL($php->factory_key);
return $codb;
