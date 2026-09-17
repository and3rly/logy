<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Inventario_estado_model extends General_model {

	public $codigo;
	public $nombre;
	public $orden = 0;
	public $activo = 1;
	public $empresa_id;

	public function __construct($id="")
	{
		parent::__construct();

		if (!empty($id)) {
			$this->cargar($id);
		}
	}
}

/* End of file Inventario_estado_model.php */
/* Location: ./application/models/inventario/Inventario_estado_model.php */
