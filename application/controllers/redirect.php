<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Redirect extends CI_Controller {
	function __construct () {
		parent::__construct();
		$redir = '';
		switch ( $this->uri->segment(1) ) {
			case 'all':
				$redir = 'main#/comics/online';
				break;
			case 'browse':
				if ($this->uri->segment(3))
					$redir = 'main#/browse/'. $this->uri->segment(3) .'/1';
				else 
					$redir = 'main#/new';
				break;
			default :
				show_404();
		}
		redirect($redir, 'refresh');
	}
}
// End of file redirect.php