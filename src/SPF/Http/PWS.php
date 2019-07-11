<?php
namespace SPF\Http;

use Swoole;

/**
 * Class Http_LAMP
 * @package Swoole
 */
class PWS implements SPF\IFace\Http
{
    function header($k, $v)
    {
        $k = ucwords($k);
        App::getInstance()->response->setHeader($k, $v);
    }

    function status($code)
    {
        App::getInstance()->response->setHttpStatus($code);
    }

    function response($content)
    {
        $this->finish($content);
    }

    function redirect($url, $mode = 302)
    {
        App::getInstance()->response->setHttpStatus($mode);
        App::getInstance()->response->setHeader('Location', $url);
    }

    function finish($content = null)
    {
        App::getInstance()->request->finish = 1;
        if ($content)
        {
            App::getInstance()->response->body = $content;
        }
        throw new SPF\Exception\Response;
    }

    function setcookie($name, $value = null, $expire = null, $path = '/', $domain = null, $secure = null, $httponly = null)
    {
        App::getInstance()->response->setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    function getRequestBody()
    {
        return App::getInstance()->request->body;
    }
}
