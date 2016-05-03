<?php
namespace Swoole\Platform;

class Windows
{
    function kill($pid, $signo)
    {
        return false;
    }
}