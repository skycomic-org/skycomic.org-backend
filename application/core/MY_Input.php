<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Input extends CI_Input {

	function __construct () {
		parent::__construct();
	}

	function get_method () {
		return $_SERVER['REQUEST_METHOD'];
	}
	
	public function put ($index) {
		$_PUT = array();
		if ('PUT' == $_SERVER['REQUEST_METHOD']) {
			parse_str(file_get_contents('php://input'), $_PUT);
		}
		if ( isset($_PUT[$index]) ) {
			return $_PUT[$index];
		} else {
			return False;
		}
	}
}