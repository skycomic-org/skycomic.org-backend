<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Rss extends CI_Controller {
	private $auth_length = 5;
	private $limit = 30;

	public function index () {
		$this->output->cache(30);
		$this->load->model('comic_model');
		$expires = date('D, d M Y H:i:s',time() + 300) . ' GMT';
		header_remove("Pragma");
		header("Expires: ".$expires);
		header("Cache-control: max-age=300");
		header('Content-type: text/xml');
		$data['new'] = $this->comic_model->read_chapters_newest($this->limit);
		$this->load->view('rss', $data);
	}

	function json () {
		$this->output->cache(30);
		$this->load->model('comic_model');
		$newChapter = $this->comic_model->read_chapters_newest($this->limit);
		echo json_encode($newChapter);
	}

	public function favorite ($uid = False) {
		// if (!$this->auth_model->auth_check($uid, $auth, $this->auth_length))
			// $this->output->error(403, 'permission denied.');
		$this->output->cache(30);
		$this->load->model('user_model');
		if ($uid === False) {
			if ($uid = $this->session->userdata('user_id')) {
				$user = $this->user_model->read_by_user_id($uid);
				redirect('rss/favorite/' . $user->id);
			} else {
				$this->output->error(400, 'please login first.');
			}
		}
		$this->load->model('favorite_model', 'favorite');
		$user = $this->user_model->read_by_id($uid);
		$favorite = $this->favorite->read($user->sn);

		$result = array();
		foreach ($favorite as $comic) {
			if ($comic['stop_renew'] == 1)
				continue;
			$data = array(
				'tid' => $comic['tid'],
				'title' => $comic['title']
			);
			foreach ($comic['comics'] as $chapter) {
				$row = array_merge($data, $chapter);
				$row['time'] = strtotime($chapter['update_time']);
				unset($row['date']);
				$result[] = $row;
			}
		}
		usort($result, function ($a, $b) {
			return $a['time'] < $b['time'];
		});
		array_splice($result, 30);
		foreach ($result as &$row) {
			unset($row['time']);
		}

		if (!$this->input->get('format') || $this->input->get('format') == 'xml') {
			header('Content-Type: application/atom+xml; charset=utf-8');
			$this->load->view('api/rss_favorite', array('data' => $result));
		} else {
			$this->output->set_data($result);
			$this->output->json(200);
			return $this->output->obj();
		}
	}

	function sitemap () {
		$this->output->cache(24*60);
		$data = [];
		$data[] = (object)['url' => 'http://www.skycomic.org/main'];
		$titles = $this->db->select('id')
							->from('index_title')
							->get()->result();
		foreach ($titles as $title) {
			$data[] = (object) [
				'url' => 'http://www.skycomic.org/main#/title/' . $title->id,
				'image' => 'http://www.skycomic.org/images/thumbnail/' . $title->id . '/1'
			];
		}
		$this->load->view('sitemap', ['urls' => $data]);
	}
}

/* End of file rss.php */
/* Location: ./application/controllers/rss.php */