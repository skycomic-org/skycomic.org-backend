<?php
namespace site8comic {

use \Exception;

class Parser {
	protected $html;

	function __construct (&$html) {
		$this->html = &$html;
	}
};

/**
 * example: http://www.8comic.com/html/103.html
 */
class ComicParser extends Parser {
	public function parseChapters () {
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

	public function parseIntro () {
		if (!preg_match('/<td colspan="3"[^>]+>([^<]+)/', $this->html, $intro)) {
			return '';
		}

		return preg_replace('/^　　/', '', toUTF8($intro[1]));
	}

	public function parseStopRenew () {
		preg_match('/<a href="#Comic">(.+)<\/td>/U', $this->html, $title);
		if (!isset($title[1])) {
			throw new Exception('parseStopRenew : Cannot get title.');
		}

		$t = toUTF8($title[1]);
		return intval(strpos( $t ,"連載中") === False);
	}

	public function parseCatid () {
		preg_match("/cview\('\d+-\d+\.html',(\d+)\);/", $this->html, $catid);
		if (!isset($catid[1])) {
			throw new Exception('Cannot get catid.');
		}

		return intval($catid[1]);
	}

	public function parseAuthorName () {
		if (! preg_match('/'. toBig5('作者：') .'<\/td>[^<]*<td[^>]+>([^<]+)/', $this->html, $author)) {
			return '';
		}

		return toUTF8(unhtmlentities($author[1]));
	}
};

/**
 * example: http://www.8comic.com/online/Domain-103.html
 * may vary with cidURLMapping
 */
class ChapterParser extends Parser {
	public	function parseCode () {
		preg_match('/var cs=\'([^\']+)\'/', $this->html, $code);
		if (!isset($code[1])) {
			throw new Exception('Cannot get code.');
		}

		return $code[1];
	}

	public function codeExtract ($code) {
		$hol = substr($code, 0, 3);
		$regexp = "/(\w{3}\d|\w{2}\d{2}|\w\d{3})(\w\d|\d{2})(\d)(\w{2}\d|\w\d{2}|\d{3})(.{40})/";

		if (! preg_match_all($regexp, $code, $results, PREG_SET_ORDER)) {
			throw new Exception("code invalid ({$code}:{$regexp}), maybe 8comic changed it?");
		} else {
			$return = array();
			foreach ($results as $result) {
				$meta = array();
				list(, $num, $sid, $meta['did'], $pages, $meta['code']) = $result;
				$meta['num'] = $this->removePlaceHolder($num);
				$meta['sid'] = $this->removePlaceHolder($sid);
				$meta['pages'] = $this->removePlaceHolder($pages);
				$return[ $meta['num'] ] = $meta;
			}
			return $return;
		}
	}

	private function removePlaceHolder($item) {
		preg_match('/(\d+)/', $item, $result);
		return $result[1];
	}
};

class ChapterFetcher {
	/**
	 * 8comic's CID -> URL mappings, please refer to $url->js
	 * ex: array(1 => '/online/Domain-', ...)
	 */
	private static $cidURLMapping = array();
	private $CI;
	private $comicParser;
	private $chapterParser;
	private $sid;
	public static $gURLs;

	function __construct ($sid) {
		$this->CI = & get_instance();
		$this->sid = $sid;
		$this->fetchJSMapping();
	}

	private function fetchJSMapping () {
		if (!empty(ChapterFetcher::$cidURLMapping)) {
			return ;
		}
		elog('start fetchJSMapping', 'debug');

		$jsContent = $this->CI->grab->curlUseProxy($this->CI->curl)
			->url(ChapterFetcher::$gURLs->js)
			->add()->get();

		if (empty($jsContent)) {
			throw new Exception("Missing Js File, please check if " . ChapterFetcher::$gURLs->js . ' changed URL.');
		}

		if (!preg_match_all('/if *\( *(catid.+)\) *baseurl[^"]+"([^"]+)/', $jsContent, $results, PREG_SET_ORDER)) {
			throw new Exception("js regex parse failed for " . ChapterFetcher::$gURLs->js);
		}

		foreach ($results as $result) {
			list(, $catIDString, $cidRelatedURL) = $result;
			if (!preg_match_all("/catid *== *(\d+)/", $catIDString, $catIDResults, PREG_SET_ORDER)) {
				throw new Exception("js regex parse failed for " . ChapterFetcher::$gURLs->js);
			}

			foreach ($catIDResults as $cid) {
				$cid = intval($cid[1]);
				ChapterFetcher::$cidURLMapping[$cid] = ChapterFetcher::$gURLs->base . $cidRelatedURL;
			}
		}
	}

