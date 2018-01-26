<?php

namespace Swoole\Coroutine\Component;

use Swoole;
use Swoole\Coroutine\Context;

abstract class Base
{
    /**
     * @var \SplQueue
     */
    protected $pool;
    protected $config;
    protected $type;

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
            $redis = $this->create();
        }
        Context::put($this->type, $redis);
    }

    function _freeObject()
    {
        $cid = Swoole\Coroutine::getuid();
        if ($cid < 0)
        {
            return;
        }
        $_redis = Context::get($this->type);
        if ($_redis)
        {
            $this->pool->push($_redis);
            Context::delete($this->type);
        }
    }

    protected function _getObject()
    {
        return Context::get($this->type);
    }

    abstract function create();
}