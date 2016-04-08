<?php

class Site_99comic extends CI_Model {
	private $sid;
	private $siteName = '99comic';
	private $url = array(
		'main' => 'http://www.99comic.com',
		'list' => 'http://www.99comic.com/lists/',
		'thumbnail' => 'http://img.99mh.com',
		'title' => 'http://www.99comic.com/comic/99',
		'comic' => 'http://www.99comic.com/comics/',
		'js_url' => 'http://www.99comic.com/script/viewhtml.js'
	);
	private $CI;
	private $html;
	private $dbchapters;
	private $title_info;

	function __construct () {
		$this->url = (object) $this->url;
		$this->CI = & get_instance();
		$this->CI->load->library('curl');
		$this->CI->load->library('ecache');
		$this->CI->curl->tmp_folder(PRIVATE_PATH .'tmp/');
		$this->CI->load->model('grab_model', 'grab');
		$this->CI->load->helper('grab');
		$this->CI->load->helper('gb2big5');

		$this->sid = $this->db->select('sid')
							  ->from('sites')
							  ->where('name', $this->siteName)
							  ->get()->row()->sid;
	}

	public function check () {
		$result = (object) [];
		$main = $this->CI->curl->url($this->url->list)->add()->get();
		$main_img = $this->CI->curl->url('http://img.99mh.com/comicui/2779.jpg')->add()->get();

		$result->site_ok = strlen($main) > 30000;
		$result->img_ok = strlen($main_img) > 10000;
		return $result;
	}

	// type = title | chapter
	public function execute ($type) {
		switch ( $type ) {
			case 'title' :
			case 'chapter' :
				$this->{'update_'.$type}();
				break;
			default:
				throw new Exception('Undefined type, expected "title" or "chapter".');
		}
	}

	// OK
	public function image_comic ($cid, $page, $getLink) {
		$this->CI->load->model('comic_model', 'comic');
		$chapter = $this->CI->comic->read_chapter_by_cid($cid);

		$CI = &$this->CI;
		$js_url = $this->url->js_url;
		$sites = $this->CI->ecache->get('enskycomic_99comic_JS', function () use (&$CI, $js_url){
			$js = $CI->curl->url($js_url)->add()->get();
			if (!preg_match('/var sDS = "([^"]+)/', $js, $urls) ) {
				throw new Exception('js url is gone!');
			}
			return explode('|', $urls[1]);
		}, 60*60*24);

		$url = $sites[$chapter->meta->spath-1] . $chapter->meta->pics[$page-1];
		if ($getLink) {
			return $url;
		}

#		if (! $this->agent->is_mobile()) {
#			redirect($url);
#		} else {
			if ( !$this->CI->grab->render_image([
				'url' => $url,
				'referer' => $this->url->comic . $chapter->index . "o" . $chapter->uri . '/'
			]) ) {
				$this->CI->grab->signal_comic_error($cid);
				elog('site_' . $this->siteName . ' - image error, cid=' . $cid);
				show_404();
			}
#		}
	}

	// OK
	public function image_thumbnail ($tid, $medium, $getLink) {
		$meta = json_decode($this->db->select('meta')->from('index_title')->where('id', $tid)->get()->row()->meta);
		$url = $this->url->thumbnail . $meta->thumbnail;
		if ($getLink) {
			return $url;
		}
		$this->CI->grab->render_image([
			'url' => $url,
			'thumbnail' => True,
			'referer' => $this->url->main
		]);
	}

	// OK
	private function update_title () {
		$urls = $this->get_title_urls();
		$htmls = $this->CI->curl->getData_multi_tmp($urls);
		$urls = array();
		foreach ($htmls as $html) {
			$this->html = $html;
			$urls = array_merge($urls, $this->parse_comic_list());
		}
		$urls = array_unique($urls);

		for ($i = 0; $i <= count($urls); $i += 100) {
			$now_urls = array_slice($urls, $i, 100);
			$htmls = $this->CI->curl->getData_multi_tmp($now_urls);

			foreach ($htmls as $html) {
				$this->html = &$html;
				$info = $this->parse_title_info();
				$insert = array(
					'vtid' => $this->CI->grab->read_vtid($info['name']),
					'sid' => $this->sid,
					'index' => $info['index'],
					'name' => $info['name'],
					'cat_id' => $this->CI->grab->read_catid_by_name($info['name'])
				);
				$update = array(
					'meta' => $info['meta'],
					'stop_renew' => $info['stop_renew'],
					'author_id' => $info['author_id'],
					'intro' => $info['intro']
				);
				$row = $this->db->select('id')
									->from('index_title')
									->where('sid', $this->sid)
									->where('index', $info['index'])
									->get()->row();
				if ($row && isset($row->id)) {
					$id = $row->id;
					$this->db->where('id', $id)
							->update('index_title', $update);
				} else {
					$insert = array_merge($update, $insert);
					$this->db->insert('index_title', $insert, True);
				}
			}
		}
	}

