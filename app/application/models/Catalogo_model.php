<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Catalogo_model extends CI_Model {

	public function verRol($args=[])
	{
		$tmp = $this->db
		->where("activo", 1)
		->get("rol");

		return verConsulta($tmp, $args);
	}

	public function verMunicipios($args=[])
	{
		$tmp = $this->db
		->where("activo", 1)
		->get("municipio");

		return verConsulta($tmp, $args);
	}

	public function verDepartamentos($args=[])
	{
		$tmp = $this->db
		->where("activo", 1)
		->get("departamento");

		return verConsulta($tmp, $args);
	}
}

/* End of file Catalogo_model.php */
/* Location: ./application/models/Catalogo_model.php */