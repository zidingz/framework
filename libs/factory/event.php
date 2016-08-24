<?php
global $php;
$config = $php->config['event'][$php->factory_key];
if (empty($config) or !isset($config['async']))
{
    throw new Exception("require event[$php->factory_key] config.");
}
if ($config['async'] && empty($config['type']))
{
    throw new Exception("\"type\" config required in event aysnc mode");
}
return new Swoole\Component\Event($config);
