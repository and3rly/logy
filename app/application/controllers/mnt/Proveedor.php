<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Proveedor extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Proveedor_model"]);
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Proveedor_model->_buscar($_GET)
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "nombre")) {
				
				$prov = new Proveedor_model($id);

				if ($prov->existe($datos)) {
					$data["mensaje"] = "El proveedor que intenta guardar ya existe.";
				} else {
					if ($prov->guardar($datos)) {
						$data["exito"] = 1;
						$data["mensaje"] = "Proveedor guardada con éxito.";
						$data["linea"] = $prov->_buscar([
							"id"  => $prov->getPK(),
							"uno" => true
						]);
					} else {
						$data["mensaje"] = $prov->getMensaje();
					}
				}
			} else {
				$data["mensaje"] = "Complete los campos marcados con *.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto";
		}

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Proveedor.php */
/* Location: ./application/controllers/mnt/Proveedor.php */ ?>