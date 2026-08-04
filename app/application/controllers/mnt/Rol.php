<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rol extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Rol_model"]);
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Rol_model->buscar()
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "nombre")) {
				$rol = new Rol_model($id);

				if ($rol->guardar($datos)) {
					$data["exito"] = 1;
					$data["mensaje"] = "Rol guardado con éxito.";
					$data["linea"] = $rol->buscar([
						"id" => $rol->getPK(),
						"_uno" => true
					]);
				} else {
					$data["mensaje"] = $rol->getMensaje();
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

/* End of file Rol.php */
/* Location: ./application/controllers/mnt/Rol.php */ ?>
