<?php
define('DEBUG', 'on');
define('WEBPATH', realpath(__DIR__ . '/..'));
//包含框架入口文件
require WEBPATH . '/libs/lib_config.php';

if (empty($argv[2]))
{
    echo "php redis_copy.php [aof_file] [dst redis server].\n";
    echo "etc: php redis_copy.php /data/redis_new_slave/appendonly.aof tcp://192.168.1.73:6001\n";
    die;
}

$aof_file = $argv[1];
$redis_server = $argv[2];
if (!is_file($aof_file))
{
    echo "redis aof file[$aof_file] not exist.\n";
}
Swoole\Redis::syncFromAof($aof_file, $redis_server);