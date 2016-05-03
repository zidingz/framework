<?php
namespace Swoole\Platform;

class Linux
{
    function kill($pid, $signo)
    {
        return posix_kill($pid, $signo);
    }
}