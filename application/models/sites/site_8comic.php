<?php
class Site_8comic extends CI_Model {
	private $sid;
	private $siteName = '8comic';
	private $html;
	private $CI;
	private $url = array(
		'index' => 'http://www.8comic.com/comic/all.html',
		'title' => 'http://www.8comic.com/html/'
	);
	
	function __construct () {
		$this->CI = & get_instance();
		$this->CI->load->library('curl');
		$this->CI->curl->tmp_folder(PRIVATE_PATH .'tmp/');
		$this->CI->load->model('grab_model', 'grab');
		$this->CI->load->helper('grab');
		
		$this->sid = $this->db->select('sid')
							  ->from('sites')
							  ->where('name', $this->siteName)
							  ->get()->row()->sid;
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

	public function check () {
		$result = (object)[];
		$all_page = $this->CI->curl->url($this->url['index'])->add()->get();
		$thumbnail = $this->CI->curl->url('http://www.8comic.com/pics/0/7340s.jpg')->add()->get();
		$result->site_ok = strlen($all_page) >= 887087;
		$result->img_ok = strlen($thumbnail) >= 7751;
		return $result;
	}
	
	public function image_comic ($cid, $page, $getLink) {
		$sql='SELECT t1.meta, t1.pages, t1.`index`, t2.`index` as itemid'
			.' FROM index_chapter AS t1'
			.' LEFT JOIN index_title AS t2 ON t1.tid = t2.id'
			.' WHERE cid = '.$this->db->escape($cid);
		$r = $this->db->query($sql)->row();
		$meta = json_decode($r->meta, true);
		$c_index = $r->index;
		$itemid = $r->itemid;
		$pages = $r->pages;
		
		if ( $meta ) {			
			$m =(($page-1)/10)%10+(($page-1)%10)*3;
			$img = page_convert($page). '_' .substr($meta['code'], $m, 3);
			$url = "http://img". $meta['sid'] .".8comic.com/"
				. $meta['did'] ."/". $itemid ."/". $meta['num'] ."/". $img .".jpg";
			if ($getLink) {
				return $url;
			}
			redirect($url);
			/*
			if ( !$this->CI->grab->render_image([
				'url' => $url,
				'referer' => $this->url['title']
			]) ) {
				$this->CI->grab->signal_comic_error($cid);
				elog('site_8comic - image error, cid=' . $cid);
				show_404();
			}
			*/
		} else {
			header('HTTP/404 File Not Found');
			exit;
		}
	}
	
	public function image_thumbnail ($tid, $medium, $getLink) {
		if ( !is_num($tid) ) {
			return False;
		}
		$sql='SELECT `index` FROM index_title'
			.' WHERE id = '. $tid;
		$q = $this->db->select('index')
					  ->from('index_title')
					  ->where('id', $tid)
					  ->get();
		if ( $q->num_rows() == 1 ) {
			$index = $q->row()->index;
			$url = 'http://www.8comic.com/pics/0/'. $index . ($medium ? '' : 's') .'.jpg';
			if ($getLink) {
				return $url;
			}
			redirect($url);
			$this->CI->grab->render_image([
				'url' => $url,
				'thumbnail' => True,
				'referer' => $this->url['title']
			]);
		} else {
			header('HTTP/404 Not Found');
			exit;
		}
	}
	
	private function update_title () {
		$html = $this->CI->curl->url($this->url['index'])
							   ->proxy(False)
							   ->add()->get();
	    $result = array();
		if ( !preg_match_all( '/<a href="\/html\/(\d+)\.html"[^>]+>([^<]+)<\/a>/', $html, $result, PREG_SET_ORDER ) ) {
			throw new Exception('Index grabed failed.');
		} else {
			foreach ( $result as $row ) {
				list(, $tid, $title) = $row;
				$title = trim( unhtmlentities( toUTF8($title) ) );
				$insert = array(
					'vtid' => $this->CI->grab->read_vtid($title),
					'sid' => $this->sid,
					'index' => $tid,
					'name' => $title,
					'cat_id' => $this->CI->grab->read_catid_by_name($title),
					'stop_renew' => 0
				);
				$this->db->insert('index_title', $insert, True);
			}
		}
	}
	
	private function update_chapter () {
		$this->CI->load->model('comic_model', 'comic');
		$titles = $this->CI->grab->read_title_by_sid($this->sid);
		$urls = array();
		foreach ($titles as $row) {
			$urls[] = $this->url['title'] . $row['index'] .'.html';
		}
		
		try {
			$htmls = $this->CI->curl->getData_multi_tmp($urls);
		} catch (Exception $e) {
			throw $e;
		}
		
		foreach ($titles as $j => $row) {
			$this->html = &$htmls[$j];
		    $title = array();
			try{
				$title['author_id'] = $this->CI->grab->get_author_id($this->parse_author_name());
				$title['intro'] = $this->parse_intro();
				$title['stop_renew'] = $this->parseStopRenew();
				$title['meta'] = array('catid' => $this->parseCatid());
			} catch (Exception $e) {
				elog("site_8comic - " . $row['name'].'parse error:'. $e);
				continue;
			}
			$chapters = $this->parseChapters();
			
			$update = $title;
			$update['meta'] = json_encode($title['meta']);
			$this->db->where('id', $row['id'])
					 ->update('index_title', $update);			
			if ( $this->checkInDB($chapters, $row['id']) !== False ) {
				continue;
			}

			
			$url = $this->getUrlByCatId($title['meta']['catid']) . $row['index'] .'.html';
			
			$this->html = $this->CI->curl->url($url)->proxy(False)->add()->get();

			if (!$this->html) {
				throw new Exception('Cannot access Comic Page.');
			}
			
			try {
				$codes = $this->parseCode();
			} catch (Exception  $e ) {
				elog("site_8comic - parse code error!");
				continue;
			}
			
			$each_vol = $this->code_opt($codes);
			
			$comics = $this->CI->comic->read_chapters_by_tid($row['id']);
			$i = 1;
			$grabbed_chapter_names = array();
			foreach ($chapters as $index => $c_name) {
				$meta = $each_vol[$index];
				$pages = $meta['pages'];
				unset($meta['pages']);
				
				$dbcomic = isset( $comics[$i-1] ) ? $comics[$i-1] : NULL;
				if ( $dbcomic == NULL OR $dbcomic->name != $c_name OR $dbcomic->meta->num != $index ) {
					$sql = 'UPDATE index_chapter SET `index` = `index` + 10000 WHERE tid = '.$row['id'].' AND `index` = '.$i;
					$this->db->query($sql);
					$insert = array(
						'sid' => $this->sid,
						'tid' => $row['id'],
						'index' => $i,
						'name' => $c_name,
						'pages' => $pages,
						'meta' => json_encode($meta),
						'update_time' => date('Y/m/d H:i:s')
					);
					
					$this->db->replace('index_chapter', $insert);
					// reload new comics
					$comics = $this->CI->comic->read_chapters_by_tid($row['id']);
					$grabbed_chapter_names[] = $c_name;
				} else if ($dbcomic->error == 1) {
					$update = array(
						'name' => $c_name,
						'pages' => $pages,
						'meta' => json_encode($meta),
						'error' => 0
					);
					$this->db->where('cid', $dbcomic->cid)
							 ->update('index_chapter', $update);
				}
				$i++;
			}
			// if db's comic > grabbed comic, remove 多出來的
			$diff = count($comics) - count($chapters);
			if ( $diff ) {
				$this->db->where('tid', $row['id'])
						 ->where('index >', count($chapters))
						 ->delete('index_chapter');
			}
			elog("site_8comic - {$row['name']}'s new chapters: ". implode(",", $grabbed_chapter_names), 'grab');
		}
	}

	private function parse_author_name () {
		if ( ! preg_match('/'. toBig5('作者：') .'<\/td>[^<]*<td[^>]+>([^<]+)/', $this->html, $author) ) {
			return '';
		}
		return toUTF8(unhtmlentities($author[1]));
	}

	private function parse_intro () {
		if ( ! preg_match('/<td colspan="3"[^>]+>([^<]+)/', $this->html, $intro) ) {
			return '';
		}
		return preg_replace('/^　　/', '', toUTF8($intro[1]));
	}
	
	private function code_opt ($code) {
		$each_vol = explode("|",$code);
		$result = array();
		foreach ( $each_vol as $vol ) {
			$meta = array();
			list($meta['num'], $meta['sid'], $meta['did'], $meta['pages'], $meta['code']) = explode(" ", $vol);
			$result[ $meta['num'] ] = $meta;
		}
		return $result;
	}
	
	// we need to check chapter and 8comic's index are all match
	private function checkInDB ($chapters = False, $tid = False) {
		$sql='SELECT name, meta, `error` FROM index_chapter WHERE `tid` = '.$tid;
		$result = $this->db->query($sql)->result_array();
		
		$sql_metas = array();
		foreach ($result as $row) {
			if ( $row['error'] == 1 ) {
				return False;
			}
			$sql_metas[ $row['name'] ] = json_decode($row['meta']);
		}
		
		foreach ($chapters as $index => $chapter) {
			$find = isset($sql_metas[$chapter]);
			if ($find == False) {
				return False;
			} else if ( $sql_metas[$chapter]->num != $index ) {
				return False;
			}
		}
		
		return True;
	}
	
	private function getUrlByCatId ($catid) {
		$sql='SELECT url FROM 8comic_js WHERE catid = '.$catid;
		$q = $this->db->query($sql);
		if($q->num_rows() == 0)
			throw new Exception('8comic js has some problem.');
		return $q->row()->url;
	}
	
	private function parseStopRenew () {
		preg_match('/<a href="#Comic">(.+)<\/a>/U', $this->html, $title);
		if (!isset($title[1]))
			throw new Exception(' parseStopRenew : Cannot get title.');
		
		$t = toUTF8($title[1]);
		
		return intval(strpos( $t ,"連載中") === False);
	}
	
	private function parseCatid () {
		preg_match("/cview\('\d+-\d+\.html',(\d+)\);/", $this->html, $catid);
		if (!isset($catid[1]))
			throw new Exception('Cannot get catid.');
		return intval($catid[1]);
	}
	
	private	function parseCode () {
		preg_match('/var allcodes="([^"]+)"/', $this->html, $code);
		if (!isset($code[1]))
			throw new Exception('Cannot get code.');
		return $code[1];
	}
	
	private function parseChapters () {
		preg_match_all("/cview\('\d+-(\d+)\.html',\d+\);.+>.+\s(.+)</", $this->html, $chapters, PREG_SET_ORDER);
		if ( !isset($chapters[0]) OR !isset($chapters[0][0]) ) {
			throw new Exception('Cannot get chapters.');
		}
		$chs = array();
		foreach ($chapters as $chapter) {
			// 8comic's chapter id as array index, chapter name as value.
			$chs[$chapter[1]] = trim( toUTF8( strip_tags( preg_replace('/script>[^<]+/', "", $chapter[2]) ) ) );
		}
		return $chs;
	}
}
