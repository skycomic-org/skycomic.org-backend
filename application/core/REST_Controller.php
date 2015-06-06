<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This Controller is used to do member tasks.
 * (User will be redirect to portal if he does not login)
 *
 * @author Chieh Yu (welkineins@gmail.com)
 */
// class REST_Controller extends MEMBER_Controller
class REST_Controller extends MX_Controller
{
	/**
	 * Constructor
	 */
	
	protected $limit = 15;
	
	public function __construct () {
		parent::__construct();
	}
	
	// remap controller name
	public function _remap ($method, $params = array()) {
		$prefix = '';
		switch ($this->input->get_method()) {
			case 'GET':
				if ( ($limit = $this->input->get('limit')) ) {
					if ( !is_num($limit) OR $limit < 1 ) {
						$this->output->json(400, 'invalid limit setting(need to be 0 < int <= 200).');
					} elseif ( $limit > 200 ) {
						$this->limit = 200;
					} else {
						$this->limit = $limit;
					}
				}
				$prefix = 'read';
				break;
			case 'PUT':
				$prefix = 'update';
				break;
			case 'POST':
				$prefix = 'create';
				break;
			case 'DELETE':
				$prefix = 'delete';
				break;
		}
		$method = $prefix . '_' .$method;
		if (method_exists($this, $method)) {
			return call_user_func_array(array($this, $method), $params);
		}
		$this->output->json(404, 'method not found.');
	}
	
} // End of MEMBER_Controller class

/* End of file MEMBER_Controller.php */
