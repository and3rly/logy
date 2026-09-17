<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Stock_model extends General_model {

	public $producto_id;
	public $unidad_medida_id;
	public $producto_presentacion_id;
	public $fecha_vence;
	public $cantidad;
	public $activo;
	public $usuario_id;
	public $sucursal_id;

	public function __construct($id="")
	{
		parent::__construct();

		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	public function _buscar($args=[])
	{
		if (elemento($args, "termino")) {
			$termino = trim($args["termino"]);

			$this->db
			->group_start()
			->like("b.codigo", $termino)
			->or_like("b.codigo_barra", $termino)
			->or_like("b.nombre", $termino)
			->or_like("d.nombre", $termino)
			->group_end();
		}

		if (elemento($args, "sucursal_id")) {
			$this->db->where("a.sucursal_id", $args["sucursal_id"]);
		}

		if (elemento($args, "categoria_id")) {
			$this->db->where("b.categoria_id", $args["categoria_id"]);
		}

		if (elemento($this->usr, "empresa_id")) {
			$this->db
			->where("b.empresa_id", $this->usr["empresa_id"])
			->where("f.empresa_id", $this->usr["empresa_id"]);
		}

		$this->db
		->where("a.activo", 1)
		->where("b.activo", 1);

		$tmp = $this->db
		->select("
			CONCAT(a.producto_id, '-', a.unidad_medida_id, '-', a.sucursal_id) as clave,
			a.producto_id,
			a.unidad_medida_id,
			a.sucursal_id,
			b.codigo,
			b.codigo_barra,
			b.nombre as nombre_producto,
			b.existencia_minima,
			b.control_vence,
			c.codigo as codigo_unidad,
			c.nombre as nombre_unidad,
			d.nombre as nombre_marca,
			e.nombre as nombre_categoria,
			e.etiqueta as etiqueta_categoria,
			f.nombre as nombre_sucursal,
			SUM(a.cantidad) as cantidad,
			COUNT(a.id) as lotes,
			MIN(CASE
				WHEN a.cantidad > 0 AND a.fecha_vence IS NOT NULL THEN a.fecha_vence
				ELSE NULL
			END) as fecha_vence_proxima
		", false)
		->join("producto b", "b.id = a.producto_id")
		->join("unidad_medida c", "c.id = a.unidad_medida_id")
		->join("marca d", "d.id = b.marca_id")
		->join("categoria e", "e.id = b.categoria_id")
		->join("sucursal f", "f.id = a.sucursal_id")
		->group_by([
			"a.producto_id", "a.unidad_medida_id", "a.sucursal_id",
			"b.codigo", "b.codigo_barra", "b.nombre", "b.existencia_minima",
			"b.control_vence", "c.codigo", "c.nombre", "d.nombre", "e.nombre", "e.etiqueta", "f.nombre"
		])
		->order_by("b.nombre", "asc")
		->order_by("f.nombre", "asc")
		->get("$this->_tabla a");

		$lista = verConsulta($tmp, $args);

		if (!empty($args["estado"]) && is_array($lista)) {
			$lista = array_values(array_filter($lista, function($row) use ($args) {
				$cantidad = (float) $row->cantidad;
				$minima = (float) $row->existencia_minima;

				if ($args["estado"] === "agotado") {
					return $cantidad <= 0;
				}

				if ($args["estado"] === "bajo") {
					return $cantidad > 0 && $cantidad <= $minima;
				}

				if ($args["estado"] === "disponible") {
					return $cantidad > $minima;
				}

				return true;
			}));
		}

		return $lista;
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

		$categorias = $this->db
		->select("id, nombre")
		->where("empresa_id", $empresaId)
		->where("activo", 1)
		->order_by("nombre", "asc")
		->get("categoria")
		->result();

		return [
			"sucursales" => $sucursales,
			"categorias" => $categorias
		];
	}

	public function existenciasParaAjuste()
	{
		return $this->db
		->select("a.producto_id, a.unidad_medida_id, a.sucursal_id,
			SUM(a.cantidad) as cantidad", false)
		->join("producto b", "b.id = a.producto_id")
		->join("sucursal c", "c.id = a.sucursal_id")
		->where("a.activo", 1)
		->where("b.activo", 1)
		->where("b.empresa_id", $this->usr["empresa_id"])
		->where("c.empresa_id", $this->usr["empresa_id"])
		->group_by(["a.producto_id", "a.unidad_medida_id", "a.sucursal_id"])
		->get("$this->_tabla a")
		->result();
	}

	public function lotesDisponibles($productoId, $unidadMedidaId, $sucursalId, $presentacionId=null, $fechaVence=null)
	{
		$this->db
		->select("a.id, a.cantidad, a.producto_id, a.unidad_medida_id,
			a.producto_presentacion_id, a.fecha_vence, a.sucursal_id")
		->join("producto b", "b.id = a.producto_id")
		->join("sucursal c", "c.id = a.sucursal_id")
		->where("a.producto_id", $productoId)
		->where("a.unidad_medida_id", $unidadMedidaId)
		->where("a.sucursal_id", $sucursalId)
		->where("a.activo", 1)
		->where("a.cantidad >", 0)
		->where("b.empresa_id", $this->usr["empresa_id"])
		->where("c.empresa_id", $this->usr["empresa_id"]);

		if (!empty($presentacionId)) {
			$this->db->where("a.producto_presentacion_id", $presentacionId);
		}

		if (!empty($fechaVence)) {
			$this->db->where("a.fecha_vence", $fechaVence);
		}

		$this->db
		->order_by("CASE WHEN a.fecha_vence IS NULL THEN 1 ELSE 0 END", "ASC", false)
		->order_by("a.fecha_vence", "ASC")
		->order_by("a.id", "ASC");

		$sql = $this->db->get_compiled_select("$this->_tabla a");

		return $this->db->query($sql . " FOR UPDATE")->result();
	}

	public function descontar($id, $cantidad)
	{
		$cantidad = round((float) $cantidad, 2);

		$this->db
		->set("cantidad", "cantidad - " . $this->db->escape($cantidad), false)
		->where("id", $id)
		->where("cantidad >=", $cantidad)
		->update($this->_tabla);

		return $this->db->affected_rows() === 1;
	}

	public function bloquearParaAjuste($id)
	{
		return $this->db->query(
			"SELECT a.id, a.cantidad
			 FROM stock a
			 INNER JOIN producto b ON b.id = a.producto_id
			 INNER JOIN sucursal c ON c.id = a.sucursal_id
			 WHERE a.id = ? AND a.activo = 1
			   AND b.empresa_id = ? AND c.empresa_id = ?
			 FOR UPDATE",
			[$id, $this->usr["empresa_id"], $this->usr["empresa_id"]]
		)->row();
	}

	public function incrementar($id, $cantidad)
	{
		$cantidad = round((float) $cantidad, 2);

		$this->db
		->set("cantidad", "cantidad + " . $this->db->escape($cantidad), false)
		->where("id", $id)
		->update($this->_tabla);

		return $this->db->affected_rows() === 1;
	}

	public function bloquearExistenciaInicial($productoId, $unidadMedidaId, $sucursalId)
	{
		return $this->db->query(
			"SELECT a.id, a.cantidad
			 FROM stock a
			 INNER JOIN producto b ON b.id = a.producto_id
			 INNER JOIN sucursal c ON c.id = a.sucursal_id
			 WHERE a.producto_id = ?
			   AND a.unidad_medida_id = ?
			   AND a.sucursal_id = ?
			   AND a.activo = 1
			   AND b.empresa_id = ?
			   AND c.empresa_id = ?
			 FOR UPDATE",
			[
				$productoId, $unidadMedidaId, $sucursalId,
				$this->usr["empresa_id"], $this->usr["empresa_id"]
			]
		)->result();
	}

}

/* End of file Stock_model.php */
/* Location: ./application/models/inventario/Stock_model.php */
