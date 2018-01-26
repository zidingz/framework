<?php

namespace Swoole\Coroutine\Component;

use Swoole\Coroutine\MySQL as CoMySQL;
use Swoole\IDbRecord;

class MySQL extends Base
{
    function create()
    {
        $db = new CoMySQL;
        if ($db->connect($this->config) === false)
        {
            return false;
        }
        else
        {
            return $db;
        }
    }

    function query($sql)
    {
        /**
         * @var $db CoMySQL
         */
        $db = $this->_getObject();
        if (!$db)
        {
            return false;
        }
       return new MySQLRecordSet($db->query($sql));
    }

}

class MySQLRecordSet implements IDbRecord
{
    public $result;

    function __construct($result)
    {
        $this->result = $result;
    }

    function fetch()
    {
        return $this->result[0];
    }

    function fetchall()
    {
        return $this->result;
    }

    function __get($key)
    {
        return $this->result->$key;
    }
}
