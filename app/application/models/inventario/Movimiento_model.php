<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Movimiento_model extends General_model {

	public $stock_id;
	public $movimiento_tipo_id;
	public $cantidad;
	public $compra_id;
	public $inventario_ajuste_id;
	public $inventario_det_id;
	public $venta_detalle_id;
	public $usuario_id;
	public $observacion;

	public function __construct($id="")
	{
		parent::__construct();

		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	private function aplicarFiltros($args=[], $saldoAnterior=false)
	{
		if (elemento($args, "termino")) {
			$termino = trim($args["termino"]);

			$this->db
			->group_start()
			->like("c.codigo", $termino)
			->or_like("c.codigo_barra", $termino)
			->or_like("c.nombre", $termino)
			->group_end();
		}

		if (elemento($args, "sucursal_id")) {
			$this->db->where("b.sucursal_id", $args["sucursal_id"]);
		}

		if (elemento($args, "fecha_hasta")) {
			$this->db->where("DATE(a.fecha) <=", $args["fecha_hasta"]);
		}

		if ($saldoAnterior && elemento($args, "fecha_desde")) {
			$this->db->where("DATE(a.fecha) <", $args["fecha_desde"]);
		} else if (!$saldoAnterior && elemento($args, "fecha_desde")) {
			$this->db->where("DATE(a.fecha) >=", $args["fecha_desde"]);
		}

		if (elemento($this->usr, "empresa_id")) {
			$this->db
			->where("c.empresa_id", $this->usr["empresa_id"])
			->where("f.empresa_id", $this->usr["empresa_id"])
			->where("d.empresa_id", $this->usr["empresa_id"]);
		}
	}

	public function _buscar($args=[])
	{
		$this->aplicarFiltros($args);

		$tmp = $this->db
		->select("
			a.id,
			a.fecha,
			a.cantidad,
			a.observacion,
			a.compra_id,
			a.inventario_ajuste_id,
			a.inventario_det_id,
			a.venta_detalle_id,
			a.movimiento_tipo_id,
			b.producto_id,
			b.unidad_medida_id,
			b.sucursal_id,
			b.fecha_vence,
			c.codigo as codigo_producto,
			c.nombre as nombre_producto,
			d.codigo as codigo_tipo,
			d.nombre as nombre_tipo,
			e.codigo as codigo_unidad,
			e.nombre as nombre_unidad,
			f.nombre as nombre_sucursal,
			g.numero as numero_compra,
			i.numero as numero_ajuste,
			k.numero as numero_inventario,
			h.nombre as nombre_usuario
		")
		->join("stock b", "b.id = a.stock_id")
		->join("producto c", "c.id = b.producto_id")
		->join("movimiento_tipo d", "d.id = a.movimiento_tipo_id")
		->join("unidad_medida e", "e.id = b.unidad_medida_id")
		->join("sucursal f", "f.id = b.sucursal_id")
		->join("compra g", "g.id = a.compra_id", "left")
		->join("usuario h", "h.id = a.usuario_id")
		->join("inventario_ajuste i", "i.id = a.inventario_ajuste_id AND i.empresa_id = c.empresa_id", "left")
		->join("inventario_det j", "j.id = a.inventario_det_id", "left")
		->join("inventario_enc k", "k.id = j.inventario_enc_id AND k.empresa_id = c.empresa_id", "left")
		->order_by("c.nombre", "asc")
		->order_by("f.nombre", "asc")
		->order_by("a.fecha", "asc")
		->order_by("a.id", "asc")
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}

	public function saldosAnteriores($args=[])
	{
		if (!elemento($args, "fecha_desde")) {
			return [];
		}

		$this->aplicarFiltros($args, true);

		return $this->db
		->select("
			b.producto_id,
			b.unidad_medida_id,
			b.sucursal_id,
			SUM(a.cantidad) as saldo
		", false)
		->join("stock b", "b.id = a.stock_id")
		->join("producto c", "c.id = b.producto_id")
		->join("movimiento_tipo d", "d.id = a.movimiento_tipo_id")
		->join("sucursal f", "f.id = b.sucursal_id")
		->group_by(["b.producto_id", "b.unidad_medida_id", "b.sucursal_id"])
		->get("$this->_tabla a")
		->result();
	}

	public function getCatalogos()
	{
		$empresaId = elemento($this->usr, "empresa_id");

		$sucursales = $this->db
		->select("id, nombre")
		->where("empresa_id", $empresaId)
		->where("activo", 1)
		->order_by("nombre", "asc")
		->get("sucursal")
		->result();

		$tipos = $this->db
		->select("id, codigo, nombre")
		->where("empresa_id", $empresaId)
		->where("activo", 1)
		->order_by("nombre", "asc")
		->get("movimiento_tipo")
		->result();

		return [
			"sucursales" => $sucursales,
			"tipos"      => $tipos
		];
	}

}

/* End of file Movimiento_model.php */
/* Location: ./application/models/inventario/Movimiento_model.php */
