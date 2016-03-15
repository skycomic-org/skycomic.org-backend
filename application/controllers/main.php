<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Main extends MEMBER_Controller {

	public function index () {
		if ( base_url() != 'http://' . $_SERVER['HTTP_HOST'] . '/' ) {
			redirect(base_url());
		}
		$this->sandvich
			 ->partial('nav', 'nav/main')
			 ->partial('content', 'partial/main')
			 ->render('layout/main', array(
				'fluid' => True
			 ));
	}

	public function ad ($tid = false, $type = 1) {
		if ($this->session->userdata('vip')) {
			return;
		}
		$meta = (object) array(
			'kw' => '漫畫網,最新連載,最新漫畫,skycomic,comic,漫畫,海賊王,火影忍者,獵人,死神,Free Comic,免費漫畫,Naruto,One Piece,Bleach,Hunter X Hunter',
			'title' => '',
			'description' => '地表最快的漫畫網站'
		);
		if ($tid) {
			$this->load->model('comic_model', 'comic');
			$title = $this->comic->read_title_by_tid($tid);
			if ( count($title) ) {
				$meta->kw = $title->name . ',' . $meta->kw;
				$meta->title = $title->name . ' - ';
				$meta->description = $title->intro;
			}
		}
		$this->load->view('partial/ad', array('meta' => $meta));
	}
}

/* End of file main.php */