	public function fetch () {
		elog('start fetch', 'debug');
		$this->CI->load->model('comic_model', 'comic');
		$titles = $this->CI->grab->read_title_by_sid($this->sid);
		$htmls = $this->fetchChapterHTMLs($titles);

		foreach ($titles as $idx => $title) {
			try {
				$this->fetchComic($title, $htmls[$idx]);
			} catch (Exception $e) {
				elog($e->getMessage());
				continue;
			}
		}
		elog('done fetch', 'debug');
	}

	private function fetchChapterHTMLs ($titles) {
		elog('start fetchChapterHTMLs', 'debug');
		$urls = array();
		foreach ($titles as $row) {
			$urls[] = ChapterFetcher::$gURLs->title . $row['index'] .'.html';
		}

		return $this->CI->grab->curlUseProxy($this->CI->curl)->getData_multi_tmp($urls);
	}

	private function fetchComic (&$title, &$html) {
		elog("start fetchComic {$title['name']}", 'debug');
		$this->comicParser = new ComicParser($html);

		$chapters = $this->comicParser->parseChapters();
		if ($this->CI->grab->is_in_db(count($chapters), $title['id'])) {
			elog("pass {$title['name']} : all in database", 'debug');
			return ;
		}

		$this->updateTitleInfo($title['id']);
		$this->chapterParser = new ChapterParser($this->getComicHTML($title['index']));

		$extractedInfo = $this->chapterParser->codeExtract($this->chapterParser->parseCode());
		$comicsInDB = $this->CI->comic->read_chapters_by_tid($title['id']);
		$dbIdx = 1;
		$newChapterNames = array();

		foreach ($chapters as $idx => $chapterName) {
			$meta = $extractedInfo[$idx];
			$pages = $meta['pages'];
			unset($meta['pages']);

			$comicInDB = isset($comicsInDB[$dbIdx - 1]) ? $comicsInDB[$dbIdx - 1] : NULL;
			if ( $comicInDB === NULL
				OR $comicInDB->name != $chapterName
				OR $comicInDB->meta->num != $idx ) {
				$sql = 'UPDATE index_chapter SET `index` = `index` + 10000 WHERE tid = '.$title['id'].' AND `index` = ' . $dbIdx;
				$this->CI->db->query($sql);
				$insert = array(
					'sid' => $this->sid,
					'tid' => $title['id'],
					'index' => $dbIdx,
					'name' => $chapterName,
					'pages' => $pages,
					'meta' => json_encode($meta),
					'update_time' => date('Y/m/d H:i:s')
				);

				$this->CI->db->replace('index_chapter', $insert);
				$newChapterNames[] = $chapterName;
			} else if ($comicInDB->error == 1) {
				$update = array(
					'name' => $chapterName,
					'pages' => $pages,
					'meta' => json_encode($meta),
					'error' => 0
				);
				$this->CI->db->where('cid', $comicInDB->cid)->update('index_chapter', $update);
			}
			$dbIdx++;
		}

		$this->CI->db->where('tid', $title['id'])
			->where('index >', count($chapters))
			->delete('index_chapter');
		elog("site_8comic - {$title['name']}'s new chapters: ". implode(",", $newChapterNames), 'grab', 'info');
	}

	private function updateTitleInfo ($chapterID) {
		$title = array();
		$title['author_id'] = $this->CI->grab->get_author_id($this->comicParser->parseAuthorName());
		$title['intro'] = $this->comicParser->parseIntro();
		$title['stop_renew'] = $this->comicParser->parseStopRenew();
		$title['meta'] = json_encode(array('catid' => $this->comicParser->parseCatid()));
		$this->CI->db->where('id', $chapterID)->update('index_title', $title);
	}

