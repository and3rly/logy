<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Compra_model extends General_model {

	public $compra_estado_id;
	public $proveedor_id;
	public $empresa_id;
	public $usuario_id;
	public $forma_pago_id;
	public $moneda_id;
	public $sucursal_id;
	public $numero;
	public $factura_numero;
	public $factura_fecha;
	public $total_costo;
	public $anulado;
	public $fecha_anulado;

	public function __construct($id="")
	{
		parent::__construct();

		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	public function guardar($args=[])
	{
		if ($this->getPK() === null) {
			$parametro = $this->db
			->select("abr_compra")
			->where("empresa_id", $this->usr["empresa_id"])
			->where("activo", 1)
			->get("empresa_parametro")
			->row();

			if (!$parametro || empty($parametro->abr_compra)) {
				$this->setMensaje("Configure la abreviatura para órdenes de compra.");
				return false;
			}

			$correlativo = $this->db
			->where("empresa_id", $this->usr["empresa_id"])
			->count_all_results("compra") + 1;

			$args->numero = strtoupper($parametro->abr_compra) . "-" . str_pad($correlativo, 10, "0", STR_PAD_LEFT);
			$args->compra_estado_id = 1;
			$args->anulado = 0;
		}

		return parent::guardar($args);
	}

	public function anular()
	{
		if ($this->getPK() === null) {
			$this->setMensaje("La orden de compra no existe.");
			return false;
		}

		if ((int) $this->compra_estado_id !== 1 || (int) $this->anulado === 1) {
			$this->setMensaje("Solo puede anular órdenes de compra en estado Creada.");
			return false;
		}

		$datos = (object) [
			"compra_estado_id" => 3,
			"anulado"          => 1,
			"fecha_anulado"    => date("Y-m-d H:i:s")
		];

		if (parent::guardar($datos)) {
			$this->db
			->where("compra_id", $this->getPK())
			->where("anulado", 0)
			->update("compra_detalle", ["anulado" => 1]);

			if ($this->db->trans_status() !== FALSE) {
				return true;
			}

			$this->setMensaje("No fue posible anular el detalle de la orden de compra.");
		}

		return false;
	}

	public function puedeRecibir()
	{
		if ($this->getPK() === null ||
			$this->empresa_id != $this->usr["empresa_id"]) {
			$this->setMensaje("La orden de compra no existe.");
			return false;
		}

		if ($this->compra_estado_id != 1 || $this->anulado == 1) {
			$this->setMensaje("Solo puede recibir órdenes de compra en estado Creada.");
			return false;
		}

		return true;
	}

	public function recibir()
	{
		if (!$this->puedeRecibir()) {
			return false;
		}

		return parent::guardar((object) [
			"compra_estado_id" => 2
		]);
	}

	public function _buscar($args=[])
	{
		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		} else {
			if (elemento($args, "termino")) {
				$termino = trim($args["termino"]);

				$this->db
				->group_start()
				->like("a.numero", $termino)
				->or_like("a.factura_numero", $termino)
				->or_like("b.nombre", $termino)
				->group_end();
			}

			if (elemento($args, "compra_estado_id")) {
				$this->db->where("a.compra_estado_id", $args["compra_estado_id"]);
			}

			if (elemento($args, "fecha_desde")) {
				$this->db->where("DATE(a.fecha) >=", $args["fecha_desde"]);
			}

			if (elemento($args, "fecha_hasta")) {
				$this->db->where("DATE(a.fecha) <=", $args["fecha_hasta"]);
			}
		}

		if (elemento($this->usr, "empresa_id")) {
			$this->db->where("a.empresa_id", $this->usr["empresa_id"]);
		}

		$tmp = $this->db
		->select("
			a.*,
			b.nombre as nombre_proveedor,
			b.identificacion as identificacion_proveedor,
			b.direccion as direccion_proveedor,
			b.telefono as telefono_proveedor,
			b.correo as correo_proveedor,
			c.nombre as nombre_estado,
			c.etiqueta,
			d.nombre as nombre_forma_pago,
			e.nombre as nombre_moneda,
			e.simbolo as simbolo_moneda,
			f.nombre as nombre_sucursal,
			f.direccion as direccion_sucursal,
			f.telefono as telefono_sucursal,
			g.nombre as nombre_usuario
		")
		->join("proveedor b", "b.id = a.proveedor_id")
		->join("compra_estado c", "c.id = a.compra_estado_id")
		->join("forma_pago d", "d.id = a.forma_pago_id")
		->join("moneda e", "e.id = a.moneda_id")
		->join("sucursal f", "f.id = a.sucursal_id")
		->join("usuario g", "g.id = a.usuario_id")
		->order_by("a.fecha", "desc")
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}
}

/* End of file Compra_model.php */
/* Location: ./application/models/compra/Compra_model.php */
