<?php
class Ecache {
	private $CI;
	function __construct () {
		$this->CI = &get_instance();
		$this->CI->load->driver('cache', array('adapter' => 'apc', 'backup' => 'file'));
	}

	function get ($key, $func = null, $ttl = 600) {
		if ($data = $this->CI->cache->get($key)) {
			$result = json_decode($data);
		} else {
			$result = array($func());
			$this->CI->cache->save($key, json_encode($result), $ttl);
		}
		return $result[0];
	}
}