	private function getComicHTML ($chapterIndex) {
		$catID = intval($this->comicParser->parseCatid());
		$url = ChapterFetcher::$cidURLMapping[$catID] . $chapterIndex .'.html';
		$html = $this->CI->grab->curlUseProxy($this->CI->curl)->url($url)->add()->get();
		if (!$html) {
			throw new Exception('Cannot access Comic Page.' . $url);
		}
		return $html;
	}
};

ChapterFetcher::$gURLs =  (object) array(
	'base' => 'http://www.8comic.com',
	'index' => 'http://www.8comic.com/comic/all.html',
	'title' => 'http://www.8comic.com/html/',
	'js' => 'http://www.8comic.com/js/comicview.js'
);

} // namespace site8comic

namespace {
class Site_8comic extends CI_Model {
	/**
	 * Site ID
	 */
	private $sid;
	private $siteName = '8comic';
	/**
	 * CI Instance for model to use
	 */
	private $CI;

	function __construct () {
		$this->CI = & get_instance();
		$this->CI->load->library('curl');
		$this->CI->curl->tmp_folder(PRIVATE_PATH . 'tmp/');
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

	/**
	 * Check whether site is okay
	 * @return {site_ok: bool, img_ok: bool}
	 */
	public function check () {
		$result = (object)[];
		$all_page = $this->grab->curlUseProxy($this->CI->curl)->url(ChapterFetcher::$gURLs->index)->add()->get();
		$thumbnail = $this->grab->curlUseProxy($this->CI->curl)->url('http://www.8comic.com/pics/0/7340s.jpg')->add()->get();
		$result->site_ok = strlen($all_page) >= 887087;
		$result->img_ok = strlen($thumbnail) >= 7751;
		return $result;
	}

	/**
	 * Get the image url from Database
	 * @param cid: int chapter ID
	 * @param page: int page number
	 * @param getLink: bool whether get link or not
	 * @return getLink ? string picture URL : picture binary
	 */
	public function image_comic ($cid, $page, $getLink) {
		$sql='SELECT t1.meta, t1.pages, t1.`index`, t2.`index` as itemid'
			.' FROM index_chapter AS t1'
			.' LEFT JOIN index_title AS t2 ON t1.tid = t2.id'
			.' WHERE cid = ' . $this->db->escape($cid);
		$r = $this->db->query($sql)->row();
		$meta = json_decode($r->meta, true);
		$c_index = $r->index;
		$itemid = $r->itemid;
		$pages = $r->pages;

		if (!$meta) {
			exit404();
		}

		// Logic from 8comic
		$m = (($page-1)/10)%10+(($page-1)%10)*3;
		$img = page_convert($page). '_' .substr($meta['code'], $m, 3);
		$url = "http://img". $meta['sid'] .".8comic.com/" . $meta['did'] ."/". $itemid ."/". $meta['num'] ."/". $img .".jpg";
		if ($getLink) {
			return $url;
		}

		if (!$this->CI->grab->render_image([
			'url' => $url,
			'referer' => ChapterFetcher::$gURLs->title
		])) {
			$this->CI->grab->signal_comic_error($cid);
			elog('site_8comic - image error, cid=' . $cid);
			exit404();
		}
	}

	/**
	 * Get the image thumbnail url from Database
	 * @param tid: int title ID
	 * @param medium: bool whether medium
	 * @param getLink: bool whether get link or not
	 * @return getLink ? string picture URL : redirect header
	 */
	public function image_thumbnail ($tid, $medium, $getLink) {
		if (!is_num($tid)) {
			return False;
		}

		$sql='SELECT `index` FROM index_title'
			.' WHERE id = '. $tid;
		$q = $this->db->select('index')
					  ->from('index_title')
					  ->where('id', $tid)
					  ->get();

		if ($q->num_rows() == 0) {
			header('HTTP/1.0 404 Not Found');
			exit;
		}

		$index = $q->row()->index;
		$url = 'http://www.8comic.com/pics/0/'. $index . ($medium ? '' : 's') .'.jpg';
		if ($getLink) {
			return $url;
		}
		redirect($url);
	}

	private function update_title () {
		$html = $this->grab->curlUseProxy($this->CI->curl)
			->url(ChapterFetcher::$gURLs->index)
			->add()->get();

		$result = array();
		if (!preg_match_all('/<a href="\/html\/(\d+)\.html"[^>]+>([^<]+)<\/a>/', $html, $result, PREG_SET_ORDER )) {
			throw new Exception('Index grabed failed.');
		}

		foreach ( $result as $row ) {
			list(, $tid, $title) = $row;
			$title = trim(unhtmlentities(toUTF8($title)));
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

	private function update_chapter () {
		elog('start update chapter', 'debug');
		$chapterFetcher = new site8comic\ChapterFetcher($this->sid);
		$chapterFetcher->fetch();
	}
}

} // global namespace
