<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cuenta_pagar_model extends General_model {

	public $proveedor_id;
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
	public $compra_id = null;
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
		if (elemento($args, "proveedor_id")) $this->db->where("a.proveedor_id", $args["proveedor_id"]);
		if (elemento($args, "moneda_id")) $this->db->where("a.moneda_id", $args["moneda_id"]);
		if (elemento($args, "compra_id")) $this->db->where("a.compra_id", $args["compra_id"]);

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
				case "pendiente": $this->db->where("a.anulado", 0)->where("a.abono", 0)->where("a.saldo >", 0); break;
				case "parcial": $this->db->where("a.anulado", 0)->where("a.abono >", 0)->where("a.saldo >", 0); break;
				case "pagada": $this->db->where("a.anulado", 0)->where("a.saldo", 0); break;
				case "vencida": $this->db->where("a.anulado", 0)->where("a.saldo >", 0)->where("a.fecha_vence <", date("Y-m-d")); break;
				case "anulada": $this->db->where("a.anulado", 1); break;
			}
		}

		$tmp = $this->db
		->select("a.*, b.nombre as nombre_proveedor, b.identificacion,
			c.nombre as nombre_moneda, c.codigo as codigo_moneda, c.simbolo as simbolo_moneda,
			d.nombre as nombre_usuario, e.numero as numero_compra,
			CASE
				WHEN a.anulado = 1 THEN 'ANULADA'
				WHEN a.saldo <= 0 THEN 'PAGADA'
				WHEN a.fecha_vence < CURDATE() THEN 'VENCIDA'
				WHEN a.abono > 0 THEN 'PARCIAL'
				ELSE 'PENDIENTE'
			END as estado", false)
		->join("proveedor b", "b.id = a.proveedor_id AND b.empresa_id = a.empresa_id")
		->join("moneda c", "c.id = a.moneda_id AND c.empresa_id = a.empresa_id")
		->join("usuario d", "d.id = a.usuario_id")
		->join("compra e", "e.id = a.compra_id", "left")
		->where("a.empresa_id", $this->usr["empresa_id"])
		->order_by("a.fecha_vence", "asc")->order_by("a.id", "desc")
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}

	public function bloquear($id)
	{
		return $this->db->query(
			"SELECT * FROM cuenta_pagar WHERE id = ? AND empresa_id = ? FOR UPDATE",
			[$id, $this->usr["empresa_id"]]
		)->row();
	}

	public function proveedorActivo($id)
	{
		return $this->db->where("id", $id)->where("empresa_id", $this->usr["empresa_id"])
			->where("activo", 1)->get("proveedor")->row();
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
			"proveedores" => $this->db->select("id, nombre, identificacion, credito, credito_dias, credito_limite")
				->where("empresa_id", $empresaId)->where("activo", 1)->order_by("nombre", "asc")->get("proveedor")->result(),
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

	public function sincronizarCompra($compra, $proveedor, $esCredito)
	{
		$actual = $this->db->query(
			"SELECT * FROM cuenta_pagar WHERE compra_id = ? AND empresa_id = ? FOR UPDATE",
			[$compra->getPK(), $this->usr["empresa_id"]]
		)->row();

		if (!$esCredito) {
			if (!$actual || (int) $actual->anulado === 1) return true;
			if ((float) $actual->abono > 0) {
				$this->setMensaje("No puede cambiar la orden a contado porque la cuenta por pagar ya tiene pagos.");
				return false;
			}
			$this->db->where("id", $actual->id)->update($this->_tabla, [
				"anulado" => 1, "anulado_fecha" => date("Y-m-d H:i:s"),
				"anulado_motivo" => "Orden cambiada a una forma de pago distinta de Crédito",
				"anulado_usuario" => $this->usr["id"]
			]);
			return $this->db->trans_status() !== FALSE;
		}

		$total = round((float) $compra->total_costo, 5);
		if ($total <= 0) { $this->setMensaje("El total de la cuenta por pagar debe ser mayor que cero."); return false; }
		$fecha = !empty($compra->factura_fecha) ? substr($compra->factura_fecha, 0, 10) : date("Y-m-d");
		$dias = max(0, (int) $proveedor->credito_dias);
		$facturaNumero = !empty(trim((string) $compra->factura_numero)) ? trim($compra->factura_numero) : $compra->numero;
		if ($actual && (float) $actual->abono > 0 && (
			round((float) $actual->total, 5) !== $total ||
			(int) $actual->proveedor_id !== (int) $proveedor->id ||
			(int) $actual->moneda_id !== (int) $compra->moneda_id ||
			$actual->factura_fecha !== $fecha ||
			$actual->factura_numero !== $facturaNumero
		)) {
			$this->setMensaje("No puede cambiar los datos contables de una orden cuya cuenta por pagar ya tiene pagos.");
			return false;
		}
		$datos = (object) [
			"proveedor_id" => (int) $proveedor->id,
			"factura_fecha" => $fecha,
			"factura_numero" => $facturaNumero,
			"factura_documento" => null,
			"credito_dias" => $dias,
			"fecha_vence" => date("Y-m-d", strtotime($fecha . " +" . $dias . " days")),
			"total" => $total, "abono" => $actual ? (float) $actual->abono : 0,
			"saldo" => $total - ($actual ? (float) $actual->abono : 0),
			"moneda_id" => (int) $compra->moneda_id,
			"compra_id" => (int) $compra->getPK(),
			"referencia" => "Orden de compra " . $compra->numero,
			"origen" => 2, "anulado" => 0, "anulado_fecha" => null,
			"anulado_motivo" => null, "anulado_usuario" => null
		];

		$cuenta = new Cuenta_pagar_model($actual ? $actual->id : "");
		$guardado = $cuenta->guardar($datos);
		if (!$guardado && $actual && $this->db->trans_status() !== FALSE) return true;
		if (!$guardado) $this->setMensaje($cuenta->getMensaje());
		return $guardado;
	}

	public function anularPorCompra($compraId)
	{
		$cuenta = $this->db->query(
			"SELECT * FROM cuenta_pagar WHERE compra_id = ? AND empresa_id = ? AND anulado = 0 FOR UPDATE",
			[$compraId, $this->usr["empresa_id"]]
		)->row();
		if (!$cuenta) return true;
		if ((float) $cuenta->abono > 0) {
			$this->setMensaje("No puede anular la orden porque su cuenta por pagar ya tiene pagos.");
			return false;
		}
		$this->db->where("id", $cuenta->id)->update($this->_tabla, [
			"anulado" => 1, "anulado_fecha" => date("Y-m-d H:i:s"),
			"anulado_motivo" => "Orden de compra anulada", "anulado_usuario" => $this->usr["id"]
		]);
		return $this->db->trans_status() !== FALSE;
	}
}

/* End of file Cuenta_pagar_model.php */
