<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Comic_model extends CI_Model {
	private $t_title = 'index_title';
	private $t_chapter = 'index_chapter';
	public $limit = 50;

	private function extract_meta ($row) {
		if ( !is_array($row) ) {
			$row = (object) $row;
			$row->meta = json_decode($row->meta);
		} else {
			foreach ($row as &$r) {
				$r = (object) $r;
				$r = $this->extract_meta($r);
			}
		}
		return $row;
	}

	public function fix_date (&$q) {
		foreach ($q as &$row) {
			$date = $row['update_time'];
			$row['dateStr'] = timeFix($date);
			// $row['dateTime'] = $date;
			$row['date'] = substr($date, 0, 10);
			$row = (object) $row;
		}
	}

	public function tid2vtid ($tid) {
		$q = $this->db->select('vtid')
					  ->from($this->t_title)
					  ->where('id', $tid)
					  ->get()->row();
	  	if ($q && isset($q->vtid)) {
	  		return $q->vtid;
	  	} else {
	  		return False;
	  	}
	}

	public function read_chapters_newest ($limit) {
		$sql="SELECT t1.tid, t1.cid, t1.name AS chapter, t2.name AS title, t1.update_time"
			." FROM index_chapter AS t1"
			." LEFT JOIN index_title AS t2 ON(t1.`tid` = t2.`id`)"
			.' WHERE (t1.name like "第%" OR t1.name like "%話" OR t1.name like "%捲") AND t1.`error` = 0'
			." ORDER BY t1.`update_time` DESC LIMIT ".$limit;
		$q = $this->db->query($sql)->result_array();
		$this->fix_date($q);
		return $q;
	}

	public function read_title_by_tid ($id) {
		$q = $this->db->select('*')
					 ->from($this->t_title)
					 ->join('title_info AS INFO', 'INFO.tid = '. $this->t_title .'.id', 'left')
					 ->where('id', $id)
					 ->limit(1)->get();
		return $q->num_rows() == 0 ? array() : $this->extract_meta($q->row());
	}

	public function read_title_by_index ($id) {
		$q = $this->db->select('*')
					 ->from($this->t_title)
					 ->join('title_info AS INFO', 'INFO.tid = '. $this->t_title .'.id', 'left')
					 ->where('index', $id)
					 ->limit(1)->get();
		return $q->num_rows() == 0 ? array() : $this->extract_meta($q->row());
	}

	public function read_titles_by_vtid ($vtid) {
		$q = $this->db->select('*')
					 ->from($this->t_title)
					 ->where('vtid', $vtid)
					 ->get();
		return $q->num_rows() == 0 ? array() : $this->extract_meta($q->result_array());
	}

	public function read_titles_by_cat_id ($id) {
		$q = $this->db->select('*')
					 ->from($this->t_title)
					 ->where('cat_id', $id)
					 ->get();
		return $q->num_rows() == 0 ? array() : $this->extract_meta($q->result_array());
	}

	private function read_titles_prep ($cat_id, $type) {
		if ( !is_num($cat_id) )
			$cat_id = 0;

		if ( $type == 'online' ) {
			$this->db->where('stop_renew', '0');
		} else {
			$this->db->where('stop_renew', '1');
		}

		if ( $cat_id != 0 ) {
			$this->db->where('cat_id', $cat_id);
		}
	}

	public function read_vtitles_by_author ($author_id) {
		$data = $this->db->select('VT.vtid, T.id AS tid, VT.name AS title, SUM(INFO.pop) AS `pop`, MAX(INFO.update_time) AS `update_time`')
						 ->from('virtual_title AS VT')
						 ->join('index_title AS T', 'T.vtid = VT.vtid')
						 ->join('title_info AS INFO', 'INFO.tid = T.id', 'left')
						 ->order_by('pop', 'desc')
						 ->group_by('VT.vtid')
						 ->where('author_id', $author_id)
						 ->get()->result_array();
		$this->fix_date($data);
		return $data;
	}

	public function read_vtitles ($cat_id, $type, $page, $order) {
		$this->read_titles_prep($cat_id, $type);
		if ($order == 'name') {
			$order = 'T.name';
		}
		$data = $this->db->select('VT.vtid, T.id AS tid, VT.name AS title, SUM(INFO.pop) AS `pop`, MAX(INFO.update_time) AS `update_time`')
						 ->from('virtual_title AS VT')
						 ->join('index_title AS T', 'T.vtid = VT.vtid')
						 ->join('title_info AS INFO', 'INFO.tid = T.id', 'left')
						 ->order_by($order, 'desc')
						 ->group_by('VT.vtid')
						 ->limit($this->limit, ($page-1) * $this->limit)
						 ->get()->result_array();
		$this->fix_date($data);
		return $data;
	}

	public function read_vtitles_count ($cat_id, $type) {
		$this->read_titles_prep($cat_id, $type);
		return $this->db->select('VT.vtid')
						 ->from('virtual_title AS VT')
						 ->join('index_title AS T', 'T.vtid = VT.vtid', 'left')
						 ->group_by('VT.vtid')
						 ->get()->num_rows();
	}

	public function read_titles ($cat_id, $type, $page, $order) {
		$this->read_titles_prep($cat_id, $type);
		$data = $this->db->select('id AS tid, name AS title, INFO.pop, INFO.update_time')
						 ->from('index_title AS TITLE')
						 ->join('title_info AS INFO', 'INFO.tid = TITLE.id', 'left')
						 ->order_by($order, 'desc')
						 ->limit($this->limit, ($page-1) * $this->limit)
						 ->get()->result_array();
		$this->fix_date($data);
		return $data;
	}

	public function read_titles_count ($cat_id, $type) {
		$this->read_titles_prep($cat_id, $type);
		return $this->db->count_all_results('index_title');
	}

	public function read_chapter_by_cid ($id) {
		$q = $this->db->select('*')
					 ->from($this->t_chapter)
					 ->where('cid', $id)
					 ->limit(1)->get();
		return $q->num_rows() == 0 ? array() : $this->extract_meta($q->row());
	}

	public function read_chapters_by_tid ($id) {
		$q = $this->db->select('*')
					 ->from($this->t_chapter)
					 ->where('tid', $id)
					 ->order_by('index')
					 ->get();
		return $q->num_rows() == 0 ? array() : $this->extract_meta($q->result_array());
	}
}