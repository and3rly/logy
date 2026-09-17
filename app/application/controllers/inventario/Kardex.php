<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kardex extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model("inventario/Movimiento_model");
		$this->output->set_content_type('application/json');
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$args = [
			"termino"            => $this->input->get("termino", true),
			"sucursal_id"        => $this->input->get("sucursal_id", true),
			"movimiento_tipo_id" => $this->input->get("movimiento_tipo_id", true),
			"fecha_desde"        => $this->input->get("fecha_desde", true),
			"fecha_hasta"        => $this->input->get("fecha_hasta", true)
		];

		if (!empty($args["fecha_desde"]) &&
			!empty($args["fecha_hasta"]) &&
			$args["fecha_desde"] > $args["fecha_hasta"]) {
			$this->output->set_output(json_encode([
				"lista"   => [],
				"mensaje" => "La fecha inicial no puede ser mayor que la fecha final."
			]));
			return;
		}

		$saldos = [];
		foreach ($this->Movimiento_model->saldosAnteriores($args) as $row) {
			$clave = $row->producto_id . "-" . $row->unidad_medida_id . "-" . $row->sucursal_id;
			$saldos[$clave] = (float) $row->saldo;
		}

		$lista = [];
		$totalEntradas = 0;
		$totalSalidas = 0;

		foreach ($this->Movimiento_model->_buscar($args) as $row) {
			$clave = $row->producto_id . "-" . $row->unidad_medida_id . "-" . $row->sucursal_id;
			$cantidad = (float) $row->cantidad;
			$saldos[$clave] = ($saldos[$clave] ?? 0) + $cantidad;
			$row->entrada = $cantidad > 0 ? $cantidad : 0;
			$row->salida = $cantidad < 0 ? abs($cantidad) : 0;
			$row->saldo = $saldos[$clave];

			if (!empty($args["movimiento_tipo_id"]) &&
				(string) $row->movimiento_tipo_id !== (string) $args["movimiento_tipo_id"]) {
				continue;
			}

			if ($row->entrada > 0) {
				$totalEntradas++;
			}

			if ($row->salida > 0) {
				$totalSalidas++;
			}
			$lista[] = $row;
		}

		$this->output->set_output(json_encode([
			"lista" => array_reverse($lista),
			"resumen" => [
				"movimientos" => count($lista),
				"entradas"    => $totalEntradas,
				"salidas"     => $totalSalidas
			]
		]));
	}

	public function get_datos()
	{
		$this->output->set_output(json_encode([
			"cat" => $this->Movimiento_model->getCatalogos()
		]));
	}
}

/* End of file Kardex.php */
/* Location: ./application/controllers/inventario/Kardex.php */