	// OK
	private function update_chapter () {
		$this->CI->load->model('comic_model', 'comic');
		$titles = $this->CI->grab->read_title_by_sid($this->sid);
		$urls = array();
		foreach ($titles as $row) {
			$urls[] = "{$this->url->title}{$row['index']}/";
		}

		$htmls = $this->CI->curl->getData_multi_tmp($urls);

		foreach ($titles as $j => $row) {
			try {
				$this->title_info = $row;
				$this->html = &$htmls[$j];
				$info = $this->parse_title_info();
				$this->update_title_info($info, $row['id']);
				$chapters = $this->parse_chapters();

				$this->dbchapters = $this->CI->comic->read_chapters_by_tid($row['id']);
				list($urls, $indexes) = $this->need_to_grab($chapters, $row['id']);
				if ( count( $urls ) == 0 ) {
					continue;
				}
				elog('site_' . $this->siteName . ' - ' . $row['name'], 'grab');
				$chapter_htmls = $this->CI->curl->getData_multi_tmp($urls);

				foreach ($chapter_htmls as $i => $chapter_html) {
					$this->html = $chapter_html;
					$this->get_chapter_info($chapters[$indexes[$i]]);
				}
				$this->update_db_chapter($chapters, $row['id']);
			} catch (Exception $e) {
				elog($e->getMessage());
			}
		}
	}

	// OK
	private function update_db_chapter (&$chapters, $tid) {
		$cn = count($chapters);
		$chapters = array_reverse($chapters);

		// convert db chapters to key: uri => value: chapter
		$db_uri_hash = $this->convert_dbchapters('uri');
		$db_index_hash = $this->convert_dbchapters('index');

		foreach ($chapters as $c_index => $chapter) {
			if (!isset($chapter['meta']) OR !$chapter['pages']) {
				$chapter['meta'] = null;
				$chapter['pages'] = 0;
			}
			// correct_index: the correct index in database
			$correct_index = $c_index + 1;
			$d_chapter = isset( $db_uri_hash[$chapter['uri']] ) ? $db_uri_hash[$chapter['uri']] : False;
			if ( $d_chapter == False ) { // not found this chapter
				if ( isset($db_index_hash[$correct_index]) ) { // collide
					$sql = 'UPDATE index_chapter SET `index` = `index` + 10000 WHERE tid = '.$tid.' AND `index` = ' . $correct_index;
					$this->db->query($sql);
				}
				$insert = array(
					'sid' => $this->sid,
					'tid' => $tid,
					'index' => $correct_index,
					'uri' => $chapter['uri'],
					'name' => $chapter['chapter'],
					'pages' => $chapter['pages'],
					'meta' => $chapter['meta'],
					'update_time' => date('Y/m/d H:i:s')
				);
				$this->db->insert('index_chapter', $insert);
				elog("site_" . $this->siteName . " - new: " . $chapter['chapter'] .'pages: '. $chapter['pages'], 'grab');
			} else { // found this chapter
				if ( $d_chapter->index != $correct_index ) { // but index not correct
					$sql = 'UPDATE index_chapter SET `index` = `index` + 10000 WHERE tid = '.$tid.' AND `index` = ' . $correct_index;
					$this->db->query($sql);
					$update = array(
						'index' => $correct_index
					);
					$where = [
						'tid' => $tid,
						'uri' => $chapter['uri']
					];
					$this->db->update('index_chapter', $update, $where);
					elog("site_" . $this->siteName . " - index mod: " . $chapter['chapter'] .'pages: '. $chapter['pages'] . " {$d_chapter->index}->{$correct_index}", 'grab');
				} else if ($d_chapter->error == 1) { // but error
					$update = array(
						'name' => $chapter['chapter'],
						'pages' => $chapter['pages'],
						'meta' => $chapter['meta'],
						'error' => 0
					);
					$this->db->where('cid', $d_chapter->cid)
							 ->update('index_chapter', $update);
					elog("site_" . $this->siteName . " - regrab: " . $chapter['chapter'] .'pages: '. $chapter['pages'], 'grab');
				}
			}
		}
		$diff = $this->db->select('COUNT(*) AS `count`')->from('index_chapter')->where('tid', $tid)->get()->row()->count - $cn;
		if ( $diff > 0 ) {
			$this->db->where('tid', $tid)
					 ->where('index >', $cn)
					 ->delete('index_chapter');
		}
	}

