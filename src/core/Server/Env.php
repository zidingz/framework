<?php
namespace SPF\Protocol;

use SPF;

class Env
{
    static function getEnv()
    {
        if (!SPF\Network\Server::$useSwooleHttpServer) {
            return SPF\Protocol\RPCServer::$clientEnv;
        } else {
            return SPF\Http\ExtServer::$clientEnv;
        }
    }

    static function getError()
    {

    }

    static function getErrorMsg()
    {

    }
}
