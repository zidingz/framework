<?php
namespace SPF\Protocol;
use SPF;

/**
 * 协议基类，实现一些公用的方法
 * @package SPF\Protocol
 */
abstract class Base implements SPF\IFace\Protocol
{
    public $default_port;
    public $default_host;
    /**
     * @var \SPF\IFace\Log
     */
    public $log;

    /**
     * @var \SPF\Server
     */
    public $server;

    /**
     * @var array
     */
    protected $clients;

    /**
     * 设置Logger
     * @param $log
     */
    function setLogger($log)
    {
        $this->log = $log;
    }

    function run($array)
    {
        \SPF\Error::$echo_html = true;
        $this->server->run($array);
    }

    function daemonize()
    {
        $this->server->daemonize();
    }

    /**
     * 打印Log信息
     * @param $msg
     * @param string $type
     */
    function log($msg)
    {
        $this->log->info($msg);
    }

    function task($task, $dstWorkerId = -1, $callback = null)
    {
        $this->server->task($task, $dstWorkerId = -1, $callback);
    }

    function onStart($server)
    {

    }

    function onConnect($server, $client_id, $from_id)
    {

    }

    function onClose($server, $client_id, $from_id)
    {

    }

    function onShutdown($server)
    {

    }
}