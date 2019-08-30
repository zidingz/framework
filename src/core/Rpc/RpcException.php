<?php

namespace SPF\Rpc;

use SPF\Exception\Exception;

class RpcException extends Exception
{
    const ERR_SUCCESS = 0; // 成功

    const ERR_HEADER = 9001; //错误的包头
    const ERR_TOOBIG = 9002; //请求包体长度超过允许的范围
    const ERR_SERVER_BUSY = 9003; //服务器繁忙，超过处理能力

    const ERR_UNPACK = 9204; // 解包失败
    const ERR_PARAMS = 9205; // 参数错误
    const ERR_NOFUNC = 9206; // 函数不存在
    const ERR_CALL = 9207; // 执行错误
    const ERR_ACCESS_DENY = 9208; // 访问被拒绝，客户端主机未被授权
    const ERR_USER = 9209; // 用户名密码错误
    const ERR_UNSUPPORT_FMT = 9210; // 不支持的打包格式
    const ERR_INVALID_TARS = 9211; // tars包数据不合法

    const ERR_SEND = 9301; // 发送客户端失败

    const ERR_LOGIC = 9401; // 服务端逻辑错误

    const ERR_UNKNOWN = 9901; // 未知错误


    protected $errMsg = [
        0 => 'success',

        9001 => '错误的包头',
        9002 => '请求包体长度超过允许的范围',
        9003 => '服务器繁忙',

        9204 => '解包失败',
        9205 => '参数错误',
        9206 => '函数不存在',
        9207 => '执行错误',
        9208 => '访问被拒绝，客户端主机未被授权',
        9209 => '用户名密码错误',
        9210 => '不支持的打包格式',
        9211 => 'tars包数据不合法',

        9301 => '发送客户端失败',

        9301 => '服务端逻辑错误',

        9301 => '未知错误',
    ];

    /**
     * @var array
     */
    protected $context = [];

    /**
     * 抛异常时一般可以仅填写已提供的错误码，如果错误码不是已提供的，会自动转为ERR_UNKNOWN
     * 异常信息如果没有提供，会使用异常字段的错误码描述信息
     * 
     * @param int $code
     * @param array $context 上下文信息
     * @param string $message
     */
    public function __construct(int $code, array $context = [], string $message = null)
    {
        if (!isset($this->errMsg[$code])) {
           $code = self::ERR_UNKNOWN; 
        }

        if (is_null($message)) {
            $message = $this->errMsg[$code];
        }

        $this->context = $context;
        
        parent::__construct($message, $code);
    }

    /**
     * 获取异常上下文信息
     * 
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
