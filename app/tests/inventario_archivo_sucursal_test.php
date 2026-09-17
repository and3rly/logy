<?php
define('BASEPATH', __DIR__);

class BaseDatosFalsa {
	public $filtros = [];
	public $tabla;

	public function where($campo, $valor=null, $escape=null)
	{
		$this->filtros[] = [$campo, $valor, $escape];
		return $this;
	}

	public function get($tabla)
	{
		$this->tabla = $tabla;
		return $this;
	}

	public function num_rows()
	{
		return 0;
	}

	public function escape($valor)
	{
		return "'" . addslashes($valor) . "'";
	}
}

class General_model {
	public $db;
	public $usr = ['empresa_id' => 9];
	public $_tabla = 'inventario_enc';

	public function __construct()
	{
		$this->db = new BaseDatosFalsa();
	}
}

require __DIR__ . '/../application/models/inventario/Inventario_enc_model.php';

$modelo = new Inventario_enc_model();
$modelo->archivoExiste(str_repeat('a', 64), 27);

$filtros = [];
foreach ($modelo->db->filtros as $filtro) {
	$filtros[$filtro[0]] = $filtro[1];
}

if (!isset($filtros['empresa_id']) || (int) $filtros['empresa_id'] !== 9) {
	throw new RuntimeException('La búsqueda del archivo no quedó limitada por empresa.');
}
if (!isset($filtros['sucursal_id']) || (int) $filtros['sucursal_id'] !== 27) {
	throw new RuntimeException('La búsqueda del archivo no quedó limitada por sucursal.');
}
if (!isset($filtros['archivo_hash']) || $filtros['archivo_hash'] !== str_repeat('a', 64)) {
	throw new RuntimeException('La búsqueda del archivo no incluyó su hash.');
}

$sinSucursal = new Inventario_enc_model();
if ($sinSucursal->archivoExiste(str_repeat('b', 64), null) !== false) {
	throw new RuntimeException('Una búsqueda sin sucursal debe rechazarse.');
}
if (!empty($sinSucursal->db->filtros)) {
	throw new RuntimeException('No debe consultarse la base sin una sucursal válida.');
}

echo "Inventario inicial: el archivo duplicado se valida por empresa y sucursal." . PHP_EOL;
