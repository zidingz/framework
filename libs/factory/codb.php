<?php
global $php;
if (empty($php->config['db'][$php->factory_key]))
{
    throw new Swoole\Exception\Factory("db->{$php->factory_key} is not found.");
}

$codb = new Swoole\Client\CoMySQL($php->config['db'][$php->factory_key]);
return $codb;
