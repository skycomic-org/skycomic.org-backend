<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User_model extends CI_Model {
	private $table = 'user';
	
	function __construct () {
		parent::__construct();
	}
	
	public function read_view_by_tid ($tid, $newest=False) {
		if ( !is_num($tid) ) {
			return array();
		}
		$sql='SELECT `index` AS c_order FROM `user_lastview` AS t1'
			.' LEFT JOIN index_chapter AS t2 USING(`cid`)'
			.' WHERE t1.`tid` = '.$tid.' AND t1.u_sn = '.$this->auth_model->getUserId()
			.' ORDER BY `index` DESC';
		if ($newest) {
			$sql.=' LIMIT 0,1';
		}
		$result = $this->db->query($sql)->result_array();
		if ( !$newest ) {
			$c_orders = array();
			foreach( $result as $row ){
				$c_orders[]= $row['c_order'];
			}
		}
		return count($result) > 0 ? ( $newest ? $result[0]['c_order'] : $c_orders ) : array();
	}

	public function read_by_user_id ($user_id) {
		$q = $this->db->select('*')
							 ->from($this->table)
							 ->where('sn', $user_id)
							 ->limit(1)->get();
		return $q->num_rows() == 0 ? array() : $q->row();
	}
	
	public function read_by_id ($id) {
		$q = $this->db->select('*')
							 ->from($this->table)
							 ->where('id', $id)
							 ->limit(1)->get();
		return $q->num_rows() == 0 ? array() : $q->row();
	}
	
	public function read_by_email ($email) {
		$q = $this->db->select('*')
							 ->from($this->table)
							 ->where('email', $email)
							 ->limit(1)->get();
		return $q->num_rows() == 0 ? array() : $q->row();
	}
}