<?php
define('DEBUG', 'on');
define('WEBPATH', realpath(__DIR__ . '/..'));
//包含框架入口文件
require WEBPATH . '/libs/lib_config.php';

$php->db->forceMaster = true;
$php->db->query("select now() as now_t");
