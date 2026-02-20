<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rol_model extends General_model {
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

/* End of file Rol_model.php */
/* Location: ./application/models/mnt/Rol_model.php */ ?>