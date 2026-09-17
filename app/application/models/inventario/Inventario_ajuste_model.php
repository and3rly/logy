<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Inventario_ajuste_model extends General_model {

	public $numero;
	public $inventario_ajuste_tipo_id;
	public $inventario_ajuste_estado_id;
	public $motivo;
	public $observacion;
	public $fecha_aplicado;
	public $fecha_anulado;
	public $empresa_id;
	public $sucursal_id;
	public $usuario_id;
	public $usuario_aplico_id;
	public $usuario_anulo_id;

	public function __construct($id="")
	{
		parent::__construct();

		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	public function _buscar($args=[])
	{
		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		}

		if (elemento($args, "termino")) {
			$termino = trim($args["termino"]);
			$this->db
			->group_start()
			->like("a.numero", $termino)
			->or_like("a.motivo", $termino)
			->or_like("a.observacion", $termino)
			->or_like("b.nombre", $termino)
			->group_end();
		}

		if (elemento($args, "inventario_ajuste_estado_id")) {
			$this->db->where("a.inventario_ajuste_estado_id", $args["inventario_ajuste_estado_id"]);
		}

		if (elemento($args, "inventario_ajuste_tipo_id")) {
			$this->db->where("a.inventario_ajuste_tipo_id", $args["inventario_ajuste_tipo_id"]);
		}

		if (elemento($args, "naturaleza")) {
			$this->db->where("b.naturaleza", $args["naturaleza"]);
		}

		if (elemento($args, "sucursal_id")) {
			$this->db->where("a.sucursal_id", $args["sucursal_id"]);
		}

		if (elemento($args, "fecha_desde")) {
			$this->db->where("DATE(a.fecha) >=", $args["fecha_desde"]);
		}

		if (elemento($args, "fecha_hasta")) {
			$this->db->where("DATE(a.fecha) <=", $args["fecha_hasta"]);
		}

		$tmp = $this->db
		->select("a.*,
			b.codigo as codigo_tipo,
			b.nombre as nombre_tipo,
			b.naturaleza,
			b.requiere_observacion,
			c.codigo as codigo_estado,
			c.nombre as nombre_estado,
			d.nombre as nombre_sucursal,
			e.nombre as nombre_usuario,
			f.nombre as nombre_usuario_aplico,
			g.nombre as nombre_usuario_anulo,
			(SELECT COUNT(*) FROM inventario_ajuste_detalle ad
			 WHERE ad.inventario_ajuste_id = a.id AND ad.activo = 1) as lineas,
			(SELECT COALESCE(SUM(ad.cantidad), 0) FROM inventario_ajuste_detalle ad
			 WHERE ad.inventario_ajuste_id = a.id AND ad.activo = 1) as cantidad_total", false)
		->join("inventario_ajuste_tipo b", "b.id = a.inventario_ajuste_tipo_id AND b.empresa_id = a.empresa_id")
		->join("inventario_ajuste_estado c", "c.id = a.inventario_ajuste_estado_id AND c.empresa_id = a.empresa_id")
		->join("sucursal d", "d.id = a.sucursal_id AND d.empresa_id = a.empresa_id")
		->join("usuario e", "e.id = a.usuario_id")
		->join("usuario f", "f.id = a.usuario_aplico_id", "left")
		->join("usuario g", "g.id = a.usuario_anulo_id", "left")
		->where("a.empresa_id", $this->usr["empresa_id"])
		->order_by("a.fecha", "desc")
		->order_by("a.id", "desc")
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}

	public function bloquear($id)
	{
		return $this->db->query(
			"SELECT a.*, b.naturaleza, b.requiere_observacion, c.codigo AS codigo_estado
			 FROM inventario_ajuste a
			 INNER JOIN inventario_ajuste_tipo b
			   ON b.id = a.inventario_ajuste_tipo_id AND b.empresa_id = a.empresa_id
			 INNER JOIN inventario_ajuste_estado c
			   ON c.id = a.inventario_ajuste_estado_id AND c.empresa_id = a.empresa_id
			 WHERE a.id = ? AND a.empresa_id = ?
			 FOR UPDATE",
			[$id, $this->usr["empresa_id"]]
		)->row();
	}

	public function generarNumero()
	{
		return "AJ-" . date("Ymd") . "-" . strtoupper(bin2hex(random_bytes(3)));
	}

	public function estadoId($codigo)
	{
		$row = $this->db
		->select("id")
		->where("empresa_id", $this->usr["empresa_id"])
		->where("codigo", $codigo)
		->where("activo", 1)
		->get("inventario_ajuste_estado")
		->row();

		return $row ? $row->id : null;
	}

	public function tipoActivo($id)
	{
		return $this->db
		->where("id", $id)
		->where("empresa_id", $this->usr["empresa_id"])
		->where("activo", 1)
		->get("inventario_ajuste_tipo")
		->row();
	}

	public function sucursalActiva($id)
	{
		return $this->db
		->where("id", $id)
		->where("empresa_id", $this->usr["empresa_id"])
		->where("activo", 1)
		->get("sucursal")
		->row();
	}

	public function productoActivo($id)
	{
		return $this->db
		->select("id, unidad_medida_id, control_vence")
		->where("id", $id)
		->where("empresa_id", $this->usr["empresa_id"])
		->where("tipo_producto", "B")
		->where("activo", 1)
		->get("producto")
		->row();
	}

	public function presentacionValida($id, $productoId)
	{
		if (empty($id)) {
			return true;
		}

		return $this->db
		->where("id", $id)
		->where("producto_id", $productoId)
		->where("activo", 1)
		->get("producto_presentacion")
		->num_rows() > 0;
	}

	public function getCatalogos()
	{
		$empresaId = $this->usr["empresa_id"];

		$tipos = $this->db
		->select("id, codigo, nombre, naturaleza, requiere_observacion")
		->where("empresa_id", $empresaId)
		->where("activo", 1)
		->order_by("naturaleza", "asc")
		->order_by("nombre", "asc")
		->get("inventario_ajuste_tipo")
		->result();

		$estados = $this->db
		->select("id, codigo, nombre")
		->where("empresa_id", $empresaId)
		->where("activo", 1)
		->order_by("orden", "asc")
		->get("inventario_ajuste_estado")
		->result();

		$sucursales = $this->db
		->select("id, nombre")
		->where("empresa_id", $empresaId)
		->where("activo", 1)
		->order_by("nombre", "asc")
		->get("sucursal")
		->result();

		$productos = $this->db
		->select("a.id, a.codigo, a.codigo_barra, a.nombre, a.unidad_medida_id,
			a.control_vence, b.nombre as nombre_um, c.nombre as nombre_categoria,
			d.nombre as nombre_marca")
		->join("unidad_medida b", "b.id = a.unidad_medida_id")
		->join("categoria c", "c.id = a.categoria_id")
		->join("marca d", "d.id = a.marca_id")
		->where("a.empresa_id", $empresaId)
		->where("a.tipo_producto", "B")
		->where("a.activo", 1)
		->order_by("a.nombre", "asc")
		->get("producto a")
		->result();

		return [
			"tipos"      => $tipos,
			"estados"    => $estados,
			"sucursales" => $sucursales,
			"productos"  => $productos
		];
	}

	public function cambiarEstado($id, $estadoId, $campos=[])
	{
		$datos = array_merge(["inventario_ajuste_estado_id" => $estadoId], $campos);

		$this->db
		->where("id", $id)
		->where("empresa_id", $this->usr["empresa_id"])
		->update($this->_tabla, $datos);

		return $this->db->affected_rows() > 0;
	}
}

/* End of file Inventario_ajuste_model.php */
/* Location: ./application/models/inventario/Inventario_ajuste_model.php */
