<?php
namespace SPF\Protocol;
use SPF;

require_once LIBPATH . '/function/cli.php';

class AppFPM extends FastCGI
{
    protected $router_function;
    protected $apps_path;

    function onStart($serv)
    {
        parent::onStart($serv);
        if (empty($this->apps_path))
        {
            if (!empty($this->config['apps']['apps_path']))
            {
                $this->apps_path = $this->config['apps']['apps_path'];
            }
            else
            {
                throw new \Exception(__CLASS__.": require apps_path");
            }
        }
        $php = App::getInstance();
        $php->addHook(Swoole::HOOK_CLEAN, function(){
            $php = App::getInstance();
            //模板初始化
            if(!empty($php->tpl))
            {
                $php->tpl->clear_all_assign();
            }
        });
    }

    function onRequest(SPF\Request $request)
    {
        return App::getInstance()->handlerServer($request);
    }
}
