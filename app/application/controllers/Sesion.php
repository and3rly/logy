<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sesion extends CI_Controller {

	public function index()
	{
		
	}

	public function iniciar_sesion()
	{
		$datos = json_decode(file_get_contents("php://input"));

		echo "<pre>";
		print_r ($datos);
		echo "</pre>";
	}

}

/* End of file Sesion.php */
/* Location: ./application/controllers/Sesion.php */ ?>