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

        $step =  self::READ_LINE_NUMBER;
        $n_lines = 0;
        $n_bytes = 0;
        $command = array();
        $n_success = 0;
        $_send = '';

        readfile:
        while(!feof($fp))
        {
            step_switch:
            switch($step)
            {
                case self::READ_LINE_NUMBER:
                    $line = fgets($fp, 8192);
                    //没有数据
                    if ($line === false)
                    {
                        goto wait;
                    }
                    $r = preg_match('/\*(\d+)/', $line, $match);
                    if ($r)
                    {
                        $n_lines = $match[1];
                        $command = array();
                        $step = self::READ_LENGTH;
                        $_send = $line;
                    }
                    else
                    {
                        exit("error data[1]. seek=".ftell($fp)."\n{$line}\n");
                    }
                    break;
                case self::READ_LENGTH:
                    $oldSeek = ftell($fp);
                    read_length:
                    $line = fgets($fp, 8192);
                    if ($line === false)
                    {
                        sleep(1);
                        fseek($fp, $oldSeek);
                        echo "read length[4] failed.\n";
                        var_dump($_send);
                        goto read_length;
                    }

                    $r = preg_match('/\$(\d+)/', $line, $match);
                    if ($r)
                    {
                        $n_bytes = $match[1];
                        $_send .= $line;
                        //空字符串
                        if ($n_bytes == 0)
                        {
                            goto step_switch;
                        }
                        else
                        {
                            $step = self::READ_DATA;
                        }
                    }
                    else
                    {
                        exit("error data[2]. seek=".ftell($fp)."\n{$line}\n");
                    }
                    break;
                case self::READ_DATA:
                    $oldSeek = ftell($fp);
                    read_data:
                    $data = Stream::read($fp, $n_bytes);
                    if (strlen($data) === 0)
                    {
                        sleep(1);
                        fseek($fp, $oldSeek);
                        echo "read data[4] failed.\n";
                        var_dump($_send);
                        goto read_data;
                    }
                    $command[] = $data;
                    fgets($fp, 8192);
                    $_send .= $data."\r\n";
                    if (count($command) == $n_lines)
                    {
                        $step = self::READ_LINE_NUMBER;
                        //$_send = implode(" ", $command)."\r\n";
                        if (strcasecmp("select", $command[0]) == 0)
                        {
                            goto step_switch;
                        }
                        if (Stream::write($dstRedis, $_send) != strlen($_send))
                        {
                            exit("write data failed. seek=".ftell($fp)."\n$command\n");
                        }
                        if (fread($dstRedis, 8192) == false)
                        {
                            exit("read data failed. seek=".ftell($fp)."\n");
                        }
                        $n_success ++;
                        if ($n_success % 1000 == 0)
                        {
                            echo "$n_success finish.\n";
                        }
                    }
                    else
                    {
                        $step = self::READ_LENGTH;
                    }
                    break;
            }
        }

        wait:
        //等待100ms后继续读
        sleep(2);
        echo "read eof, seek=".ftell($fp)."\n";
        goto readfile;
    }
}
