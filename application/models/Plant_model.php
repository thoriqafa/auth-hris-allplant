<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Plant_model extends CI_Model {

	public function __construct()
    {
        parent::__construct();
        $this->load->database(); // ← INI WAJIB
    }

    public function get_all()
    {
        return $this->db
                    ->select('id, plant_id, plant_name')
                    ->from('plant_config')
					->order_by('plant_name')
                    ->get()
                    ->result();
    }
}
