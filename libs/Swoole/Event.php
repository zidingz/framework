<?php
namespace Swoole;

class Event
{
    /**
     * @var IFace\Queue
     */
	protected $_queue;
    protected $_handles = array();
    protected $config;
    protected $async = false;

    function __construct($config)
    {
        $this->config = $config;
        //同步模式，直接执行函数
        if (isset($config['async']) and $config['async'])
        {
            $class = $config['type'];
            if (!class_exists($class))
            {
                throw new Exception\NotFound("class $class not found.");
            }
            $this->_queue = new $class($config);
            $this->async = true;
        }
    }

    /**
     * 处理器
     * @param $type
     * @param $data
     * @return mixed
     */
    protected function _execute($type, $data)
    {
        if (!isset($this->_handles[$type]))
        {
            $handlers = array();
            $path = \Swoole::$app_path.'/events/'.$type.'.php';
            if (is_file($path))
            {
                $_conf = include $path;
                if ($_conf and isset($_conf['handlers']) and is_array($_conf['handlers']))
                {
                    foreach ($_conf['handlers'] as $h)
                    {
                        if (class_exists($h))
                        {
                            $object = new $h;
                            if ($object instanceof IFace\EventHandler)
                            {
                                $handlers[] = $object;
                                continue;
                            }
                        }
                        trigger_error("invalid event handler[$type|$h]", E_USER_WARNING);
                    }
                }
            }
            $this->_handles[$type] = $handlers;
        }

        foreach($this->_handles[$type] as $handler)
        {
            /**
             * @var  $handler  IFace\EventHandler
             */
            $handler->trigger($type, $data);
        }
        return true;
    }

    /**
     * 触发事件
     * @param $type
     * @param $data
     * @return mixed
     * @throws Exception\NotFound
     */
    function trigger($type, $data)
    {
        /**
         * 同步，直接在引发事件时处理
         */
        if ($this->async)
        {
            return $this->_queue->push(array('type' => $type, 'data' => $data));
        }
        /**
         * 异步，将事件压入队列
         */
        else
        {
            return $this->_execute($type, $data);
        }
    }

    /**
     * 运行工作进程
     */
	function runWorker($worker_num = 1)
    {
        while (true)
        {
            $event = $this->_queue->pop();
            if ($event)
            {
                $this->_execute($event['type'], $event['data']);
            }
            else
            {
                usleep(100000);
            }
        }
	}
}
