<?php
namespace SPF\Server;

use SPF;
use SPF\Struct\Response;

class Env
{
    static function getEnv() : Response
    {
        if (!SPF\Network\Server::$useSwooleHttpServer) {
            return SPF\Protocol\RPCServer::$clientEnv;
        } else {
            return SPF\Http\ExtServer::$clientEnv;
        }
    }
}
