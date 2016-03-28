<?php
namespace Swoole;

class Stream
{
    static function read($fp, $length)
    {
        $content = '';

        for ($readn = 0; $readn < $length; $readn += $n)
        {
            if ($length - $readn > 8192)
            {
                $buf = fread($fp, 8192);
            }
            else
            {
                $buf = fread($fp, $length - $readn);
            }
            //读文件失败了
            if ($buf === false)
            {
                break;
            }
            else
            {
                $content .= $buf;
                $n = strlen($buf);
            }
        }
        return $content;
    }

    static function write($fp, $content)
    {
        $length = strlen($content);
        for ($written = 0; $written < $length; $written += $n)
        {
            if ($length - $written >= 8192)
            {
                $n = fwrite($fp, substr($content, 8192));
            }
            else
            {
                $n = fwrite($fp, substr($content, $written));
            }
            //写文件失败了
            if (empty($n))
            {
                break;
            }
        }
        return $written;
    }
}