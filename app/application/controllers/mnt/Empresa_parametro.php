<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Empresa_parametro extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Empresa_parametro_model"]);
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{	
		$data = $this->Empresa_parametro_model->buscar([
			"empresa_id" => $_SESSION["empresa_id"],
			"_uno"       => true
		]);

		$this->output->set_output(json_encode(["lista" => $data]));
	}

	public function guardar($id="")
	{	
		$data = ["exito" => 0];

		$datos = json_decode(file_get_contents("php://input"));

		$ep = new Empresa_parametro_model($id);

		if ($ep->guardar($datos)) {
			$data["exito"]   = 1;
			$data["mensaje"] = "Parámetro guardado con éxito.";
			$data["linea"]   = $ep->buscar([
				"id"   => $ep->getPK(),
				"_uno" => true
			]);

		} else {
			$data["mensaje"] = $ep->getMensaje();
		}

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Empresa_parametro.php */
/* Location: ./application/controllers/mnt/Empresa_parametro.php */ ?>