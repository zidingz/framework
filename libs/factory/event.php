<?php
global $php;
$config = $php->config['event'][$php->factory_key];
if (empty($config) or !isset($config['async']))
{
    throw new Exception("require event[$php->factory_key] config.");
}
return new Swoole\Component\Event($config);
