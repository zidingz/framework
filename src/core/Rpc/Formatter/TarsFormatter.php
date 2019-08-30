<?php

namespace SPF\Formatter;

use SPF\Rpc\RpcException;
use Throwable;
use SPF\Rpc\Tool\Helper;

class TarsFormatter implements Formatter
{
    /**
     * 对响应的数据进行encode，然后交由通讯协议进行传输
     * 
     * @param mixed $data
     * 
     * @return string
     */
    public static function encode($data)
    {
        $iVersion = 3;
        $cPacketType = 0;
        $iMessageType = 0;
        $iRequestId = 0;
        $statuses = [];
        $servantName = '';
        $funcName = '';

        $context = [];
        $iTimeout = 0;

        $rspBuf = \TUPAPI::encode(
            $iVersion,
            $iRequestId,
            $servantName,
            $funcName,
            $cPacketType,
            $iMessageType,
            $iTimeout,
            $context,
            $statuses,
            $data
        );

        return $rspBuf;
    }

    /**
     * 对通讯协议获取的请求数据进行decode
     * 
     * @param string $buffer
     * 
     * @return mixed
     */
    public static function decode($buffer)
    {
        try {
            // TODO decode失败的异常处理
            $unpackResult = \TUPAPI::decode($buffer);

            $parsedFunc = Helper::parserFuncName($unpackResult['sFuncName']);
            $reqParams = $this->convertToArgs($parsedFunc['params'], $unpackResult);

            return [
                'class' => $parsedFunc['class'],
                'function' => $parsedFunc['function'],
                'func_params' => $parsedFunc['params'],
                'req_params' => $reqParams,
            ];
        } catch (RpcException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RpcException(RpcException::ERR_INVALID_TARS, ['error' => $e->getMessage()]);
        }
    }

    // 完成了对入包的decode之后,获取到了sBuffer
    public function convertToArgs($params, $unpackResult)
    {
        try {
            $sBuffer = $unpackResult['sBuffer'];

            $unpackMethods = [
                'bool' => '\TUPAPI::getBool',
                'byte' => '\TUPAPI::getChar',
                'char' => '\TUPAPI::getChar',
                'unsigned byte' => '\TUPAPI::getUInt8',
                'unsigned char' => '\TUPAPI::getUInt8',
                'short' => '\TUPAPI::getShort',
                'unsigned short' => '\TUPAPI::getUInt16',
                'int' => '\TUPAPI::getInt32',
                'unsigned int' => '\TUPAPI::getUInt32',
                'long' => '\TUPAPI::getInt64',
                'float' => '\TUPAPI::getFloat',
                'double' => '\TUPAPI::getDouble',
                'string' => '\TUPAPI::getString',
                'enum' => '\TUPAPI::getShort',
                'map' => '\TUPAPI::getMap',
                'vector' => '\TUPAPI::getVector',
                'struct' => '\TUPAPI::getStruct',
            ];

            $args = [];
            foreach ($params as $param) {
                $type = $param['type'];
                $unpackMethod = $unpackMethods[$type];
                // 需要判断是否是简单类型,还是vector或map或struct
                if ($type === 'map' || $type === 'vector') {
                    if ($param['ref']) {
                        ${$param['name']} = $this->createInstance($param['proto']);
                        $args[] = ${$param['name']};
                    } else {
                        // 对于复杂的类型,需要进行实例化
                        $proto = $this->createInstance($param['proto']);
                        $args[] = $unpackMethod($param['name'], $proto, $sBuffer, false, 3);
                    }
                } elseif ($type === 'struct') {
                    if ($param['ref']) {
                        ${$param['name']} = new $param['proto']();
                        $args[] = ${$param['name']};
                    } else {
                        // 对于复杂的类型,需要进行实例化
                        $proto = new $param['proto']();
                        $value = $unpackMethod($param['name'], $proto, $sBuffer, false, 3);
                        $this->fromArray($value, $proto);
                        $args[] = $proto;
                    }
                } // 基本类型
                else {
                    if ($param['ref']) {
                        $args[] = null;
                    } else {
                        $args[] = $unpackMethod($param['name'], $sBuffer, false, 3);
                    }
                }

                $args[] = $value;
            }

            // // 对于输出参数而言,所需要的仅仅是对应的实例化而已
            // $index = 0;
            // foreach ($outParams as $outParam) {
            //     ++$index;
            //     $type = $outParam['type'];

            //     $protoName = 'proto' . $index;

            //     // 如果是结构体
            //     if ($type === 'map' || $type === 'vector') {
            //         $$protoName = $this->createInstance($outParam['proto']);
            //         $args[] = $$protoName;
            //     } elseif ($type === 'struct') {
            //         $$protoName = new $outParam['proto']();
            //         $args[] = $$protoName;
            //     } else {
            //         $protoName = null;
            //         $args[] = $protoName;
            //     }
            // }

            return $args;
        } catch (Throwable $e) {
            throw new RpcException(RpcException::ERR_INVALID_TARS, ['message' => $e->getMessage()]);
        }
    }

