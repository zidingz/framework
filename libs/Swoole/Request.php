<?php
namespace Swoole;

class Request
{
    /**
     * 文件描述符
     * @var int
     */
    public $fd;
    public $id;

    /**
     * 请求时间
     * @var int
     */
    public $time;

    /**
     * 客户端IP
     * @var
     */
    public $remote_ip;

    /**
     * 客户端PORT
     * @var
     */
    public $remote_port;

    public $get = array();
    public $post = array();
    public $file = array();
    public $cookie = array();
    public $session = array();
    public $request = array();
    public $server = array();

    /**
     * @var \StdClass
     */
    public $attrs;

    public $head = array();
    public $body;
    public $meta = array();

    public $finish = false;
    public $ext_name;
    public $status;

    /**
     * 将原始请求信息转换到PHP超全局变量中
     */
    function setGlobal()
    {
        if ($this->get)
        {
            $_GET = $this->get;
        }
        if ($this->post)
        {
            $_POST = $this->post;
        }
        if ($this->file)
        {
            $_FILES = $this->file;
        }
        if ($this->cookie)
        {
            $_COOKIE = $this->cookie;
        }
        if ($this->server)
        {
            $_SERVER = $this->server;
        }
        $this->request = $_REQUEST = array_merge($this->get, $this->post, $this->cookie);

        $_SERVER['REQUEST_URI'] = $this->meta['uri'];
        $_SERVER['REMOTE_ADDR'] = $this->remote_ip;
        $_SERVER['REMOTE_PORT'] = $this->remote_port;
        $_SERVER['REQUEST_METHOD'] = $this->meta['method'];
        $_SERVER['REQUEST_TIME'] = $this->time;
        $_SERVER['SERVER_PROTOCOL'] = $this->meta['protocol'];
        if (!empty($this->meta['query']))
        {
            $_SERVER['QUERY_STRING'] = $this->meta['query'];
        }
        /**
         * 将HTTP头信息赋值给$_SERVER超全局变量
         */
        foreach ($this->head as $key => $value)
        {
            $_key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
            $_SERVER[$_key] = $value;
        }
    }

