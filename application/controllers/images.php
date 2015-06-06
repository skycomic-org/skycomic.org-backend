<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Images extends CI_Controller {

	function __construct () {
		parent::__construct();
		$this->output->cacheable();
	}

	private function error () {
		header('HTTP/404 Not Found');
		exit;
	}

	public function index () {
		$this->error();
	}
	
	public function comic ($cid, $page, $getLink = False) {
		if ( !is_num($cid) OR !is_num($page) ) {
			$this->error();
		}
		$this->load->model('grab_model');
		$link = $this->grab_model->image_comic($cid, $page, $getLink);
		if ($getLink) {
			$this->output->set_data(["url" => $link]);
			$this->output->json(200);
		}
	}
	
	public function thumbnail ($tid, $medium = False, $getLink = False) {
		if ( !is_num($tid) ) {
			$this->error();
		}
		$this->load->model('grab_model');
		$link = $this->grab_model->image_thumbnail($tid, $medium, $getLink);
		if ($getLink) {
			$this->output->set_data(["url" => $link]);
			$this->output->json(200);
		}
	}
	
}

/* End of file main.php */