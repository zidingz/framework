<?php
global $php;
$user = new Swoole\Auth($php->config['user'],$php->factory_key);
return $user;
