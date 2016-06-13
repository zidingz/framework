<?php
global $php;
$config = $php->config['db'][$php->factory_key];
if (empty($config))
{
    throw new Swoole\Exception\Factory("db->{$php->factory_key} is not found.");
}
$config_use_proxy = $php->config['db'][$php->factory_key]['use_proxy'];
if (!empty($config_use_proxy))
{
    $db = new Swoole\Database\Proxy($config_use_proxy);
}
else
{
    $db = new Swoole\Database($config);
    $db->connect();
}
return $db;
