<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Marca_model extends General_model {

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

/* End of file Marca_model.php */
/* Location: ./application/models/Marca_model.php */