<?php

function elog ($msg, $level = 'error') {
	$LEVEL = strtoupper($level);
	$date = date('Y-m-d');
	$time = date('H:i:s');
	$file = APPPATH . "logs/log-{$date}.php";
	if (!file_exists($file)) {
		file_put_contents($file, "<?php exit;\n\n");
	}

	$fp = fopen($file, 'a+');

	flock($fp, LOCK_EX);
	fwrite($fp, "$LEVEL - $date $time --> $msg\n");
	flock($fp, LOCK_UN);

	fclose($fp);
	if (ENVIRONMENT == 'development')
		echo "[$level] $msg\n";
}

function elog_rotate () {
	// rotate a month
	$tenday = date('Y-m-d', time() - 60*60*24*31);
	$yesterday = date('Y-m-d', time() - 60*60*24);
	$today = date('Y-m-d');

	$tenday_log = APPPATH . "logs/log-{$tenday}.php";
	$yesterday_log = APPPATH . "logs/log-{$yesterday}.php";
	$today_log = APPPATH . "logs/log-{$today}.php";

	echo file_get_contents($yesterday_log);
	@ unlink($tenday_log);
}