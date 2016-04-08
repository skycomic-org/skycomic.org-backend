<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Comment_model extends CI_Model {
	private $limit = 16;
	private $error;

	function __construct () {
		parent::__construct();
		$this->user_id = $this->session->userdata('user_id');
	}

	public function get_error () {
		$e = $this->error;
		$this->error = '';
		return $e;
	}

	private function set_error ($error) {
		$this->error = $error;
		return $this;
	}

	public function set_limit ($limit) {
		$this->limit = $limit;
		return $this;
	}

	private function read_sql () {
		$hush_sql = 'SELECT comment_id, COUNT(*) AS hush FROM comic_comment_push WHERE push = -1 GROUP BY comment_id ';
		$push_sql = 'SELECT comment_id, COUNT(*) AS push FROM comic_comment_push WHERE push = 1 GROUP BY comment_id ';
		$this->db->select('U.sn AS u_sn, U.nickname, CC.id, CC.cid, IC.name AS chapter, CC.parent_id, CC.title, CC.content, CC.`time`, PUSH.push, HUSH.hush')
				 ->from('comic_comment AS CC')
				 ->join('('. $hush_sql .') AS HUSH', 'CC.id = HUSH.comment_id', 'left')
				 ->join('('. $push_sql .') AS PUSH', 'CC.id = PUSH.comment_id', 'left')
				 ->join('user AS U', 'U.sn = CC.u_sn', 'left')
				 ->join('index_chapter AS IC', 'IC.cid = CC.cid', 'left')
				 ->order_by('CC.id', 'desc');
	}

	private function fixDate (&$row) {
		$date = $row['time'];
		$row['dateStr'] = timeFix($date);
		$row['date'] = substr($date, 0, 10);
		$row = (object) $row;
	}

	private function fixContent ($content) {
		$content = htmlspecialchars($content);
		$content = str_replace("\n",'<br />',$content);
		$content = str_replace("\r",'',$content);
		return $content;
	}

	public function create ($data) {
		$data['time'] = date('Y/m/d H:i:s');
		$data['u_sn'] = $this->user_id;
		$data['content'] = $this->fixContent($data['content']);
		return $this->db->insert('comic_comment', $data);
	}

	public function update ($comment_id, $content) {
		$cdata = $this->read_by_comment_id($comment_id);
		if (count($cdata) == 0) {
			$this->set_error('no this comment');
			return False;
		} else if (!$cdata->my_own) {
			$this->set_error('permission denied');
			return False;
		} else {
			return $this->db->where('id', $comment_id)
							 ->update('comic_comment', array(
								'content' => $this->fixContent($content)
							 ));
		}
	}

	public function read ( &$where_func, $parent_id = NULL, $count_query = False ) {
		$where_func();
		$this->read_sql();
		if ( $parent_id === NULL ) {
			$this->db->where('parent_id IS NULL');
		} else if ( $parent_id ) {
			$this->db->where('parent_id', $parent_id);
		}
		$q = $this->db->get();
		if ( $count_query ) {
			return $q->num_rows();
		}
		if ( $q->num_rows() != 0 ) {
			$r = $q->result_array();
			foreach ($r as &$row) {
				$this->fixDate($row);
				if ( $row->u_sn == $this->user_id ) {
					$row->my_own = True;
				} else {
					$row->my_own = False;
				}
				unset($row->u_sn);
				$row->push = $row->push === null ? 0 : $row->push;
				$row->hush = $row->hush === null ? 0 : $row->hush;
				// replys
				if ( $parent_id === NULL ) {
					$row->replys = $this->read($where_func, $row->id);
				}
			}
			return $r;
		} else {
			return array();
		}
	}

	public function read_by_tid ($tid, $page) {
		$self = $this;
		$where_func = function () use(&$self, $tid) {
			$self->db->where('CC.tid', $tid);
		};
		$this->db->limit($this->limit, ($page - 1) * $this->limit);
		return array(
			'data' => $this->read($where_func),
			'pages' => ceil($this->read($where_func, NULL, True) / $this->limit)
		);
	}

	public function read_by_cid ($cid, $page) {
		$self = $this;
		$where_func = function () use(&$self, $cid) {
			$self->db->where('CC.cid', $cid);
		};
		$this->db->limit($this->limit, ($page - 1) * $this->limit);
		return array(
			'data' => $this->read($where_func),
			'pages' => ceil($this->read($where_func, NULL, True) / $this->limit)
		);
	}

	public function read_by_comment_id ($cid) {
		$where_func = function () {};
		$this->db->where('CC.id', $cid);
		$r = $this->read($where_func, False);
		return isset($r[0]) ? $r[0] : array();
	}
}

// end of file comment_model.php