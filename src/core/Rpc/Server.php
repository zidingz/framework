<?php
namespace SPF\Rpc;

use SPF;
use SPF\Exception\ValidateException;
use SPF\Coroutine\BaseContext as Context;
use SPF\Exception\Exception;
use SPF\Struct\Response;
use SPF\Rpc\Protocol\TcpProtocol;
use SPF\Rpc\Protocol\HttpProtocol;

class Server
{
    use TcpProtocol, HttpProtocol;

    /**
     * 版本号
     */
    const SDK_VERSION = 10000;

    const HEADER_SIZE = 32;
    // length: body数据长度，使用mb_strlen计算
    // formatter: body打包格式，tars|protobuf|serialize|json，会转换为enum类型，此处为ID
    // request_id: 请求ID
    // errno: 错误码
    // uid: 用户ID
    // server_version: 当前服务版本，由服务提供方定义，格式为 六位整数，前两位为主版本，中间两位为子版本，最后两位为修正版本：
    //      例如 1.13.5，则标记为 011305，省略前面的0，变成 11305
    // sdk_version: 当前RPC SDK版本，由RPC基础组件提供方定义
    // reserve: 保留字段
    const HEADER_STRUCT = "Nlength/Nformatter/Nrequest_id/Nerrno/Nuid/Nserver_version/Nsdk_version/Nreserve";
    const HEADER_PACK = "NNNNNNNN";// 4*8=32

    const DECODE_PHP = 1;   // 使用PHP的serialize打包
    const DECODE_JSON = 2;   // 使用json_encode打包
    const DECODE_MSGPACK = 3;   // 使用msgpack打包
    const DECODE_SWOOLE = 4;   // 使用swoole_serialize打包
    const DECODE_GZIP = 128; // 启用GZIP压缩

    const ALLOW_IP = 1;
    const ALLOW_USER = 2;

    /**
     * Swoole Server对象
     * 
     * @var \swoole_server|\swoole_http_server|\swoole_websocket_server
     */
    public $server = null;

    /**
     * 客户端环境变量
     * @var array
     */
    static $clientEnv;
    /**
     * 请求头
     * @var array
     */
    static $requestHeader;
    static $clientEnvKey = "client_env";
    static $requestHeaderKey = "request_header";
    static $stop = false;

    public $packet_maxlen = 2465792; //2M默认最大长度

    protected $_headers = array(); //保存头
    protected $appNamespaces = array(); //应用程序命名空间
    protected $ipWhiteList = array(); //IP白名单
    protected $userList = array(); //用户列表
    protected $verifyIp = false;
    protected $verifyUser = false;

    protected function handleHttpRequest(\swoole_http_request $request)
    {
        return 'handled data';
    }

    protected function handleTcpRequest($data)
    {
        return 'handled data';
    }

    protected function encodePacket($response, $reqHeader, $errno = 0)
    {
        return pack(
            self::HEADER_PACK,
            strlen($response),
            $reqHeader['formatter'],
            $reqHeader['request_id'],
            $errno,
            $reqHeader['uid'],
            Config::get('project.base.version'), // TODO 可能因为多进程，导致配置取不到
            self::SDK_VERSION,
            0 // 保留字段
        ) . $response;
    }

    protected function decodePacket($request)
    {
        return unpack(self::HEADER_STRUCT, substr($request, 0, self::HEADER_SIZE));
    }

    protected function beforeStart(\swoole_server $server)
    {

    }

    /**
     * 启动Server
     */
    public function start()
    {
        // 创建servers
        foreach(Config::get('project.server', []) as $conn) {
            switch($conn['protocol']) {
                case 'tcp':
                    $this->createTcpServer($conn);
                    break;
                case 'http':
                    $this->createHttpServer($conn);
                    break;
                default:
                    throw new Exception("not support protocol: {$conn['protocol']}");
            }
        }

        if (is_null($this->server)) {
            throw new Exception("you must configure one server at least one");
        }

        // 注册公共事件监听
        $this->server->on('Start', [$this, 'onStart']);
        $this->server->on('Shutdown', [$this, 'onShutdown']);
        $this->server->on('ManagerStart', [$this, 'onManagerStart']);
        $this->server->on('ManagerStop', [$this, 'onManagerStop']);
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->on('WorkerStop', [$this, 'onWorkerStop']);
        $this->server->on('WorkerExit', [$this, 'onWorkerExit']);
        $this->server->on('WorkerError', [$this, 'onWorkerError']);
        
        // 暴露hook
        $this->beforeStart($this->server);

        $this->server->start();
    }


    public function onStart(\swoole_server $server)
    {
        // do something
    }

    public function onShutdown(\swoole_server $server)
    {
        // do something
    }

    public function onManagerStart(\swoole_server $serv)
    {
        // do something
    }

    public function onManagerStop(\swoole_server $serv)
    {
        // do something
    }

    public function onWorkerStart(swoole_server $server, int $workerId)
    {
        // 清理Opcache缓存
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        // 清理APC缓存
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }

        // do something
    }

    public function onWorkerStop(Swoole\Server $server, int $workerId)
    {
        // do something
    }

    public function onWorkerExit(swoole_server $server, int $workerId)
    {
        // do something
    }

    public function onWorkerError(\swoole_server $serv, int $workerId, int $workerPid, int $exitCode, int $signal)
    {
        // do something
    }
}
