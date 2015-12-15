<?php
/**
 * Created by PhpStorm.
 * User: yanchunhao
 * Date: 2015/12/2
 * Time: 15:44
 */

namespace Swoole\Client;

class CLMySQL {
	private $conn, $dbname, $pack, $delay_sql = [];
	private $host, $port;
	public $last_errno, $last_erro_msg;


	function __construct($host, $port, $dbname) {
		$this->pack = new CLPack();
		$this->host = $host;
		$this->port = $port;
		$this->dbname = $dbname;
		$this->conn = new \swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);
		$this->connect();
	}

	function select_db($dbname) {
		$this->dbname = $dbname;
	}

	function connect() {
		$this->pack->reset();
		$this->conn->connect($this->host, $this->port);
	}

	function getPack() {
		while (1) {
			$data = $this->conn->recv();
			if ($data == false) {
				throw new \Exception('连接Mysql网络中断');
			}
			$r = $this->pack->unpack($data);
			if ($r === false) {
				#包错误，断线重试
				echo "包错误\n";
				$this->conn->close();
				return false;
			}
			return $r;
		}
	}

	function query($sql) {
		$is_multi = true;
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
		if ($r && !$is_multi) {
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
		}
		return $r;
	}

	function insert_id() {
		return $this->query('insert_id');
	}

	function affected_rows() {
		return $this->query('affected_rows');
	}

	function reload() {
		$this->conn->send(CLPack::pack(CLPack::CMD_reload));
		return $this->getPack();
	}
}