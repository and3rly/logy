<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Empresa_parametro_model extends General_model {

	public $empresa_id;
	public $abr_recepcion    = null;
	public $abr_cotizacion    = null;
	public $abr_producto     = null;
	public $abr_compra       = null;
	public $abr_venta        = null;
	public $decimal_cantidad = null;
	public $decimal_monto    = null;
	public $activo           = 1;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

}

/* End of file Empresa_parametro_model.php */
/* Location: ./application/models/Empresa_parametro_model.php */