<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Inventario_enc_model extends General_model {

	public $numero;
	public $inventario_tipo_id;
	public $inventario_estado_id;
	public $fecha_corte;
	public $observacion;
	public $archivo_nombre;
	public $archivo_hash;
	public $fecha_procesado;
	public $fecha_anulado;
	public $empresa_id;
	public $sucursal_id;
	public $usuario_id;
	public $usuario_proceso_id;
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

		if (elemento($args, "tipo_codigo")) {
			$this->db->where("b.codigo", $args["tipo_codigo"]);
		}

		if (elemento($args, "termino")) {
			$termino = trim($args["termino"]);
			$this->db
			->group_start()
			->like("a.numero", $termino)
			->or_like("a.archivo_nombre", $termino)
			->or_like("a.observacion", $termino)
			->group_end();
		}

		if (elemento($args, "inventario_estado_id")) {
			$this->db->where("a.inventario_estado_id", $args["inventario_estado_id"]);
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
		->select("a.*, b.codigo as codigo_tipo, b.nombre as nombre_tipo,
			c.codigo as codigo_estado, c.nombre as nombre_estado,
			d.nombre as nombre_sucursal, e.nombre as nombre_usuario,
			f.nombre as nombre_usuario_proceso, g.nombre as nombre_usuario_anulo,
			(SELECT COUNT(*) FROM inventario_det x
			 WHERE x.inventario_enc_id = a.id AND x.activo = 1) as lineas,
			(SELECT COALESCE(SUM(x.cantidad_fisica), 0) FROM inventario_det x
			 WHERE x.inventario_enc_id = a.id AND x.activo = 1) as cantidad_total", false)
		->join("inventario_tipo b", "b.id = a.inventario_tipo_id AND b.empresa_id = a.empresa_id")
		->join("inventario_estado c", "c.id = a.inventario_estado_id AND c.empresa_id = a.empresa_id")
		->join("sucursal d", "d.id = a.sucursal_id AND d.empresa_id = a.empresa_id")
		->join("usuario e", "e.id = a.usuario_id")
		->join("usuario f", "f.id = a.usuario_proceso_id", "left")
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
			"SELECT a.*, b.codigo AS codigo_tipo, c.codigo AS codigo_estado
			 FROM inventario_enc a
			 INNER JOIN inventario_tipo b
			   ON b.id = a.inventario_tipo_id AND b.empresa_id = a.empresa_id
			 INNER JOIN inventario_estado c
			   ON c.id = a.inventario_estado_id AND c.empresa_id = a.empresa_id
			 WHERE a.id = ? AND a.empresa_id = ?
			 FOR UPDATE",
			[$id, $this->usr["empresa_id"]]
		)->row();
	}

	public function generarNumero()
	{
		return "INV-" . date("Ymd") . "-" . strtoupper(bin2hex(random_bytes(3)));
	}

	public function archivoExiste($hash, $sucursalId)
	{
		if (empty($hash) || empty($sucursalId)) {
			return false;
		}

		return $this->db
		->where("empresa_id", $this->usr["empresa_id"])
		->where("sucursal_id", (int) $sucursalId)
		->where("archivo_hash", $hash)
		->where("inventario_estado_id IN (
			SELECT id FROM inventario_estado
			WHERE empresa_id = " . $this->db->escape($this->usr["empresa_id"]) . "
			AND codigo IN ('VALIDADO', 'PROCESADO')
		)", null, false)
		->get($this->_tabla)
		->num_rows() > 0;
	}

	public function cambiarEstado($id, $estadoId, $campos=[])
	{
		$datos = array_merge(["inventario_estado_id" => $estadoId], $campos);

		$this->db
		->where("id", $id)
		->where("empresa_id", $this->usr["empresa_id"])
		->update($this->_tabla, $datos);

		return $this->db->affected_rows() > 0;
	}
}

/* End of file Inventario_enc_model.php */
/* Location: ./application/models/inventario/Inventario_enc_model.php */
