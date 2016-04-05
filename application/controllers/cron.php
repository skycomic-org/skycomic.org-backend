<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cron extends CI_Controller {

	function __construct () {
		parent::__construct();
		error_reporting(E_ALL);
		if ( ! $this->input->is_cli_request() ) {
			show_404();
			exit;
		}
	}

	public function check () {
		$this->load->model('grab_model');
		print_r($this->grab_model->check());
	}

	public function cron_update ($type) {
		elog('cron_update ' . $type, 'debug');
		$this->load->model('grab_model');
		$this->load->library('forkjobs');
		$sites = $this->grab_model->read_sites();
		foreach ($sites as $row) {
			$site = $row['name'];
			elog('updating ' . $site, 'debug');
			$this->load->model('sites/site_'. $site, 'site_model_' . $site);
			try {
				$this->{'site_model_' . $site}->execute($type);
			} catch (Exception $e) {
				elog('site['.$site.'] Caught exception: ',  $e->getMessage());
			}
		}
		elog('regen_title_info', 'debug');
		$this->grab_model->regen_title_info();
	}

	public function cron_popular () {
		$this->load->model('pop_model');
		$this->pop_model->cron();
	}

	public function cron_reset_stop_renew () {
		$this->db->update('index_title', array(
			'stop_renew' => 0
		));
	}

	// check proxy state and dynamiclly adjust weight
	public function proxy () {
		if (ENVIRONMENT != 'image') {
			return ;
		}
		$timeout = 0.25;
		$this->load->library('curl');

		$proxys = $this->db->select('ip, port')->from('proxys')->get()->result();
		$origin = $this->curl->url('http://www.8comic.com/images/logo.gif')
							->add()->get();
		$avail_proxys = [];
		foreach ( $proxys as $proxy ) {
			$time = microtime(True);
			$image = $this->curl->url('http://www.8comic.com/images/logo.gif')
							->proxy( $proxy->ip .':'. $proxy->port )
							->timeout(5)
							->referer('http://www.8comic.com')
							->add()->get();
			$time = (microtime(True) - $time);
			if ( strlen($origin) == strlen($image) && $time < $timeout ) {
				$avail_proxys[] = [
					'ip' => $proxy->ip,
					'port' => $proxy->port,
					'speed' => (1 / $time)
				];
				$avail = '1';
			} else {
				$avail = '0';
			}
			$this->db->update('proxys', [
				'available' => $avail
			], [
				'ip' => $proxy->ip,
				'port' => $proxy->port
			]);
		}
		if ( count($avail_proxys) == 0 ) {
			echo 'WARNING !! All proxys are down!!!';
		} else {
			$min = $avail_proxys[0]['speed'];
			foreach ($avail_proxys as $proxy) {
				if ( $proxy['speed'] < $min ) {
					$min = $proxy['speed'];
				}
			}
			foreach ($avail_proxys as $proxy) {
				$weight = ceil($proxy['speed'] / $min);
				$this->db->update('proxys', [
					'weight' => $weight
				], [
					'ip' => $proxy['ip'],
					'port' => $proxy['port']
				]);
			}
		}
	}

	// each five minute excutes.
	public function rutine () {
		$this->proxy();
	}

	public function init () {
		$this->cron_update('title');
		$this->update();
	}

	public function daily () {
		$w = date('N');//week, 1 to 7
        $h = date('H');
        $m = date('i');
		$this->cron_popular();
        #$this->cron_reset_stop_renew();
        elog_rotate();
		if ( $w == 1 ) {
			$this->cron_update( 'title' );
		}
	}

	// forcing update chapters
	public function update () {
		$this->cron_update( 'chapter' );
	}
}
/* End of file cron.php */
/* Location: ./application/controllers/cron.php */
