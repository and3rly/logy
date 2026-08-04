<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Movimiento_model extends General_model {

	public $stock_id;
	public $movimiento_tipo_id;
	public $cantidad;
	public $compra_id;
	public $usuario_id;
	public $observacion;

	public function __construct($id="")
	{
		parent::__construct();

		if (!empty($id)) {
			$this->cargar($id);
		}
	}

}

/* End of file Movimiento_model.php */
/* Location: ./application/models/inventario/Movimiento_model.php */
