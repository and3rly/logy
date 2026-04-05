<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Empresa extends CI_Controller {

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function guardar($id="")
	{	
		$datos = json_decode(file_get_contents("php://input"));
	}
}

/* End of file Empresa.php */
/* Location: ./application/controllers/mnt/Empresa.php */ ?>