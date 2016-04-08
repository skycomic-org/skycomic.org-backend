<?php
class Site_xxbh extends CI_Model {
	private $sid;
	private $siteName = 'xxbh';
	private $html;
	private $CI;
	private $url = array(
		'main' => 'http://comic.xxbh.net',
		'index' => 'http://comic.xxbh.net/alist_78850.html',
		'site_js' => 'http://img_v1.dm08.com/img_v1/cn_130117.js'
	);

	private $dbchapters;

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
		$main = $this->CI->curl->url($this->url->index)->add()->get();
		$main_js = $this->CI->curl->url($this->url->site_js)->add()->get();

		$result->site_ok = strlen($main) > 10000;
		$result->img_ok = strlen($main_js) > 2000;
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

	/* OK */
	public function image_comic ($cid, $page, $getLink) {
		$this->CI->load->model('comic_model', 'comic');
		$chapter = $this->CI->comic->read_chapter_by_cid($cid);
		$url = $chapter->meta->site . $chapter->meta->pics[$page-1];
		if ($getLink) {
			return $url;
		}
		if ( !$this->CI->grab->render_image([
			'url' => $url,
			'referer' => 'http://comic.xxbh.net' . $chapter->uri
		]) ) {
			$this->CI->grab->signal_comic_error($cid);
			elog('site_xxbh - image error, cid=' . $cid);
			show_404();
		}
	}

	/* OK */
	public function image_thumbnail ($tid, $medium, $getLink) {
		$url = $this->db->select('meta')->from('index_title')->where('id', $tid)->get()->row()->meta;
		if ( $medium ) {
			$url = str_replace('120x168/', '', $url);
		}
		if ($getLink) {
			return $url;
		}
		$this->CI->grab->render_image([
			'url' => $url,
			'thumbnail' => True,
			'referer' => $this->url->main
		]);
	}

	/* OK */
	private function update_title () {
		$word_index_urls = $this->get_word_index();
		$urls = $word_index_urls;
		// echo "grabbing max_page.\n";
		foreach ($word_index_urls as $url) {
			// echo "$url ... ";
			$max_page = $this->get_word_maxpage($url);
			$url = substr($url, 0, -5);
			for ( $i=1; $i<= $max_page; $i++ ) {
				$urls[] = "{$url}_{$i}.html";
			}
			// echo "done\n";
		}

		// echo "grabbing all pages.\n";
		$htmls = $this->CI->curl->getData_multi_tmp($urls);

		// echo "parsing html & insert.\n";
		foreach ($htmls as $html) {
			$this->html = $html;
			$titles = $this->get_title_info();
			foreach ($titles as $title) {
				$insert = array(
					'vtid' => $this->CI->grab->read_vtid($title['title']),
					'sid' => $this->sid,
					'index' => $title['index'],
					'name' => $title['title'],
					'cat_id' => $this->CI->grab->read_catid_by_name($title['title']),
					'meta' => $title['image'],
					'stop_renew' => 0
				);
				$this->db->insert('index_title', $insert, True);
			}
		}
	}

