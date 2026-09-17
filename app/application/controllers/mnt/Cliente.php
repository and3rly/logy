<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cliente extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Cliente_model"]);
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
			"lista" => $this->Cliente_model->_buscar($_GET)
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() !== "post") {
			$data["mensaje"] = "Método incorrecto";
			$this->output->set_output(json_encode($data));
			return;
		}

		$datos = json_decode(file_get_contents("php://input"));

		if (!$datos || !verPropiedad($datos, "nombre")) {
			$data["mensaje"] = "Complete los campos marcados con *.";
			$this->output->set_output(json_encode($data));
			return;
		}

		foreach (["razon_social", "identificacion", "codigo", "direccion", "telefono", "correo", "municipio_id"] as $campo) {
			if (property_exists($datos, $campo) && is_string($datos->$campo)) {
				$datos->$campo = trim($datos->$campo);
				if ($datos->$campo === "") $datos->$campo = null;
			}
		}
		if (!property_exists($datos, "telefono")) $datos->telefono = null;

		if ($datos->telefono !== null &&
			(!ctype_digit((string) $datos->telefono) || (int) $datos->telefono > 2147483647)) {
			$data["mensaje"] = "El teléfono debe contener únicamente dígitos y estar dentro del rango permitido.";
			$this->output->set_output(json_encode($data));
			return;
		}

		$datos->credito = !empty($datos->credito) ? 1 : 0;
		$datos->activo = !property_exists($datos, "activo") || !empty($datos->activo) ? 1 : 0;
		$creditoLimite = $datos->credito_limite ?? 0;
		$creditoDias = $datos->credito_dias ?? 0;

		if ($datos->credito &&
			(!is_numeric($creditoLimite) || (float) $creditoLimite < 0 ||
			 filter_var($creditoDias, FILTER_VALIDATE_INT) === false || (int) $creditoDias < 0)) {
			$data["mensaje"] = "Revise el límite y los días de crédito.";
			$this->output->set_output(json_encode($data));
			return;
		}

		$datos->credito_limite = $datos->credito ? (float) $creditoLimite : 0;
		$datos->credito_dias = $datos->credito ? (int) $creditoDias : 0;

		if (!empty($id) && empty($this->Cliente_model->_buscar([
			"id" => $id,
			"uno" => true
		]))) {
			$data["mensaje"] = "Cliente no encontrado.";
			$this->output->set_status_header("404");
			$this->output->set_output(json_encode($data));
			return;
		}

		$cliente = new Cliente_model($id);

		if ($cliente->existe($datos)) {
			$data["mensaje"] = "El cliente que intenta guardar ya existe.";
		} elseif ($cliente->guardar($datos)) {
			$data["exito"] = 1;
			$data["mensaje"] = "Cliente guardado con éxito.";
			$data["linea"] = $cliente->_buscar([
				"id"  => $cliente->getPK(),
				"uno" => true
			]);
		} elseif ($cliente->getMensaje() === "Nada que actualizar") {
			$data["exito"] = 1;
			$data["mensaje"] = "Cliente sin cambios.";
			$data["linea"] = $cliente->_buscar([
				"id"  => $cliente->getPK(),
				"uno" => true
			]);
		} else {
			$data["mensaje"] = $cliente->getMensaje();
		}

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Cliente.php */
/* Location: ./application/controllers/mnt/Cliente.php */ ?>
