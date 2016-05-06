<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Grab_model extends CI_Model {
	private $CI;
	function __construct () {
		parent::__construct();
		$this->CI = & get_instance();
		$this->CI->load->library('curl');
	}

	public function check () {
		$finalResults = [];
		$sites = $this->read_sites();
		foreach ($sites as $row) {
			$site = $row['name'];
			$site_id = $row['sid'];

			$this->load->model('sites/site_'. $site, 'site_model_' . $site);
			$result = $this->{'site_model_' . $site}->check();

			$yesterday = new Datetime('yesterday');
			$maxUpdateTime = new Datetime($this->db->select('MAX(update_time) AS max_update_time')
				->from('index_chapter')
				->where('sid', $site_id)
				->get()->row()->max_update_time);
			$result->crawler_ok = $maxUpdateTime > $yesterday;
			$finalResults[$site][] = $result;
		}
		return $finalResults;
	}

	/**
	 * check whether chapters are all in database
	 */
	public function is_in_db ($chapterCount, $tid) {
		$count = $this->db->select('COUNT(*) AS `count`')
			->from('index_chapter')
			->where('tid', $tid)
			->get()->row()->count;

		if ($chapterCount != $count) {
			return False;
		}

		return $this->db->select('COUNT(*) AS `count`')
			->from('index_chapter')
			->where('tid', $tid)
			->where('error', 1)
			->get()->row()->count == 0;
	}

	public function signal_comic_error ($cid) {
		$this->CI->curl->url(base_url() . 'api/comic_error/' . $cid)->add()->get();
	}

	public function read_vtid ($name) {
		if ( empty($name) ) {
			return NULL;
		}
		$row = $this->db->select('vtid')->from('virtual_title')->where('name', $name)->get()->row();
		if ( !isset($row->vtid) || !$row->vtid ) {
			$this->db->insert('virtual_title', [
				'name' => $name
			]);
			$vtid = $this->db->insert_id();
		} else {
			$vtid = $row->vtid;
		}
		return $vtid;
	}

	public function get_author_id ($name) {
		if ( empty($name) ) {
			return NULL;
		}
		$row = $this->db->select('id')->from('author')->where('name', $name)->get()->row();
		if ( !isset($row->id) || !$row->id ) {
			$this->db->insert('author', [
				'name' => $name
			]);
			$id = $this->db->insert_id();
		} else {
			$id = $row->id;
		}
		return $id;
	}

	public function curlUseProxy ($curl) {
		if (!isset($this->proxys_)) {
			$this->proxys_ = $this->read_proxys('index');
		}
		return $curl->header('cache-control', 'no-cache')
			->proxy($this->proxys_);
	}

	public function render_image ($params) {
		$params['referer'] = isset($params['referer']) ? $params['referer'] : False;
		$params['thumbnail'] = isset($params['thumbnail']) ? $params['thumbnail'] : False;
		$params['type'] = isset($params['type']) ? $params['type'] : 'jpeg';

		if ( $params['referer'] ) {
			$this->CI->curl->referer($params['referer']);
		}
		$image = $this->CI->curl->url($params['url'])
						->proxy( $this->read_proxys() )
						->add()->get();

		$reasonable_size = $params['thumbnail'] ? 1024 : 1024*10;
		if ( strlen($image) < $reasonable_size || strlen($image) == 36974 ) {
			elog('grab_model - image error, url=' . $params['url']);
			return False;
		}

		$offset = 3600 * 24 * 30;
		 // calc the string in GMT not localtime and add the offset
		header_remove('Pragma');
		$this->CI->output->set_header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + $offset));
		$this->CI->output->set_header('Cache-Control: max-age='. $offset);
		$this->CI->output->set_content_type('image/' . $params['type']);
		$this->CI->output->set_output($image);
		return True;
	}

	public function regen_title_info () {
		$pop_sql = 'REPLACE INTO `title_info` (tid, pop, update_time)'
				 . ' SELECT CHAPTER.tid, SUM(pop.month) AS pop, MAX(CHAPTER.update_time) AS update_time'
				 . ' FROM index_chapter AS CHAPTER'
				 . ' LEFT JOIN pop ON pop.cid = CHAPTER.cid'
				 . ' GROUP BY CHAPTER.tid';
		$this->db->query($pop_sql);
	}

	public function read_sites ($sid = False) {
		if ($sid === False) {
			$this->db->where('enable', '1');
		} else {
			$this->db->where('sid', $sid);
			$sql='SELECT * FROM sites WHERE sid = '.intval($sid);
			$arr = $db->query_assoc($sql);
			return isset($arr[0]) ? $arr[0] : False;
		}
		$q = $this->db->select('*')
					  ->from('sites')
					  ->get();
		return $q->num_rows() != 0 ? $q->result_array() : False;
	}

	public function read_title_by_sid ($sid) {
		$sql = 'SELECT * FROM index_title WHERE stop_renew = 0 AND sid = '.$sid;
		$result = $this->db->query($sql)->result_array();
		foreach ($result as &$row) {
			$row['meta'] = json_decode($row['meta']);
		}
		return $result;
	}

	public function read_proxys ($usage='image') {
		$sql='SELECT * FROM proxys WHERE available = 1 AND `usage` = "'. $usage .'"';
		$result = $this->db->query($sql)->result_array();
		$proxys = array();
		foreach ( $result as $row ) {
			for ( $i = 1; $i <= $row['weight']; $i++ ) {
				$proxys[] = $row['ip'] . ':' . $row['port'];
			}
		}
		return $proxys;
	}

	public function read_catid_by_name ($title) {
		$sql='SELECT t2.category_id'
			.' FROM chinese_MPS t1'
			.' LEFT JOIN category t2 ON t1.MPS = t2.name'
			.' WHERE t1.chinese = "'. substr($title, 0, 3) .'"';
		$q = $this->db->query($sql);
		if ($q->num_rows() == 0) {
			$alth = strtoupper( substr($title, 0, 1) );
			$sql='SELECT category_id FROM category'
				.' WHERE name = "'. $alth .'"';
			$q = $this->db->query($sql);
		}
		$row = $q->row();
		return isset($row->category_id) ? $row->category_id : 74;
	}

	public function image_comic ($cid, $page=False, $getLink = False) {
		$this->CI = & get_instance();
	    $sql = 'SELECT sites.name FROM index_chapter'
			 .' LEFT JOIN sites USING(`sid`)'
			 .' WHERE cid = '. $this->db->escape($cid);
		$result = $this->db->query($sql)->result_array();
		if ( count($result) > 0 ) {
			$this->CI->load->model('sites/site_'. $result[0]['name'], 'mod');
			return $this->CI->mod->image_comic($cid, $page, $getLink);
		} else {
			show_404();
		}
	}

	public function image_thumbnail ($tid, $medium = False, $getLink = False) {
		$this->CI = & get_instance();
		$sql = 'SELECT sites.name FROM index_title'
			 .' LEFT JOIN sites USING(`sid`)'
			 .' WHERE id = '. $this->db->escape($tid);
		$result = $this->db->query($sql)->result_array();
		if ( count($result) ) {
			$this->CI->load->model('sites/site_'. $result[0]['name'], 'mod');
			return $this->CI->mod->image_thumbnail($tid, $medium, $getLink);
		} else {
			header('HTTP/404 Not Found');
			exit;
		}
	}

}

// end of file grab_model.php