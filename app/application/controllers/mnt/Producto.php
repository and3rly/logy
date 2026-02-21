<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Producto extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(["mnt/Producto_model"]);
		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Producto_model->_buscar($_GET)
		];

		$this->output->set_output(json_encode($data));
	}

	public function get_datos()
	{
		$data = [
			"cat" => [
				"marcas"     => $this->catalogo->verMarcas(),
				"unidades"   => $this->catalogo->verUnidadesMedida(),
				"categorias" => $this->catalogo->verCategorias(),
				"tipos"      => getTiposProductos()
			]
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = (object) $_POST;

			$producto = new Producto_model($id);

			if ($producto->existe($datos)) {
				$data["mensaje"] = "El producto que intenta guardar ya existe.";
			} else {
				if (elemento($_FILES, 'foto') && 
					elemento($_FILES['foto'], 'tmp_name')) {
					$imagen = subirArchivo([
						'tmp_name' => $_FILES['foto']['tmp_name'],
						'type'     => $_FILES['foto']['type'],
						'name'     => $_FILES['foto']['name'],
						'carpeta'  => 'producto'
					]);
				
					if ($imagen) {
						$datos->foto  = $imagen->key;
					}
				}

				if ($producto->guardar($datos)) {
					$accion = $id ? "actualizado" : "guardado";

					$data["exito"]   = 1;
					$data['mensaje'] = "Producto $accion correctamente.";
					$data["linea"]   = $producto->_buscar([
						"id" => $producto->getPK(), 
						"uno" => true
					]);
				} else {
					$data["mensaje"] = $producto->getMensaje();
				}
			}
		} else {
			$data["mensaje"] = "Método incorrecto.";
		}

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Producto.php */
/* Location: ./application/controllers/mnt/Producto.php */ ?>