	/* SEEMS OK */
	private function update_chapter () {
		$this->CI->load->model('comic_model', 'comic');
		$titles = $this->CI->grab->read_title_by_sid($this->sid);
		$urls = array();
		foreach ($titles as $row) {
			$urls[] = "{$this->url->main}/colist_{$row['index']}.html";
		}

		$htmls = $this->CI->curl->getData_multi_tmp($urls);

		foreach ($titles as $j => $row) {
			try {
				$this->html = &$htmls[$j];
				$this->parse_update_title_info($row['id']);
				$chapters = $this->parse_chapters();
				$this->dbchapters = $this->CI->comic->read_chapters_by_tid($row['id']);
				list($urls, $indexes) = $this->need_to_grab($chapters, $row['id']);
				if ( count( $urls ) == 0 ) {
					continue;
				}
				elog('site_xxbh - ' . $row['name'], 'grab');
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

	/* SEEMS OK */
	private function update_db_chapter (&$chapters, $tid) {
		$cn = count($chapters);
		$chapters = array_reverse($chapters);

		// convert db chapters to key: uri => value: chapter
		$db_uri_hash = $this->convert_dbchapters('uri');
		$db_index_hash = $this->convert_dbchapters('index');

		foreach ($chapters as $c_index => $chapter) {
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
				elog("site_xxbh - new: " . $chapter['chapter'] .'pages: '. $chapter['pages'], 'grab');
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
					elog("site_xxbh - index mod: " . $chapter['chapter'] .'pages: '. $chapter['pages'] . " {$d_chapter->index}->{$correct_index}", 'grab');
				} else if ($d_chapter->error == 1) { // but error
					$update = array(
						'name' => $chapter['chapter'],
						'pages' => $chapter['pages'],
						'meta' => $chapter['meta'],
						'error' => 0
					);
					$this->db->where('cid', $d_chapter->cid)
							 ->update('index_chapter', $update);
					elog("site_xxbh - regrab: " . $chapter['chapter'] .'pages: '. $chapter['pages'], 'grab');
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

	// convert db chapters to key: uri => value: chapter
	private function convert_dbchapters ($key) {
		$return_chapters = [];
		foreach ($this->dbchapters as $value) {
			$return_chapters[$value->{$key}] = $value;
		}
		return $return_chapters;
	}

	private function parse_update_title_info ($tid) {
		$update = [];
		$update['author_id'] = $this->CI->grab->get_author_id($this->parse_author_name());
		$update['intro'] = $this->parse_intro();
		$update['stop_renew'] = $this->parse_stop_renew();
		$this->db->update('index_title', $update, ['id' => $tid]);
	}

	private function parse_author_name () {
		if ( ! preg_match('/<b>作者<\/b><a[^>]+>([^<]+)/', $this->html, $author) ) {
			return '';
		}
		return gb2big5($author[1]);
	}

	private function parse_intro () {
		if ( ! preg_match('/<i class="d_sam" id="det">(.+)<\/a>/U', $this->html, $intro) ) {
			return '';
		}
		return strip_tags(gb2big5($intro[1]), '<br>');
	}

	/* OK */
	private function parse_stop_renew () {
		return strpos($this->html, '连载中') === False;
	}

	/* OK */
	private function get_chapter_info (&$chapter) {
		if ( !preg_match_all('/src="(http:\/\/[^\/]+\/coojs\/[^.]+.js)"/', $this->html, $matches) ) {
			throw new Exception("Error parse_js_url");
		}
		$js_url = $matches[1][0];
		$js = $this->CI->curl->url($js_url)->add()->get();
		if ( !preg_match("/msg='([^']*)';var maxPage =(\d+);var img_s = (\d+);/", $js, $matches) ) {
			throw new Exception("Error parse_js_code, url: " . $js_url);
		}
		list(, $msg, $chapter['pages'], $sid) = $matches;
		$site = $this->get_site($sid);
		$chapter['meta'] = json_encode([
			'site' => $site,
			'pics' => explode('|', $msg)
		]);
	}

	/* OK */
	private function get_site( $sid ) {
		$CI = $this->CI;
		$js_url = $this->url->site_js;
		return $this->CI->ecache->get('enskycomic_xxbh_js', function () use (&$CI, $js_url){
			$js = $CI->curl->url($js_url)->add()->get();
			if (!preg_match_all('/"([^"]+)";/', $js, $urls) ) {
				throw new Exception('js url is gone!');
			}
			return $urls;
		}, 60*60*24)[1][$sid - 1];
	}

	/* OK */
	private function need_to_grab (&$chapters, $tid) {
		$indexes = [];
		$uri_hash = [];

		foreach ($chapters as $i => $chapter) {
			$uri_hash[$chapter['uri']] = $this->url->main . $chapter['uri'];
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

	/* OK */
	private function parse_chapters () {
		$result = [];
		if (!preg_match_all('/<a(?: class="f_red")? href="([^"]+)" target="_blank" title=[^>]+>([^<]+)<\/a>/', $this->html, $matches, PREG_SET_ORDER)) {
			elog('site_xxbh - Error Chapter Parsing.');
		} else {
			foreach ($matches as $row) {
				$result[] = [
					'uri' => $row[1],
					'chapter' => gb2big5($row[2])
				];
			}
		}
		return $result;
	}

	/* OK */
	private function get_title_info () {
		$titles = [];
		if ( !preg_match_all('/<a href="\/colist_(\d+).html"[^>]+>([^<]+)</', $this->html, $title_infos, PREG_SET_ORDER) ) {
			elog('site_xxbh - Error Title Parsing.');
		} else if ( !preg_match_all('/img src="([^"]+)" alt/', $this->html, $imgs) ) {
			elog('site_xxbh - Error Title Image Parsing.');
		} else {
			foreach ($imgs[1] as $i => $img) {
				$row = [];
				list(, $row['index'], $row['title']) = $title_infos[$i];
				$row['title'] = gb2big5($row['title']);
				$row['image'] = $img;
				$titles[] = $row;
			}
		}
		return $titles;
	}

	/* OK */
	private function get_word_maxpage ($url) {
		$md5 = md5($url);
		$CI = &$this->CI;
		$url = substr($url, 0, -5);
		return $this->CI->ecache->get('enskycomic_xxbh_maxpage_' . $md5, function () use (&$CI, $url) {
			$binary_search = function ($min, $max, $try) use(&$CI, $url, &$binary_search) {
				if ( ($min + 1) >= $max ) {
					return $min;
				} else {
					$tryurl = $url . "_" . $try . ".html";
					$html = $CI->curl->url($tryurl)->add()->get();
					if ( strlen($html) > 20 ) {
						return $binary_search($try, $max, intval(($try + $max) / 2));
					} else {
						return $binary_search($min, $try, intval(($try + $min) / 2));
					}
				}
			};
			return $binary_search(1, 75, 75);
		}, 60*60*24*10);
	}

	/* OK */
	private function get_word_index () {
		$html = $this->CI->curl->url($this->url->index)->add()->get();
		if ( !preg_match_all('/<a(?: class="act")? href="([^"]+)" title=".开头的/', $html, $matches) ){
			throw new Exception('word index pattern has changed.');
		}
		$urls = $matches[1];
		foreach ($urls as &$url) {
			$url = $this->url->main . $url;
		}
		return $urls;
	}
}
