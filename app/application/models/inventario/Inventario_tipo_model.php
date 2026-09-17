<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Inventario_tipo_model extends General_model {

	public $codigo;
	public $nombre;
	public $descripcion;
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

/* End of file Inventario_tipo_model.php */
/* Location: ./application/models/inventario/Inventario_tipo_model.php */
