<?php

namespace SPF\Client;

use SPF\Core;
use SPF\Coroutine\RPC as CoRPC;

if (Core::$enableCoroutine)
{
    class SOA extends CoRPC
    {

    }
}
else
{
    class SOA extends RPC
    {

    }
}
