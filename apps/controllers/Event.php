<?php
namespace App\Controller;

use Swoole;

class Event extends Swoole\Controller
{
    function test()
    {
        echo "event trigger\n";
        $res = $this->event->trigger("hello", array(Swoole\Redis::getIncreaseId('queue'), "hello world", __DIR__));
        echo "event trigger\n";
    }
}