<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Existencia extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model("inventario/Stock_model");
		$this->output->set_content_type('application/json');
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$lista = $this->Stock_model->_buscar($_GET);
		$productos = [];
		$sucursales = [];
		$bajoMinimo = 0;
		$agotados = 0;
		$proximosVencer = 0;
		$limiteVencimiento = date("Y-m-d", strtotime("+30 days"));

		foreach ($lista as $row) {
			$cantidad = (float) $row->cantidad;
			$minima = (float) $row->existencia_minima;
			$productos[$row->producto_id] = true;
			$sucursales[$row->sucursal_id] = true;

			if ($cantidad <= 0) {
				$agotados++;
			} else if ($cantidad <= $minima) {
				$bajoMinimo++;
			}

			if ((int) $row->control_vence === 1 &&
				!empty($row->fecha_vence_proxima) &&
				substr($row->fecha_vence_proxima, 0, 10) >= date("Y-m-d") &&
				substr($row->fecha_vence_proxima, 0, 10) <= $limiteVencimiento) {
				$proximosVencer++;
			}
		}

		$this->output->set_output(json_encode([
			"lista" => $lista,
			"resumen" => [
				"productos"       => count($productos),
				"sucursales"       => count($sucursales),
				"bajo_minimo"      => $bajoMinimo,
				"agotados"         => $agotados,
				"proximos_vencer"  => $proximosVencer
			]
		]));
	}

	public function get_datos()
	{
		$this->output->set_output(json_encode([
			"cat" => $this->Stock_model->getCatalogos()
		]));
	}
}

/* End of file Existencia.php */
/* Location: ./application/controllers/inventario/Existencia.php */
