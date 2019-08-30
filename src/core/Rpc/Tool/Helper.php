<?php

namespace SPF\Rpc\Tool;

use SPF\Rpc\RpcException;

class Helper
{   
    /**
     * @param string $bufferFuncName
     * 
     * @return array
     */
    public static function parserFuncName($bufferFuncName)
    {
        // ns1.ns2.class@func 格式
        list($package, $func) = explode('@', $bufferFuncName);
        $class = str_replace('.', '\\', $package);
        $map = ReflectionClassMap::getMap();

        if (!isset($map[$class])) {
            throw new RpcException(RpcException::ERR_NOFUNC, ['class' => $class]);
        }
        if (!isset($map[$class][$func])) {
            throw new RpcException(RpcException::ERR_NOFUNC, ['class' => $class, 'function' => $func]);
        }

        return [
            'class' => $class,
            'function' => $func,
            'params' => $map[$class][$func],
        ];
    }
}
