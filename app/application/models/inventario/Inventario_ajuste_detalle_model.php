<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Inventario_ajuste_detalle_model extends General_model {

	public $inventario_ajuste_id;
	public $producto_id;
	public $unidad_medida_id;
	public $producto_presentacion_id;
	public $cantidad;
	public $fecha_vence;
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
		if (elemento($args, "inventario_ajuste_id")) {
			$this->db->where("a.inventario_ajuste_id", $args["inventario_ajuste_id"]);
		}

		$tmp = $this->db
		->select("a.*, b.codigo, b.codigo_barra, b.nombre as nombre_producto,
			b.control_vence, c.codigo as codigo_um, c.nombre as nombre_um")
		->join("inventario_ajuste d", "d.id = a.inventario_ajuste_id")
		->join("producto b", "b.id = a.producto_id AND b.empresa_id = d.empresa_id")
		->join("unidad_medida c", "c.id = a.unidad_medida_id")
		->where("d.empresa_id", $this->usr["empresa_id"])
		->where("a.activo", 1)
		->order_by("a.id", "asc")
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}

	public function reemplazar($ajusteId, $lineas)
	{
		$this->db->where("inventario_ajuste_id", $ajusteId)->delete($this->_tabla);

		if ($this->db->trans_status() === FALSE) {
			return false;
		}

		foreach ($lineas as $linea) {
			$datos = [
				"inventario_ajuste_id"   => $ajusteId,
				"producto_id"            => $linea->producto_id,
				"unidad_medida_id"       => $linea->unidad_medida_id,
				"producto_presentacion_id" => !empty($linea->producto_presentacion_id) ? $linea->producto_presentacion_id : null,
				"cantidad"               => round((float) $linea->cantidad, 2),
				"fecha_vence"            => !empty($linea->fecha_vence) ? $linea->fecha_vence : null,
				"observacion"            => isset($linea->observacion) ? trim($linea->observacion) : null,
				"activo"                 => 1
			];

			if (!$this->db->insert($this->_tabla, $datos)) {
				return false;
			}
		}

		return $this->db->trans_status() !== FALSE;
	}
}

/* End of file Inventario_ajuste_detalle_model.php */
/* Location: ./application/models/inventario/Inventario_ajuste_detalle_model.php */
