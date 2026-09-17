<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Venta extends CI_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->model([
			"venta/Venta_model",
			"venta/Venta_detalle_model",
			"mnt/Empresa_model",
			"cxc/Cuenta_cobrar_model",
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

	public function buscar()
	{
		$this->responder([
			"lista" => $this->Venta_model->_buscar([
				"termino"         => $this->input->get("termino", true),
				"venta_estado_id" => $this->input->get("venta_estado_id", true),
				"sucursal_id"     => $this->input->get("sucursal_id", true),
				"fecha_desde"     => $this->input->get("fecha_desde", true),
				"fecha_hasta"     => $this->input->get("fecha_hasta", true)
			])
		]);
	}

	public function get_datos()
	{
		$this->responder(["cat" => $this->Venta_model->getCatalogos()]);
	}

	public function productos()
	{
		$sucursalId = (int) $this->input->get("sucursal_id", true);
		if ($sucursalId <= 0 || !$this->Venta_model->sucursalActiva($sucursalId)) {
			$this->responder(["lista" => [], "mensaje" => "Seleccione una sucursal válida."]);
			return;
		}

		$this->responder([
			"lista" => $this->Venta_model->productosDisponibles($sucursalId, [
				"termino"      => $this->input->get("termino", true),
				"categoria_id" => $this->input->get("categoria_id", true)
			])
		]);
	}

	public function detalle($id="")
	{
		$venta = $this->Venta_model->_buscar(["id" => $id, "uno" => true]);
		if (!$venta) {
			$this->responder(["lista" => [], "mensaje" => "La venta no existe."]);
			return;
		}

		$this->responder([
			"lista" => $this->Venta_detalle_model->_buscar(["venta_id" => $id])
		]);
	}

	public function imprimir($id="")
	{
		$venta = $this->Venta_model->_buscar(["id" => $id, "uno" => true]);
		if (!$venta) {
			$this->output->set_status_header(404);
			$this->responder(["mensaje" => "La venta no existe."]);
			return;
		}

		$empresa = $this->Empresa_model->actual();
		$detalle = $this->Venta_detalle_model->_buscar(["venta_id" => $id]);
		$logo = !empty($empresa->logo)
			? "https://lh3.googleusercontent.com/d/" . rawurlencode($empresa->logo)
			: null;
		$html = $this->load->view("venta/comprobante", [
			"empresa" => $empresa,
			"logo" => $logo,
			"venta" => $venta,
			"detalle" => $detalle
		], true);

		$bufferLevel = ob_get_level();
		ob_start();
		$mpdf = new \Mpdf\Mpdf([
			"format" => "Letter",
			"default_font" => "dejavusanscondensed",
			"margin_left" => 12,
			"margin_right" => 12,
			"margin_top" => 12,
			"margin_bottom" => 18,
			"tempDir" => sys_get_temp_dir()
		]);
		$numero = $venta->correlativo ?: "BORRADOR-" . $venta->id;
		$mpdf->SetTitle($numero);
		$mpdf->SetAuthor($empresa->nombre ?? "Logy");
		$mpdf->SetHTMLFooter('<table width="100%" style="font-size:9px;color:#6b7280"><tr><td>Venta ' . htmlspecialchars($numero, ENT_QUOTES, 'UTF-8') . '</td><td align="right">Página {PAGENO} de {nbpg}</td></tr></table>');
		$mpdf->WriteHTML($html);
		$pdf = $mpdf->Output("", \Mpdf\Output\Destination::STRING_RETURN);
		while (ob_get_level() > $bufferLevel) { ob_end_clean(); }

		$archivo = preg_replace('/[^A-Za-z0-9_-]/', '_', $numero) . ".pdf";
		$this->output
			->set_content_type("application/pdf")
			->set_header('Content-Disposition: inline; filename="' . $archivo . '"')
			->set_output($pdf);
	}

	private function prepararLineas($detalle, &$totales, &$mensaje)
	{
		if (!is_array($detalle) || empty($detalle)) {
			$mensaje = "Agregue al menos un producto a la venta.";
			return false;
		}

		$lineas = [];
		$productos = [];
		$totales = [
			"total_precio" => 0,
			"total_costo"  => 0,
			"ganancia"     => 0,
			"base"         => 0,
			"iva"          => 0,
			"isr"          => 0,
			"descuento"    => 0
		];

		foreach ($detalle as $entrada) {
			$productoId = isset($entrada->producto_id) ? (int) $entrada->producto_id : 0;
			$producto = $this->Venta_model->productoActivo($productoId);

			if (!$producto || isset($productos[$productoId])) {
				$mensaje = !$producto
					? "Uno de los productos no existe o está inactivo."
					: "No repita el mismo producto en la venta.";
				return false;
			}

			$decimalesCantidad = $producto->tipo_producto === "B" ? 2 : 5;
			$cantidad = isset($entrada->cantidad)
				? round((float) $entrada->cantidad, $decimalesCantidad)
				: 0;

			if ($cantidad <= 0) {
				$mensaje = "Revise las cantidades de la venta.";
				return false;
			}

			$precio = round((float) $producto->precio, 5);
			$costo = round((float) $producto->costo, 5);
			$porcentajeDescuento = isset($entrada->descuento)
				? max(0, min(100, round((float) $entrada->descuento, 5)))
				: 0;
			$totalPrecio = round($cantidad * $precio, 5);
			$totalCosto = round($cantidad * $costo, 5);
			$descuentoTotal = round($totalPrecio * ($porcentajeDescuento / 100), 5);
			$base = round($totalPrecio - $descuentoTotal, 5);
			$ganancia = round($base - $totalCosto, 5);

			$lineas[] = [
				"producto_id"             => $producto->id,
				"unidad_medida_id"        => $producto->unidad_medida_id,
				"producto_precio_costo_id" => null,
				"cantidad"                => $cantidad,
				"precio"                  => $precio,
				"costo"                   => $costo,
				"total_precio"            => $totalPrecio,
				"total_costo"             => $totalCosto,
				"ganancia"                => $ganancia,
				"base"                    => $base,
				"iva"                     => 0,
				"isr"                     => 0,
				"descuento"               => $porcentajeDescuento,
				"descuento_total"         => $descuentoTotal,
				"tipo_producto"           => $producto->tipo_producto
			];

			$totales["total_precio"] += $totalPrecio;
			$totales["total_costo"] += $totalCosto;
			$totales["ganancia"] += $ganancia;
			$totales["base"] += $base;
			$totales["descuento"] += $descuentoTotal;
			$productos[$productoId] = true;
		}

		foreach ($totales as $campo => $valor) {
			$totales[$campo] = round($valor, 5);
		}

		return $lineas;
	}

	private function crearMovimiento($stockId, $tipoId, $cantidad, $detalleId, $correlativo, $observacion=null)
	{
		$movimiento = new Movimiento_model();

		return $movimiento->guardar([
			"stock_id"           => $stockId,
			"movimiento_tipo_id" => $tipoId,
			"cantidad"           => round((float) $cantidad, 2),
			"venta_detalle_id"   => $detalleId,
			"observacion"        => $observacion ?: "Venta " . $correlativo
		]);
	}

	private function descontarInventario($venta, $lineas, $tipoMovimiento, &$mensaje)
	{
		foreach ($lineas as $linea) {
			if ($linea->tipo_producto !== "B") {
				continue;
			}

			$restante = round((float) $linea->cantidad, 2);
			$lotes = $this->Stock_model->lotesDisponibles(
				$linea->producto_id,
				$linea->unidad_medida_id,
				$venta->sucursal_id
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
					$mensaje = "La existencia cambió mientras se facturaba. Intente nuevamente.";
					return false;
				}

				if (!$this->crearMovimiento($lote->id, $tipoMovimiento->id, -$tomar, $linea->id, $venta->correlativo)) {
					$mensaje = "No fue posible registrar la salida de inventario.";
					return false;
				}

				$restante = round($restante - $tomar, 2);
			}

			if ($restante > 0) {
				$mensaje = "No hay existencia suficiente para completar uno de los productos.";
				return false;
			}
		}

		return true;
	}

	private function procesar($id, $facturar)
	{
		$data = ["exito" => 0];

		if ($this->input->method() !== "post") {
			$data["mensaje"] = "Método incorrecto.";
			$this->responder($data);
			return;
		}

		$entrada = json_decode(file_get_contents("php://input"));
		if (!$entrada || empty($entrada->sucursal_id) || empty($entrada->venta_serie_id) ||
			empty($entrada->moneda_id) || empty($entrada->forma_pago_id) || !isset($entrada->detalle)) {
			$data["mensaje"] = "Complete los datos del encabezado y agregue productos.";
			$this->responder($data);
			return;
		}

		$sucursal = $this->Venta_model->sucursalActiva((int) $entrada->sucursal_id);
		$serie = $this->Venta_model->catalogoEmpresa("venta_serie", (int) $entrada->venta_serie_id);
		$moneda = $this->Venta_model->catalogoEmpresa("moneda", (int) $entrada->moneda_id);
		$formaPago = $this->Venta_model->catalogoEmpresa("forma_pago", (int) $entrada->forma_pago_id);
		$cliente = !empty($entrada->cliente_id)
			? $this->Venta_model->catalogoEmpresa("cliente", (int) $entrada->cliente_id)
			: null;

		if (!$sucursal || !$serie || !$moneda || !$formaPago || (!empty($entrada->cliente_id) && !$cliente)) {
			$data["mensaje"] = "La sucursal, serie, cliente, moneda o forma de pago no es válida.";
			$this->responder($data);
			return;
		}

		$esCredito = strcasecmp(trim((string) $formaPago->nombre), "Crédito") === 0;
		if ($facturar && $esCredito && !$cliente) {
			$data["mensaje"] = "Seleccione un cliente para facturar una venta a crédito.";
			$this->responder($data);
			return;
		}

		$totales = [];
		$lineas = $this->prepararLineas($entrada->detalle, $totales, $data["mensaje"]);
		if ($lineas === false) {
			$this->responder($data);
			return;
		}

		$estadoCreado = $this->Venta_model->estadoId("Creado");
		$estadoFacturada = $this->Venta_model->estadoId("Facturada");
		if (!$estadoCreado || ($facturar && !$estadoFacturada)) {
			$data["mensaje"] = "Configure los estados Creado y Facturada para ventas.";
			$this->responder($data);
			return;
		}

		$tipoCambio = isset($entrada->tipo_cambio) ? round((float) $entrada->tipo_cambio, 5) : 1;
		if ($tipoCambio <= 0) {
			$data["mensaje"] = "El tipo de cambio debe ser mayor que cero.";
			$this->responder($data);
			return;
		}

		$this->db->trans_begin();
		$ventaActual = null;
		if (!empty($id)) {
			$ventaActual = $this->Venta_model->bloquear($id);
			if (!$ventaActual || strcasecmp($ventaActual->nombre_estado, "Creado") !== 0) {
				$this->db->trans_rollback();
				$data["mensaje"] = "Solo puede modificar ventas en estado Creado.";
				$this->responder($data);
				return;
			}
		}

		$datosVenta = [
			"empresa_id"      => $_SESSION["empresa_id"],
			"sucursal_id"     => (int) $sucursal->id,
			"usuario_id"      => $_SESSION["id"],
			"moneda_id"       => (int) $moneda->id,
			"cliente_id"      => $cliente ? (int) $cliente->id : null,
			"forma_pago_id"   => (int) $formaPago->id,
			"venta_estado_id" => $estadoCreado,
			"venta_serie_id"  => (int) $serie->id,
			"vendedor_id"     => $_SESSION["id"],
			"total_precio"    => $totales["total_precio"],
			"total_costo"     => $totales["total_costo"],
			"ganancia"        => $totales["ganancia"],
			"base"            => $totales["base"],
			"iva"             => 0,
			"isr"             => 0,
			"descuento"       => $totales["descuento"],
			"tipo_cambio"     => $tipoCambio,
			"referencia"      => isset($entrada->referencia) && trim($entrada->referencia) !== ""
				? mb_substr(trim($entrada->referencia), 0, 300)
				: null,
			"certificada"     => 0,
			"anulado"         => 0
		];

		if (empty($id)) {
			$venta = new Venta_model();
			if (!$venta->guardar($datosVenta)) {
				$this->db->trans_rollback();
				$data["mensaje"] = $venta->getMensaje() ?: "No fue posible crear la venta.";
				$this->responder($data);
				return;
			}
			$id = $venta->getPK();
		} else if (!$this->Venta_model->actualizar($id, $datosVenta)) {
			$this->db->trans_rollback();
			$data["mensaje"] = "No fue posible actualizar la venta.";
			$this->responder($data);
			return;
		}

		$detalleGuardado = $this->Venta_detalle_model->reemplazar($id, $lineas);
		if ($detalleGuardado === false) {
			$this->db->trans_rollback();
			$data["mensaje"] = $this->Venta_detalle_model->getMensaje() ?: "No fue posible guardar el detalle.";
			$this->responder($data);
			return;
		}

		if ($facturar) {
			$serieBloqueada = $this->Venta_model->bloquearSerie($serie->id);
			$tipoMovimiento = $this->catalogo->verTiposMovimiento(["codigo" => "VTA", "uno" => true]);
			$siguiente = $serieBloqueada && (int) $serieBloqueada->correlativo === 0
				? (int) $serieBloqueada->inicio
				: ($serieBloqueada ? (int) $serieBloqueada->correlativo + 1 : 0);

			if (!$serieBloqueada || $siguiente <= 0 || $siguiente > (int) $serieBloqueada->fin) {
				$this->db->trans_rollback();
				$data["mensaje"] = "La serie seleccionada no tiene correlativos disponibles.";
				$this->responder($data);
				return;
			}

			if (!$tipoMovimiento) {
				$this->db->trans_rollback();
				$data["mensaje"] = "Configure el tipo de movimiento VTA.";
				$this->responder($data);
				return;
			}

			$digitos = max(strlen((string) $serieBloqueada->inicio), strlen((string) $serieBloqueada->fin));
			$correlativo = strtoupper($serieBloqueada->codigo) . "-" . str_pad($siguiente, $digitos, "0", STR_PAD_LEFT);
			$ventaFacturada = (object) [
				"id"             => $id,
				"sucursal_id"    => $sucursal->id,
				"correlativo"    => $correlativo
			];

			if (!$this->descontarInventario($ventaFacturada, $detalleGuardado, $tipoMovimiento, $data["mensaje"])) {
				$this->db->trans_rollback();
				$this->responder($data);
				return;
			}

			$this->db
			->where("id", $serieBloqueada->id)
			->where("empresa_id", $_SESSION["empresa_id"])
			->update("venta_serie", ["correlativo" => $siguiente]);

			$fechaFactura = date("Y-m-d");
			$facturada = $this->Venta_model->actualizar($id, [
				"venta_estado_id" => $estadoFacturada,
				"correlativo"      => $correlativo,
				"factura_fecha"    => $fechaFactura,
				"factura_numero"   => (string) $siguiente,
				"factura_serie"    => strtoupper($serieBloqueada->codigo)
			]);

			if (!$facturada || $this->db->trans_status() === FALSE) {
				$this->db->trans_rollback();
				$data["mensaje"] = "No fue posible finalizar la venta.";
				$this->responder($data);
				return;
			}

			if ($esCredito) {
				$creditoDias = max(0, (int) $cliente->credito_dias);
				$cuenta = new Cuenta_cobrar_model();
				$cuentaGuardada = $cuenta->guardar((object) [
					"cliente_id"       => (int) $cliente->id,
					"factura_fecha"    => $fechaFactura,
					"factura_numero"   => $correlativo,
					"factura_documento" => null,
					"credito_dias"     => $creditoDias,
					"fecha_vence"      => date("Y-m-d", strtotime($fechaFactura . " +" . $creditoDias . " days")),
					"total"            => $totales["total_precio"],
					"abono"            => 0,
					"saldo"            => $totales["total_precio"],
					"moneda_id"        => (int) $moneda->id,
					"venta_id"         => (int) $id,
					"referencia"       => $datosVenta["referencia"],
					"origen"           => 2,
					"anulado"          => 0
				]);

				if (!$cuentaGuardada || $this->db->trans_status() === FALSE) {
					$this->db->trans_rollback();
					$data["mensaje"] = "No fue posible generar la cuenta por cobrar.";
					$this->responder($data);
					return;
				}
			}
		}

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			$data["mensaje"] = "No fue posible guardar la venta.";
		} else {
			$this->db->trans_commit();
			$data["exito"] = 1;
			$data["mensaje"] = $facturar ? "Venta facturada con éxito." : "Venta guardada en estado Creado.";
			$data["linea"] = $this->Venta_model->_buscar(["id" => $id, "uno" => true]);
			$data["detalle"] = $this->Venta_detalle_model->_buscar(["venta_id" => $id]);
		}

		$this->responder($data);
	}

	public function guardar($id="")
	{
		$this->procesar($id, false);
	}

	public function facturar($id="")
	{
		$this->procesar($id, true);
	}

	public function pagar($id="")
	{
		$data = ["exito" => 0];
		if ($this->input->method() !== "post" || empty($id)) {
			$data["mensaje"] = "Solicitud incorrecta.";
			$this->responder($data);
			return;
		}

		$this->db->trans_begin();
		$venta = $this->Venta_model->bloquear($id);
		$estadoPagada = $this->Venta_model->estadoId("Pagada");

		if (!$venta || strcasecmp($venta->nombre_estado, "Facturada") !== 0) {
			$this->db->trans_rollback();
			$data["mensaje"] = "Solo puede marcar como pagada una venta facturada.";
			$this->responder($data);
			return;
		}

		if (!$estadoPagada || !$this->Venta_model->actualizar($id, ["venta_estado_id" => $estadoPagada])) {
			$this->db->trans_rollback();
			$data["mensaje"] = "No fue posible cambiar la venta a Pagada.";
			$this->responder($data);
			return;
		}

		$this->db->trans_commit();
		$data["exito"] = 1;
		$data["mensaje"] = "Venta marcada como pagada.";
		$data["linea"] = $this->Venta_model->_buscar(["id" => $id, "uno" => true]);
		$this->responder($data);
	}

	public function anular($id="")
	{
		$data = ["exito" => 0];
		if ($this->input->method() !== "post" || empty($id)) {
			$data["mensaje"] = "Solicitud incorrecta.";
			$this->responder($data);
			return;
		}

		$entrada = json_decode(file_get_contents("php://input"));
		$motivo = isset($entrada->motivo) ? trim($entrada->motivo) : "";
		if ($motivo === "") {
			$data["mensaje"] = "Indique el motivo de la anulación.";
			$this->responder($data);
			return;
		}

		$this->db->trans_begin();
		$venta = $this->Venta_model->bloquear($id);
		$estadoAnulada = $this->Venta_model->estadoId("Anulada");

		if (!$venta || strcasecmp($venta->nombre_estado, "Anulada") === 0) {
			$this->db->trans_rollback();
			$data["mensaje"] = !$venta ? "La venta no existe." : "La venta ya está anulada.";
			$this->responder($data);
			return;
		}

		if (!$estadoAnulada) {
			$this->db->trans_rollback();
			$data["mensaje"] = "Configure el estado Anulada para ventas.";
			$this->responder($data);
			return;
		}

		$continuar = true;
		if (strcasecmp($venta->nombre_estado, "Creado") !== 0) {
			$tipoAnulacion = $this->catalogo->verTiposMovimiento(["codigo" => "VAN", "uno" => true]);
			$movimientos = $this->db
			->select("a.stock_id, a.cantidad, a.venta_detalle_id")
			->join("venta_detalle b", "b.id = a.venta_detalle_id")
			->join("venta c", "c.id = b.venta_id")
			->join("movimiento_tipo d", "d.id = a.movimiento_tipo_id")
			->where("b.venta_id", $id)
			->where("c.empresa_id", $_SESSION["empresa_id"])
			->where("d.codigo", "VTA")
			->where("a.cantidad <", 0)
			->order_by("a.id", "asc")
			->get("movimiento a")
			->result();

			if (!$tipoAnulacion) {
				$continuar = false;
				$data["mensaje"] = "Configure el tipo de movimiento VAN.";
			}

			foreach ($movimientos as $movimiento) {
				if (!$continuar) break;
				$stock = $this->Stock_model->bloquearParaAjuste($movimiento->stock_id);
				$cantidad = abs(round((float) $movimiento->cantidad, 2));
				$continuar = $stock && $this->Stock_model->incrementar($stock->id, $cantidad);
				if ($continuar) {
					$continuar = $this->crearMovimiento(
						$stock->id, $tipoAnulacion->id, $cantidad,
						$movimiento->venta_detalle_id, $venta->correlativo,
						"Anulación de venta " . $venta->correlativo
					);
				}
				if (!$continuar) $data["mensaje"] = "No fue posible devolver la existencia de la venta.";
			}
		}

		if ($continuar) {
			$continuar = $this->Venta_model->actualizar($id, [
				"venta_estado_id" => $estadoAnulada,
				"anulado"         => 1,
				"anulado_fecha"   => date("Y-m-d H:i:s"),
				"anulado_usuario" => $_SESSION["id"],
				"anulado_motivo"  => mb_substr($motivo, 0, 500)
			]);
		}

		if ($continuar && $this->db->trans_status() !== FALSE) {
			$this->db->trans_commit();
			$data["exito"] = 1;
			$data["mensaje"] = strcasecmp($venta->nombre_estado, "Creado") === 0
				? "Borrador anulado."
				: "Venta anulada y existencia devuelta.";
			$data["linea"] = $this->Venta_model->_buscar(["id" => $id, "uno" => true]);
		} else {
			$this->db->trans_rollback();
			if (empty($data["mensaje"])) $data["mensaje"] = "No fue posible anular la venta.";
		}

		$this->responder($data);
	}
}

/* End of file Venta.php */
