<?php
include __DIR__.'/bootstrap.php';

/**
 * 产生类库的全局变量
 */
global $php;
$php = Swoole::getInstance();
