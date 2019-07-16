<?php

namespace SPF\Queue;

use SPF;

/**
 * 文件存储的队列
 * @package SPF\Queue
 */
class File implements SPF\IFace\Queue
{
    private $data;
    public $file;
    public $name;

    public $put_save = true;

    function __construct($config)
    {
        if (!empty($config['name'])) $this->name = $config['name'];
        $this->file = SPF\App::getInstance()->app_path . '/cache/' . $config['name'] . '.fc';
        $this->load();
    }

    /**
     * 加载队列
     */
    function load()
    {
        $content = trim(file_get_contents($this->file));
        $this->data = explode("\n", $content);
    }

    /**
     * 保存队列
     */
    function save()
    {
        file_put_contents($this->file, implode("\n", $this->data));
    }

    /**
     * 入队
     * @see libs/system/IQueue#put($data)
     * @param $data
     */
    function push($data)
    {
        if (is_array($data) or is_object($data)) $data = serialize($data);
        //入队
        $this->data[] = $data;
        if ($this->put_save) $this->save();
    }

    /**
     * 出对
     * @see libs/system/IQueue#get()
     */
    function pop()
    {
        //出对
        return array_shift($this->data);
    }

    function __destruct()
    {
        $this->save();
    }
}