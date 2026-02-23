<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Plant extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Plant_model');
        header('Content-Type: application/json');
    }

    public function index()
    {
        $plants = $this->Plant_model->get_all();

        echo json_encode([
            'status' => 'success',
            'data'   => $plants
        ]);
    }
}
