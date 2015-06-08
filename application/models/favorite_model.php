<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Favorite_model extends CI_Model {
	private $t_title = 'index_title';
	private $t_chapter = 'index_chapter';
	private $table = 'favorite';
	
	private $CI;

	function __construct () {
		parent::__construct();
		$this->CI = get_instance();
		$this->CI->load->model('auth_model');
		$this->user_id = $this->auth_model->getUserId();
	}
	
	public function read ($uid = False) {
		if ($uid === False ) {
			$uid = $this->user_id;
		}
		
		$this->CI->load->model('user_model', 'user');
		
		$array = $this->db->select('vtid, F.tid, name AS title, stop_renew')
							->from('favorite AS F')
							->join('index_title AS IT', 'F.tid = IT.id', 'LEFT')
							->join('title_info AS TI', 'F.tid = TI.tid', 'LEFT')
							->where('F.u_sn', $uid)
							->order_by('TI.update_time', 'DESC')
							->get()->result_array();
		foreach ($array as $i=>$row) {
			$sql='SELECT cid, name AS chapter, pages, update_time, `index` FROM index_chapter '
				.'WHERE tid = '.$row['tid'].' AND ( name like "第%" OR name like "%話" OR name like "%集" OR name like "%卷" OR name like "%捲" )'
				.'ORDER BY `index` DESC LIMIT 5';
			$comics = $this->db->query($sql)->result_array();
			$t_sn_viewed = $this->CI->user->read_view_by_tid($row['tid']);
			foreach ($comics as &$row1) {
				$date = $row1['update_time'];
				$row1['dateStr'] = timefix($date);
				$row1['date'] = substr($date, 0, 10);
				// 沒看過 & 一周內
				$row1['new'] = ! in_array($row1['index'], $t_sn_viewed) 
					& ( (time() - strtotime($row1['update_time'])) < 7*24*60*60 );
			}
			$array[$i]['comics']=$comics;
		}
		return $array;
	}

	public function create ($tid) {
		return $this->db->insert($this->table, array(
				'tid' => $tid,
				'u_sn' => $this->user_id
			), True);
	}
	
	public function delete ($tid) {
		return $this->db->where('u_sn', $this->user_id)
						->where('tid', $tid)
						->delete($this->table);
	}
}