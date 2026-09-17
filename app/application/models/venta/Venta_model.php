<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Venta_model extends General_model {

	public $empresa_id;
	public $sucursal_id;
	public $usuario_id;
	public $moneda_id;
	public $cliente_id;
	public $forma_pago_id;
	public $venta_estado_id;
	public $venta_serie_id;
	public $vendedor_id;
	public $total_precio;
	public $total_costo;
	public $ganancia;
	public $base;
	public $iva;
	public $isr;
	public $descuento;
	public $tipo_cambio;
	public $correlativo;
	public $referencia;
	public $factura_fecha;
	public $factura_numero;
	public $factura_serie;
	public $factura_uuid;
	public $certificada;
	public $certificada_fecha;
	public $certificada_usuario;
	public $anulado;
	public $anulado_fecha;
	public $anulado_usuario;
	public $anulado_motivo;

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
			->like("a.correlativo", $termino)
			->or_like("a.factura_numero", $termino)
			->or_like("a.referencia", $termino)
			->group_end();
		}

		if (elemento($args, "venta_estado_id")) {
			$this->db->where("a.venta_estado_id", $args["venta_estado_id"]);
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
			b.nombre as nombre_estado,
			b.etiqueta as etiqueta_estado,
			c.nombre as nombre_serie,
			c.codigo as codigo_serie,
			d.nombre as nombre_moneda,
			d.simbolo as simbolo_moneda,
			e.nombre as nombre_forma_pago,
			f.nombre as nombre_usuario,
			g.nombre as nombre_sucursal,
			h.nombre as nombre_vendedor,
			i.nombre as nombre_cliente,
			i.identificacion as identificacion_cliente")
		->join("venta_estado b", "b.id = a.venta_estado_id")
		->join("venta_serie c", "c.id = a.venta_serie_id AND c.empresa_id = a.empresa_id")
		->join("moneda d", "d.id = a.moneda_id")
		->join("forma_pago e", "e.id = a.forma_pago_id")
		->join("usuario f", "f.id = a.usuario_id")
		->join("sucursal g", "g.id = a.sucursal_id AND g.empresa_id = a.empresa_id")
		->join("usuario h", "h.id = a.vendedor_id", "left")
		->join("cliente i", "i.id = a.cliente_id AND i.empresa_id = a.empresa_id", "left")
		->where("a.empresa_id", $this->usr["empresa_id"])
		->order_by("a.fecha", "desc")
		->order_by("a.id", "desc")
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}

	public function getCatalogos()
	{
		$empresaId = $this->usr["empresa_id"];

		$series = $this->db
		->select("id, nombre, codigo, inicio, fin, correlativo, electronico")
		->where("empresa_id", $empresaId)
		->where("activo", 1)
		->order_by("nombre", "asc")
		->get("venta_serie")
		->result();

		$estados = $this->db
		->select("id, nombre, orden, etiqueta")
		->where("activo", 1)
		->order_by("orden", "asc")
		->get("venta_estado")
		->result();

		$sucursales = $this->db
		->select("id, nombre")
		->where("empresa_id", $empresaId)
		->where("activo", 1)
		->order_by("nombre", "asc")
		->get("sucursal")
		->result();

		$formasPago = $this->db
		->select("id, nombre")
		->where("empresa_id", $empresaId)
		->where("activo", 1)
		->order_by("nombre", "asc")
		->get("forma_pago")
		->result();

		$monedas = $this->db
		->select("id, codigo, simbolo, nombre")
		->where("empresa_id", $empresaId)
		->where("activo", 1)
		->order_by("nombre", "asc")
		->get("moneda")
		->result();

		$categorias = $this->db
		->select("id, nombre, etiqueta")
		->where("empresa_id", $empresaId)
		->where("activo", 1)
		->order_by("nombre", "asc")
		->get("categoria")
		->result();

		$clientes = $this->db
		->select("id, nombre, razon_social, identificacion, codigo, credito, credito_limite, credito_dias")
		->where("empresa_id", $empresaId)
		->where("activo", 1)
		->order_by("nombre", "asc")
		->get("cliente")
		->result();

		return [
			"series"        => $series,
			"estados"       => $estados,
			"sucursales"    => $sucursales,
			"formas_pago"   => $formasPago,
			"monedas"       => $monedas,
			"categorias"    => $categorias,
			"clientes"      => $clientes
		];
	}

	public function productosDisponibles($sucursalId, $args=[])
	{
		$empresaId = $this->usr["empresa_id"];

		if (elemento($args, "termino")) {
			$termino = trim($args["termino"]);
			$this->db
			->group_start()
			->like("a.codigo", $termino)
			->or_like("a.codigo_barra", $termino)
			->or_like("a.nombre", $termino)
			->group_end();
		}

		if (elemento($args, "categoria_id")) {
			$this->db->where("a.categoria_id", $args["categoria_id"]);
		}

		$tmp = $this->db
		->select("a.id, a.codigo, a.codigo_barra, a.nombre, a.descripcion,
			a.tipo_producto, a.precio, a.costo, a.foto, a.unidad_medida_id,
			b.codigo as codigo_unidad, b.nombre as nombre_unidad,
			c.id as categoria_id, c.nombre as nombre_categoria, c.etiqueta as etiqueta_categoria,
			d.nombre as nombre_marca,
			CASE WHEN a.tipo_producto = 'S' THEN NULL ELSE COALESCE(SUM(e.cantidad), 0) END as existencia", false)
		->join("unidad_medida b", "b.id = a.unidad_medida_id")
		->join("categoria c", "c.id = a.categoria_id")
		->join("marca d", "d.id = a.marca_id")
		->join("stock e", "e.producto_id = a.id AND e.unidad_medida_id = a.unidad_medida_id AND e.sucursal_id = " . $this->db->escape($sucursalId) . " AND e.activo = 1", "left")
		->where("a.empresa_id", $empresaId)
		->where("a.activo", 1)
		->group_by(["a.id", "a.codigo", "a.codigo_barra", "a.nombre", "a.descripcion",
			"a.tipo_producto", "a.precio", "a.costo", "a.foto", "a.unidad_medida_id",
			"b.codigo", "b.nombre", "c.id", "c.nombre", "c.etiqueta", "d.nombre"])
		->having("(a.tipo_producto = 'S' OR COALESCE(SUM(e.cantidad), 0) > 0)", null, false)
		->order_by("a.nombre", "asc")
		->get("producto a");

		return $tmp->result();
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

	public function catalogoEmpresa($tabla, $id)
	{
		return $this->db
		->where("id", $id)
		->where("empresa_id", $this->usr["empresa_id"])
		->where("activo", 1)
		->get($tabla)
		->row();
	}

	public function estadoId($nombre)
	{
		$row = $this->db
		->select("id")
		->where("LOWER(nombre) =", strtolower($nombre))
		->where("activo", 1)
		->get("venta_estado")
		->row();

		return $row ? $row->id : null;
	}

	public function productoActivo($id)
	{
		return $this->db
		->select("id, codigo, nombre, tipo_producto, unidad_medida_id, precio, costo")
		->where("id", $id)
		->where("empresa_id", $this->usr["empresa_id"])
		->where("activo", 1)
		->get("producto")
		->row();
	}

	public function bloquear($id)
	{
		return $this->db->query(
			"SELECT a.*, b.nombre AS nombre_estado
			 FROM venta a
			 INNER JOIN venta_estado b ON b.id = a.venta_estado_id
			 WHERE a.id = ? AND a.empresa_id = ?
			 FOR UPDATE",
			[$id, $this->usr["empresa_id"]]
		)->row();
	}

	public function bloquearSerie($id)
	{
		return $this->db->query(
			"SELECT * FROM venta_serie
			 WHERE id = ? AND empresa_id = ? AND activo = 1
			 FOR UPDATE",
			[$id, $this->usr["empresa_id"]]
		)->row();
	}

	public function actualizar($id, $datos)
	{
		$this->db
		->where("id", $id)
		->where("empresa_id", $this->usr["empresa_id"])
		->update($this->_tabla, $datos);

		return $this->db->affected_rows() > 0 || $this->db->trans_status() !== FALSE;
	}
}

/* End of file Venta_model.php */
