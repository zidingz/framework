<?php
define('DEBUG', 'on');
define('WEBPATH', realpath(__DIR__ . '/..'));
//包含框架入口文件
require WEBPATH . '/libs/lib_config.php';

$res = table('user_login')->gets(array(
    'cache' => true,
    //'_cache_key' => 'user_1234',
    //'_cache_lifetime' => 180,
    '_cache_object_id' => 'master',
));

var_dump($res);