<?php

namespace SPF\Rpc;

use SPF\Rpc\Formatter\FormatterFactory;
use SPF\Rpc\Protocol\ProtocolHeader;

class Client
{
    // Client SDK 版本
    const SDK_VERSION = 10101;

    public $config = [
        // 本地SDK命名空间前缀
        'localNsPrefix' => null,
        // 服务端SDK命名空间前缀
        'serverNsPrefix' => null,
        // 业务方提供SDK版本
        'sdkVersion' => 0,
        // 打包协议
        'format' => \SPF\Rpc\Formatter\FormatterFactory::FMT_TARS,
        // 是否启用打包协议代理，启用代理之后sdk会自动将PHP的数据类型array等转化为相应打包类型数据，比如TARS_Map、TARS_Vector等
        'formatProxy' => false,
        // 客户端超时时间，单位：秒
        'timeout' => 1.0,
        // 包最大长度，tcp协议必填
        'package_max_length' => 2 * 1024 * 1024,
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * @param string $class 完整类名，SDK会自动去除前缀进行转换
     * @param string $function 方法名
     * @param array $encodeBufs 编码之后的buffers数组
     */
    public function call($class, $function, $encodeBufs = [])
    {
        // TODO 对class去除公共命名空间前缀
        $class = $class;
        $funcName = $this->encodeFuncName($class, $function);
        // 将参数进行打包操作，跟配置的打包类型
        $buffer = $this->encodeByTars($encodeBufs, $funcName);

        // 对包体增加32字节header
        $reqPacket = $this->encodePacket($buffer);

        // 发送请求，根据服务发现的host:port:protocol发送请求
        if ($this->config['protocol'] == 'http') {
            $resPacket = $this->sendHttpRequest($reqPacket);
        } else {
            $resPacket = $this->sendTcpRequest($reqPacket);
        }

        // 对包体解析32字节header
        $resDecode = $this->decodePacket($resPacket);

        // 如果有异常，抛出相应异常
        if ($resDecode['header']['errno'] != 0) {
            throw new RpcException($resDecode['header']['errno']);
        }

        // 如果无异常，响应转换，根据配置的打包类型进行解包
        $response = $this->decodeByTars($resDecode['body']);

        // 返回数据
        return $response;
    }

    /**
     * @param string $class
     * @param string $function
     * 
     * @return string ns1.ns2.class@func
     */
    protected function encodeFuncName($class, $function)
    {
        return str_replace('\\', '.', $class) . '@' . $function;
    }

    protected function encodeByTars($bufs, $funcName)
    {
        return FormatterFactory::encode($this->config['format'], $bufs, $funcName);
    }

    protected function decodeByTars($bufs)
    {
        return FormatterFactory::decode($this->config['format'], $bufs);
    }

    protected function encodePacket($buffer)
    {
        $requestId = 0;
        $uid = 0;
        $errno = 0;
        return ProtocolHeader::encode(
            $buffer,
            strlen($buffer),
            $this->config['format'],
            $errno,
            $requestId,
            $uid,
            self::SDK_VERSION,
            $this->config['sdkVersion']
        );
    }

    protected function decodePacket($response)
    {
        return ProtocolHeader::decode($response);
    }

    protected function sendHttpRequest($packet)
    {
        // TODO 从服务发现获取host和port
        $host = $this->config['host'];
        $port = $this->config['port'];
        $ch = curl_init("http://{$host}:{$port}");
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $packet,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config['timeout'],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    protected function sendTcpRequest($packet)
    {
        $socket = new \swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP, SWOOLE_SOCK_SYNC);
        $socket->set([
            'open_length_check' => true,
            'package_max_length' => $this->config['package_max_length'],
            'package_length_type' => 'N',
            'package_body_offset' => ProtocolHeader::HEADER_SIZE,
            'package_length_offset' => 0,
        ]);

        // TODO 从服务发现获取host和port
        // TODO 判断是否连接成功
        $ret = $socket->connect($this->config['host'], $this->config['port'], $this->config['timeout']);

        $socket->send($packet);
        $response = $socket->recv();
        // TODO 保持连接不断开
        $socket->close();

        return $response;
    }
}
