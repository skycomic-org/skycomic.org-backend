<?php

class Category_model extends CI_Model {
	
	public function read () {
		return $this->read_by_lang();
	}
	
	public function read_by_lang ($lang = False, $type) {
		if ($lang == 'en') {
			$this->db->where('category_id <= 26');
		} else if ($lang !== False) {
			$this->db->where('category_id > 26');
		}
		
		if ( $type == 'online' ) {
			$this->db->where('stop_renew = 0');
		} else {
			$this->db->where('stop_renew = 1');
		}
	
		$array = $this->db->select('CAT.category_id, CAT.name')
						  ->from('index_title AS TITLE')
						  ->join('category AS CAT', 'TITLE.cat_id = CAT.category_id', 'left')
						  ->group_by('category_id')
						  ->order_by('category_id', 'asc')
						  ->get()->result_array();
		return $array;
	}
	
	public function read_by_cat_id ($cid) {
		if ( ! is_num($cid) )
			return False;
		$q = $this->db->select('name')
					  ->from('category')
					  ->where('category_id', $cid)
					  ->get();
		return $q->num_rows() == 0 ? array() : $q->row();
	}
}

// End of file category_model.php