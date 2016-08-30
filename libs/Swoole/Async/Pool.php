<?php
namespace Swoole\Async;

/**
 * 通用的连接池框架
 * @package Swoole\Async
 */
class Pool
{
    /**
     * 连接池的尺寸，最大连接数
     * @var int $poolSize
     */
    protected $poolSize;

    /**
     * idle connection
     * @var array $resourcePool
     */
    protected $resourcePool = array();

    /**
     * @var \SplQueue
     */
    protected $idlePool;


    /**
     * @var \SplQueue
     */
    protected $taskQueue;

    protected $createFunction;

    protected $config;

    /**
     * @param int $poolSize
     * @param array $config
     * @throws \Exception
     */
    public function __construct($config = array(), $poolSize = 100)
    {
        $this->poolSize = $poolSize;
        $this->taskQueue = new \SplQueue();
        $this->idlePool = new \SplQueue();
        $this->config = $config;
    }

    /**
     * 加入到连接池中
     * @param $resource
     */
    function join($resource)
    {
        //保存到空闲连接池中
        $this->resourcePool[spl_object_hash($resource)] = $resource;
        $this->release($resource);
    }

    /**
     * @param $callback
     */
    function create($callback)
    {
        $this->createFunction = $callback;
    }

    /**
     * 移除资源
     * @param $resource
     * @return bool
     */
    function remove($resource)
    {
        $rid = spl_object_hash($resource);
        if (!isset($this->resourcePool[$rid]))
        {
            return false;
        }

        //重建IdlePool
        $tmpPool = array();
        while(count($this->idlePool) > 0)
        {
            $_resource = $this->idlePool->dequeue();
            if (spl_object_hash($_resource) == $rid)
            {
                continue;
            }
            $tmpPool[] = $_resource;
        }
        //添加到空闲队列
        foreach($tmpPool as $_resource)
        {
            $this->idlePool->enqueue($_resource);
        }
        unset($rid);
        return true;
    }

    /**
     * 请求资源
     * @param callable $callback
     * @return bool
     */
    public function request(callable $callback)
    {
        //入队列
        $this->taskQueue->enqueue($callback);
        //有可用资源
        if (count($this->idlePool) > 0)
        {
            $this->doTask();
        }
        //没有可用的资源, 创建新的连接
        elseif (count($this->resourcePool) < $this->poolSize)
        {
            call_user_func($this->createFunction);
        }
    }

    /**
     * 释放资源
     * @param $resource
     */
    public function release($resource)
    {
        $this->idlePool->enqueue($resource);
        //有任务要做
        if (count($this->taskQueue) > 0)
        {
            $this->doTask();
        }
    }

    protected function doTask()
    {
        $resource = $this->idlePool->dequeue();
        $callback = $this->taskQueue->dequeue();
        call_user_func($callback, $resource);
    }
}