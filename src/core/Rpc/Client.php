<?php

namespace SPF\Rpc;

use SPF\Rpc\Formatter\FormatterFactory;
use SPF\Rpc\Protocol\ProtocolHeader;
use Throwable;

class Client
{
    // Client SDK 版本
    const SDK_VERSION = 10101;

    /**
     * 默认配置
     * 
     * @var array
     */
    public $config = [
        // 服务名称
        'service' => null,
        // 本地SDK命名空间前缀
        'localNsPrefix' => null,
        // 业务方提供SDK版本
        'sdkVersion' => 0,
        // 打包协议
        'format' => \SPF\Rpc\Formatter\FormatterFactory::FMT_TARS,
        // 是否启用打包协议代理，启用代理之后sdk会自动将PHP的数据类型array等转化为相应打包类型数据，比如TARS_Map、TARS_Vector等
        'formatProxy' => false,
        // 客户端超时时间，单位：秒
        'timeout' => 1.0,
        // 包最大长度，tcp协议必填
        'packageMaxLength' => 2 * 1024 * 1024,
        // 优先协议，如果服务支持多协议，优先选择使用的协议
        'preferProtocol' => 'tcp',
    ];

    /**
     * 所有服务列表
     * 
     * @var array
     */
    public $servers = [];

    /**
     * 中间件
     * 
     * @var array
     */
    protected $middlewares = [];

    /**
     * 是否自定义请求handle
     * 
     * @var callable|array
     */
    protected $requestHandle = null;

    /**
     * 实例列表
     * 
     * @var self
     */
    protected static $instance = null;

    /**
     * 获取单例实例
     * 
     * @return self
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        // 初始化服务列表
        $this->getServers();

        // 根据是否开启优先协议配置优先协议
        if ($this->config['preferProtocol']) {
            $this->filterServersByPreferProtocol();
        }
    }

    /**
     * 注册中间件
     * 
     * @param string $name 中间件名称
     * @param callable|array $handle 中间件handle
     * 
     * @return self
     */
    public function registerMiddleware($name, $handle)
    {
        // TODO 校验中间件是否合法
        $this->middlewares[$name] = $handle;

        return $this;
    }

    /**
     * 移除中间件
     * 
     * @param string $name 中间件名称
     * 
     * @return self
     */
    public function removeMiddleware($name)
    {
        unset($this->middlewares[$name]);

        return $this;
    }

    /**
     * @param callable|string|array $requestHandle
     * 
     * @return self
     */
    public function registerRequestHandle($requestHandle)
    {
        $this->requestHandle = $requestHandle;

        return $this;
    }

    /**
     * 执行中间件
     */
    protected function handleMiddleware($request)
    {
        // TODO
        return;
        // 先执行指定协议的中间件
        foreach ($this->middlewares as $name => $handle) {
            try {
                if (call_user_func($handle, $request) === false) {
                    return false;
                }
            } catch (Throwable $e) { 
                // do something
            }
        }
    }

    /**
     * @param string $class 完整类名，SDK会自动去除前缀进行转换
     * @param string $function 方法名
     * @param array $encodeBufs 编码之后的buffers数组
     * 
     * @return mixed
     */
    public function call($class, $function, $encodeBufs = [])
    {
        // TODO 中间件
        $response = $this->handleCall($class, $function, $encodeBufs);

        return $response;
    }

