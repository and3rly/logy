<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cuenta_pagar_pago_model extends General_model {
	public $cuenta_pagar_id;
	public $forma_pago_id;
	public $comprobante_numero = null;
	public $documento_fecha = null;
	public $documento_numero = null;
	public $documento_comprobante = null;
	public $total = 0;
	public $usuario_id;
	public $anulado = 0;
	public $anulado_fecha = null;
	public $anulado_motivo = null;
	public $anulado_usuario = null;

	public function __construct($id="") { parent::__construct(); if (!empty($id)) $this->cargar($id); }

	public function _buscar($args=[])
	{
		if (elemento($args, "id")) $this->db->where("a.id", $args["id"]);
		if (elemento($args, "cuenta_pagar_id")) $this->db->where("a.cuenta_pagar_id", $args["cuenta_pagar_id"]);
		$tmp = $this->db->select("a.*, b.nombre as nombre_forma_pago,
			c.nombre as nombre_usuario, d.factura_numero, d.proveedor_id, d.moneda_id,
			e.nombre as nombre_proveedor, e.identificacion,
			f.nombre as nombre_moneda, f.codigo as codigo_moneda, f.simbolo as simbolo_moneda", false)
			->join("forma_pago b", "b.id = a.forma_pago_id")
			->join("usuario c", "c.id = a.usuario_id")
			->join("cuenta_pagar d", "d.id = a.cuenta_pagar_id")
			->join("proveedor e", "e.id = d.proveedor_id AND e.empresa_id = d.empresa_id")
			->join("moneda f", "f.id = d.moneda_id AND f.empresa_id = d.empresa_id")
			->where("d.empresa_id", $this->usr["empresa_id"])
			->order_by("a.fecha", "desc")->order_by("a.id", "desc")
			->get("$this->_tabla a");
		return verConsulta($tmp, $args);
	}

	public function asignarComprobante($id, $fecha)
	{
		$numero = "EGR-" . date("Y", strtotime($fecha)) . "-" . str_pad($id, 6, "0", STR_PAD_LEFT);
		$this->db->where("id", $id)->where("comprobante_numero IS NULL", null, false)
			->update($this->_tabla, ["comprobante_numero" => $numero]);
		return $this->db->affected_rows() === 1 ? $numero : null;
	}
}

/* End of file Cuenta_pagar_pago_model.php */
