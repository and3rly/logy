<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Usuario extends CI_Controller {

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function prueba($value='')
	{
		die("Prueba");
	}
}

/* End of file Usuario.php */
/* Location: ./application/controllers/Usuario.php */ ?>