    /**
     * @param string $class
     * @param string $funcName
     * @param array $encodeBufs
     * 
     * @return mixed
     */
    protected function handleCall($class, $function, $encodeBufs)
    {
        $funcName = $this->encodeFuncName($class, $function);

        // 将参数进行打包操作，跟配置的打包类型
        $buffer = $this->formatterEncode($encodeBufs, $funcName);

        // 对包体增加32字节header
        $reqPacket = $this->encodePacket($buffer);

        // 发送请求
        $resPacket = $this->sendRequest($reqPacket);

        // 对包体解析32字节header
        $resDecode = $this->decodePacket($resPacket);

        // 如果有异常，抛出相应异常
        if ($resDecode['header']['errno'] != 0) {
            throw new RpcException($resDecode['header']['errno']);
        }

        // 如果无异常，响应转换，根据配置的打包类型进行解包
        $response = $this->formatterDecode($resDecode['body']);

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
        // 对class去除公共命名空间前缀   
        $localNsPrefix = $this->config['localNsPrefix'] . '\\';
        if (substr($class, 0, strlen($localNsPrefix)) == $localNsPrefix) {
            $class = substr($class, strlen($localNsPrefix));
        }

        return str_replace('\\', '.', $class) . '@' . $function;
    }

    /**
     * @param array $bufs
     * @param string $funcName
     * 
     * @return string
     */
    protected function formatterEncode($bufs, $funcName)
    {
        return FormatterFactory::encode($this->config['format'], $bufs, $funcName);
    }

    /**
     * @param string $bufs
     * 
     * @return mixed
     */
    protected function formatterDecode($bufs)
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

    /**
     * 发送请求
     * 
     * @param string $reqPacket 请求包体
     * 
     * @return string 响应包体
     */
    protected function sendRequest($reqPacket)
    {
        $conn = $this->getConnByWeight();

        if (!is_null($this->requestHandle)) {
            $resPacket = call_user_func($this->requestHandle, $reqPacket, $conn, $this);
        } else {
            $requestHandle = 'sendRequestBy' . ucfirst($conn['protocol']);
            if (!method_exists($this, $requestHandle)) {
                throw new RpcException(RpcException::ERR_UNKNOWN, ['connection' => $conn], 'Request Handle Not Found');
            }
            $resPacket = $this->{$requestHandle}($reqPacket, $conn);
        }
        
        return $resPacket;
    }

    protected function sendRequestByHttp($packet, $conn)
    {
        $ch = curl_init("http://{$conn['host']}:{$conn['port']}");
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

    protected function sendRequestByTcp($packet, $conn)
    {
        $socket = new \swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP, SWOOLE_SOCK_SYNC);
        $socket->set([
            'open_length_check' => true,
            'package_max_length' => $this->config['packageMaxLength'],
            'package_length_type' => 'N',
            'package_body_offset' => ProtocolHeader::HEADER_SIZE,
            'package_length_offset' => 0,
        ]);

        // TODO 从服务发现获取host和port
        // TODO 判断是否连接成功
        $ret = $socket->connect($conn['host'], $conn['port'], $this->config['timeout']);

        $socket->send($packet);
        $response = $socket->recv();
        // TODO 保持连接不断开
        $socket->close();

        return $response;
    }

    /**
     * 从服务发现取服务列表
     * // TODO
     * 
     * @return array
     */
    protected function getServers()
    {
        $this->servers = [
            [
                'protocol' => 'http',
                'host' => 'localhost',
                'port' => 8804,
                'weight' => 100,
            ],
            [
                'protocol' => 'tcp',
                'host' => 'localhost',
                'port' => 8805,
                'weight' => 101,
            ],
        ];
    }

    /**
     * 根据配置优先协议配置过滤服务列表
     */
    protected function filterServersByPreferProtocol()
    {
        $servers = array_filter($this->servers, function($server) {
            return $server['protocol'] === $this->config['preferProtocol'];
        });

        // 没有指定协议服务，自动降级为权重连接
        if (!empty($servers)) {
            $this->servers = $servers;
        }
    }

    /**
     * 根据权重选择连接
     */
    protected function getConnByWeight()
    {
        // TODO 实现权重算法
        $servers = $this->servers;
        usort($servers, function($a, $b) {
            return $b['weight'] - $a['weight'] > 0 ? 1 : -1;
        });

        return $servers[0];
    }

    /**
     * 设置配置项
     * 
     * @param string $key
     * @param mixed $value
     * 
     * @return self
     */
    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;

        return $this;
    }
}
