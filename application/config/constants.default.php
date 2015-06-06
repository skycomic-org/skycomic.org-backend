<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Server Statics
|--------------------------------------------------------------------------
|
*/
define('PUBLIC_PATH', 		FCPATH . 'public/');
define('PRIVATE_PATH', 		FCPATH . 'private/');
# change to your cdn image link
define('CDN_LINK', 		"http://" . $_SERVER['HTTP_HOST'] . '/');
# change to your fb app config
define('FB_APPID', 			'');
define('FB_SECRET',			'');

define('MEDIAWIKI_PATH',    PRIVATE_PATH . 'mediawiki-1.15.4/');

define('ISDEBUG', 			0);
define('ALLOW_REGISTER',	1);
define('GRAB_FORK',			0);

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

/* End of file constants.php */
/* Location: ./application/config/constants.php */
