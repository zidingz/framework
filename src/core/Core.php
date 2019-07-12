<?php

namespace SPF;

if (!class_exists('Swoole', false))
{
    require __DIR__ . '/Swoole.php';
}

class Core extends \Swoole
{

}