	private function update_title_info (&$info, $tid) {
		$update = array(
			'meta' => $info['meta'],
			'stop_renew' => $info['stop_renew'],
			'author_id' => $info['author_id'],
			'intro' => $info['intro']
		);
		$this->db->where('id', $tid)
				->update('index_title', $update);
	}

	// convert db chapters to key: uri => value: chapter
	// NOK
	private function convert_dbchapters ($key) {
		$return_chapters = [];
		foreach ($this->dbchapters as $value) {
			$return_chapters[$value->{$key}] = $value;
		}
		return $return_chapters;
	}

	// Seems OK
	private function get_chapter_info (& $chapter) {
		if (preg_match('/var sFiles="([^"]+)";var sPath="(\d+)/', $this->html, $matches)) {
			$imgs = explode('|', $matches[1]);
			$chapter['meta'] = json_encode([
				'pics' => $imgs,
				'spath' => $matches[2]
			]);
			$chapter['pages'] = count($imgs);
			return True;
		} else {
			elog('site_'. $this->siteName .' - Error get_chapter_info.');
			return False;
		}
	}

	// Seems OK
	private function need_to_grab (&$chapters, $tid) {
		$indexes = [];
		$uri_hash = [];

		foreach ($chapters as $i => $chapter) {
			$uri_hash[$chapter['uri']] = $this->url->comic . $this->title_info['index'] . "o" . $chapter['uri'] . '/';
			$indexes[$chapter['uri']] = $i;
		}

		foreach ($this->dbchapters as $chapter) {
			if (!$chapter->error) {
				unset($uri_hash[$chapter->uri]);
				unset($indexes[$chapter->uri]);
			}
		}
		return [array_values($uri_hash), array_values($indexes)];
	}

	private function chapter_fix ($chapter) {
		$chapter = gb2big5($chapter);
		$chapter = str_replace('集', '話', $chapter);
		$chapter = str_replace('卷', '集', $chapter);
		return $chapter;
	}

	// OK
	private function parse_chapters () {
		if (preg_match_all("/comics\/\d+o(\d+)\/'>([^<]+)/", $this->html, $matches, PREG_SET_ORDER)) {
			$isset = [];
			foreach ($matches as $row) {
				if (!isset($isset[$row[1]])) {
					$isset[$row[1]] = True;
					$result[] = [
						'uri' => $row[1],
						'chapter' => $this->chapter_fix($row[2])
					];
				}
			}
			return $result;
		} else {
			elog('site_'. $this->siteName .' - Error Chapter Parsing.');
		}
	}

	// OK
	private function parse_title_info () {
		try {
			$info = array();

			$info['stop_renew'] = !preg_match('/漫畫狀態：(?:<\/b><b class="red">)?連載/', $this->html);

			preg_match('/<div class="cCon">([^<]+)/', $this->html, $result);
			$info['intro'] = trim(strip_tags(gb2big5($result[1]), '<br>'));

			if (preg_match('/漫畫作者：(?:<\/b>)?(?:<a[^>]+>)?([^<]+)</', $this->html, $result)) {
				$info['author_id'] = $this->CI->grab->get_author_id($result[1]);
			} else {
				$info['author_id'] = null;
			}

			preg_match("/<h1><a title='[^']+' href='\/comic\/99(\d+)\/'>([^<]+)/", $this->html, $result);
			$info['index'] = $result[1];
			$info['name'] = gb2big5($result[2]);

			preg_match("/<div class=\"block\"><img alt='[^']+' src='http:\/\/img.99mh.com([^']+)/", $this->html, $result);
			$info['meta'] = json_encode([
				'thumbnail' => $result[1]
			]);
			return $info;
		} catch (Exception $e) {
			throw new Exception("Parse title info error");
		}
	}

	// OK
	private function parse_comic_list () {
		if (preg_match_all("/<a href='\/comic\/99(\d+)\/' title='([^'\[]+)[^']*'>/", $this->html, $result)) {
			$urls = $result[1];
			foreach ($urls as $i => $url) {
				$urls[$i] = $this->url->title . $url . '/';
			}
			return array_unique($urls);
		} else {
			throw new Exception('title is not correct, check '. $this->url->list);
		}
	}

	// OK
	private function get_title_urls () {
		$html = $this->curl->url($this->url->list)->add()->get();
		if (preg_match("/href='\/lists\/(\d+)\/' title='最後一頁'/", $html, $result)) {
			$maxpage = $result[1];
			$urls = array($this->url->list);
			foreach (range(2, $maxpage) as $page) {
				$urls[] = $this->url->list . $page . '/';
			}
			return $urls;
		} else {
			throw new Exception('title is not correct, check '. $this->url->list);
		}
	}
}
