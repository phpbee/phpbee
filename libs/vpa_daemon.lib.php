<?php
declare(ticks=1);


class vpa_daemon_base {
	protected $pid;
	protected $name;
	private $running;
	protected $pid_dir='/var/run/';
	
	function __construct($name) {
		$this->name=$name;
	}
	
	function _main() {
		while ($this->running) {
			$this->main();
		}
	}
	
	// User defined main function
	protected function main() {
		echo date('d.m.Y H:i:s')."\n";
		sleep(5);
	}
	
	
	// User defined init function
	protected function init() {
	
	}
	
	public function start() {
		return $this->_init();
	}

	public function onestart() {
		$this->init();
		$this->main('onestart');
	}
	
	private function _fork () {
		$child_pid = pcntl_fork();
		if( $child_pid ) {
			// выходим из родительского, привязанного к консоли, процесса
			exit;  
		}
		posix_setsid();
	}
	
	protected function _init () {
		if ($this->is_daemon_active($this->pid_dir.$this->name.'.pid')) {
			echo $this->name." already started\n";
			return;
		}
		$this->_fork();
		$this->pid=getmypid();
		file_put_contents($this->pid_dir.$this->name.'.pid', $this->pid);
		pcntl_signal(SIGTERM, array($this,"sig_handler"));
		$this->running=true;
		echo "Starting ".$this->name." successful\n";
		$this->init();
		return $this->_main();
	}
	
	
	private function is_daemon_active($pid_file) {
		if( is_file($pid_file) ) {
			$pid = file_get_contents($pid_file);
			if(posix_kill($pid,0)) {
				return true;
			} else {
				if(!unlink($pid_file)) {
					echo "Cannot remove pid file ".$this->pid_dir.$this->name."!";
					exit(-1);
				}
			}
		}
		return false;
	}
	
	// User defined quit function 
	protected function quit () {
	}
	
	protected function _quit() {
		$this->quit();
		$this->running = false;
		unlink($this->pid_dir.$this->name.'.pid');
		echo "Ending ".$this->name." successful\n";
	}
	
	//Обработчик
	protected function sig_handler($signo) {
		switch($signo) {
			case SIGTERM:
				$this->_quit();
				break;
			default: 
			break;
		}
	}


}

?>
