<?php
define('DEBUG', 'on');
define('WEBPATH', realpath(__DIR__ . '/..'));
//包含框架入口文件
require WEBPATH . '/libs/lib_config.php';

Swoole\Redis::syncFromAof('/data/redis_new_slave/appendonly.aof', 'tcp://192.168.1.73:6001');