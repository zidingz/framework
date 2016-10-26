<?php
define('DEBUG', 'on');
define('WEBPATH', realpath(__DIR__ . '/..'));
//包含框架入口文件
require WEBPATH . '/libs/lib_config.php';

class AStruct extends Swoole\Memory\Struct
{
    /**
     * @fieldtype int32
     */
    public $id;

    /**
     * @FileType char[40]
     */
    public $data;

    /**
     * @fieldtype double
     */
    public $price;

    /**
     * @fieldtype float
     */
    public $price2;

    /**
     * @fieldtype int64
     */
    public $count;
}

$a = new AStruct(false);
$str = $a->pack(array(
    13,
    'hello',
    999.9,
    888.8,
    99999,
));

file_put_contents('test.bin', $str);exit;

var_dump(strlen($str));

$result = $a->unpack($str);
var_dump($result);
