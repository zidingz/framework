<?php

namespace Swoole\Coroutine\Component;

use Swoole;
use Swoole\Coroutine\Context;
use Swoole\Coroutine\Redis as CoRedis;

class Redis
{
    /**
     * @var \SplQueue
     */
    protected $pool;
    protected $config;

    function __construct($config)
    {
        $this->config = $config;
        $this->pool = new \SplQueue();
        \Swoole::getInstance()->beforeAction([$this, '_createObject']);
        \Swoole::getInstance()->afterAction([$this, '_freeObject']);
    }

    function _createObject()
    {
        if ($this->pool->count() > 0)
        {
            $redis = $this->pool->pop();
        }
        else
        {
            $redis = $this->connect();
        }
        Context::put('redis', $redis);
    }

    function _freeObject()
    {
        $cid = Swoole\Coroutine::getuid();
        if ($cid < 0)
        {
            return;
        }
        $_redis = Context::get('redis');
        if ($_redis)
        {
            $this->pool->push($_redis);
            Context::delete('redis');
        }
    }

    function connect()
    {
        $redis = new CoRedis($this->config);
        if ($redis->connect($this->config['host'], $this->config['port']) === false)
        {
            return false;
        }
        else
        {
            return $redis;
        }
    }

    function get($key)
    {
        /**
         * @var $redis \Redis
         */
        $redis = Context::get('redis');

        return $redis->get($key);
    }
}