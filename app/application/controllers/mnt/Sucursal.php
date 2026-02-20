<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sucursal extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Sucursal_model"]);
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function get_datos()
	{
		$data = [
			"cat" => [
				"municipios"    => $this->catalogo->verMunicipios(),
				"departamentos" => $this->catalogo->verDepartamentos()
			]
		];
		
		$this->output->set_output(json_encode($data));
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Sucursal_model->_buscar($_GET)
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "nombre") &&
				verPropiedad($datos, "municipio_id")) {
				
				$sucursal = new Sucursal_model($id);

				if ($sucursal->existe($datos)) {
					$data["mensaje"] = "La sucursal que intenta guardar ya existe.";
				} else {
					if ($sucursal->guardar($datos)) {
						$data["exito"] = 1;
						$data["mensaje"] = "Sucursal guardada con éxito.";
						$data["linea"] = $sucursal->_buscar([
							"id"  => $sucursal->getPK(),
							"uno" => true
						]);
					} else {
						$data["mensaje"] = $sucursal->getMensaje();
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

/* End of file Sucursal.php */
/* Location: ./application/controllers/mnt/Sucursal.php */ ?>