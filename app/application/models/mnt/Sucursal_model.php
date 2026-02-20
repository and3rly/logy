<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sucursal_model extends General_model {

	public $nombre;
	public $direccion = null;
	public $telefono = null;
	public $correo = null;
	public $activo = 1;
	public $empresa_id;
	public $municipio_id;
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
		->where("municipio_id", $args->municipio_id)
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
			b.nombre as nombre_municipio,
			c.id as departamento_id,
			c.nombre as nombre_departamento"
		)
		->join("municipio b","b.id = a.municipio_id")
		->join("departamento c","c.id = b.departamento_id")
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}
}

/* End of file Sucursal_model.php */
/* Location: ./application/models/Sucursal_model.php */