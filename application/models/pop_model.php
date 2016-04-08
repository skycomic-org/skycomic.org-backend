<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pop_model extends CI_Model {

	function __construct () {
		parent::__construct();
		$this->user_id = $this->session->userdata('user_id');
	}

	public function cron () {
		$now = new DateTime();
		if ( $now->format('d') == 1 ) { // 每月1日
			$this->db->query('UPDATE pop SET month=0');
		}
		if ( $now->format('N') == 1 ) { // 每禮拜一
			$this->db->query('UPDATE pop SET week=0');
		}
		$this->db->query('UPDATE pop SET today=0');
		$this->db->query('TRUNCATE TABLE `pop_ip`');
	}

	public function create ($tid, $cid) {
		$count = $this->db->where('cid', $cid)
						  ->count_all_results('pop');

		if ($count == 0) { // 如果該漫畫尚未被點過
			$this->db->insert('pop', array(
				'tid' => $tid,
				'cid' => $cid
			), True);
			$this->db->insert('pop_ip', array(
				'cid' => $cid,
				'u_sn' => $this->user_id,
				'ip' => $_SERVER['REMOTE_ADDR']
			), True);
		} else { // 如果有被看過
			$result = $this->db->simple_query('INSERT INTO pop_ip (cid, u_sn, ip)'
					. ' VALUES("'. $cid .'","'. $this->user_id .'","'. $_SERVER['REMOTE_ADDR'] .'" )');
			if ( $result === True ) {
				$this->db->query('UPDATE `pop` SET `today` = (`today` +1) ,`week` = (`week`+ 1) ,`month` = (`month` + 1) WHERE `cid` = '. $cid);
			}
		}
	}

	public function last_view ($limit) {
		$this->load->library('ecache');
		$CI = $this;
		$q = $this->ecache->get('comic_user_lastview_' . $limit,
			function () use(&$CI, $limit) {
				$sql = implode(' ', array('SELECT * FROM ',
						'(SELECT *',
						'FROM  `user_lastview`',
						'ORDER BY  `viewtime` DESC',
						'LIMIT 32',
						') t1',
						'GROUP BY cid',
						'ORDER BY `viewtime` DESC',
						'LIMIT ' . $limit));
				$q = $CI->db->select('ul.tid, ul.cid, ic.name AS chapter, it.name AS title, ic.update_time, ul.viewtime, pop.today AS pop')
							->from('('. $sql .') AS `ul`')
							->join('index_chapter AS `ic`', 'ul.cid = ic.cid')
							->join('pop', 'ul.cid = pop.cid')
							->join('index_title AS `it`', 'ul.tid = it.id')
							->get()->result_array();
				return $q;
			}, 60);
		return $q;
	}

	public function read_titles ($type, $limit) {
		$this->load->library('ecache');
		$CI = $this;
		$q = $this->ecache->get('comic_titles_' . $type . '_' . $limit,
			function () use(&$CI, $type, $limit) {
				$t1 = 'SELECT t3.id AS tid, SUM(t1.'.$type.') AS `pop`, t3.name as title'
					. ' FROM pop AS t1, index_chapter AS t2, index_title AS t3'
					. ' WHERE t1.cid = t2.cid AND t2.tid = t3.id'
					. ' GROUP BY t2.tid'
					;
				$sql = 'SELECT * FROM ('.$t1.') AS t1 ORDER BY t1.`pop` DESC LIMIT '.$limit;
				$q = $CI->db->query($sql)->result_array();
				return $q;
			}, 60*60*2);
		return $q;
	}

	public function read_chapters ($type, $limit) {
		$this->load->library('ecache');
		$CI = $this;
		$q = $this->ecache->get('comic_chapters_' . $type . '_' . $limit,
			function () use(&$CI, $type, $limit) {
				$sql = 'SELECT t3.id AS tid, t1.cid, `'.$type.'` AS `pop`, t2.name as chapter, t3.name AS `title`'
					 . ' FROM pop AS t1, index_chapter AS t2, index_title AS t3'
					 . ' WHERE t1.cid = t2.cid AND t2.tid = t3.id'
					 . ' ORDER BY `pop` DESC'
					 . ' LIMIT '.$limit
					 ;
				$q = $CI->db->query($sql)->result_array();
				return $q;
			},60*60*2);
		return $q;
	}
}
