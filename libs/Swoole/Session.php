<?php
namespace Swoole;

/**
 * 会话控制类
 * 通过SwooleCache系统实现会话控制，可支持FileCache,DBCache,Memcache以及更多
 * @author Tianfeng.Han
 * @package SwooleSystem
 * @package Login
 */
class Session
{
    protected $config;

    // 类成员属性定义
    static $cache_prefix = "phpsess_";
    static $cookie_key = 'PHPSESSID';
    static $sess_size = 32;

    /**
     * 是否启动
     * @var bool
     */
    public $isStart = false;
    protected $sessID;
    protected $readonly; //是否为只读，只读不需要保存
    protected $open;

    /**
     * @var IFace\Cache
     */
    protected $cache;

    /**
     * 使用PHP内建的SESSION
     * @var bool
     */
    public $use_php_session = true;

    protected $cookie_lifetime = 86400000;
    protected $session_lifetime = 0;
    protected $cookie_domain = null;
    protected $cookie_path = '/';

    public function __construct($config)
    {
        $this->config = $config;
        $this->cache = Factory::getCache($config['cache_id']);
        /**
         * cookie过期时间
         */
        if (isset($config['cookie_lifetime']))
        {
            $this->cookie_lifetime = intval($config['cookie_lifetime']);
        }
        /**
         * cookie的路径
         */
        if (isset($config['cookie_path']))
        {
            $this->cookie_path = $config['cookie_path'];
        }
        /**
         * cookie域名
         */
        if (isset($config['cookie_domain']))
        {
            $this->cookie_domain = $config['cookie_domain'];
        }
        /**
         * session的过期时间
         */
        if (isset($config['session_lifetime']))
        {
            $this->session_lifetime = intval($config['cache_lifetime']);
        }
    }

    public function start($readonly = false)
    {
        if (empty(\Swoole::$php->request))
        {
            throw new SessionException("The method must be used when requested.");
        }
        $this->isStart = true;
        if ($this->use_php_session)
        {
            session_start();
        }
        else
        {
            $this->readonly = $readonly;
            $this->open = true;
            $sessid = Cookie::get(self::$cookie_key);
            if (empty($sessid))
            {
                $sessid = RandomKey::randmd5(40);
                \Swoole::$php->http->setCookie(self::$cookie_key, $sessid, time() + $this->cookie_lifetime,
                    $this->cookie_path, $this->cookie_domain);
            }
            $_SESSION = $this->load($sessid);
        }
        \Swoole::$php->request->session = $_SESSION;
    }

    function setId($session_id)
    {
        $this->sessID = $session_id;
        if ($this->use_php_session)
        {
            session_id($session_id);
        }
    }

    /**
     * 获取SessionID
     * @return string
     */
    function getId()
    {
        if ($this->use_php_session)
        {
            return session_id();
        }
        else
        {
            return $this->sessID;
        }
    }

    public function load($sessId)
    {
        $this->sessID = $sessId;
        $data = $this->get($sessId);
        if ($data)
        {
            return unserialize($data);
        }
        else
        {
            return array();
        }
    }

    public function save()
    {
        return $this->set($this->sessID, serialize($_SESSION));
    }

    /**
     * @param string $save_path
     * @param string $sess_name
     * @return bool
     */
    public function open($save_path = '', $sess_name = '')
    {
        self::$cache_prefix = $save_path . '_' . $sess_name;

        return true;
    }

    /**
     * 关闭Session
     * @param   NULL
     * @return  bool    true/false
     */
    public function close()
    {
        return true;
    }

    /**
     * 读取Session
     * @param   String $sessId
     * @return  bool    true/false
     */
    public function get($sessId)
    {
        $session = $this->cache->get(self::$cache_prefix . $sessId);
        //先读数据，如果没有，就初始化一个
        if (!empty($session))
        {
            return $session;
        }
        else
        {
            return array();
        }
    }

    /**
     * 设置Session的值
     * @param $sessId
     * @param string $session
     * @return bool
     */
    public function set($sessId, $session = '')
    {
        $key = self::$cache_prefix . $sessId;
        return $this->cache->set($key, $session, $this->session_lifetime);
    }

    /**
     * 销毁Session
     * @param string $sessId
     * @return bool
     */
    public function delete($sessId = '')
    {
        return $this->cache->delete(self::$cache_prefix . $sessId);
    }

    /**
     * 内存回收
     * @param   NULL
     * @return  bool    true/false
     */
    public function gc()
    {
        return true;
    }

    /**
     * 初始化Session，配置Session
     * @return  bool  true/false
     */
    function init()
    {
        //不使用 GET/POST 变量方式
        ini_set('session.use_trans_sid', 0);
        //设置垃圾回收最大生存时间
        ini_set('session.gc_maxlifetime', $this->session_lifetime);
        //使用 COOKIE 保存 SESSION ID 的方式
        ini_set('session.use_cookies', 1);
        ini_set('session.cookie_path', '/');
        //多主机共享保存 SESSION ID 的 COOKIE
        ini_set('session.cookie_domain', $this->cookie_domain);
        //将 session.save_handler 设置为 user，而不是默认的 files
        session_module_name('user');
        //定义 SESSION 各项操作所对应的方法名
        session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'get'),
            array($this, 'set'),
            array($this, 'delete'),
            array($this, 'gc'));
        session_start();

        return true;
    }
}

class SessionException extends \Exception
{

}