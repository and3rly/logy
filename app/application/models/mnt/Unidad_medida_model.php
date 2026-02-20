<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Unidad_medida_model extends General_model {

	public $codigo;
	public $nombre;
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

/* End of file Unidad_medida_model.php */
/* Location: ./application/models/Unidad_medida_model.php */