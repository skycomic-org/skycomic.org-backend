<?php

require_once APPPATH . "vendor/autoload.php";

class Cache_Override {
	public $prefix = "page_cache:";
	private $pointer = '-=->';

	function __construct () {
		$driver = new Stash\Driver\FileSystem();
		$options = array('path' => __DIR__ . '/../cache/');
		$driver->setOptions($options);
		$this->stash = new Stash\Pool($driver);
	}

	public function get_key () {
		global $CFG, $URI;
		return $this->prefix . $URI->rsegment(1) . ":" . md5($CFG->item('base_url').$CFG->item('index_page').$URI->uri_string);
	}

	public function get_cache ($data) {
		$headers_ = strstr($data, $this->pointer, true);
		$headers = json_decode( $headers_ );
		if (count($headers) > 0) {
			foreach ($headers as $header) {
				@header($header[0], $header[1]);
			}
		}
		$body = substr($data, strlen($headers_) + strlen($this->pointer));
		return $body;
	}

	public function save_cache (& $headers, & $data, $ttl = 0) {
		$item = $this->stash->getItem($this->get_key());
		$store = json_encode($headers) . $this->pointer . $data;
		return $item->set($store, $ttl);
	}

	public function lock () {
		$item = $this->stash->getItem($this->get_key());
		$item->lock();
	}

	public function execute () {
		$item = $this->stash->getItem($this->get_key());
		$data = $item->get(Stash\Item::SP_OLD);
		if ( !$item->isMiss() ) {
			echo $this->get_cache($data);
			exit;
		}
	}
}
