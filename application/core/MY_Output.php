<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Output extends CI_Output {
	private $cache_instance = null;
	protected $json;

	function __construct () {
		parent::__construct();
		$this->cleanup();
	}

	public function cacheable () {
		header_remove('Set-Cookie');
		$this->set_header('X-Cacheable: True');
	}

	public function cleanup () {
		$this->json = array(
			'http' => array(
				'code' => 200,
				'msg'  => ''
			)
		);
		return $this;
	}

	function set_data ($key, $val=NULL) {
		if ( $val !== NULL ) {
			$this->json['data'][$key] = $val;
		} else {
			$this->json['data'] = $key;
		}
		return $this;
	}

	function http_code ($code = NULL) {
		if ( $code === NULL ) {
			return $this->json['http']['code'];
		} else {
			$this->json['http']['code'] = $code;
			return $this;
		}
	}

	function http_msg ($msg = NULL) {
		if ( $msg === NULL ) {
			return $this->json['http']['msg'];
		} else {
			$this->json['http']['msg'] = $msg;
			return $this;
		}
	}

	function obj () {
		return (object)$this->json;
	}

	function json ($code=NULL, $msg=NULL) {
		if ($code !== NULL) {
			$this->http_code($code);
		}
		if ($msg !== NULL) {
			$this->http_msg($msg);
		}
		if ( isset($this->json['data']) AND is_array($this->json['data']) ) {
			$this->json['count'] = count($this->json['data']);
		}

		$this->set_header('HTTP/1.1 '.$this->json['http']['code']);
		$this->set_content_type('application/json');
		$this->set_output(json_encode($this->json));
	}

	function error ($code=NULL, $msg=NULL) {
		$this->json($code, $msg);
		$this->_display();
		exit;
	}

	public function cache ($minute) {
		if (ENVIRONMENT == 'development')
			return;
		require_once APPPATH . 'hooks/cache_override.php';

		parent::cache($minute);
		$offset = $minute * 60;
		 // calc the string in GMT not localtime and add the offset
		$this->set_header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + $offset));
		$this->set_header('Cache-Control: max-age='. $offset);
		header_remove('Pragma');
		$this->cacheable();
		$this->cache_instance = new Cache_Override();
		$this->cache_instance->lock();
	}

	public function _write_cache (& $output, & $headers) {
		$this->cache_instance->save_cache($headers, $output, $this->cache_expiration * 60);
		log_message('debug', "Cache file written: ".$this->cache_instance->get_key());

		$expire = time() + ($this->cache_expiration * 60);
		$this->set_cache_header($_SERVER['REQUEST_TIME'], $expire);
	}

	/**
	 * Display Output
	 *
	 * All "view" data is automatically put into this variable by the controller class:
	 *
	 * $this->final_output
	 *
	 * This function sends the finalized output data to the browser along
	 * with any server headers and profile data.  It also stops the
	 * benchmark timer so the page rendering speed and memory usage can be shown.
	 *
	 * @access	public
	 * @param 	string
	 * @return	mixed
	 */
	function _display($output = '')
	{
		// Note:  We use globals because we can't use $CI =& get_instance()
		// since this function is sometimes called by the caching mechanism,
		// which happens before the CI super object is available.
		global $BM, $CFG;

		// Grab the super object if we can.
		if (class_exists('CI_Controller'))
		{
			$CI =& get_instance();
		}

		// --------------------------------------------------------------------

		// Set the output data
		if ($output == '')
		{
			$output =& $this->final_output;
		}

		// --------------------------------------------------------------------

		// Do we need to write a cache file?  Only if the controller does not have its
		// own _output() method and we are not dealing with a cache file, which we
		// can determine by the existence of the $CI object above
		if ($this->cache_expiration > 0 && isset($CI) && ! method_exists($CI, '_output'))
		{
			$this->_write_cache($output, $this->headers);
		}

		// --------------------------------------------------------------------

		// Parse out the elapsed time and memory usage,
		// then swap the pseudo-variables with the data

		$elapsed = $BM->elapsed_time('total_execution_time_start', 'total_execution_time_end');

		if ($this->parse_exec_vars === TRUE)
		{
			$memory	 = ( ! function_exists('memory_get_usage')) ? '0' : round(memory_get_usage()/1024/1024, 2).'MB';

			$output = str_replace('{elapsed_time}', $elapsed, $output);
			$output = str_replace('{memory_usage}', $memory, $output);
		}

		// --------------------------------------------------------------------

		// Is compression requested?
		if ($CFG->item('compress_output') === TRUE && $this->_zlib_oc == FALSE)
		{
			if (extension_loaded('zlib'))
			{
				if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) AND strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE)
				{
					ob_start('ob_gzhandler');
				}
			}
		}

		// --------------------------------------------------------------------

		// Are there any server headers to send?
		if (count($this->headers) > 0)
		{
			foreach ($this->headers as $header)
			{
				@header($header[0], $header[1]);
			}
		}

		// --------------------------------------------------------------------

		// Does the $CI object exist?
		// If not we know we are dealing with a cache file so we'll
		// simply echo out the data and exit.
		if ( ! isset($CI))
		{
			echo $output;
			log_message('debug', "Final output sent to browser");
			log_message('debug', "Total execution time: ".$elapsed);
			return TRUE;
		}

		// --------------------------------------------------------------------

		// Do we need to generate profile data?
		// If so, load the Profile class and run it.
		if ($this->enable_profiler == TRUE)
		{
			$CI->load->library('profiler');

			if ( ! empty($this->_profiler_sections))
			{
				$CI->profiler->set_sections($this->_profiler_sections);
			}

			// If the output data contains closing </body> and </html> tags
			// we will remove them and add them back after we insert the profile data
			if (preg_match("|</body>.*?</html>|is", $output))
			{
				$output  = preg_replace("|</body>.*?</html>|is", '', $output);
				$output .= $CI->profiler->run();
				$output .= '</body></html>';
			}
			else
			{
				$output .= $CI->profiler->run();
			}
		}

		// --------------------------------------------------------------------

		// Does the controller contain a function named _output()?
		// If so send the output there.  Otherwise, echo it.
		if (method_exists($CI, '_output'))
		{
			$CI->_output($output);
		}
		else
		{
			echo $output;  // Send it to the browser!
		}

		log_message('debug', "Final output sent to browser");
		log_message('debug', "Total execution time: ".$elapsed);
	}
}