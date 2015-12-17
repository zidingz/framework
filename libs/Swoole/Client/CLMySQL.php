<?php
/**
 * Created by PhpStorm.
 * User: yanchunhao
 * Date: 2015/12/2
 * Time: 15:44
 */

namespace Swoole\Client;

use Swoole\CLPack;

class CLMySQL {
	private $conn, $dbname, $pack, $result = array(), $result_id = 1;
	private $host, $port;
	public $last_errno, $last_erro_msg, $is_connect = false;

	function __construct($host, $port, $dbname, $pconnect = true) {
		$this->pack = new CLPack();
		$this->host = $host;
		$this->port = $port;
		$this->dbname = $dbname;
		$this->conn = new \swoole_client($pconnect ? (SWOOLE_SOCK_TCP | SWOOLE_KEEP) : SWOOLE_SOCK_TCP);
		$this->conn->on('Close', [
			$this,
			'OnClose'
		]);
		if (!$this->connect()) {
			throw new \Exception("数据库连接失败 $host,$port,$dbname");
		}
	}

	function select_db($dbname) {
		$this->dbname = $dbname;
		return true;
	}

	function connect() {
		$this->pack->reset();
		$this->is_connect = $this->conn->connect($this->host, $this->port);
		return $this->is_connect;
	}

	function getPack() {
		while (1) {
			$data = @$this->conn->recv();
			if ($data == false) {
				throw new \Exception('连接Mysql网络中断');
			}
			$r = $this->pack->unpack($data);
			if ($r === false) {
				#包错误，断线重试
				#echo "包错误\n";
				$this->conn->close();
				return false;
			}
			if ($r) {
				return $r[0];
			}
		}
	}

	function query($sql) {
		$is_multi = true;
		$sign = mt_rand();
		if (!is_array($sql)) {
			$is_multi = false;
			$sql = [$this->dbname => $sql];
		}
		if (false === $this->conn->send(CLPack::pack($sql))) {
			$this->conn->close();
			$this->connect();
			if (false === $this->conn->send(CLPack::pack($sql))) {
				{
					throw new \Exception('连接Mysql网络中断');
				}
			}
		}
		$r = $this->getPack();
		if ($r === false) {
			$this->last_errno = 1;
			$this->last_erro_msg = $this->pack->last_err;
		}
		/*if(!is_array($r)){
			print_r($sql);
			print_r($r);exit;
		}*/
		foreach ($r as $k => $v) {
			if ($v[0] != 0) {
				$this->last_errno = $v[0];
				$this->last_erro_msg = $v[1];
				return false;
			}
		}

		$result_id = $this->result_id++;
		$this->result[$result_id] = $r;
		/*if ($r && !$is_multi) {
			if (!isset($r[$this->dbname])) {
				return false;
			} else {
				if ($r[$this->dbname][0] == 0) {
					return $r[$this->dbname][1];
				} else {
					$this->last_errno = $r[$this->dbname][0];
					$this->last_erro_msg = $r[$this->dbname][1];
					return false;
				}
			}
		}*/
		return $result_id;
	}

	function fetch($result_id, $dbname = '') {
		if (isset($this->result[$result_id])) {
			if (!$dbname) {
				$dbname = $this->dbname;
			}
			if ($this->result[$result_id][$this->dbname][0] == 0) {
				return $this->result[$result_id][$this->dbname][1];
			}
		}
		return false;
	}

	function fetch_row($result_id, $seek, $dbname = '') {
		if (isset($this->result[$result_id])) {
			if (!$dbname) {
				$dbname = $this->dbname;
			}
			if ($this->result[$result_id][$this->dbname][0] == 0 && isset($this->result[$result_id][$this->dbname][1][$seek])) {
				return $this->result[$result_id][$this->dbname][1][$seek];
			}
		}
		return false;
	}

	function num_rows($result_id, $dbname = '') {
		if (isset($this->result[$result_id])) {
			if (!$dbname) {
				$dbname = $this->dbname;
			}
			if ($this->result[$result_id][$this->dbname][0] == 0) {
				return count($this->result[$result_id][$this->dbname][1]);
			}
		}
		return 0;
	}

	function free_result($result_id) {
		if (isset($this->result[$result_id])) {
			unset($this->result[$result_id]);
		}
	}

	function insert_id() {
		$result_id = $this->query('insert_id');
		return $this->fetch($result_id);
	}

	function affected_rows() {
		$result_id = $this->query('affected_rows');
		return $this->fetch($result_id);
	}

	function onClose(\swoole_client $client) {
		//连接中断
		throw new \Exception("clmysql连接被中断");
	}
}