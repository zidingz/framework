<?php
namespace Swoole\Queue;

/**
 * Redis内存队列
 */
class Redis implements \Swoole\IFace\Queue
{
    /**
     * @var \redis
     */
    protected $redis;
    protected $key = 'swoole:queue';

    function __construct($config)
    {
        if (empty($config['id']))
        {
            $config['id'] = 'master';
        }
        $this->redis = \Swoole::$php->redis($config['id']);
        if (!empty($config['key']))
        {
            $this->key = $config['key'];
        }
    }

    /**
     * 出队
     * @return bool|mixed
     */
    function pop()
    {
        $ret = $this->redis->lPop($this->key);
        if ($ret)
        {
            return unserialize($ret);
        }
        else
        {
            return false;
        }
    }

    /**
     * 入队
     * @param $data
     * @return int
     */
    function push($data)
    {
        return $this->redis->rPush($this->key, serialize($data));
    }
}