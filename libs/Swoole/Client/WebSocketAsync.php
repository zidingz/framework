<?php
namespace Swoole\Client;

class WebSocketAsync extends WebSocket
{
    private $callbacks = [];
    /**
     * Connect client to server
     * @return $this
     */
    public function connect($timeout = 0.5)
    {
        if (!extension_loaded('swoole'))
        {
			throw new Exception('swoole extension is required for WebSocketAsync');
        }
        $this->socket = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

		$this->socket->on("connect", array($this, 'onConnect'));
		$this->socket->on("receive", array($this, 'onReceive'));
		$this->socket->on("error", array($this, 'onError'));
		$this->socket->on("close", array($this, 'onClose'));

		$this->socket->connect($this->host, $this->port, $timeout);
    }

	public function on($event, $callable) {
		$this->callbacks[$event] = $callable;
	}

	private function _callback($event) {
		return isset($this->callbacks[$event]) ? $this->callbacks[$event] : '';
	}

	public function onConnect(\swoole_client $socket) {

		if ($callable = $this->_callback('connect')) call_user_func($callable, $this);

		$socket->send($this->createHeader());
	}

	public function onReceive(\swoole_client $socket, $data) {

		if ($callable = $this->_callback('receive')) call_user_func($callable, $this, $data);

        $this->buffer .= $data;
        $frame = $this->parseData($this->buffer);
        if ($frame)
        {
            $this->buffer = '';
			$this->onMessage($socket, $this->parseIncomingRaw($frame));
        }
	}

	public function onError(\swoole_client $socket) {

		if ($callable = $this->_callback('error')) call_user_func($callable, $this);

	}

	public function onClose(\swoole_client $socket) {

		if ($callable = $this->_callback('close')) call_user_func($callable, $this);
	}

	public function onMessage(\swoole_client $socket, $frame) {

		if ($callable = $this->_callback('message')) call_user_func($callable, $this, $frame);

	}
}
