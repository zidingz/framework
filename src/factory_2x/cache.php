<?php
$configs = App::getInstance()->config['cache'];
if (empty($configs[App::getInstance()->factory_key]))
{
    throw new SPF\Exception\Factory("cache->".App::getInstance()->factory_key." is not found.");
}
return SPF\Factory::getCache(Swoole::$php->factory_key);