<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Venta_detalle_model extends General_model {

	public $venta_id;
	public $producto_id;
	public $unidad_medida_id;
	public $producto_precio_costo_id;
	public $cantidad;
	public $precio;
	public $costo;
	public $total_precio;
	public $total_costo;
	public $ganancia;
	public $base;
	public $iva;
	public $isr;
	public $descuento;
	public $descuento_total;
	public $anulado;

	public function __construct($id="")
	{
		parent::__construct();

		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	public function _buscar($args=[])
	{
		if (elemento($args, "venta_id")) {
			$this->db->where("a.venta_id", $args["venta_id"]);
		}

		$tmp = $this->db
		->select("a.*, b.codigo, b.codigo_barra, b.nombre as nombre_producto,
			b.tipo_producto, b.foto, c.codigo as codigo_unidad, c.nombre as nombre_unidad")
		->join("producto b", "b.id = a.producto_id")
		->join("unidad_medida c", "c.id = a.unidad_medida_id")
		->join("venta d", "d.id = a.venta_id")
		->where("d.empresa_id", $this->usr["empresa_id"])
		->where("a.anulado", 0)
		->order_by("a.id", "asc")
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}

	public function reemplazar($ventaId, $lineas)
	{
		$this->db
		->where("venta_id", $ventaId)
		->where("anulado", 0)
		->update($this->_tabla, ["anulado" => 1]);

		$resultado = [];
		foreach ($lineas as $linea) {
			$detalle = new Venta_detalle_model();
			$linea["venta_id"] = $ventaId;
			$linea["anulado"] = 0;

			if (!$detalle->guardar($linea)) {
				$this->setMensaje($detalle->getMensaje());
				return false;
			}

			$linea["id"] = $detalle->getPK();
			$resultado[] = (object) $linea;
		}

		return $resultado;
	}
}

/* End of file Venta_detalle_model.php */
