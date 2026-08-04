<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Empresa_model extends General_model {

	public $nombre;
	public $razon_social;
	public $identificacion;
	public $direccion;
	public $telefono;
	public $correo;
	public $logo;
	public $activo;
	public $municipio_id;

	public function __construct($id="")
	{
		parent::__construct();

		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	public function actual()
	{
		return $this->db
		->where("id", $this->usr["empresa_id"])
		->get($this->_tabla)
		->row();
	}
}

/* End of file Empresa_model.php */
/* Location: ./application/models/mnt/Empresa_model.php */
