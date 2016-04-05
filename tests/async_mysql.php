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
$pool = new Swoole\Async\MySQL($config, 10);

$sql1 = "INSERT INTO `test`.`userinfo`
 (`id`, `name`, `passwd`, `regtime`, `lastlogin_ip`)
 VALUES ('0', 'womensss', 'world', '2015-06-15 13:50:34', '4')";
$sql2 = "update userinfo set name='rango' where id = 16";
$sql3 = "show tables";

for ($i = 0; $i < 2000; $i++)
{
    $pool->query($sql3, function (mysqli $mysqli, $result) use ($i)
    {
        if ($result === true)
        {
            echo "insert_id={$mysqli->_insert_id}, _affected_rows={$mysqli->_affected_rows}\n";
        }
        elseif ($result === false)
        {
            echo "errno={$mysqli->_errno}, error={$mysqli->_error}\n";
            sleep(1);
        }
        else
        {
            //var_dump($result);
        }
        echo "$i\t" . str_repeat('-', 120) . "\n";
        usleep(100000);
    });
}
