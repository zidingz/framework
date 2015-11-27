<?php
define('DEBUG', 'on');
define('WEBPATH', realpath(__DIR__ . '/..'));
//包含框架入口文件
require WEBPATH . '/libs/lib_config.php';

//$cli = new Swoole\Client\WebSocket('127.0.0.1', 9501);
//$res = $cli->connect();
////测试各种大包
//for($i=0; $i< 1000; $i++)
//{
//    $_send = str_repeat('A', rand(7000, 90000));
//    $n = $cli->send($_send);
//    echo "sent: ".strlen($_send).' bytes, '."n=$n\n";
//    $frame = $cli->recv();
//    echo "recv: ".strlen($frame->data)." bytes\n";
//}


$cli = new Swoole\Async\WebSocket('127.0.0.1', 9501);

$cli->on('open', function(Swoole\Async\WebSocket $o, $header){
    var_dump($header);
    $_send = str_repeat('A', rand(7000, 90000));
    $n = $o->send($_send);
    echo "sent: " . strlen($_send) . ' bytes, ' . "n=$n\n";
});

$cli->on('message', function(Swoole\Async\WebSocket $o, $frame){
    echo "recv: ".strlen($frame->data)." bytes\n";
    $o->disconnect();
});

$cli->connect();