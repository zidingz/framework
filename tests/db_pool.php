<?php
define('DEBUG', 'on');
define('WEBPATH', realpath(__DIR__ . '/..'));
//包含框架入口文件
require WEBPATH . '/libs/lib_config.php';

$config = array(
    'host' => '127.0.0.1',
    'user' => 'root',
    'password' => 'root',
    'database' => 'test',
);

$pool = new \Swoole\Async\Pool($config, 100);

$pool->create(function () use ($pool, $config)
{
    $db = new swoole_mysql;
    $db->on('close', function ($db) use ($pool)
    {
        $pool->remove($db);
    });
    return $db->connect($config, function ($db, $result) use ($pool)
    {
        $pool->join($db);
    });
});

for ($i = 0; $i < 1000; $i++)
{
    $pool->request(function ($db)  use ($pool)
    {
        $r = $db->query("show tables", function (swoole_mysql $db, $r) use ($pool)
        {
            global $s;
            if ($r === false)
            {
                var_dump($db->error, $db->errno);
            }
            elseif ($r === true)
            {
                var_dump($db->affected_rows, $db->insert_id);
            }
            echo "count=" . count($r) . ", time=" . (microtime(true) - $s), "\n";
            //var_dump($r);
            //释放资源
            $pool->release($db);
        });

        if ($r == false) {
            die("xxx\n");
        }

    });
}
