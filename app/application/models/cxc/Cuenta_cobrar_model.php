<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cuenta_cobrar_model extends General_model {

	public $cliente_id;
	public $factura_fecha;
	public $factura_numero;
	public $factura_documento;
	public $credito_dias = 0;
	public $fecha_vence;
	public $total = 0;
	public $abono = 0;
	public $saldo = 0;
	public $moneda_id;
	public $empresa_id;
	public $usuario_id;
	public $venta_id = null;
	public $referencia = null;
	public $anulado = 0;
	public $anulado_fecha = null;
	public $anulado_motivo = null;
	public $anulado_usuario = null;
	public $origen = 1;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) $this->cargar($id);
	}

	public function _buscar($args=[])
	{
		if (elemento($args, "id")) $this->db->where("a.id", $args["id"]);
		if (elemento($args, "cliente_id")) $this->db->where("a.cliente_id", $args["cliente_id"]);
		if (elemento($args, "moneda_id")) $this->db->where("a.moneda_id", $args["moneda_id"]);

		if (elemento($args, "termino")) {
			$termino = trim($args["termino"]);
			$this->db->group_start()
				->like("a.factura_numero", $termino)
				->or_like("a.factura_documento", $termino)
				->or_like("a.referencia", $termino)
				->or_like("b.nombre", $termino)
				->or_like("b.identificacion", $termino)
			->group_end();
		}

		if (elemento($args, "estado")) {
			switch ($args["estado"]) {
				case "pendiente":
					$this->db->where("a.anulado", 0)->where("a.abono", 0)->where("a.saldo >", 0);
					break;
				case "parcial":
					$this->db->where("a.anulado", 0)->where("a.abono >", 0)->where("a.saldo >", 0);
					break;
				case "pagada":
					$this->db->where("a.anulado", 0)->where("a.saldo", 0);
					break;
				case "vencida":
					$this->db->where("a.anulado", 0)->where("a.saldo >", 0)->where("a.fecha_vence <", date("Y-m-d"));
					break;
				case "anulada":
					$this->db->where("a.anulado", 1);
					break;
			}
		}

		$tmp = $this->db
		->select("a.*, b.nombre as nombre_cliente, b.identificacion,
			c.nombre as nombre_moneda, c.codigo as codigo_moneda, c.simbolo as simbolo_moneda,
			d.nombre as nombre_usuario,
			CASE
				WHEN a.anulado = 1 THEN 'ANULADA'
				WHEN a.saldo <= 0 THEN 'PAGADA'
				WHEN a.fecha_vence < CURDATE() THEN 'VENCIDA'
				WHEN a.abono > 0 THEN 'PARCIAL'
				ELSE 'PENDIENTE'
			END as estado", false)
		->join("cliente b", "b.id = a.cliente_id AND b.empresa_id = a.empresa_id")
		->join("moneda c", "c.id = a.moneda_id AND c.empresa_id = a.empresa_id")
		->join("usuario d", "d.id = a.usuario_id")
		->where("a.empresa_id", $this->usr["empresa_id"])
		->order_by("a.fecha_vence", "asc")
		->order_by("a.id", "desc")
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}

	public function bloquear($id)
	{
		return $this->db->query(
			"SELECT * FROM cuenta_cobrar WHERE id = ? AND empresa_id = ? FOR UPDATE",
			[$id, $this->usr["empresa_id"]]
		)->row();
	}

	public function clienteActivo($id)
	{
		return $this->db->where("id", $id)->where("empresa_id", $this->usr["empresa_id"])
			->where("activo", 1)->get("cliente")->row();
	}

	public function monedaActiva($id)
	{
		return $this->db->where("id", $id)->where("empresa_id", $this->usr["empresa_id"])
			->where("activo", 1)->get("moneda")->row();
	}

	public function formaPagoActiva($id)
	{
		return $this->db->where("id", $id)->where("empresa_id", $this->usr["empresa_id"])
			->where("activo", 1)->get("forma_pago")->row();
	}

	public function getCatalogos()
	{
		$empresaId = $this->usr["empresa_id"];
		return [
			"clientes" => $this->db->select("id, nombre, identificacion, credito, credito_dias, credito_limite")
				->where("empresa_id", $empresaId)->where("activo", 1)->order_by("nombre", "asc")->get("cliente")->result(),
			"monedas" => $this->db->select("id, nombre, codigo, simbolo")
				->where("empresa_id", $empresaId)->where("activo", 1)->order_by("nombre", "asc")->get("moneda")->result(),
			"formas_pago" => $this->db->select("id, nombre")
				->where("empresa_id", $empresaId)->where("activo", 1)->order_by("nombre", "asc")->get("forma_pago")->result()
		];
	}

	public function aplicarAbono($id, $monto)
	{
		$monto = round((float) $monto, 5);
		$this->db->set("abono", "abono + " . $this->db->escape($monto), false)
			->set("saldo", "saldo - " . $this->db->escape($monto), false)
			->where("id", $id)->where("empresa_id", $this->usr["empresa_id"])
			->where("anulado", 0)->where("saldo >=", $monto)->update($this->_tabla);
		return $this->db->affected_rows() === 1;
	}
}

/* End of file Cuenta_cobrar_model.php */
