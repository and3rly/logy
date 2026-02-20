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
	public $rol_id;

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
		->where("activo", 1)
		->get("$this->_tabla")
		->row();

		if (!$tmp || !password_verify($args->clave, $tmp->clave)) {
			return false;
		}

		$this->cargar($tmp->id);
		return true;
	}

	public function existe($args=[])
	{	
		if ($this->getPK()) {
			$this->db->where("id <> ", $this->getPK());
		}

		$tmp = $this->db
		->where("nombre", $args->nombre)
		->where("alias", $args->alias)
		->where("rol_id", $args->rol_id)
		->where("activo", 1)
		->get("$this->_tabla");

		return $tmp->num_rows() > 0;
	}

	public function _buscar($args=[])
	{
		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		}

		$tmp = $this->db
		->select("
			a.*, 
			b.nombre as nombre_empresa,
			c.nombre as nombre_rol"
		)
		->join("empresa b","b.id = a.empresa_id")
		->join("rol c","c.id = a.rol_id")
		->order_by("a.fecha", "desc")
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}
}

/* End of file Usuario_model.php */
/* Location: ./application/models/Usuario_model.php */