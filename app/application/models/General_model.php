<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class General_model extends CI_Model {
	protected $_tabla = "";
	protected $_llave = "id";
	protected $_pk = null;
	protected $mensaje = "";
	protected $foreignKey;
	protected $usr;

	public function __construct()
	{
		parent::__construct();
		$this->_tabla = $this->getTabla();
		$this->usr    = $this->session->userdata();
	}

	public function limpiarGeneral()
	{
		$this->_pk = null;
		$this->mensaje = "";
	}

	public function getPK()
	{
		return $this->_pk;
	}

	public function setPK($valor = null)
	{
		return $this->_pk = $valor;
	}

	public function setCodigo($valor)
	{
		$this->_codigo = $valor;
	}

	public function getMensaje()
	{
	    return $this->mensaje;
	}
	
	public function setMensaje($mensaje)
	{
	    $this->mensaje .= $mensaje;
	    return $this;
	}

	public function setTabla($nombre)
	{
		$this->_tabla = $nombre;

		$tmp = explode(".", $nombre);

		$this->_llave = count($tmp) > 1 ? $tmp[1] : $nombre;
	}

	public function setLlave($nombre)
	{
		$this->_llave = $nombre;
	}

	private function getTabla()
	{
		return str_replace("_model", "", strtolower(get_class($this)));
	}

	public function setDatos($args=[])
	{
		foreach ($args as $campo => $valor) {
			if (property_exists($this, $campo)) {
				if (is_object($this->{$campo})) {
					$this->{$campo}->setPK($valor);
				} else {
					$this->$campo = $valor;
				}
			}
		}
	}

	public function cargar($valor)
	{
		$tmp = $this->db
		->where($this->_llave, $valor)
		->get($this->_tabla)
		->row();

		$var = $this->_llave;
		$this->setPK($tmp->$var);

		$this->setDatos($tmp);
	}

	private function getDatos()
	{
		$tmp = [];

		foreach (get_object_vars($this) as $key => $value) {
			if (property_exists($this, $key)) {
				try {
					$rp = new ReflectionProperty($this, $key);

					if ($rp->isPublic()) {
						if (is_object($this->{$key})) {
							$obj = $this->{$key};
							$tmp[$obj->getForanea()] = $obj->getPK();
						} else {
							$tmp[$key] = $value;
						}
					}
				} catch (\Throwable $th) {
					# ...
				}
			}
		}

		return $tmp;
	}

	public function guardar($args=[])
	{
		$this->setDatos($args);

		if ($this->_pk === null) {
			if (property_exists($this, 'usuario_id') && empty($this->usuario_id)) {
				$this->usuario_id = $this->usr["usuario_id"];
			}

			if (property_exists($this, 'empresa_id') && empty($this->empresa_id)) {
				$this->empresa_id = $this->usr["empresa_id"];
			}

			$this->db->insert($this->_tabla, $this);

			if ($this->db->affected_rows() > 0) { 
				$this->cargar($this->db->insert_id());
				return true;
			} else {
				$this->setMensaje("No pude guardar los datos, por favor intente nuevamente.");
			}
		} else {
			$this->db
			->where($this->_llave, $this->_pk)
			->update($this->_tabla, $this);

			if ($this->db->affected_rows() == 0) {
				$this->setMensaje("Nada que actualizar");
			} else {
				$this->cargar($this->_pk);
				return true;
			}
		}

		return FALSE;
	}

	public function buscar($args = [])
	{
		$inicio = isset($args["_inicio"]) ?? 0;
		
		if (isset($args["_limite"])) {
        	$this->db->limit($args["_limite"], $inicio);
        	unset($args['_limite']);
        }

        unset($args['_inicio']);

		if (isset($args["descripcion"])) {
        	$this->db->like('descripcion', $args['descripcion']);
        }

		if (isset($args['_between'])) {
			$campo  = $args['_between'][0];
			$valor1 = $args['_between'][1];
			$valor2 = $args['_between'][2];

			$this->db->where("{$campo} between '{$valor1}' and '{$valor2}'");	
		}

		if (isset($args['_orden_rand'])) {
			$this->db->order_by('rand()');
		}

		if (isset($args['_orden_asc'])) {
			$this->db->order_by($args['_orden_asc'], 'asc');
		}

		if (isset($args['_orden_desc'])) {
			$this->db->order_by($args['_orden_desc'], 'desc');
		}

		if (count($args) > 0) {
			foreach ($args as $key => $row) {
				if (substr($key, 0, 1) != "_") {
					$columna = (count(explode(".", $key)) > 1 ? $key : "{$this->_tabla}.{$key}");
 
					if (is_array($row)) {
						$this->db->where_in($columna, $row);
					} else {
						$this->db->where($columna, $row);
					}
				}
			}	
		}

		$tmp = $this->db->get($this->_tabla);

		if (isset($args['_uno'])) {
			return $tmp->row();
		}

		return $tmp->result();
	}
}

/* End of file General_model.php */
/* Location: ./application/models/General_model.php */