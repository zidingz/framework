<?php

namespace SPF\Rpc\Protocol;

use SPF\Formatter\FormatterFactory;
use Throwable;

/**
 * @property \swoole_server|\swoole_http_server|\swoole_websocket_server $server 
 */
trait TcpProtocol
{
    /**
     * 创建tcp服务
     * 
     * @param array $conn 配置信息
     */
    protected function createTcpServer($conn)
    {
        if (is_null($this->server)) {
            $this->server = new \swoole_server($conn['host'], $conn['port'], $conn['mode'], $conn['type']);
            $this->server->on('Connect', [$this, 'onConnect']);
            $this->server->on('Receive', [$this, 'onReceive']);
            $this->server->on('Close', [$this, 'onClose']);
            
            if (!empty($conn['settings'])) {
                $this->server->set($conn['settings']);
            }
        } else {
            $server = $this->server->addListener($conn['host'], $conn['port'], $conn['type']);
            $server->on('Connect', [$this, 'onConnect']);
            $server->on('Receive', [$this, 'onReceive']);
            $server->on('Close', [$this, 'onClose']);

            $settings = [
                'open_http_protocol' => false,
                'open_http2_protocol' => false,
                'open_websocket_protocol' => false,
            ];
            if (!empty($conn['settings'])) {
                $settings = array_merge($settings, $conn['settings']);
            }
            $server->set($settings);
        }
    }

    /**
     * TCP连接建立连接
     */
    public function onConnect(\swoole_server $server, int $fd, int $reactorId)
    {
        // TODO
    }

    /**
     * 接收Tcp消息
     */
    public function onReceive(\swoole_server $server, int $fd, int $reactorId, string $data)
    {
        try {

            // TODO
            $this->beforeReceive($server, $fd, $reactorId, $data);

            list($header, $body) = $this->decodePacket($data);
            $request = FormatterFactory::decode($header['formatter'], $body);
            $request['packet_header'] = $header;
            $request['client'] = [];

            // middleware, such as allowIp, allowUser

            // TODO
        } catch (Throwable $e) {

        }
    }

    protected function beforeReceive(\swoole_server $server, int $fd, int $reactorId, string $data)
    {
        // TODO
    }

    /**
     * TCP连接断开
     */
    public function onClose(swoole_server $server, int $fd, int $reactorId)
    {
        // TODO
    }
}
