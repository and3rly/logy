<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Empresa extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model("mnt/Empresa_model");
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function actual()
	{
		$empresa = $this->Empresa_model->actual();

		if (!$empresa) {
			$this->output->set_status_header("404");
			$this->output->set_output(json_encode([
				"exito" => 0,
				"mensaje" => "No se encontró la empresa activa."
			]));
			return;
		}

		$this->output->set_output(json_encode([
			"exito" => 1,
			"empresa" => [
				"nombre" => $empresa->nombre,
				"logo" => $empresa->logo
			]
		]));
	}

	public function guardar($id="")
	{	
		$datos = json_decode(file_get_contents("php://input"));
	}
}

/* End of file Empresa.php */
/* Location: ./application/controllers/mnt/Empresa.php */ ?>
