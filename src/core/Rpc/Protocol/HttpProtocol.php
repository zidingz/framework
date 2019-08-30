<?php

namespace SPF\Rpc\Protocol;

use Throwable;

/**
 * @property \swoole_server|\swoole_http_server|\swoole_websocket_server $server 
 */
trait HttpProtocol
{
    /**
     * 创建http服务
     * 
     * @param array $conn 配置信息
     */
    protected function createHttpServer($conn)
    {
        if (is_null($this->server)) {
            $this->server = new \swoole_http_server($conn['host'], $conn['port']);
            $this->server->on('Request', [$this, 'onRequest']);
            
            if (!empty($conn['settings'])) {
                $this->server->set($conn['settings']);
            }
        } else {
            $server = $this->server->addListener($conn['host'], $conn['port']);
            $server->on('Request', [$this, 'onRequest']);

            $settings = [
                'open_http_protocol' => true,
                'open_http2_protocol' => false,
                'open_websocket_protocol' => false,
            ];
            if (!empty($conn['settings'])) {
                $settings = array_merge($settings, $conn['settings']);
            }
            $server->set($settings);
        }
    }

    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        try {
            // TODO
            $this->beforeRequest($request, $response);
            // TODO
        } catch (Throwable $e) {

        }
    }

    protected function beforeRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        // TODO
    }
}
