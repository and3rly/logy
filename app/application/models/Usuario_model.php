<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Usuario_model extends General_model {

	public $nombre;
	public $alias;
	public $clave;
	public $correo = NULL;
	public $telefono = NULL;
	public $foto = NULL;
	public $activo = 1;
	public $empresa_id;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	public function iniciarSesion($args=[])
	{
		$tmp = $this->db
		->where("alias", $args->alias)
		->where("clave", "md5('{$args->clave}')", false)
		->where("activo", 1)
		->get("$this->_tabla")
		->row();

		if (!$tmp) { 
			return false; 
		}

		$this->cargar($tmp->id);
    	return true;
	}

}

/* End of file Usuario_model.php */
/* Location: ./application/models/Usuario_model.php */