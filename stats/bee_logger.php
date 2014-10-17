#!/usr//bin/php
<?php
require_once('../libs/vpa_daemon.lib.php');
date_default_timezone_set('Europe/Moscow');
error_reporting(E_ALL | E_STRICT);
$config=array(
		'host'=>'5.9.20.143',
		'port'=>8080,
	);

class bee_logger_daemon extends vpa_daemon_base {
	
	protected $config;
	protected $db_conn;
	
	function __construct($name,$config) {
		$this->config=$config;
		$this->logfile=dirname(__FILE__).DIRECTORY_SEPARATOR.'sites.log';
		parent::__construct($name);
	}
	
	function init() {
			file_put_contents($this->logfile,'bee_logger_daemon::init'.PHP_EOL,FILE_APPEND);
	}
	
	function main() {
		$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_bind($socket, $this->config['host'], $this->config['port']);

		$from = '';
		$port = 0;
		while (true) {
			socket_recvfrom($socket, $buf, 1024, 0, $from, $port);
			//echo $buf.PHP_EOL;
			file_put_contents($this->logfile,$buf.PHP_EOL,FILE_APPEND);
		}
	}
	
	function quit() {
	}
}


$daemon=new bee_logger_daemon('bee_logger',$config);
$daemon->start();

?>
