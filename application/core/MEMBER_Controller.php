<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This Controller is used to do member tasks.
 * (User will be redirect to portal if he does not login)
 *
 * @author Chieh Yu (welkineins@gmail.com)
 */
class MEMBER_Controller extends CI_Controller
{
	/**
	 * Constructor
	 */
	public function __construct () {
		parent::__construct();
		if ( $this->auth_model->invalidIP() ) {
			die('abuse, permission denied.');
		}
		if ( $this->auth_model->logined() !== True ) {
			$this->auth_model->login('guest', 'guestguest');
		}
	}
	
} // End of MEMBER_Controller class

/* End of file MEMBER_Controller.php */
