<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Proveedor_model extends General_model {

	public $nombre;
	public $identificacion = null;
	public $direccion = null;
	public $telefono = null;
	public $correo = null;
	public $credito = 0;
	public $credito_limite = null;
	public $credito_dias = 0;
	public $activo = 1;
	public $empresa_id;
	public $usuario_id;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	public function existe($args=[])
	{	
		if ($this->getPK()) {
			$this->db->where("id <> ", $this->getPK());
		}

		$tmp = $this->db
		->where("nombre", $args->nombre)
		->where("identificacion", $args->identificacion)
		->where("activo", 1)
		->get("$this->_tabla");

		return $tmp->num_rows() > 0;
	}

	public function _buscar($args=[])
	{
		if (elemento($args, "id")) {
			$this->db->where("id", $args["id"]);
		}

		$tmp = $this->db
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}
}

/* End of file Proveedor_model.php */
/* Location: ./application/models/Proveedor_model.php */