    /**
     * LAMP环境初始化
     */
    function initWithLamp()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->cookie = $_COOKIE;
        $this->server = $_SERVER;
        $this->request = $_REQUEST;
    }

    function unsetGlobal()
    {
        $_REQUEST = $_SESSION = $_COOKIE = $_FILES = $_POST = $_SERVER = $_GET = array();
    }

    function isWebSocket()
    {
        return isset($this->head['Upgrade']) && strtolower($this->head['Upgrade']) == 'websocket';
    }

    /**
     * 跳转网址
     * @param $url
     * @return unknown_type
     */
    public static function redirect($url,$mode=302)
    {
        Http::redirect($url, $mode);
        return;
    }
    /**
     * 发送下载声明
     * @return unknown_type
     */
    static function download($mime,$filename)
    {
        header("Content-type: $mime");
        header("Content-Disposition: attachment; filename=$filename");
    }

    /**
     * 获取客户端IP
     * @return string
     */
    static function getClientIP()
    {
        if (isset($_SERVER["HTTP_CLIENT_IP"]) and strcasecmp($_SERVER["HTTP_CLIENT_IP"], "unknown"))
        {
            return $_SERVER["HTTP_CLIENT_IP"];
        }
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) and strcasecmp($_SERVER["HTTP_X_FORWARDED_FOR"], "unknown"))
        {
            return $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
        if (isset($_SERVER["REMOTE_ADDR"]))
        {
            return $_SERVER["REMOTE_ADDR"];
        }
        return "";
    }

    /**
     * 获取客户端浏览器信息
     * @return string
     */
    static function getBrowser()
    {
        $sys = $_SERVER['HTTP_USER_AGENT'];
        if (stripos($sys, "Firefox/") > 0)
        {
            preg_match("/Firefox\/([^;)]+)+/i", $sys, $b);
            $exp[0] = "Firefox";
            $exp[1] = $b[1];
        }
        elseif (stripos($sys, "Maxthon") > 0)
        {
            preg_match("/Maxthon\/([\d\.]+)/", $sys, $aoyou);
            $exp[0] = "傲游";
            $exp[1] = $aoyou[1];
        }
        elseif (stripos($sys, "MSIE") > 0)
        {
            preg_match("/MSIE\s+([^;)]+)+/i", $sys, $ie);
            $exp[0] = "IE";
            $exp[1] = $ie[1];
        }
        elseif (stripos($sys, "OPR") > 0)
        {
            preg_match("/OPR\/([\d\.]+)/", $sys, $opera);
            $exp[0] = "Opera";
            $exp[1] = $opera[1];
        }
        elseif (stripos($sys, "Edge") > 0)
        {
            preg_match("/Edge\/([\d\.]+)/", $sys, $Edge);
            $exp[0] = "Edge";
            $exp[1] = $Edge[1];
        }
        elseif (stripos($sys, "Chrome") > 0)
        {
            preg_match("/Chrome\/([\d\.]+)/", $sys, $google);
            $exp[0] = "Chrome";
            $exp[1] = $google[1];
        }
        elseif (stripos($sys, 'rv:') > 0 && stripos($sys, 'Gecko') > 0)
        {
            preg_match("/rv:([\d\.]+)/", $sys, $IE);
            $exp[0] = "IE";
            $exp[1] = $IE[1];
        }
        else
        {
            $exp[0] = "Unkown";
            $exp[1] = "";
        }

        return $exp[0] . '(' . $exp[1] . ')';
    }
    /**
     * 获取客户端操作系统信息
     * @return string
     */
    static function getOS()
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];

        if (preg_match('/win/i', $agent) && strpos($agent, '95'))
        {
            $os = 'Windows 95';
        }
        elseif (preg_match('/win 9x/i', $agent) && strpos($agent, '4.90'))
        {
            $os = 'Windows ME';
        }
        elseif (preg_match('/win/i', $agent) && preg_match('/98/i', $agent))
        {
            $os = 'Windows 98';
        }
        elseif (preg_match('/win/i', $agent) && preg_match('/nt 6.0/i', $agent))
        {
            $os = 'Windows Vista';
        }
        elseif (preg_match('/win/i', $agent) && preg_match('/nt 6.1/i', $agent))
        {
            $os = 'Windows 7';
        }
        elseif (preg_match('/win/i', $agent) && preg_match('/nt 6.2/i', $agent))
        {
            $os = 'Windows 8';
        }
        elseif (preg_match('/win/i', $agent) && preg_match('/nt 10.0/i', $agent))
        {
            $os = 'Windows 10';
        }
        elseif (preg_match('/win/i', $agent) && preg_match('/nt 5.1/i', $agent))
        {
            $os = 'Windows XP';
        }
        elseif (preg_match('/win/i', $agent) && preg_match('/nt 5/i', $agent))
        {
            $os = 'Windows 2000';
        }
        elseif (preg_match('/win/i', $agent) && preg_match('/nt/i', $agent))
        {
            $os = 'Windows NT';
        }
        elseif (preg_match('/win/i', $agent) && preg_match('/32/i', $agent))
        {
            $os = 'Windows 32';
        }
        elseif (preg_match('/linux/i', $agent))
        {
            $os = 'Linux';
        }
        elseif (preg_match('/unix/i', $agent))
        {
            $os = 'Unix';
        }
        elseif (preg_match('/sun/i', $agent) && preg_match('/os/i', $agent))
        {
            $os = 'SunOS';
        }
        elseif (preg_match('/ibm/i', $agent) && preg_match('/os/i', $agent))
        {
            $os = 'IBM OS/2';
        }
        elseif (preg_match('/Mac/i', $agent) && preg_match('/PC/i', $agent))
        {
            $os = 'Macintosh';
        }
        elseif (preg_match('/PowerPC/i', $agent))
        {
            $os = 'PowerPC';
        }
        elseif (preg_match('/AIX/i', $agent))
        {
            $os = 'AIX';
        }
        elseif (preg_match('/HPUX/i', $agent))
        {
            $os = 'HPUX';
        }
        elseif (preg_match('/NetBSD/i', $agent))
        {
            $os = 'NetBSD';
        }
        elseif (preg_match('/BSD/i', $agent))
        {
            $os = 'BSD';
        }
        elseif (preg_match('/OSF1/i', $agent))
        {
            $os = 'OSF1';
        }
        elseif (preg_match('/IRIX/i', $agent))
        {
            $os = 'IRIX';
        }
        elseif (preg_match('/FreeBSD/i', $agent))
        {
            $os = 'FreeBSD';
        }
        elseif (preg_match('/teleport/i', $agent))
        {
            $os = 'teleport';
        }
        elseif (preg_match('/flashget/i', $agent))
        {
            $os = 'flashget';
        }
        elseif (preg_match('/webzip/i', $agent))
        {
            $os = 'webzip';
        }
        elseif (preg_match('/offline/i', $agent))
        {
            $os = 'offline';
        }
        else
        {
            $os = 'Unknown';
        }

        return $os;
    }
}