    private function createInstance($proto)
    {
        if ($this->isBasicType($proto)) {
            return $this->convertBasicType($proto);
        } elseif (!strpos($proto, '(')) {
            $structInst = new $proto();

            return $structInst;
        } else {
            $pos = strpos($proto, '(');
            $className = substr($proto, 0, $pos);
            if ($className == '\TARS_Vector') {
                $next = trim(substr($proto, $pos, strlen($proto) - $pos), '()');
                $args[] = $this->createInstance($next);
            } elseif ($className == '\TARS_Map') {
                $next = trim(substr($proto, $pos, strlen($proto) - $pos), '()');
                $pos = strpos($next, ',');
                $left = substr($next, 0, $pos);
                $right = trim(substr($next, $pos, strlen($next) - $pos), ',');

                $args[] = $this->createInstance($left);
                $args[] = $this->createInstance($right);
            } elseif ($this->isBasicType($className)) {
                $next = trim(substr($proto, $pos, strlen($proto) - $pos), '()');
                $basicInst = $this->createInstance($next);
                $args[] = $basicInst;
            } else {
                $structInst = new $className();
                $args[] = $structInst;
            }
            $ins = new $className(...$args);
        }

        return $ins;
    }

    private function isBasicType($type)
    {
        $basicTypes = [
            '\TARS::BOOL',
            '\TARS::CHAR',
            '\TARS::CHAR',
            '\TARS::UINT8',
            '\TARS::UINT8',
            '\TARS::SHORT',
            '\TARS::UINT16',
            '\TARS::INT32',
            '\TARS::UINT32',
            '\TARS::INT64',
            '\TARS::FLOAT',
            '\TARS::DOUBLE',
            '\TARS::STRING',
            '\TARS::INT32',
        ];

        return in_array($type, $basicTypes);
    }

    private function convertBasicType($type)
    {
        $basicTypes = [
            '\TARS::BOOL' => 1,
            '\TARS::CHAR' => 2,
            '\TARS::UINT8' => 3,
            '\TARS::SHORT' => 4,
            '\TARS::UINT16' => 5,
            '\TARS::FLOAT' => 6,
            '\TARS::DOUBLE' => 7,
            '\TARS::INT32' => 8,
            '\TARS::UINT32' => 9,
            '\TARS::INT64' => 10,
            '\TARS::STRING' => 11,
        ];

        return $basicTypes[$type];
    }

    // 将数组转换成对象
    private function fromArray($data, &$structObj)
    {
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (method_exists($structObj, 'set' . ucfirst($key))) {
                    call_user_func_array([$this, 'set' . ucfirst($key)], [$value]);
                } elseif ($structObj->$key instanceof \TARS_Struct) {
                    $this->fromArray($value, $structObj->$key);
                } else {
                    $structObj->$key = $value;
                }
            }
        }
    }
}