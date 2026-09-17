<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Inventario_det_model extends General_model {

	public $inventario_enc_id;
	public $producto_id;
	public $unidad_medida_id;
	public $producto_presentacion_id;
	public $fecha_vence;
	public $cantidad_sistema = 0;
	public $cantidad_fisica;
	public $diferencia;
	public $costo;
	public $observacion;
	public $activo = 1;

	public function __construct($id="")
	{
		parent::__construct();

		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	public function _buscar($args=[])
	{
		if (elemento($args, "inventario_enc_id")) {
			$this->db->where("a.inventario_enc_id", $args["inventario_enc_id"]);
		}

		$tmp = $this->db
		->select("a.*, b.codigo, b.codigo_barra, b.nombre as nombre_producto,
			b.control_vence, c.codigo as codigo_um, c.nombre as nombre_um,
			d.nombre as nombre_marca, e.nombre as nombre_categoria")
		->join("inventario_enc f", "f.id = a.inventario_enc_id")
		->join("producto b", "b.id = a.producto_id AND b.empresa_id = f.empresa_id")
		->join("unidad_medida c", "c.id = a.unidad_medida_id")
		->join("marca d", "d.id = b.marca_id")
		->join("categoria e", "e.id = b.categoria_id")
		->where("f.empresa_id", $this->usr["empresa_id"])
		->where("a.activo", 1)
		->order_by("a.id", "asc")
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}

	public function guardarLineas($inventarioId, $lineas)
	{
		foreach ($lineas as $linea) {
			$detalle = new Inventario_det_model();
			if (!$detalle->guardar([
				"inventario_enc_id"        => $inventarioId,
				"producto_id"              => $linea->producto_id,
				"unidad_medida_id"         => $linea->unidad_medida_id,
				"producto_presentacion_id" => null,
				"fecha_vence"              => $linea->fecha_vence,
				"cantidad_sistema"         => 0,
				"cantidad_fisica"          => $linea->cantidad,
				"diferencia"               => $linea->cantidad,
				"costo"                    => $linea->costo,
				"observacion"              => $linea->observacion,
				"activo"                   => 1
			])) {
				$this->setMensaje($detalle->getMensaje());
				return false;
			}
		}

		return true;
	}
}

/* End of file Inventario_det_model.php */
/* Location: ./application/models/inventario/Inventario_det_model.php */
