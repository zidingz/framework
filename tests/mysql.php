<?php
define('DEBUG', 'on');
define('WEBPATH', realpath(__DIR__ . '/..'));
//包含框架入口文件
require WEBPATH . '/libs/lib_config.php';

$config = array(
    'type' => Swoole\Database::TYPE_MYSQLi,
    'host' => '10.10.2.38',
    'user' => 'root',
    'password' => 'root',
    'database' => 'chelun',
);

$db = new \Swoole\Database($config);
$db->connect();
$res = $db->query("select * from test22");
var_dump($res);
