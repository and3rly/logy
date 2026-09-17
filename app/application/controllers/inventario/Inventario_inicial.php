<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventario_inicial extends CI_Controller {

	const MAX_FILAS = 1000;
	const MAX_ARCHIVO = 5242880;
	const ENCABEZADOS = [
		"codigo_producto", "codigo_barra", "nombre_producto", "descripcion",
		"marca", "clasificacion", "unidad_codigo", "costo", "precio",
		"existencia_minima", "control_vencimiento", "fecha_vencimiento",
		"cantidad", "observacion"
	];

	public function __construct()
	{
		parent::__construct();

		$this->load->model([
			"inventario/Inventario_tipo_model",
			"inventario/Inventario_estado_model",
			"inventario/Inventario_enc_model",
			"inventario/Inventario_det_model",
			"inventario/Stock_model",
			"inventario/Movimiento_model",
			"mnt/Producto_model",
			"mnt/Marca_model",
			"mnt/Categoria_model",
			"mnt/Unidad_medida_model",
			"mnt/Sucursal_model"
		]);
		$this->load->library("Excel_service");

		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	private function responder($data)
	{
		$this->output->set_output(json_encode($data));
	}

	private function archivoExcel(&$mensaje)
	{
		$mensaje = "";
		if (empty($_FILES["archivo"]) || !isset($_FILES["archivo"]["error"])) {
			$mensaje = "Seleccione el archivo Excel.";
			return null;
		}

		$archivo = $_FILES["archivo"];
		if ((int) $archivo["error"] !== UPLOAD_ERR_OK) {
			$mensaje = (int) $archivo["error"] === UPLOAD_ERR_INI_SIZE || (int) $archivo["error"] === UPLOAD_ERR_FORM_SIZE
				? "El archivo supera el tamaño permitido." : "No fue posible recibir el archivo.";
			return null;
		}

		$nombre = basename($this->texto($archivo["name"]));
		$ruta = isset($archivo["tmp_name"]) ? $archivo["tmp_name"] : "";
		$tamano = is_file($ruta) ? filesize($ruta) : 0;
		if (strtolower(pathinfo($nombre, PATHINFO_EXTENSION)) !== "xlsx") {
			$mensaje = "Solo se admiten archivos .xlsx.";
			return null;
		}
		if ($tamano <= 0 || $tamano > self::MAX_ARCHIVO) {
			$mensaje = "El archivo debe pesar como máximo 5 MB.";
			return null;
		}

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		$mime = $finfo->file($ruta);
		$permitidos = [
			"application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
			"application/zip", "application/x-zip-compressed", "application/octet-stream"
		];
		if (!in_array($mime, $permitidos, true)) {
			$mensaje = "El contenido del archivo no corresponde a un libro .xlsx.";
			return null;
		}

		try {
			$filas = $this->excel_service->leerPlantillaXlsx(
				$ruta, self::ENCABEZADOS, self::MAX_FILAS
			);
		} catch (Throwable $error) {
			$mensaje = $error->getMessage() ?: "No fue posible interpretar el archivo Excel.";
			return null;
		}

		return (object) [
			"nombre" => $nombre,
			"hash"   => hash_file("sha256", $ruta),
			"filas"  => $filas
		];
	}

	private function texto($valor)
	{
		if ($valor === null || is_array($valor) || is_object($valor)) {
			return "";
		}

		return preg_replace('/\s+/u', ' ', trim((string) $valor));
	}

	private function decimal($valor, $decimales, &$valido)
	{
		$valido = false;
		if (is_string($valor)) {
			$valor = trim($valor);
			if (substr_count($valor, ',') === 1 && strpos($valor, '.') === false) {
				$valor = str_replace(',', '.', $valor);
			}
		}

		if ($valor === "" || $valor === null || !is_numeric($valor)) {
			return null;
		}

		$numero = (float) $valor;
		$valido = is_finite($numero) && abs($numero - round($numero, $decimales)) < 0.000001;
		return round($numero, $decimales);
	}

	private function booleano($valor)
	{
		$texto = mb_strtoupper($this->texto($valor), 'UTF-8');
		$texto = str_replace('Í', 'I', $texto);

		if (in_array($texto, ["SI", "YES", "TRUE", "1"], true)) {
			return 1;
		}

		if (in_array($texto, ["NO", "FALSE", "0"], true)) {
			return 0;
		}

		return null;
	}

	private function fecha($valor)
	{
		$valor = $this->texto($valor);
		if ($valor === "") {
			return null;
		}

		$fecha = DateTime::createFromFormat("Y-m-d", substr($valor, 0, 10));
		return $fecha && $fecha->format("Y-m-d") === substr($valor, 0, 10)
			? $fecha->format("Y-m-d 00:00:00") : false;
	}

	private function resolverMarca($nombre, $crear, &$mensaje)
	{
		$lista = $this->Marca_model->buscar([
			"empresa_id" => $_SESSION["empresa_id"], "nombre" => $nombre
		]);
		if (count($lista) > 1) {
			$mensaje = "Existe más de una marca con el mismo nombre.";
			return null;
		}
		if (count($lista) === 1) {
			$marca = $lista[0];
			if (!(int) $marca->activo) {
				$mensaje = "La marca existe, pero está inactiva.";
				return null;
			}
			return $marca;
		}

		if (!$crear) {
			return (object) ["id" => null, "nombre" => $nombre, "nuevo" => true];
		}

		$marcaModel = new Marca_model();
		if (!$marcaModel->guardar(["nombre" => $nombre, "activo" => 1])) {
			$mensaje = $marcaModel->getMensaje() ?: "No fue posible crear la marca.";
			return null;
		}

		return (object) ["id" => $marcaModel->getPK(), "nombre" => $nombre, "nuevo" => true];
	}

	private function resolverCategoria($nombre, $crear, &$mensaje)
	{
		$lista = $this->Categoria_model->buscar([
			"empresa_id" => $_SESSION["empresa_id"], "nombre" => $nombre
		]);
		if (count($lista) > 1) {
			$mensaje = "Existe más de una clasificación con el mismo nombre.";
			return null;
		}
		if (count($lista) === 1) {
			$categoria = $lista[0];
			if (!(int) $categoria->activo) {
				$mensaje = "La clasificación existe, pero está inactiva.";
				return null;
			}
			return $categoria;
		}

		if (!$crear) {
			return (object) ["id" => null, "nombre" => $nombre, "nuevo" => true];
		}

		$categoriaModel = new Categoria_model();
		if (!$categoriaModel->guardar([
			"nombre" => $nombre, "etiqueta" => "default", "activo" => 1
		])) {
			$mensaje = $categoriaModel->getMensaje() ?: "No fue posible crear la clasificación.";
			return null;
		}

		return (object) ["id" => $categoriaModel->getPK(), "nombre" => $nombre, "nuevo" => true];
	}

	private function unidadPorCodigo($codigo)
	{
		$lista = $this->Unidad_medida_model->buscar([
			"empresa_id" => $_SESSION["empresa_id"],
			"codigo"     => $codigo,
			"activo"     => 1
		]);

		return count($lista) === 1 ? $lista[0] : null;
	}

	private function productosPor($campo, $valor)
	{
		return $this->Producto_model->buscar([
			"empresa_id" => $_SESSION["empresa_id"],
			$campo        => $valor
		]);
	}

	private function stockActual()
	{
		$mapa = [];
		foreach ($this->Stock_model->existenciasParaAjuste() as $row) {
			$clave = $row->producto_id . "-" . $row->unidad_medida_id . "-" . $row->sucursal_id;
			$mapa[$clave] = round((float) $row->cantidad, 2);
		}

		return $mapa;
	}

	private function validarFilas($filas, $sucursalId, $crear=false)
	{
		$resultado = [];
		$claves = [];
		$nuevos = [];
		$stock = $this->stockActual();

		foreach ($filas as $indice => $origen) {
			$fila = (object) $origen;
			$numeroFila = isset($fila->_fila) ? (int) $fila->_fila : $indice + 2;
			$errores = [];
			$advertencias = [];
			$codigo = $this->texto(isset($fila->codigo_producto) ? $fila->codigo_producto : "");
			$barra = $this->texto(isset($fila->codigo_barra) ? $fila->codigo_barra : "");
			$nombre = $this->texto(isset($fila->nombre_producto) ? $fila->nombre_producto : "");
			$descripcion = $this->texto(isset($fila->descripcion) ? $fila->descripcion : "");
			$marcaNombre = $this->texto(isset($fila->marca) ? $fila->marca : "");
			$categoriaNombre = $this->texto(isset($fila->clasificacion) ? $fila->clasificacion : "");
			$unidadCodigo = $this->texto(isset($fila->unidad_codigo) ? $fila->unidad_codigo : "");
			$observacion = $this->texto(isset($fila->observacion) ? $fila->observacion : "");
			$fechaVence = $this->fecha(isset($fila->fecha_vencimiento) ? $fila->fecha_vencimiento : null);
			$control = $this->booleano(isset($fila->control_vencimiento) ? $fila->control_vencimiento : "");
			$cantidad = $this->decimal(isset($fila->cantidad) ? $fila->cantidad : null, 2, $cantidadValida);
			$costoOrigen = isset($fila->costo) && $this->texto($fila->costo) !== "" ? $fila->costo : 0;
			$precioOrigen = isset($fila->precio) && $this->texto($fila->precio) !== "" ? $fila->precio : 0;
			$minimaOrigen = isset($fila->existencia_minima) && $this->texto($fila->existencia_minima) !== "" ? $fila->existencia_minima : 0;
			$costo = $this->decimal($costoOrigen, 5, $costoValido);
			$precio = $this->decimal($precioOrigen, 5, $precioValido);
			$minima = $this->decimal($minimaOrigen, 2, $minimaValida);

			if ($codigo === "" || mb_strlen($codigo) > 150) $errores[] = "Código de producto obligatorio o demasiado largo.";
			if ($barra !== "" && mb_strlen($barra) > 100) $errores[] = "Código de barras demasiado largo.";
			if ($nombre !== "" && mb_strlen($nombre) > 300) $errores[] = "Nombre de producto demasiado largo.";
			if (mb_strlen($descripcion) > 45) $errores[] = "La descripción supera los 45 caracteres permitidos actualmente.";
			if ($marcaNombre !== "" && mb_strlen($marcaNombre) > 100) $errores[] = "Marca demasiado larga.";
			if ($categoriaNombre !== "" && mb_strlen($categoriaNombre) > 150) $errores[] = "Clasificación demasiado larga.";
			if ($unidadCodigo === "" || mb_strlen($unidadCodigo) > 10) $errores[] = "Código de unidad obligatorio o demasiado largo.";
			if (mb_strlen($observacion) > 300) $errores[] = "Observación demasiado larga.";
			if (!$cantidadValida || $cantidad === null || $cantidad <= 0 || $cantidad > 99999999.99) $errores[] = "Cantidad inválida; debe ser mayor que cero y usar máximo dos decimales.";
			if (!$costoValido || $costo < 0 || $costo > 99999.99999) $errores[] = "Costo inválido.";
			if (!$precioValido || $precio < 0 || $precio > 99999.99999) $errores[] = "Precio inválido.";
			if (!$minimaValida || $minima < 0 || $minima > 99999999.99) $errores[] = "Existencia mínima inválida.";
			if ($fechaVence === false) $errores[] = "Fecha de vencimiento inválida; use AAAA-MM-DD.";

			$unidad = $unidadCodigo !== "" ? $this->unidadPorCodigo($unidadCodigo) : null;
			if (!$unidad && $unidadCodigo !== "") $errores[] = "La unidad de medida no existe o está inactiva.";

			$producto = null;
			$esNuevo = false;
			if ($codigo !== "") {
				$porCodigo = $this->productosPor("codigo", $codigo);
				if (count($porCodigo) > 1) $errores[] = "Existe más de un producto con este código.";
				else if (count($porCodigo) === 1) $producto = $porCodigo[0];
			}

			if ($producto && $barra !== "") {
				$porBarra = $this->productosPor("codigo_barra", $barra);
				if (count($porBarra) > 1) $errores[] = "Existe más de un producto con este código de barras.";
				else if (count($porBarra) === 1 && (int) $porBarra[0]->id !== (int) $producto->id) {
					$errores[] = "El código y el código de barras corresponden a productos diferentes.";
				}
			}

			if (!$producto && empty($errores) && $barra !== "") {
				$porBarra = $this->productosPor("codigo_barra", $barra);
				if (count($porBarra) > 1) $errores[] = "Existe más de un producto con este código de barras.";
				else if (count($porBarra) === 1) $producto = $porBarra[0];
			}

			$marca = null;
			$categoria = null;
			$mensajeCatalogo = "";
			if (!$producto && empty($errores)) {
				if ($nombre === "") $errores[] = "Nombre obligatorio para crear un producto.";
				if ($marcaNombre === "") $errores[] = "Marca obligatoria para crear un producto.";
				if ($categoriaNombre === "") $errores[] = "Clasificación obligatoria para crear un producto.";
				if ($control === null) $errores[] = "Control de vencimiento debe ser SI o NO para un producto nuevo.";

				if (empty($errores)) {
					$marca = $this->resolverMarca($marcaNombre, $crear, $mensajeCatalogo);
					if (!$marca) $errores[] = $mensajeCatalogo;
					$categoria = $this->resolverCategoria($categoriaNombre, $crear, $mensajeCatalogo);
					if (!$categoria) $errores[] = $mensajeCatalogo;
				}

				if (empty($errores) && $marca->id && $categoria->id) {
					$coincidencias = $this->Producto_model->buscar([
						"empresa_id"       => $_SESSION["empresa_id"],
						"nombre"           => $nombre,
						"marca_id"         => $marca->id,
						"categoria_id"     => $categoria->id,
						"unidad_medida_id" => $unidad->id
					]);
					if (count($coincidencias) > 1) $errores[] = "La combinación de nombre, marca, clasificación y unidad es ambigua.";
					else if (count($coincidencias) === 1) $producto = $coincidencias[0];
				}
			}

			if (!$producto && empty($errores)) {
				$claveNuevo = mb_strtolower($codigo, 'UTF-8');
				if (!$crear) {
					$esNuevo = true;
					$nuevos[$claveNuevo] = true;
					if (!empty($marca->nuevo)) $advertencias[] = "Se creará la marca {$marcaNombre}.";
					if (!empty($categoria->nuevo)) $advertencias[] = "Se creará la clasificación {$categoriaNombre}.";
					$advertencias[] = "Se creará el producto {$nombre}.";
				} else {
					$productoModel = new Producto_model();
					if (!$productoModel->guardar([
						"codigo"            => $codigo,
						"codigo_barra"      => $barra !== "" ? $barra : null,
						"nombre"            => $nombre,
						"descripcion"       => $descripcion,
						"tipo_producto"     => "B",
						"precio"            => $precio,
						"costo"             => $costo,
						"control_vence"     => $control,
						"existencia_minima" => $minima,
						"activo"            => 1,
						"marca_id"          => $marca->id,
						"unidad_medida_id"  => $unidad->id,
						"categoria_id"      => $categoria->id
					])) {
						$errores[] = $productoModel->getMensaje() ?: "No fue posible crear el producto.";
					} else {
						$producto = (object) [
							"id" => $productoModel->getPK(), "codigo" => $codigo,
							"nombre" => $nombre, "unidad_medida_id" => $unidad->id,
							"control_vence" => $control, "activo" => 1,
							"tipo_producto" => "B"
						];
						$esNuevo = true;
					}
				}
			}

			if ($producto) {
				if (!(int) $producto->activo || $producto->tipo_producto !== "B") $errores[] = "El producto está inactivo o no es un bien.";
				if ($unidad && (int) $producto->unidad_medida_id !== (int) $unidad->id) $errores[] = "La unidad del Excel no coincide con la unidad del producto.";
				if ($nombre !== "" && $this->texto($producto->nombre) !== $nombre) $advertencias[] = "El producto existente conserva el nombre registrado en Logy.";
				$control = (int) $producto->control_vence;
				$claveStock = $producto->id . "-" . $producto->unidad_medida_id . "-" . $sucursalId;
				if (abs(isset($stock[$claveStock]) ? $stock[$claveStock] : 0) > 0.00001) $errores[] = "El producto ya tiene existencia en la sucursal; no puede cargarse como inventario inicial.";
			}

			if ($control === 1 && !$fechaVence) $errores[] = "El producto controla vencimiento y requiere fecha.";
			if ($control === 0 && $fechaVence) $advertencias[] = "Se ignorará el vencimiento porque el producto no controla fecha.";
			if ($control === 0) $fechaVence = null;

			$claveProducto = $producto ? "p:" . $producto->id : "n:" . mb_strtolower($codigo, 'UTF-8');
			$claveDetalle = $claveProducto . "|" . ($fechaVence ?: "sin-fecha");
			if (isset($claves[$claveDetalle])) $errores[] = "Producto y vencimiento repetidos en las filas {$claves[$claveDetalle]} y {$numeroFila}.";
			else $claves[$claveDetalle] = $numeroFila;

			$resultado[] = (object) [
				"fila"               => $numeroFila,
				"codigo_producto"    => $codigo,
				"codigo_barra"       => $barra,
				"nombre_producto"    => $producto ? $producto->nombre : $nombre,
				"marca"              => $marcaNombre,
				"clasificacion"      => $categoriaNombre,
				"unidad_codigo"      => $unidadCodigo,
				"producto_id"        => $producto ? (int) $producto->id : null,
				"unidad_medida_id"   => $unidad ? (int) $unidad->id : null,
				"fecha_vence"        => $fechaVence,
				"cantidad"           => $cantidad,
				"costo"              => $costo,
				"observacion"        => $observacion !== "" ? $observacion : null,
				"estado"             => !empty($errores) ? "ERROR" : ($esNuevo ? "NUEVO" : "EXISTENTE"),
				"errores"            => $errores,
				"advertencias"       => $advertencias
			];
		}

		return $resultado;
	}

	private function resumenValidacion($lineas)
	{
		$resumen = ["filas" => count($lineas), "validas" => 0, "nuevos" => 0, "errores" => 0];
		foreach ($lineas as $linea) {
			if ($linea->estado === "ERROR") $resumen["errores"]++;
			else {
				$resumen["validas"]++;
				if ($linea->estado === "NUEVO") $resumen["nuevos"]++;
			}
		}
		return $resumen;
	}

	private function sucursalValida($id)
	{
		$lista = $this->Sucursal_model->buscar([
			"id"         => $id,
			"empresa_id" => $_SESSION["empresa_id"],
			"activo"     => 1
		]);
		return count($lista) === 1;
	}

	public function buscar()
	{
		$args = [
			"tipo_codigo"          => "INICIAL",
			"termino"             => $this->input->get("termino", true),
			"inventario_estado_id" => $this->input->get("inventario_estado_id", true),
			"sucursal_id"          => $this->input->get("sucursal_id", true),
			"fecha_desde"          => $this->input->get("fecha_desde", true),
			"fecha_hasta"          => $this->input->get("fecha_hasta", true)
		];

		$this->responder(["lista" => $this->Inventario_enc_model->_buscar($args)]);
	}

	public function get_datos()
	{
		$this->responder(["cat" => [
			"estados" => $this->Inventario_estado_model->buscar([
				"empresa_id" => $_SESSION["empresa_id"], "activo" => 1,
				"_orden_asc" => "orden"
			]),
			"sucursales" => $this->Sucursal_model->buscar([
				"empresa_id" => $_SESSION["empresa_id"], "activo" => 1,
				"_orden_asc" => "nombre"
			])
		]]);
	}

	public function detalle($id="")
	{
		$encabezado = $this->Inventario_enc_model->_buscar([
			"id" => $id, "tipo_codigo" => "INICIAL", "uno" => true
		]);
		$this->responder([
			"encabezado" => $encabezado ?: null,
			"lista" => $encabezado ? $this->Inventario_det_model->_buscar(["inventario_enc_id" => $id]) : []
		]);
	}

	public function validar()
	{
		$data = ["exito" => 0, "lista" => []];
		if ($this->input->method() !== "post") {
			$data["mensaje"] = "Método incorrecto.";
			$this->responder($data);
			return;
		}

		$sucursalId = $this->input->post("sucursal_id", true);
		$archivo = null;
		$mensajeArchivo = "";
		if (empty($sucursalId)) {
			$data["mensaje"] = "Seleccione una sucursal.";
		} else if (!$this->sucursalValida($sucursalId)) {
			$data["mensaje"] = "La sucursal no existe o no pertenece a la empresa.";
		} else {
			$archivo = $this->archivoExcel($mensajeArchivo);
			if (!$archivo) {
				$data["mensaje"] = $mensajeArchivo;
			} else {
				$data["lista"] = $this->validarFilas($archivo->filas, $sucursalId);
				$data["resumen"] = $this->resumenValidacion($data["lista"]);
				$data["archivo_nombre"] = $archivo->nombre;
				$data["archivo_hash"] = $archivo->hash;
				$data["exito"] = $data["resumen"]["errores"] === 0 ? 1 : 0;
				$data["mensaje"] = $data["exito"] ? "Archivo validado correctamente." : "Corrija las filas marcadas con error.";
			}
		}

		$this->responder($data);
	}

	public function guardar()
	{
		$data = ["exito" => 0];
		if ($this->input->method() !== "post") {
			$data["mensaje"] = "Método incorrecto.";
			$this->responder($data);
			return;
		}

		$sucursalId = $this->input->post("sucursal_id", true);
		$observacion = $this->texto($this->input->post("observacion", true));
		$excel = null;
		$mensajeArchivo = "";

		if (empty($sucursalId)) {
			$data["mensaje"] = "Seleccione una sucursal.";
		} else if (!$this->sucursalValida($sucursalId)) {
			$data["mensaje"] = "La sucursal no existe o no pertenece a la empresa.";
		} else {
			$excel = $this->archivoExcel($mensajeArchivo);
			if (!$excel) {
				$data["mensaje"] = $mensajeArchivo;
				$this->responder($data);
				return;
			}
			if (mb_strlen($observacion) > 300 || mb_strlen($excel->nombre) > 255) {
				$data["mensaje"] = "Revise la observación o el nombre del archivo.";
				$this->responder($data);
				return;
			}
			$this->db->trans_begin();
			if ($this->Inventario_enc_model->archivoExiste($excel->hash, $sucursalId)) {
				$data["mensaje"] = "Este archivo ya fue guardado o procesado en la sucursal seleccionada.";
			} else {
				$lineas = $this->validarFilas($excel->filas, $sucursalId, true);
				$resumen = $this->resumenValidacion($lineas);
				if ($resumen["errores"] > 0) {
					$data["mensaje"] = "El archivo cambió o contiene errores; vuelva a validarlo.";
					$data["lista"] = $lineas;
					$data["resumen"] = $resumen;
				} else {
					$tipo = $this->Inventario_tipo_model->buscar([
						"empresa_id" => $_SESSION["empresa_id"], "codigo" => "INICIAL",
						"activo" => 1, "_uno" => true
					]);
					$estado = $this->Inventario_estado_model->buscar([
						"empresa_id" => $_SESSION["empresa_id"], "codigo" => "VALIDADO",
						"activo" => 1, "_uno" => true
					]);

					if (!$tipo || !$estado) {
						$data["mensaje"] = "Configure los catálogos INICIAL y VALIDADO.";
					} else {
						$encabezado = new Inventario_enc_model();
						$continuar = $encabezado->guardar([
							"numero"               => $this->Inventario_enc_model->generarNumero(),
							"inventario_tipo_id"   => $tipo->id,
							"inventario_estado_id" => $estado->id,
							"fecha_corte"          => date("Y-m-d H:i:s"),
							"observacion"          => $observacion !== "" ? $observacion : null,
							"archivo_nombre"       => $excel->nombre,
							"archivo_hash"         => $excel->hash,
							"sucursal_id"          => (int) $sucursalId
						]);

						if ($continuar) {
							$continuar = $this->Inventario_det_model->guardarLineas($encabezado->getPK(), $lineas);
						}

						if ($continuar && $this->db->trans_status() !== FALSE) {
							$this->db->trans_commit();
							$data["exito"] = 1;
							$data["mensaje"] = "Inventario inicial guardado y validado.";
							$data["linea"] = $this->Inventario_enc_model->_buscar([
								"id" => $encabezado->getPK(), "tipo_codigo" => "INICIAL", "uno" => true
							]);
							$this->responder($data);
							return;
						}

						$data["mensaje"] = $encabezado->getMensaje() ?: $this->Inventario_det_model->getMensaje() ?: "No fue posible guardar el inventario.";
					}
				}
			}
			$this->db->trans_rollback();
		}

		$this->responder($data);
	}

	public function procesar($id="")
	{
		$data = ["exito" => 0];
		if ($this->input->method() !== "post") {
			$data["mensaje"] = "Método incorrecto.";
			$this->responder($data);
			return;
		}

		$this->db->trans_begin();
		$encabezado = $this->Inventario_enc_model->bloquear($id);
		if (!$encabezado || $encabezado->codigo_tipo !== "INICIAL") {
			$data["mensaje"] = "El inventario inicial no existe.";
		} else if ($encabezado->codigo_estado !== "VALIDADO") {
			$data["mensaje"] = "Solo puede procesar inventarios validados.";
		} else {
			$lineas = $this->Inventario_det_model->_buscar(["inventario_enc_id" => $id]);
			$tipoMovimiento = $this->catalogo->verTiposMovimiento(["codigo" => "IVP", "uno" => true]);
			$continuar = !empty($lineas) && $tipoMovimiento;
			if (!$continuar) $data["mensaje"] = "El inventario no tiene productos o falta el movimiento IVP.";

			$verificados = [];
			foreach ($lineas as $linea) {
				if (!$continuar) break;
				$claveStock = $linea->producto_id . "-" . $linea->unidad_medida_id;
				if (!isset($verificados[$claveStock])) {
					$lotes = $this->Stock_model->bloquearExistenciaInicial(
						$linea->producto_id, $linea->unidad_medida_id, $encabezado->sucursal_id
					);
					$actual = 0;
					foreach ($lotes as $lote) $actual += (float) $lote->cantidad;

					if (abs($actual) > 0.00001) {
						$continuar = false;
						$data["mensaje"] = "El stock cambió después de validar el archivo. Vuelva a preparar el inventario inicial.";
						break;
					}
					$verificados[$claveStock] = true;
				}

				$stock = new Stock_model();
				$continuar = $stock->guardar([
					"producto_id"              => $linea->producto_id,
					"unidad_medida_id"         => $linea->unidad_medida_id,
					"producto_presentacion_id" => $linea->producto_presentacion_id,
					"fecha_vence"              => $linea->fecha_vence,
					"cantidad"                 => $linea->diferencia,
					"activo"                   => 1,
					"sucursal_id"              => $encabezado->sucursal_id
				]);

				if ($continuar) {
					$movimiento = new Movimiento_model();
					$continuar = $movimiento->guardar([
						"stock_id"           => $stock->getPK(),
						"movimiento_tipo_id" => $tipoMovimiento->id,
						"cantidad"           => $linea->diferencia,
						"inventario_det_id"  => $linea->id,
						"observacion"        => "Inventario inicial " . $encabezado->numero
					]);
				}

				if (!$continuar) $data["mensaje"] = "No fue posible crear el stock o movimiento de una línea.";
			}

			$estado = $this->Inventario_estado_model->buscar([
				"empresa_id" => $_SESSION["empresa_id"], "codigo" => "PROCESADO",
				"activo" => 1, "_uno" => true
			]);
			if ($continuar && !$estado) {
				$continuar = false;
				$data["mensaje"] = "Configure el estado PROCESADO.";
			}

			if ($continuar) {
				$continuar = $this->Inventario_enc_model->cambiarEstado($id, $estado->id, [
					"fecha_procesado"   => date("Y-m-d H:i:s"),
					"usuario_proceso_id" => $_SESSION["id"]
				]);
			}

			if ($continuar && $this->db->trans_status() !== FALSE) {
				$this->db->trans_commit();
				$data["exito"] = 1;
				$data["mensaje"] = "Inventario inicial procesado correctamente.";
				$data["linea"] = $this->Inventario_enc_model->_buscar([
					"id" => $id, "tipo_codigo" => "INICIAL", "uno" => true
				]);
				$this->responder($data);
				return;
			}
		}

		$this->db->trans_rollback();
		$this->responder($data);
	}

	public function anular($id="")
	{
		$data = ["exito" => 0];
		if ($this->input->method() !== "post") {
			$data["mensaje"] = "Método incorrecto.";
			$this->responder($data);
			return;
		}

		$this->db->trans_begin();
		$encabezado = $this->Inventario_enc_model->bloquear($id);
		$estado = $this->Inventario_estado_model->buscar([
			"empresa_id" => $_SESSION["empresa_id"], "codigo" => "ANULADO",
			"activo" => 1, "_uno" => true
		]);

		if (!$encabezado || $encabezado->codigo_tipo !== "INICIAL") {
			$data["mensaje"] = "El inventario inicial no existe.";
		} else if (!in_array($encabezado->codigo_estado, ["BORRADOR", "VALIDADO"], true)) {
			$data["mensaje"] = "Solo puede anular inventarios que aún no fueron procesados.";
		} else if (!$estado) {
			$data["mensaje"] = "Configure el estado ANULADO.";
		} else if ($this->Inventario_enc_model->cambiarEstado($id, $estado->id, [
			"fecha_anulado"    => date("Y-m-d H:i:s"),
			"usuario_anulo_id" => $_SESSION["id"]
		]) && $this->db->trans_status() !== FALSE) {
			$this->db->trans_commit();
			$data["exito"] = 1;
			$data["mensaje"] = "Inventario inicial anulado.";
			$data["linea"] = $this->Inventario_enc_model->_buscar([
				"id" => $id, "tipo_codigo" => "INICIAL", "uno" => true
			]);
			$this->responder($data);
			return;
		} else {
			$data["mensaje"] = "No fue posible anular el inventario.";
		}

		$this->db->trans_rollback();
		$this->responder($data);
	}
}

/* End of file Inventario_inicial.php */
/* Location: ./application/controllers/inventario/Inventario_inicial.php */
