<?php
namespace Swoole;
use Swoole;

class Redis
{
    const READ_LINE_NUMBER = 0;
    const READ_LENGTH = 1;
    const READ_DATA = 2;

    public $_redis;
    public $config;

    public static $prefix = "autoinc_key:";

    static function getIncreaseId($appKey, $init_id = 1000)
    {
        if (empty($appKey))
        {
            return false;
        }
        $main_key = self::$prefix . $appKey;
        //已存在 就加1
        if (\Swoole::$php->redis->exists($main_key))
        {
            $inc = \Swoole::$php->redis->incr($main_key);
            if (empty($inc))
            {
                \Swoole::$php->log->put("redis::incr() failed. Error: ".\Swoole::$php->redis->getLastError());
                return false;
            }
            return $inc;
        }
        //上面的if条件返回false,可能是有错误，或者key不存在，这里要判断下
        else if(\Swoole::$php->redis->getLastError())
        {
            return false;
        }
        //这才是说明key不存在，需要初始化
        else
        {
            $init = \Swoole::$php->redis->set($main_key, $init_id);
            if ($init == false)
            {
                \Swoole::$php->log->put("redis::set() failed. Error: ".\Swoole::$php->redis->getLastError());
                return false;
            }
            else
            {
                return $init_id;
            }
        }
    }

    function __construct($config)
    {
        $this->config = $config;
        $this->connect();
    }

    function connect()
    {
        try
        {
            if ($this->_redis)
            {
                unset($this->_redis);
            }
            $this->_redis = new \Redis();
            if ($this->config['pconnect'])
            {
                $this->_redis->pconnect($this->config['host'], $this->config['port'], $this->config['timeout']);
            }
            else
            {
                $this->_redis->connect($this->config['host'], $this->config['port'], $this->config['timeout']);
            }
            
            if (!empty($this->config['password']))
            {
                $this->_redis->auth($this->config['password']);
            }
            if (!empty($this->config['database']))
            {
                $this->_redis->select($this->config['database']);
            }
        }
        catch (\RedisException $e)
        {
            \Swoole::$php->log->error(__CLASS__ . " Swoole Redis Exception" . var_export($e, 1));
            return false;
        }
    }

    function __call($method, $args = array())
    {
        $reConnect = false;
        while (1)
        {
            try
            {
                $result = call_user_func_array(array($this->_redis, $method), $args);
            }
            catch (\RedisException $e)
            {
                //已重连过，仍然报错
                if ($reConnect)
                {
                    throw $e;
                }

                \Swoole::$php->log->error(__CLASS__ . " [" . posix_getpid() . "] Swoole Redis[{$this->config['host']}:{$this->config['port']}]
                 Exception(Msg=" . $e->getMessage() . ", Code=" . $e->getCode() . "), Redis->{$method}, Params=" . var_export($args, 1));
                $this->_redis->close();
                $this->connect();
                $reConnect = true;
                continue;
            }
            return $result;
        }
        //不可能到这里
        return false;
    }

    /**
     * @param $file
     * @param $dstRedisServer
     * @param int $seek
     * @return bool
     */
    static function syncFromAof($file, $dstRedisServer, $seek = 0)
    {
        $fp = fopen($file, 'r');
        if (!$fp)
        {
            return false;
        }
        //偏移
        if ($seek > 0)
        {
            fseek($fp, $seek);
        }

        //目标Redis服务器
        $dstRedis = stream_socket_client($dstRedisServer, $errno, $errstr, 10);
        if (!$dstRedis)
        {
            return false;
        }

        $n_line = 0;
        $n_success = 0;
        $_send = '';
        $patten = "#^\\*(\d+)\r\n$#";

        readfile:
        while(!feof($fp))
        {
            $line = fgets($fp, 8192);
            if ($line === false)
            {
                echo "line empty\n";
                break;
            }
            $n_line++;
            $r = preg_match($patten, $line);
            if ($r)
            {
                if ($_send)
                {
                    if (Stream::write($dstRedis, $_send) === false)
                    {
                        die("写入Redis失败. $_send");
                    }
                    //清理数据
                    if (fread($dstRedis, 8192) == false)
                    {
                        echo "读取Redis失败. $_send\n";
                        for($i = 0; $i < 10; $i++)
                        {
                            $dstRedis = stream_socket_client($dstRedisServer, $errno, $errstr, 10);
                            if (!$dstRedis)
                            {
                                echo "连接到Redis($dstRedisServer)失败, 1秒后重试.\n";
                                sleep(1);
                            }
                        }
                        if (!$dstRedis)
                        {
                            echo "连接到Redis($dstRedisServer)失败\n";
                            return false;
                        }
                        $_send = $line;
                        continue;
                    }

                    $n_success ++;
                    if ($n_success % 10000 == 0)
                    {
                        echo "KEY: $n_success, LINE: $n_line. 完成\n";
                    }
                }
                $_send = $line;
            }
            else
            {
                $_send .= $line;
            }
        }

        wait:
        //等待100ms后继续读
        sleep(2);
        echo "read eof, seek=".ftell($fp)."\n";
        goto readfile;
    }
}
