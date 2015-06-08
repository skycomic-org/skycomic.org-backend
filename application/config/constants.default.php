<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once "constants_base.php";
/*
|--------------------------------------------------------------------------
| Server Statics
|--------------------------------------------------------------------------
|
*/
# change to your cdn image link
define('CDN_LINK', 		"http://" . $_SERVER['HTTP_HOST'] . '/');
# change to your fb app config
define('FB_APPID', 			'');
define('FB_SECRET',			'');

define('ISDEBUG', 			0);
define('ALLOW_REGISTER',	1);
define('GRAB_FORK',			0);

