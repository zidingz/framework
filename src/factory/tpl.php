<?php
$tpl = new SPF\Template();
$app = SPF\App::getInstance();
$tpl->assign_by_ref('php', $app->env);

if (defined('TPL_DIR')) {
    $tpl->template_dir = TPL_DIR;
} elseif (is_dir($app->app_path . '/templates')) {
    $tpl->template_dir = $app->app_path . '/templates';
} else {
    $tpl->template_dir = WEBPATH . "/templates";
}
define('TPL_BASE', $tpl->template_dir);
if (SPF\App::$debug) {
    $tpl->compile_check = true;
} else {
    $tpl->compile_check = false;
}
return $tpl;