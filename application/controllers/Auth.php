<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    private $apiKey = 'BMIAUTHALLPLANT';

    public function __construct()
    {
        parent::__construct();
        header('Content-Type: application/json');

        $clientKey = $this->input->get_request_header('X-API-KEY');

        if ($clientKey !== $this->apiKey) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Unauthorized'
            ]);
            exit;
        }

        $this->load->library('encryption');
        $this->load->database();
    }

    public function loginKaryawan()
    {
        $plantId  = $this->input->post('plant_id');
        $nik      = $this->input->post('nik');
        $password = $this->input->post('password');

        if (!$plantId || !$nik || !$password) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Data tidak lengkap'
            ]);
            return;
        }

        $plant = $this->db->where('plant_id', $plantId)
                          ->get('plant_config')
                          ->row();

        if (!$plant) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Plant tidak ditemukan'
            ]);
            return;
        }

        $dbPassword = $this->encryption->decrypt($plant->db_pass);


		$plantDb = array(
            'hostname' => $plant->db_host . ':' . $plant->db_port,
            'username' => $plant->db_user,
            'password' => $dbPassword,
            'database' => $plant->db_name,
            'dbdriver' => 'mysqli',
            'dbprefix' => '',
            'pconnect' => FALSE,
            'db_debug' => (ENVIRONMENT !== 'production'),
            'cache_on' => FALSE,
            'char_set' => 'utf8',
            'dbcollat' => 'utf8_general_ci',
        );


        $plantConn = $this->load->database($plantDb, TRUE);

        $user = $plantConn->select('
                    tbl_karyawan.id,
                    tbl_karyawan.nik,
                    tbl_karyawan.nama,
                    tbl_karyawan.tgllahir,
					tbl_karyawan.idplant,
                    tbl_karyawan.idbagian,
					tbl_plant.kodeplant as plant_code,
					tbl_plant.nama as plant_name,
                    tbl_bagian.kodebagian as bag_code,
                    tbl_bagian.nama as bag_name
                ')
                ->from('tbl_karyawan')
                ->join('tbl_plant', 'tbl_plant.id = tbl_karyawan.idplant', 'left')
                ->join('tbl_bagian', 'tbl_bagian.id = tbl_karyawan.idbagian', 'left')
                ->where('tbl_karyawan.nik', $nik)
                ->get()
                ->row();

        if (!$user) {
            echo json_encode([
                'status' => 'error',
                'message' => 'NIK tidak ditemukan'
            ]);
            return;
        }

        $tgl = date('dmy', strtotime($user->tgllahir));
        $generatedPassword = $user->nik . $tgl;

        if ($password !== $generatedPassword) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password salah'
            ]);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'user'   => [
                'id'   			=> $user->id,
                'nik'  			=> $user->nik,
                'nama' 			=> $user->nama,
				'idplant'		=> $user->idplant,
				'plant_code'	=> $user->plant_code,
				'plant_name'	=> $user->plant_name,
				'idbagian'		=> $user->idbagian,
				'bag_code'		=> $user->bag_code,
				'bag_name'		=> $user->bag_name,
				'role'			=> 'KARYAWAN'
            ],
			'db_config' => [
				'host'     => $plant->db_host,
				'port'     => $plant->db_port,
				'database' => $plant->db_name,
				'username' => $plant->db_user,
				'password' => $dbPassword
			]

        ]);
    }

	public function loginUser()
	{
		$plantId  = $this->input->post('plant_id');
        $nik      = $this->input->post('nik');
        $password = $this->input->post('password');

        if (!$plantId || !$nik || !$password) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Data tidak lengkap'
            ]);
            return;
        }

        $plant = $this->db->where('plant_id', $plantId)
                          ->get('plant_config')
                          ->row();

        if (!$plant) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Plant tidak ditemukan'
            ]);
            return;
        }

        $dbPassword = $this->encryption->decrypt($plant->db_pass);


		$plantDb = array(
            'hostname' => $plant->db_host . ':' . $plant->db_port,
            'username' => $plant->db_user,
            'password' => $dbPassword,
            'database' => $plant->db_name,
            'dbdriver' => 'mysqli',
            'dbprefix' => '',
            'pconnect' => FALSE,
            'db_debug' => (ENVIRONMENT !== 'production'),
            'cache_on' => FALSE,
            'char_set' => 'utf8',
            'dbcollat' => 'utf8_general_ci',
        );


        $plantConn = $this->load->database($plantDb, TRUE);

        $user = $plantConn->select('
                    tbl_user.id,
                    tbl_user.nama,
                    tbl_user.nama_lengkap,
					tbl_user.idplant,
                    tbl_user.id_bagian,
                    tbl_user.password,
					tbl_plant.kodeplant as plant_code,
					tbl_plant.nama as plant_name,
                    tbl_bagian.kodebagian as bag_code,
                    tbl_bagian.nama as bag_name,
					tbl_kecelakaan_kerja_user_role.role_name
                ')
                ->from('tbl_user')
                ->join('tbl_plant', 'tbl_plant.id = tbl_user.idplant', 'left')
                ->join('tbl_bagian', 'tbl_bagian.id = tbl_user.id_bagian', 'left')
                ->join('tbl_kecelakaan_kerja_user_role', 'tbl_kecelakaan_kerja_user_role.iduser = tbl_user.id', 'left')
                ->where('tbl_user.nama', $nik)
                ->get()
                ->row();

        if (!$user) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Username tidak ditemukan'
            ]);
            return;
        }

		if (!$user->role_name) {
			echo json_encode([
				'status' => 'error',
				'message' => 'Role user belum didaftarkan.'
			]);
			return;
		}

        $generatedPassword = '*' . strtoupper(sha1(sha1($password, true)));

        if ($generatedPassword !== $user->password) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Password salah'
            ]);
            return;
        }

        echo json_encode([
            'status' => 'success',
            'user'   => [
                'id'   			=> $user->id,
                'nik'  			=> $user->nama,
                'nama' 			=> $user->nama_lengkap,
				'idplant'		=> $user->idplant,
				'plant_code'	=> $user->plant_code,
				'plant_name'	=> $user->plant_name,
				'idbagian'		=> $user->id_bagian,
				'bag_code'		=> $user->bag_code,
				'bag_name'		=> $user->bag_name,
				'role'			=> $user->role_name
            ],
			'db_config' => [
				'host'     => $plant->db_host,
				'port'     => $plant->db_port,
				'database' => $plant->db_name,
				'username' => $plant->db_user,
				'password' => $dbPassword
			]

        ]);
	}

	public function login()
	{
		$plantId  = $this->input->post('plant_id');
		$nik      = $this->input->post('nik');
		$password = $this->input->post('password');

		if (!$plantId || !$nik || !$password) {
			echo json_encode([
				'status' => 'error',
				'message' => 'Data tidak lengkap'
			]);
			return;
		}

		$plant = $this->db->where('plant_id', $plantId)
						->get('plant_config')
						->row();

		if (!$plant) {
			echo json_encode([
				'status' => 'error',
				'message' => 'Plant tidak ditemukan'
			]);
			return;
		}

		$dbPassword = $this->encryption->decrypt($plant->db_pass);

		$plantDb = [
			'hostname' => $plant->db_host . ':' . $plant->db_port,
			'username' => $plant->db_user,
			'password' => $dbPassword,
			'database' => $plant->db_name,
			'dbdriver' => 'mysqli'
		];

		$plantConn = $this->load->database($plantDb, TRUE);

		// CEK DATA LOGIN UNTUK USER
		$user = $plantConn->select('
                    tbl_user.id,
                    tbl_user.nama,
                    tbl_user.nama_lengkap,
					tbl_user.idplant,
                    tbl_user.id_bagian,
                    tbl_user.password,
					tbl_plant.kodeplant as plant_code,
					tbl_plant.nama as plant_name,
                    tbl_bagian.kodebagian as bag_code,
                    tbl_bagian.nama as bag_name,
					tbl_kecelakaan_kerja_user_role.role_name
                ')
                ->from('tbl_user')
                ->join('tbl_plant', 'tbl_plant.id = tbl_user.idplant', 'left')
                ->join('tbl_bagian', 'tbl_bagian.id = tbl_user.id_bagian', 'left')
                ->join('tbl_kecelakaan_kerja_user_role', 'tbl_kecelakaan_kerja_user_role.iduser = tbl_user.id', 'left')
                ->where('tbl_user.nama', $nik)
                ->get()
                ->row();

		if ($user) {
			if ($user->role_name === null) {
				echo json_encode([
					'status' => 'error',
					'message' => 'Role user belum didaftarkan.'
				]);
				return;
			}

			$generatedPassword = '*' . strtoupper(sha1(sha1($password, true)));

			if ($generatedPassword !== $user->password) {
				echo json_encode([
					'status' => 'error',
					'message' => 'Password salah'
				]);
				return;
			}

			echo json_encode([
				'status' => 'success',
				'user'   => [
					'id'   			=> $user->id,
					'nik'  			=> $user->nama,
					'nama' 			=> $user->nama_lengkap,
					'idplant'		=> $user->idplant,
					'plant_code'	=> $user->plant_code,
					'plant_name'	=> $user->plant_name,
					'idbagian'		=> $user->id_bagian,
					'bag_code'		=> $user->bag_code,
					'bag_name'		=> $user->bag_name,
					'role'			=> $user->role_name
				],
				'db_config' => [
					'host'     => $plant->db_host,
					'port'     => $plant->db_port,
					'database' => $plant->db_name,
					'username' => $plant->db_user,
					'password' => $dbPassword
				]

			]);
			return;
		}

		// CEK DATA LOGIN UNTUK KARYAWAN
		$karyawan = $plantConn->select('
						tbl_karyawan.id,
						tbl_karyawan.nik,
						tbl_karyawan.nama,
						tbl_karyawan.tgllahir,
						tbl_karyawan.idplant,
						tbl_karyawan.idbagian,
						tbl_plant.kodeplant as plant_code,
						tbl_plant.nama as plant_name,
						tbl_bagian.kodebagian as bag_code,
						tbl_bagian.nama as bag_name
					')
					->from('tbl_karyawan')
					->join('tbl_plant', 'tbl_plant.id = tbl_karyawan.idplant', 'left')
					->join('tbl_bagian', 'tbl_bagian.id = tbl_karyawan.idbagian', 'left')
					->where('tbl_karyawan.nik', $nik)
					->get()
					->row();

		if ($karyawan) {

			$tgl = date('dmy', strtotime($karyawan->tgllahir));
			$generatedPassword = $karyawan->nik . $tgl;

			if ($password !== $generatedPassword) {
				echo json_encode([
					'status' => 'error',
					'message' => 'Password salah'
				]);
				return;
			}

			echo json_encode([
				'status' => 'success',
				'user'   => [
					'id'   			=> $karyawan->id,
					'nik'  			=> $karyawan->nik,
					'nama' 			=> $karyawan->nama,
					'idplant'		=> $karyawan->idplant,
					'plant_code'	=> $karyawan->plant_code,
					'plant_name'	=> $karyawan->plant_name,
					'idbagian'		=> $karyawan->idbagian,
					'bag_code'		=> $karyawan->bag_code,
					'bag_name'		=> $karyawan->bag_name,
					'role'			=> 'KARYAWAN'
				],
				'db_config' => [
					'host'     => $plant->db_host,
					'port'     => $plant->db_port,
					'database' => $plant->db_name,
					'username' => $plant->db_user,
					'password' => $dbPassword
				]

			]);
			return;
		}

		echo json_encode([
			'status' => 'error',
			'message' => 'User tidak ditemukan'
		]);
	}


}
