<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Exceptions extends CI_Exceptions {
	/**
	 * Constructor
	 */
	var $output;
	public function __construct () {
		parent::__construct();
		$this->output =& load_class('Output', 'core');
		$this->input =& load_class('Input', 'core');
	}

	// --------------------------------------------------------------------

	/**
	 * 404 Page Not Found Handler
	 *
	 * @access	private
	 * @param	string	the page
	 * @param 	bool	log error yes/no
	 * @return	string
	 */
	function show_404($page = '', $log_error = TRUE) {
		if ( $this->input->is_ajax_request() ) {
			$heading = "404 Page Not Found";
			$message = "The page you requested was not found.";

			// By default we log this, but allow a dev to skip it
			if ($log_error)
			{
				log_message('error', '404 Page Not Found --> '.$page);
			}

			$this->output->error(404, "page: {$page} is not found.");
		} else {
			parent::show_404($page, $log_error);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * General Error Page
	 *
	 * This function takes an error message as input
	 * (either as a string or an array) and displays
	 * it using the specified template.
	 *
	 * @access	private
	 * @param	string	the heading
	 * @param	string	the message
	 * @param	string	the template name
	 * @param 	int		the status code
	 * @return	string
	 */
	function show_error($heading, $message, $template = 'error_general', $status_code = 500) {
		if ( $this->input->is_ajax_request() ) {
			set_status_header($status_code);
			$message = $heading . "\n\n" . implode("\n", ( ! is_array($message)) ? array($message) : $message)."\n";
			
			$this->output->json($status_code, $message);
			
			if (ob_get_level() > $this->ob_level + 1)
			{
				ob_end_clean();
			}
			
			ob_start();
			$this->output->_display();
			$buffer = ob_get_contents();
			ob_end_clean();
			return $buffer;
		} else {
			return parent::show_error($heading, $message, $template, $status_code);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Native PHP error handler
	 *
	 * @access	private
	 * @param	string	the error severity
	 * @param	string	the error string
	 * @param	string	the error filepath
	 * @param	string	the error line number
	 * @return	string
	 */
	function show_php_error($severity, $message, $filepath, $line) {
		if ( $this->input->is_ajax_request() ) {
			$severity = ( ! isset($this->levels[$severity])) ? $severity : $this->levels[$severity];

			$filepath = str_replace("\\", "/", $filepath);

			// For safety reasons we do not show the full file path
			if (FALSE !== strpos($filepath, '/'))
			{
				$x = explode('/', $filepath);
				$filepath = $x[count($x)-2].'/'.end($x);
			}

			if (ob_get_level() > $this->ob_level + 1)
			{
				ob_end_clean();
			}
			ob_start();
			include(APPPATH.'errors/error_php.php');
			$msg = ob_get_contents();
			ob_end_clean();
			
			$this->output->cleanup()->error(500, $msg);
		} else {
			parent::show_php_error($severity, $message, $filepath, $line);
		}
	}
}
// END Exceptions Class

/* End of file Exceptions.php */
/* Location: ./system/core/Exceptions.php */