<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cliente_model extends General_model {

	public $nombre;
	public $razon_social = null;
	public $identificacion = null;
	public $codigo = null;
	public $direccion = null;
	public $telefono = null;
	public $correo = null;
	public $activo = 1;
	public $credito = 0;
	public $credito_limite = null;
	public $credito_dias = 0;
	public $empresa_id;
	public $usuario_id;
	public $municipio_id = null;

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
			$this->db->where("id <>", $this->getPK());
		}

		$this->db
		->where("empresa_id", $this->usr["empresa_id"])
		->where("activo", 1);

		$identificacion = verPropiedad($args, "identificacion");
		if ($identificacion) {
			$this->db->where("identificacion", $identificacion);
		} else {
			$this->db->where("nombre", $args->nombre);
		}

		return $this->db->get($this->_tabla)->num_rows() > 0;
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
		->join("municipio b", "b.id = a.municipio_id", "left")
		->join("departamento c", "c.id = b.departamento_id", "left")
		->where("a.empresa_id", $this->usr["empresa_id"])
		->order_by("a.nombre", "asc")
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}
}

/* End of file Cliente_model.php */
/* Location: ./application/models/mnt/Cliente_model.php */
