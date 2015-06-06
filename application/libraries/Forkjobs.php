<?php

class ForkJobs {
	private $child_pids = array();
	private $available = False;

	function __construct () {
		$this->available = is_callable('pcntl_fork');
	}

	function add (& $job) {
		$pid = $this->available ? pcntl_fork() : -1;
		if ($pid == 0 OR $pid == -1) {
			$job();
		} else { // parent
			$this->child_pids[] = $pid;
			return $pid;
		}
		if ($pid == 0) {
			exit;
		}
	}

	function wait ($pid) {
		if ($this->available) {
			pcntl_waitpid($pid, $status);
			return $status;
		} else {
			return False;
		}
	}

	function waitall () {
		foreach($this->child_pids as $pid) {
			pcntl_waitpid($pid, $status);
		}
		$this->child_pids = array();
		return $this;
	}

	function __destruct () {
		if (count($this->child_pids)) {
			$this->waitall();
		}
	}
}