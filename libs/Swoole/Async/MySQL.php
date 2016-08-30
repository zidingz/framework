<?php
namespace Swoole\Async;

use Swoole;

class MySQL extends Pool
{
    const DEFAULT_PORT = 3306;

    function __construct($config, $maxConnection = 100)
    {
        if (empty($config['host']))
        {
            throw new Swoole\Exception\InvalidParam("require mysql host option.");
        }
        if (empty($config['port']))
        {
            $config['port'] = self::DEFAULT_PORT;
        }
        parent::__construct($config, $maxConnection);
        $this->create(function () use ($config)
        {
            $db = new \swoole_mysql;
            $db->on('close', function ($db)
            {
                $this->remove($db);
            });
            return $db->connect($config, function ($db, $result)
            {
                $this->join($db);
            });
        });
    }

    function query($sql, callable $callabck)
    {
        $this->request(function (\swoole_mysql $db) use ($callabck, $sql)
        {
            return $db->query($sql, function (\swoole_mysql $db, $result) use ($callabck)
            {
                call_user_func($callabck, $db, $result);
                $this->release($db);
            });
        });
    }
}
