<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Compra_detalle_model extends General_model {

	public $compra_id;
	public $producto_id;
	public $unidad_medida_id;
	public $producto_presentacion_id;
	public $fecha_vence;
	public $cantidad;
	public $precio_costo;
	public $total_costo;
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
		if (elemento($args, "compra_id")) {
			$this->db->where("a.compra_id", $args["compra_id"]);
		}

		$tmp = $this->db
		->select("a.*,
			b.codigo,
			b.nombre as nombre_producto,
			b.control_vence,
			c.nombre as nombre_um")
		->join("producto b", "b.id = a.producto_id")
		->join("unidad_medida c", "c.id = a.unidad_medida_id")
		->where("a.anulado", 0)
		->order_by("a.id", "asc")
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}
}

/* End of file Compra_detalle_model.php */
/* Location: ./application/models/compra/Compra_detalle_model.php */
