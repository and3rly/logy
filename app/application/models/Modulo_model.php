<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Modulo_model extends General_model {

	public $nombre;
	public $icono = null;
	public $url = null;
	public $orden = 0;
	public $detalle = 1;
	public $activo = 1;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	public function _buscar($args=[])
	{
		$tmp = $this->db
		->where("activo", 1)
		->order_by("orden", "ASC")
		->get($this->_tabla);

		return verConsulta($tmp, $args);
	}
}

/* End of file Modulo_model.php */
/* Location: ./application/models/Modulo_model.php */