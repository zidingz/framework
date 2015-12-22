<?php
namespace Swoole\Client;
use Swoole\Database\MySQLi;
use Swoole\Database\MySQLiRecord;

/**
 * 并发MySQL客户端
 * concurrent mysql client
 * Class CoMySQL
 * @package Swoole\Client
 */
class CoMySQL
{
    protected $config;
    protected $list;
    protected $results;

    function __construct($db_key = 'master')
    {
        $this->config = \Swoole::getInstance()->config['db'][$db_key];
        //不能使用长连接，避免进程内占用大量连接
        $this->config['persistent'] = false;
    }

    /**
     * @param $sql
     * @param null $callback
     * @return bool|CoMySQLResult
     */
    function query($sql, $callback = null)
    {
        $db = new MySQLi($this->config);
        $db->connect();
        $result = $db->queryAsync($sql);
        if (!$result)
        {
            return false;
        }
        $retObj = new CoMySQLResult($db, $callback);
        $retObj->sql = $sql;
        $this->list[] = $retObj;
        return $retObj;
    }

    function wait($timeout = 1.0)
    {
        $_timeout_sec = intval($timeout);
        $_timeout_usec = intval(($timeout - $_timeout_sec) * 1000 * 1000);

        $processed = 0;
        do
        {
            $links = $errors = $reject = array();
            foreach ($this->list as $k => $retObj)
            {
                $retObj->db->_co_id = $k;
                $links[] = $errors[] = $reject[] = $retObj->db;
            }
            if (!mysqli_poll($links, $errors, $reject, $_timeout_sec, $_timeout_usec))
            {
                continue;
            }
            /**
             * @var $link mysqli
             */
            foreach ($links as $link)
            {
                if ($result = $link->reap_async_query())
                {
                    $_retObj = $this->list[$link->_co_id];
                    if (is_object($result))
                    {
                        $_retObj->result = new MySQLiRecord($result);
                        if ($_retObj->callback)
                        {
                            call_user_func($_retObj->callback, $_retObj->result);
                        }
                    }
                }
                else
                {
                    trigger_error(sprintf("MySQLi Error: %s", $link->error));
                }
                $processed++;
            }
        } while ($processed < count($this->list));
        return $processed;
    }
}

class CoMySQLResult
{
    public $db;
    public $callback = null;
    /**
     * @var MySQLiRecord
     */
    public $result;
    public $sql;
    public $code = 0;

    function __construct(\mysqli $db, callable $callback = null)
    {
        $this->db = $db;
        $this->callback = $callback;
    }
}