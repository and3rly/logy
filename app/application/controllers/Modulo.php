<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Modulo extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["Modulo_model", "Menu_model"]);
		$this->output->set_content_type('application/json');
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$modulos = $this->Modulo_model->_buscar();

		foreach ($modulos as $row) {
			if ($row->detalle == 1) {
				$row->children = $this->Menu_model->buscar([
					"modulo_id" => $row->id,
					"activo" => 1,
					"_orden_asc" => "orden"
				]);
			}
		}

		$data = [
			"lista" => $modulos
		];

		$this->output->set_output(json_encode($data));
	}

	public function configuracion()
	{
		$modulos = $this->Modulo_model->buscar([
			"_orden_asc" => "orden"
		]);

		foreach ($modulos as $row) {
			$row->menus = $this->Menu_model->buscar([
				"modulo_id" => $row->id,
				"_orden_asc" => "orden"
			]);
		}

		$this->output->set_output(json_encode([
			"lista" => $modulos
		]));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "nombre")) {
				$modulo = new Modulo_model($id);

				if ($modulo->guardar($datos)) {
					$data["exito"] = 1;
					$data["mensaje"] = "Módulo guardado con éxito.";
					$data["linea"] = $modulo->buscar([
						"id" => $modulo->getPK(),
						"_uno" => true
					]);
					$data["linea"]->menus = $this->Menu_model->buscar([
						"modulo_id" => $modulo->getPK(),
						"_orden_asc" => "orden"
					]);
				} else {
					$data["mensaje"] = $modulo->getMensaje();
				}
			} else {
				$data["mensaje"] = "Complete los campos marcados con *.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto.";
		}

		$this->output->set_output(json_encode($data));
	}

	public function guardar_menu($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (verPropiedad($datos, "modulo_id") &&
				verPropiedad($datos, "nombre") &&
				verPropiedad($datos, "url")) {
				$modulo = $this->Modulo_model->buscar([
					"id" => $datos->modulo_id,
					"_uno" => true
				]);

				if ($modulo) {
					$menu = new Menu_model($id);

					if ($menu->guardar($datos)) {
						$data["exito"] = 1;
						$data["mensaje"] = "Opción de menú guardada con éxito.";
						$data["linea"] = $menu->buscar([
							"id" => $menu->getPK(),
							"_uno" => true
						]);
					} else {
						$data["mensaje"] = $menu->getMensaje();
					}
				} else {
					$data["mensaje"] = "El módulo seleccionado no existe.";
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

/* End of file Modulo.php */
/* Location: ./application/controllers/Modulo.php */ ?>
