<?php
define('DEBUG', 'on');
define('WEBPATH', realpath(__DIR__ . '/..'));
//包含框架入口文件
require WEBPATH . '/libs/lib_config.php';

Swoole\Redis::syncFromAof(__DIR__.'/redis.aof', 'tcp://127.0.0.1:6379');