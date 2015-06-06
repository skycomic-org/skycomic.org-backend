<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Author_model extends CI_Model {
	private $table = 'author';

	public function read ($author_id) {
		return $this->db->select('*')
					  ->from($this->table)
					  ->where('id', $author_id)
					  ->get()->row();
	}

	public function read_by_name ($name) {
		return $this->db->select('*')
					  ->from($this->table)
					  ->where('name', $name)
					  ->get()->row();
	}
}