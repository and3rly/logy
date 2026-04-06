<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Menu_model extends General_model {

	public $modulo_id;
	public $nombre;
	public $orden = 0;
	public $icono;
	public $url;
	public $activo = 1;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}
}

/* End of file Menu_model.php */
/* Location: ./application/models/Menu_model.php */