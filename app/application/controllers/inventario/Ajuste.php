<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ajuste extends CI_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->model([
			"inventario/Inventario_ajuste_model",
			"inventario/Inventario_ajuste_detalle_model",
			"inventario/Stock_model",
			"inventario/Movimiento_model"
		]);

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

	private function fechaValida($fecha)
	{
		if (empty($fecha)) {
			return true;
		}

		$tmp = DateTime::createFromFormat("Y-m-d", $fecha);
		return $tmp && $tmp->format("Y-m-d") === $fecha;
	}

	private function validarLineas($lineas, &$mensaje)
	{
		if (!is_array($lineas) || empty($lineas)) {
			$mensaje = "Agregue al menos un producto al ajuste.";
			return false;
		}

		$repetidas = [];

		foreach ($lineas as $linea) {
			$productoId = isset($linea->producto_id) ? (int) $linea->producto_id : 0;
			$unidadId = isset($linea->unidad_medida_id) ? (int) $linea->unidad_medida_id : 0;
			$presentacionId = !empty($linea->producto_presentacion_id) ? (int) $linea->producto_presentacion_id : null;
			$cantidad = isset($linea->cantidad) ? round((float) $linea->cantidad, 2) : 0;
			$fechaVence = !empty($linea->fecha_vence) ? trim($linea->fecha_vence) : null;

			if ($productoId <= 0 || $unidadId <= 0 || $cantidad <= 0 || $cantidad > 99999999.99) {
				$mensaje = "Revise los productos y las cantidades del ajuste.";
				return false;
			}

			$producto = $this->Inventario_ajuste_model->productoActivo($productoId);

			if (!$producto || (int) $producto->unidad_medida_id !== $unidadId) {
				$mensaje = "Uno de los productos no existe, está inactivo o usa otra unidad de medida.";
				return false;
			}

			if (!$this->Inventario_ajuste_model->presentacionValida($presentacionId, $productoId)) {
				$mensaje = "Una de las presentaciones seleccionadas no corresponde al producto.";
				return false;
			}

			if (!$this->fechaValida($fechaVence)) {
				$mensaje = "Revise las fechas de vencimiento del detalle.";
				return false;
			}

			$clave = implode("-", [
				$productoId,
				$unidadId,
				$presentacionId ?: 0,
				$fechaVence ?: "sin-fecha"
			]);

			if (isset($repetidas[$clave])) {
				$mensaje = "No repita el mismo producto, presentación y vencimiento en el detalle.";
				return false;
			}

			$repetidas[$clave] = true;
		}

		return true;
	}

	public function buscar()
	{
		$args = [
			"termino"                       => $this->input->get("termino", true),
			"inventario_ajuste_estado_id"  => $this->input->get("inventario_ajuste_estado_id", true),
			"inventario_ajuste_tipo_id"    => $this->input->get("inventario_ajuste_tipo_id", true),
			"naturaleza"                    => $this->input->get("naturaleza", true),
			"sucursal_id"                   => $this->input->get("sucursal_id", true),
			"fecha_desde"                   => $this->input->get("fecha_desde", true),
			"fecha_hasta"                   => $this->input->get("fecha_hasta", true)
		];

		if (!empty($args["fecha_desde"]) && !empty($args["fecha_hasta"]) &&
			$args["fecha_desde"] > $args["fecha_hasta"]) {
			$this->responder(["lista" => [], "mensaje" => "La fecha inicial no puede ser mayor que la fecha final."]);
			return;
		}

		$this->responder(["lista" => $this->Inventario_ajuste_model->_buscar($args)]);
	}

	public function get_datos()
	{
		$catalogos = $this->Inventario_ajuste_model->getCatalogos();
		$catalogos["existencias"] = $this->Stock_model->existenciasParaAjuste();

		$this->responder(["cat" => $catalogos]);
	}

	public function detalle($ajusteId="")
	{
		$ajuste = $this->Inventario_ajuste_model->_buscar(["id" => $ajusteId, "uno" => true]);

		if (!$ajuste) {
			$this->responder(["lista" => [], "mensaje" => "El ajuste no existe."]);
			return;
		}

		$this->responder([
			"lista" => $this->Inventario_ajuste_detalle_model->_buscar([
				"inventario_ajuste_id" => $ajusteId
			])
		]);
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() !== "post") {
			$data["mensaje"] = "Método incorrecto.";
			$this->responder($data);
			return;
		}

		$datos = json_decode(file_get_contents("php://input"));

		if (!$datos || empty($datos->inventario_ajuste_tipo_id) ||
			empty($datos->sucursal_id) || !isset($datos->detalle)) {
			$data["mensaje"] = "Complete los campos obligatorios.";
			$this->responder($data);
			return;
		}

		$actual = null;
		if (!empty($id)) {
			$actual = $this->Inventario_ajuste_model->_buscar(["id" => $id, "uno" => true]);
			if (!$actual) {
				$data["mensaje"] = "El ajuste no existe.";
				$this->responder($data);
				return;
			}
			if ($actual->codigo_estado !== "BORRADOR") {
				$data["mensaje"] = "Solo puede modificar ajustes en borrador.";
				$this->responder($data);
				return;
			}
		}

		$tipo = $this->Inventario_ajuste_model->tipoActivo($datos->inventario_ajuste_tipo_id);
		$sucursal = $this->Inventario_ajuste_model->sucursalActiva($datos->sucursal_id);
		$motivo = isset($datos->motivo) ? trim((string) $datos->motivo) : "";
		$observacion = isset($datos->observacion) ? trim((string) $datos->observacion) : "";

		if (!$tipo || !$sucursal) {
			$data["mensaje"] = "El tipo de ajuste o la sucursal no son válidos.";
			$this->responder($data);
			return;
		}

		if ((int) $tipo->requiere_observacion === 1 && $motivo === "" && $observacion === "") {
			$data["mensaje"] = "Este tipo de ajuste requiere indicar un motivo u observación.";
			$this->responder($data);
			return;
		}

		if (mb_strlen($motivo) > 150 || mb_strlen($observacion) > 300) {
			$data["mensaje"] = "El motivo o la observación exceden la longitud permitida.";
			$this->responder($data);
			return;
		}

		if (!$this->validarLineas($datos->detalle, $data["mensaje"])) {
			$this->responder($data);
			return;
		}

		$estadoBorrador = $this->Inventario_ajuste_model->estadoId("BORRADOR");
		if (!$estadoBorrador) {
			$data["mensaje"] = "Configure el estado BORRADOR para ajustes de inventario.";
			$this->responder($data);
			return;
		}

		$this->db->trans_begin();

		$ajuste = new Inventario_ajuste_model(!empty($id) ? $id : "");
		$cabecera = (object) [
			"inventario_ajuste_tipo_id" => (int) $tipo->id,
			"sucursal_id"                => (int) $sucursal->id,
			"motivo"                     => $motivo !== "" ? $motivo : null,
			"observacion"                => $observacion !== "" ? $observacion : null
		];

		if (empty($id)) {
			$cabecera->numero = $this->Inventario_ajuste_model->generarNumero();
			$cabecera->inventario_ajuste_estado_id = $estadoBorrador;
		}

		$guardado = $ajuste->guardar($cabecera);
		if (!$guardado && !empty($id) && $this->db->trans_status() !== FALSE) {
			$guardado = true;
		}

		if ($guardado) {
			$guardado = $this->Inventario_ajuste_detalle_model->reemplazar($ajuste->getPK(), $datos->detalle);
		}

		if ($guardado && $this->db->trans_status() !== FALSE) {
			$this->db->trans_commit();
			$data["exito"] = 1;
			$data["mensaje"] = "Ajuste guardado en borrador.";
			$data["linea"] = $this->Inventario_ajuste_model->_buscar(["id" => $ajuste->getPK(), "uno" => true]);
		} else {
			$this->db->trans_rollback();
			$data["mensaje"] = $ajuste->getMensaje() ?: "No fue posible guardar el ajuste.";
		}

		$this->responder($data);
	}

	private function crearMovimiento($stockId, $tipoId, $cantidad, $ajuste, $observacion)
	{
		$movimiento = new Movimiento_model();

		return $movimiento->guardar([
			"stock_id"              => $stockId,
			"movimiento_tipo_id"    => $tipoId,
			"cantidad"              => round((float) $cantidad, 2),
			"inventario_ajuste_id"  => $ajuste->id,
			"observacion"           => $observacion
		]);
	}

	private function aplicarPositivo($ajuste, $detalle, $tipoMovimiento, &$mensaje)
	{
		foreach ($detalle as $linea) {
			$stock = new Stock_model();
			$guardado = $stock->guardar([
				"producto_id"              => $linea->producto_id,
				"unidad_medida_id"         => $linea->unidad_medida_id,
				"producto_presentacion_id" => $linea->producto_presentacion_id,
				"fecha_vence"              => $linea->fecha_vence,
				"cantidad"                 => round((float) $linea->cantidad, 2),
				"activo"                   => 1,
				"sucursal_id"              => $ajuste->sucursal_id
			]);

			if (!$guardado) {
				$mensaje = $stock->getMensaje() ?: "No fue posible aumentar el stock.";
				return false;
			}

			$observacion = "Ajuste positivo " . $ajuste->numero;
			if (!empty($linea->observacion)) {
				$observacion .= ": " . $linea->observacion;
			}

			if (!$this->crearMovimiento($stock->getPK(), $tipoMovimiento->id, $linea->cantidad, $ajuste, $observacion)) {
				$mensaje = "No fue posible registrar el movimiento positivo.";
				return false;
			}
		}

		return true;
	}

	private function aplicarNegativo($ajuste, $detalle, $tipoMovimiento, &$mensaje)
	{
		foreach ($detalle as $linea) {
			$restante = round((float) $linea->cantidad, 2);
			$lotes = $this->Stock_model->lotesDisponibles(
				$linea->producto_id,
				$linea->unidad_medida_id,
				$ajuste->sucursal_id,
				$linea->producto_presentacion_id,
				$linea->fecha_vence
			);

			foreach ($lotes as $lote) {
				if ($restante <= 0) {
					break;
				}

				$tomar = min($restante, round((float) $lote->cantidad, 2));
				if ($tomar <= 0) {
					continue;
				}

				if (!$this->Stock_model->descontar($lote->id, $tomar)) {
					$mensaje = "El stock cambió mientras se aplicaba el ajuste. Intente nuevamente.";
					return false;
				}

				$observacion = "Ajuste negativo " . $ajuste->numero;
				if (!empty($linea->observacion)) {
					$observacion .= ": " . $linea->observacion;
				}

				if (!$this->crearMovimiento($lote->id, $tipoMovimiento->id, -$tomar, $ajuste, $observacion)) {
					$mensaje = "No fue posible registrar el movimiento negativo.";
					return false;
				}

				$restante = round($restante - $tomar, 2);
			}

			if ($restante > 0) {
				$mensaje = "No hay stock suficiente para aplicar uno de los productos del ajuste.";
				return false;
			}
		}

		return true;
	}

	public function aplicar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() !== "post") {
			$data["mensaje"] = "Método incorrecto.";
			$this->responder($data);
			return;
		}

		$this->db->trans_begin();
		$ajuste = $this->Inventario_ajuste_model->bloquear($id);

		if (!$ajuste) {
			$data["mensaje"] = "El ajuste no existe.";
		} else if ($ajuste->codigo_estado !== "BORRADOR") {
			$data["mensaje"] = "Solo puede aplicar ajustes en borrador.";
		} else {
			$detalle = $this->Inventario_ajuste_detalle_model->_buscar(["inventario_ajuste_id" => $id]);
			$tipoMovimiento = $this->catalogo->verTiposMovimiento([
				"codigo" => $ajuste->naturaleza === "POSITIVO" ? "AJP" : "AJN",
				"uno"    => true
			]);

			if (empty($detalle)) {
				$data["mensaje"] = "El ajuste no tiene productos.";
			} else if (!$tipoMovimiento) {
				$data["mensaje"] = "Configure el tipo de movimiento " . ($ajuste->naturaleza === "POSITIVO" ? "AJP" : "AJN") . ".";
			} else {
				$continuar = $ajuste->naturaleza === "POSITIVO"
					? $this->aplicarPositivo($ajuste, $detalle, $tipoMovimiento, $data["mensaje"])
					: $this->aplicarNegativo($ajuste, $detalle, $tipoMovimiento, $data["mensaje"]);

				$estadoAplicado = $this->Inventario_ajuste_model->estadoId("APLICADO");
				if ($continuar && !$estadoAplicado) {
					$continuar = false;
					$data["mensaje"] = "Configure el estado APLICADO para ajustes de inventario.";
				}

				if ($continuar) {
					$continuar = $this->Inventario_ajuste_model->cambiarEstado($id, $estadoAplicado, [
						"fecha_aplicado"    => date("Y-m-d H:i:s"),
						"usuario_aplico_id" => $_SESSION["id"]
					]);
				}

				if ($continuar && $this->db->trans_status() !== FALSE) {
					$this->db->trans_commit();
					$data["exito"] = 1;
					$data["mensaje"] = "Ajuste aplicado al stock con éxito.";
					$data["linea"] = $this->Inventario_ajuste_model->_buscar(["id" => $id, "uno" => true]);
					$this->responder($data);
					return;
				}
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
		$ajuste = $this->Inventario_ajuste_model->bloquear($id);

		if (!$ajuste) {
			$data["mensaje"] = "El ajuste no existe.";
			$this->db->trans_rollback();
			$this->responder($data);
			return;
		}

		if (!in_array($ajuste->codigo_estado, ["BORRADOR", "APLICADO"], true)) {
			$data["mensaje"] = "El ajuste ya no puede anularse.";
			$this->db->trans_rollback();
			$this->responder($data);
			return;
		}

		$estadoAnulado = $this->Inventario_ajuste_model->estadoId("ANULADO");
		if (!$estadoAnulado) {
			$data["mensaje"] = "Configure el estado ANULADO para ajustes de inventario.";
			$this->db->trans_rollback();
			$this->responder($data);
			return;
		}

		if ($ajuste->codigo_estado === "BORRADOR") {
			$continuar = $this->Inventario_ajuste_model->cambiarEstado($id, $estadoAnulado, [
				"fecha_anulado"    => date("Y-m-d H:i:s"),
				"usuario_anulo_id" => $_SESSION["id"]
			]);

			if ($continuar && $this->db->trans_status() !== FALSE) {
				$this->db->trans_commit();
				$data["exito"] = 1;
				$data["mensaje"] = "Borrador de ajuste anulado.";
				$data["linea"] = $this->Inventario_ajuste_model->_buscar(["id" => $id, "uno" => true]);
			} else {
				$this->db->trans_rollback();
				$data["mensaje"] = "No fue posible anular el borrador.";
			}

			$this->responder($data);
			return;
		}

		$movimientos = $this->db
		->select("a.stock_id, a.cantidad")
		->join("stock b", "b.id = a.stock_id")
		->join("producto c", "c.id = b.producto_id")
		->where("a.inventario_ajuste_id", $id)
		->where("c.empresa_id", $_SESSION["empresa_id"])
		->order_by("a.id", "asc")
		->get("movimiento a")
		->result();

		$tipoPositivo = $this->catalogo->verTiposMovimiento(["codigo" => "AJP", "uno" => true]);
		$tipoNegativo = $this->catalogo->verTiposMovimiento(["codigo" => "AJN", "uno" => true]);
		$continuar = !empty($movimientos) && $tipoPositivo && $tipoNegativo;

		if (!$continuar) {
			$data["mensaje"] = "No existen movimientos para revertir o faltan los tipos AJP/AJN.";
		}

		foreach ($movimientos as $movimientoOriginal) {
			if (!$continuar) {
				break;
			}

			$stock = $this->Stock_model->bloquearParaAjuste($movimientoOriginal->stock_id);
			$cantidadOriginal = round((float) $movimientoOriginal->cantidad, 2);
			$cantidadReversa = -$cantidadOriginal;

			if (!$stock) {
				$continuar = false;
				$data["mensaje"] = "No se encontró una fila de stock relacionada con el ajuste.";
				break;
			}

			if ($cantidadReversa < 0) {
				$continuar = $this->Stock_model->descontar($stock->id, abs($cantidadReversa));
				$tipoMovimiento = $tipoNegativo;
				if (!$continuar) {
					$data["mensaje"] = "No hay stock suficiente para anular el ajuste positivo.";
				}
			} else {
				$continuar = $this->Stock_model->incrementar($stock->id, $cantidadReversa);
				$tipoMovimiento = $tipoPositivo;
			}

			if ($continuar) {
				$continuar = $this->crearMovimiento(
					$stock->id,
					$tipoMovimiento->id,
					$cantidadReversa,
					$ajuste,
					"Reversión del ajuste " . $ajuste->numero
				);
			}
		}

		if ($continuar) {
			$continuar = $this->Inventario_ajuste_model->cambiarEstado($id, $estadoAnulado, [
				"fecha_anulado"    => date("Y-m-d H:i:s"),
				"usuario_anulo_id" => $_SESSION["id"]
			]);
		}

		if ($continuar && $this->db->trans_status() !== FALSE) {
			$this->db->trans_commit();
			$data["exito"] = 1;
			$data["mensaje"] = "Ajuste anulado y stock revertido con éxito.";
			$data["linea"] = $this->Inventario_ajuste_model->_buscar(["id" => $id, "uno" => true]);
		} else {
			$this->db->trans_rollback();
			if (empty($data["mensaje"])) {
				$data["mensaje"] = "No fue posible anular el ajuste.";
			}
		}

		$this->responder($data);
	}
}

/* End of file Ajuste.php */
/* Location: ./application/controllers/inventario/Ajuste.php */
