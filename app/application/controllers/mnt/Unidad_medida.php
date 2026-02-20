<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Unidad_medida extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Unidad_medida_model"]);
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Unidad_medida_model->buscar()
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "nombre")) {
				$um = new Unidad_medida_model($id);

				if ($um->guardar($datos)) {
					$data["exito"] = 1;
					$data["mensaje"] = "Unidad de medida guardada con éxito.";
					$data["linea"] = $um->buscar([
						"id" => $um->getPK(),
						"_uno" => true
					]);
				} else {	
					$data["mensaje"] = $umr->getMensaje();
				}
			} else {
				$data["mensaje"] = "Complete los campos marcados con *.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto.";
		}

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Unidad_medida.php */
/* Location: ./application/controllers/Unidad_medida.php */