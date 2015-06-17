<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends REST_Controller {

	private $new_limit = 16;
	private $all_limit = 50;
	private $comment_limit = 16;
	private $long_cache = 120;
	private $short_cache = 10;

	function __construct () {
		parent::__construct();
		$this->user_id = $this->session->userdata('user_id');
	}
	
	public function index () {
		show_404();
	}
	
	private function _no_guest () {
		if ($this->session->userdata('id') == 'guest') {
			exit;
		}
	}

	/*
	 * Static APIs
	 */
	public function read_chapter ($cid) {
		$this->output->cache($this->short_cache);

		$this->load->model('comic_model');
		$data = array();
		$data = $this->comic_model->read_chapter_by_cid($cid);
		$data->title = $this->comic_model->read_title_by_tid($data->tid)->name;
		$this->output->set_data($data);
		$this->output->json(200);
		
		return $this->output->obj();
	}

	public function read_vtitle ($vtid) {
		$this->output->cache($this->short_cache);

		$this->load->model('comic_model');
		$titles = $this->comic_model->read_titles_by_vtid($vtid);
		if (count($titles) == 0) {
			$this->output->error(400, 'no such vtitle.');
		} else {
			$data = array();
			foreach($titles as $title) {
				$tid = $title->id;
				$datarow = $this->read_title($tid)->data;
				foreach ($datarow['chapters'] as &$row) {
					unset($row->meta);
				}
				$data[] = $datarow;
			}
			$this->output->set_data($data);
			$this->output->json(200);
		}
		return $this->output->obj();
	}
	
	public function read_tid2vtid ($tid) {
		$this->output->cache($this->long_cache);

		$this->load->model('comic_model');
		$vtid = $this->comic_model->tid2vtid($tid);
		if ($vtid) {
			$this->output->set_data($vtid);
			$this->output->json(200);
		} else {
			$this->output->error(400, 'not such title.');
		}
		
		return $this->output->obj();
	}

	public function read_title ($tid) {
		$this->output->cache($this->short_cache);

		$this->load->helper('timefix');
		$this->load->model('comic_model');
		$this->load->model('user_model');
		$data = array();
		$title = $this->comic_model->read_title_by_tid($tid);
		$data = [
			'tid' => $title->id,
			'title' => $title->name,
			'site' => $this->db->select('url, nickname')->from('sites')->where('sid', $title->sid)->get()->row(),
			'intro' => $title->intro,
			'stop_renew' => $title->stop_renew ? '已完結' : '連載中',
			'pop' => $title->pop,
			'author_id' => $title->author_id,
			'update_time' => timeFix($title->update_time)
		];
		$this->db->order_by('index', 'desc');
		$data['chapters'] = $this->comic_model->read_chapters_by_tid($tid);
		$author = $this->db->select('name')->from('author')->where('id', $title->author_id)->get()->row();
		$data['author'] = isset($author->name) ? $author->name : '';
		$this->output->set_data($data);
		$this->output->json(200);
		
		return $this->output->obj();
	}
	
	public function read_new () {
		$this->output->cache($this->short_cache);

		$data = array();
		$this->load->model('comic_model');
		$this->load->model('pop_model');
		
		$data['newest'] = $this->comic_model->read_chapters_newest($this->new_limit);
		$data['lastview'] = $this->pop_model->last_view($this->new_limit);
		$data['populars'] = array(
			'titles' => array(
				'today' => $this->pop_model->read_titles('today', $this->new_limit),
				'week' => $this->pop_model->read_titles('week', $this->new_limit),
				'month' => $this->pop_model->read_titles('month', $this->new_limit),
			),
			'chapters' => array(
				'today' => $this->pop_model->read_chapters('today', $this->new_limit),
				'week' => $this->pop_model->read_chapters('week', $this->new_limit),
				'month' => $this->pop_model->read_chapters('month', $this->new_limit),
			),
		);
		$this->output->set_data($data);
		$this->output->json(200);
		
		return $this->output->obj();
	}

	public function read_vtitles ($cat_id = 0, $type = 'online', $page = '1', $order = 'pop') {
		$this->output->cache($this->long_cache);

		$this->load->model('comic_model', 'comic');
		$data = array();
		$this->comic->limit = $this->all_limit;
		$data['data'] = $this->comic->read_vtitles($cat_id, $type, $page, $order);
		$data['pages'] = ceil($this->comic->read_vtitles_count($cat_id, $type) / $this->all_limit);
		$this->output->set_data($data);
		$this->output->json(200);
		
		return $this->output->obj();
	}

	// Categories
		// Read by cat_id, type, page, order
		// Type: online|finish
	public function read_titles ($cat_id = 0, $type = 'online', $page = '1', $order = 'pop') {
		$this->output->cache($this->long_cache);

		$this->load->model('comic_model', 'comic');
		$data = array();
		$this->comic->limit = $this->all_limit;
		$data['data'] = $this->comic->read_titles($cat_id, $type, $page, $order);
		$data['pages'] = ceil($this->read_titles_count($cat_id, $type)->data / $this->all_limit);
		$this->output->set_data($data);
		$this->output->json(200);
		
		return $this->output->obj();
	}
	
	// Categories
		// Read page count by cat_id, type, page, order
		// Type: online|finish
	public function read_titles_count ($cat_id = 0, $type = 'online') {
		$this->output->cache($this->long_cache);

		$this->load->model('comic_model');
		$this->output->set_data($this->comic_model->read_titles_count($cat_id, $type));
		$this->output->json(200);
		
		return $this->output->obj();
	}
	
		// Read Categories
		// Type: online|finish
	public function read_category ($type = 'online') {
		$this->output->cache($this->long_cache);

		$this->load->model('category_model', 'cat');
		$data = array();
		$data['en'] = $this->cat->read_by_lang('en', $type);
		$data['zh_tw'] = $this->cat->read_by_lang('zh_tw', $type);
		$this->output->set_data($data);
		$this->output->json(200);
		
		return $this->output->obj();
	}

	// Authors
	public function read_author ($author_id) {
		$this->output->cache($this->long_cache);

		$this->load->model('author_model');
		$this->load->model('comic_model');
		$data = array();
		$data['name'] = $this->author_model->read($author_id)->name;
		$data['works'] = $this->comic_model->read_vtitles_by_author($author_id);
		$this->output->set_data($data);
		$this->output->json(200);
		
		return $this->output->obj();
	}
	
	// Facebook Crawler
	public function read_fb_page () {
		$this->output->cache($this->short_cache);

		$this->load->library('facebook', [
			'appId' => FB_APPID,
			'secret' => FB_SECRET
		]);
		$limit = 3;
		$this->load->helper('timefix');
		$posts = $this->facebook->api('Skycomic.org/posts', ['param' => 'limit=' . $limit + 1]);
		$result = array();
		foreach ($posts['data'] as $post) {
			if ( isset($post['message']) ) {

				$d = [
					'id' => $post['id'],
					'picture' => isset($post['picture']) ? $post['picture'] : False,
					'message' => auto_link(nl2br($post['message'])) ,
					'created_time' => timeFix($post['created_time']),
					'comments' => isset($post['comments']) ? count($post['comments']['data']) : 0,
					'likes' => isset($post['likes']) ? count($post['likes']) : 0,
				];
				$result[] = $d;
				$limit --;
				if ( $limit == 0 ) break;
			}
		}
		$this->output->set_data($result);
		$this->output->json(200);
		return $this->output->obj();
	}
	
	/*
	 * Dynamic APIs
	 */
	public function read_comic_error ($cid) {
		// cache since we'll update error state depends on your grab rate.
		$this->output->cache($this->short_cache);

		$result = $this->db->select('tid')->from('index_chapter')
			->where('cid', $cid)->get();
		if (0 != $result->num_rows()) {
			$tid = $result->row()->tid;
			$this->db->where('tid', $tid)
				->update('index_chapter', ['error' => 1]);
			$this->output->json(200);
		} else {
			$this->output->error(400, 'no such cid');
		}
		return $this->output->obj();
	}

	// Search
	public function read_search ($text=False) {
		$query = htmlspecialchars(urldecode($text));
        if ( empty($query) )
            $this->output->error(400, '沒輸入欲搜尋之文字');
        if ( strlen($query) > 25 )
            $this->output->error(400, '欲搜尋之文字過長');
        
		$data = array();
		
		$data['comics'] = $this->db->select('vtid,name AS `title`')
						 ->from('virtual_title')
						 ->like('name', $query)
						 ->get()->result_array();

		$data['authors'] = $this->db->select('id,name AS `author_name`')
						 ->from('author')
						 ->like('name', $query)
						 ->get()->result_array();

		$this->output->set_data($data);
		$this->output->json(200);
		
		return $this->output->obj();
	}
	 
	// View Records
		// Read
	public function read_view_record () {
		// query last viewed comics
		$result = $this->db->select('*')
			->from('user_lastview')
			->where('u_sn', $this->user_id)
			->where('page !=', 1)
			->order_by('viewtime', 'DESC')
			->limit(20)->get()->result();

		foreach ($result as &$row) {
			$ext_info = $this->db->select('vtid, TITLE.name AS title, CHAPTER.name AS chapter')
				->from('index_chapter AS CHAPTER')
				->join('index_title AS TITLE', 'CHAPTER.tid = TITLE.id')
				->where('CHAPTER.cid', $row->cid)
				->get()->row();
			foreach ($ext_info as $k=>$v) {
				$row->{$k} = $v;
			}
		}

		$this->output->set_data($result);
		$this->output->json(200);
		return $this->output->obj();
	}

	public function read_viewed_titles ($tid) {
		$this->load->model('user_model');
		$data = $this->user_model->read_view_by_tid($tid);
		$this->output->set_data($data);
		$this->output->json(200);
		
		return $this->output->obj();
	}
	public function read_viewed_page ($cid) {
		$page = 1;
		$row = $this->db->select('page')
				->from('user_lastview')
				->where('u_sn', $this->user_id)
				->where('page !=', 1)
				->where('cid', $cid)
				->get()->row();
		if ($row) 
			$page = $row->page;
		$this->output->set_data($page);
		$this->output->json(200);
		
		return $this->output->obj();
	}
		// Create, 順便pop一下
	public function create_view_record ($cid, $page = 1) {
		$this->_no_guest();		
		$this->load->model('comic_model');
		$this->load->model('pop_model');
		$chapter = $this->comic_model->read_chapter_by_cid($cid);
		if (count($chapter) != 0 && is_num($page) ) {
			$tid = $chapter->tid;
			$uid = $this->user_id;

			$sql = "INSERT INTO `user_lastview` (`u_sn`, `cid`, `tid`, `page`)"
				 . " VALUES ({$uid}, {$cid}, {$tid}, {$page})"
				 . " ON DUPLICATE KEY UPDATE `page` = {$page}";
			$this->db->query($sql);

			$this->pop_model->create($tid, $cid);
			
			$this->output->json(200);
		} else {
			$this->output->error(400, 'no such cid');
		}
		
		return $this->output->obj();
	}

	// Favorites
		// Read
	public function read_favorite () {
		$this->load->model('favorite_model');
		$data = $this->favorite_model->read();
		$this->output->set_data($data);
		$this->output->json(200);
		
		return $this->output->obj();
	}
		
		// Create
	public function create_favorite ($tid) {
		$this->_no_guest();

		$this->load->model('comic_model');
		$this->load->model('favorite_model');
		
		if ( is_num($tid) && count($this->comic_model->read_title_by_tid($tid)) != 0 ) {
			$delete = $this->input->post('delete');
			if ( $delete == 'true' ) {
				if ( $this->favorite_model->delete($tid) ) {
					$this->output->set_data('deleted');
				} else {
					$this->output->error('delete failed.');
				}
			} else {
				if ( $this->favorite_model->create($tid) ) {
					$this->output->set_data('created');
				} else {
					$this->output->error('create failed.');
				}
			}
			$this->output->json(200);
		} else {
			$this->output->error('tid error');
		}
		
		return $this->output->obj();
	}

	// Comments
		// Read by tid
	public function read_comment_tid ($tid, $page = 1) {
		$this->load->model('comment_model');
		$data = $this->comment_model->read_by_tid($tid, $page);
		$this->output->set_data($data);
		$this->output->json(200);
		
		return $this->output->obj();
	}
		// Read by cid
	public function read_comment_cid ($cid, $page = 1) {
		$this->load->model('comment_model');
		$data = $this->comment_model->read_by_cid($cid, $page);
		$this->output->set_data($data);
		$this->output->json(200);
		
		return $this->output->obj();
	}
		// Create
	public function create_comment ($tid, $cid = False) {
		$this->_no_guest();

		$this->load->model('comment_model');
		$data = array(
			'tid' => $tid,
			'content' => $this->input->post('content')
		);
		if ($cid !== False && is_num($cid)) {
			$this->load->model('comic_model');
			$chapter = $this->comic_model->read_chapter_by_cid($cid);
			if ( count($chapter) == 0 ) {
				$this->output->error(400, 'no such chapter');
			}
			$data['cid'] = $cid;
		}
		if ( $this->input->post('parent_id') ) {
			$data['parent_id'] = $this->input->post('parent_id');
		}
		if ( $this->comment_model->create($data) ) {
			$this->output->json(200);
		} else {
			$this->output->error(400, $this->comment_model->get_error() );
		}
		return $this->output->obj();
	}
		// Update
	public function update_comment ($comment_id) {
		$this->load->model('comment_model');
		if ( $this->comment_model->update($comment_id, $this->input->put('content'))) {
			$this->output->json(200);
		} else {
			$this->output->error(400, $this->comment_model->get_error() );
		}
		return $this->output->obj();
	}
		// Delete
	public function delete_comment ($comment_id) {
		if (!is_num($comment_id)) {
			$this->output->error(400, 'error comment id');
		} else {
			$r = $this->db->where('id', $comment_id)
						  ->delete('comic_comment');
			if ( $r ) {
				$this->output->json(200);
			} else {
				$this->output->error(400, 'deletion failed(undefind comment id?)');
			}
		}
		return $this->output->obj();
	}
		// Create Push
	public function create_comment_push ($comment_id) {
		$this->_no_guest();
		
		$this->load->model('comment_model');
		$push = $this->input->post('push');
		
		$replace = array(
			'comment_id' => $comment_id,
			'u_sn' => $this->user_id,
			'push' => ($push == '1' ? '1' : '-1')
		);
		$r = $this->db->replace('comic_comment_push', $replace);
		if ( $r ) {
			$this->output->json(200);
		} else {
			$this->output->error(400, 'deletion failed(undefind comment id?)');
		}
		return $this->output->obj();
	}
}

/* End of file api.php */
