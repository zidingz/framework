<?php
define('DEBUG', 'on');
//必须设置此目录,PHP程序的根目录
define('WEBPATH', __DIR__);
define('WEBROOT', 'http://local.framework.com');

require __DIR__ . '/../vendor/autoload.php';
$app = SPF\App::getInstance(__DIR__ . '/../apps');
if (get_cfg_var('env.name') == 'dev') {
    $app->config->setPath(WEBPATH . '/apps/configs/dev/');
}
$app->handle();
