<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Producto_model extends General_model {

	public $codigo;
	public $nombre;
	public $descripcion;
	public $tipo_producto;
	public $codigo_barra = null;
	public $precio;
	public $costo;
	public $foto = null;
	public $control_vence = 0;
	public $activo = 1;
	public $marca_id;
	public $unidad_medida_id;
	public $empresa_id;
	public $categoria_id;
	public $usuario_id;
	public $existencia_minima;

	public function __construct($id="")
	{
		parent::__construct();
		if (!empty($id)) {
			$this->cargar($id);
		}
	}

	public function _buscar($args=[])
	{	
		if (elemento($args, "id")) {
			$this->db->where("a.id", $args["id"]);
		} else {
			if (elemento($args, 'termino')) {
				$termino = strtolower(trim($args["termino"]));

				$this->db->where("(
					a.nombre like '%{$termino}%' or
					b.nombre like '%{$termino}%'
				)", null, false);
			}
		}

		$tmp = $this->db
		->select("a.*,
			b.nombre as nombre_marca,
			c.nombre as nombre_categoria,
			c.etiqueta,
			d.nombre as nombre_um")
		->join("marca b","b.id = a.marca_id")
		->join("categoria c","c.id = a.categoria_id")
		->join("unidad_medida d","d.id = a.unidad_medida_id")
		->order_by("a.fecha", "desc")
		->get("$this->_tabla a");

		return verConsulta($tmp, $args);
	}

	public function existe($args=[])
	{
		if ($this->getPK()) {
			$this->db->where("id <>", $this->getPK());
		}

		$tmp = $this->db
		->where("nombre", $args->nombre)
		->where("unidad_medida_id", $args->unidad_medida_id)
		->where("marca_id", $args->marca_id)
		->where("categoria_id", $args->categoria_id)
		->get("$this->_tabla");

		return $tmp->num_rows() > 0;
	}
}

/* End of file Producto_model.php */
/* Location: ./application/models/Producto_model.php */