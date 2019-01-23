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

    protected $current_entity = 0;
    static $threshold_percent = 1.3;
    static $threshold_num = 10;

    function __construct($config)
    {
        if (empty($config['object_id']))
        {
            throw new Swoole\Exception\InvalidParam("require object_id");
        }
        $this->config = $config;
        $this->pool = new \SplQueue();
        $this->type .= '_'.$config['object_id'];
    }

    function _createObject()
    {
        while (true)
        {
            if ($this->pool->count() > 0)
            {
                $object = $this->pool->pop();
                //必须要 Swoole 2.1.1 以上版本
                if (property_exists($object, "connected") and $object->connected === false)
                {
                    continue;
                }
            }
            else
            {
                $object = $this->create();
            }
            break;
        }
        $this->current_entity ++;
        Context::put($this->type, $object);
        return $object;
    }

    function _freeObject()
    {
        $cid = Swoole\Coroutine::getuid();
        if ($cid < 0)
        {
            return;
        }
        $object = Context::get($this->type);
        if ($object)
        {
            if ($this->isReuse()) {
                $this->pool->push($object);
            }
            Context::delete($this->type);
        }
        $this->current_entity ++;
    }

    protected function _getObject()
    {
        return Context::get($this->type);
    }

    private function isReuse()
    {
        $pool_size = $this->pool->count();
        if ($pool_size == 1) {
            return true;
        }
        if ($this->current_entity > 0 && $pool_size > self::$threshold_num) {
            if ($pool_size / $this->current_entity > self::$threshold_percent) {
                return false;
            }
        }
        return true;
    }

    abstract function create();
}