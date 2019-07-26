<?php

namespace SPF\Generator\RpcSdk;

use SPF\Client\RPC;

/**
 * The rpc client only use sdk generate, connot use other condition
 */
class RpcClient
{
    public static function call(string $class, string $method, array $args = [], bool $isStatic = false)
    {
        $call = $isStatic ? "{$class}::{$method}" : [$class, $method];

        return call_user_func([RPC::getInstance(), 'task'], $call, $args)->getResult();
    }
}
