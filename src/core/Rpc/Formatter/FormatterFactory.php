<?php

namespace SPF\Formatter;

use SPF\Rpc\RpcException;

class FormatterFactory
{
    // tars 格式
    const FMT_TARS = 1;
    // grpc 格式
    const FMT_GRPC = 2;
    // php serialize 格式
    const FMT_SERIALIZE = 3;
    // json 格式
    const FMT_JSON = 4;

    protected $fmtMap = [
        self::FMT_TARS => 'tars',
        self::FMT_GRPC => 'grpc',
        self::FMT_SERIALIZE => 'serialize',
        self::FMT_JSON => 'json',
    ];

    /**
     * 对响应的数据进行encode，然后交由通讯协议进行传输
     * 
     * @param int $formatId
     * @param mixed $data
     * 
     * @return string
     */
    public static function encode($formatId, $data)
    {
        $formatter = self::getFormatter($formatId);

        return call_user_func("{$formatter}::encode", $data);
    }

    /**
     * 对通讯协议获取的请求数据进行decode
     * 
     * @param int $formatId
     * @param string $buffer
     * 
     * @return mixed
     */
    public static function decode($formatId, $buffer)
    {
        $formatter = self::getFormatter($formatId);

        return call_user_func("{$formatter}::decode", $buffer);
    }

    /**
     * @param int $formatId
     * 
     * @return string
     */
    protected static function getFormatter($formatId)
    {
        if (!in_array($formatId, $this->fmtMap)) {
            throw new RpcException(RpcException::ERR_UNSUPPORT_FMT, ['formatId' => $formatId]);
        }

        $formatter = __NAMESPACE__ . '\\' . ucfirst($this->fmtMap[$formatId]) . 'Formatter';

        return $formatter;
    }
}
