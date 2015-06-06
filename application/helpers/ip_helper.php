<?php

function is_country ($country='TW') {
	if (!isset($_SERVER["HTTP_CF_IPCOUNTRY"])) {
		$_SERVER["HTTP_CF_IPCOUNTRY"] = 'TW';
	}
	return $_SERVER["HTTP_CF_IPCOUNTRY"] == $country;
}

function ip_config () {
	require_once APPPATH . 'libraries/Ip2country.php';
	$config = array(
		'host' => 'localhost', //example host name
		'port' => 3306, //3306 -default mysql port number
		'dbName' => 'ip2country', //example db name
		'dbUserName' => 'ip2country', //example user name
		'dbUserPassword' => 'ip2country', //example user password
		'tableName' => 'ip_to_country', //example table name
	);
	$ip = $_SERVER['REMOTE_ADDR'];
	$phpIp2Country = new Ip2country($ip, $config);
	$result = array(
		'ip' => $ip,
		'country_code' => $phpIp2Country->getInfo(IP_COUNTRY_CODE),
		'country_name' => $phpIp2Country->getInfo(IP_COUNTRY_NAME)
	);
	return (object) $result;
}