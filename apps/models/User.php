<?php
namespace App\Model;
use Swoole;

class User extends Swoole\Model
{
    /**
     * 表名
     * @var string
     */
    public $table = 'users';

    function test()
    {
        $a = model('Test');
        var_dump($a);
        $key = '1234';
        $this->swoole->cache->delete($key);
        $this->db->getAffectedRows();
    }
}