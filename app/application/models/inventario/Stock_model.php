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

}

/* End of file Stock_model.php */
/* Location: ./application/models/inventario/Stock_model.php */
