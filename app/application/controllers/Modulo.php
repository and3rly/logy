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
					"_orden_asc" => "orden"
				]);
			}
		}

		$data = [
			"lista" => $modulos
		];

		$this->output->set_output(json_encode($data));
	}
}

/* End of file Modulo.php */
/* Location: ./application/controllers/Modulo.php */ ?>