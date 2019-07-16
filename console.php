#!/usr/bin/env php
<?php
define('WEBPATH', getenv('PWD'));
require_once __DIR__ . '/vendor/autoload.php';

SPF\App::getInstance()->runConsole();
