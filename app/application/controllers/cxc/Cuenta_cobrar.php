<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cuenta_cobrar extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model([
			"cxc/Cuenta_cobrar_model",
			"cxc/Cuenta_cobrar_pago_model",
			"mnt/Empresa_model"
		]);
		$this->output->set_content_type("application/json");
	}

	public function index() { $this->output->set_status_header("404"); }
	private function responder($data) { $this->output->set_output(json_encode($data)); }

	private function fechaValida($fecha, $opcional=false)
	{
		if ($opcional && empty($fecha)) return true;
		$tmp = DateTime::createFromFormat("Y-m-d", (string) $fecha);
		return $tmp && $tmp->format("Y-m-d") === $fecha;
	}

	public function buscar()
	{
		$args = [
			"termino" => $this->input->get("termino", true),
			"cliente_id" => $this->input->get("cliente_id", true),
			"moneda_id" => $this->input->get("moneda_id", true),
			"estado" => $this->input->get("estado", true)
		];
		$lista = $this->Cuenta_cobrar_model->_buscar($args);
		$resumen = [];
		foreach ($lista as $row) {
			if ((int) $row->anulado === 1) continue;
			$clave = (string) $row->moneda_id;
			if (!isset($resumen[$clave])) {
				$resumen[$clave] = [
					"moneda_id" => $row->moneda_id,
					"codigo_moneda" => $row->codigo_moneda,
					"simbolo_moneda" => $row->simbolo_moneda,
					"total" => 0, "abono" => 0, "saldo" => 0, "vencido" => 0
				];
			}
			$resumen[$clave]["total"] += (float) $row->total;
			$resumen[$clave]["abono"] += (float) $row->abono;
			$resumen[$clave]["saldo"] += (float) $row->saldo;
			if ($row->estado === "VENCIDA") $resumen[$clave]["vencido"] += (float) $row->saldo;
		}
		$this->responder(["lista" => $lista, "resumen" => array_values($resumen)]);
	}

	public function get_datos()
	{
		$this->responder(["cat" => $this->Cuenta_cobrar_model->getCatalogos()]);
	}

	public function pagos($cuentaId="")
	{
		$cuenta = $this->Cuenta_cobrar_model->_buscar(["id" => $cuentaId, "uno" => true]);
		if (!$cuenta) { $this->responder(["lista" => [], "mensaje" => "La cuenta no existe."]); return; }
		$this->responder(["lista" => $this->Cuenta_cobrar_pago_model->_buscar(["cuenta_cobrar_id" => $cuentaId])]);
	}

	public function guardar()
	{
		$data = ["exito" => 0];
		if ($this->input->method() !== "post") { $data["mensaje"] = "Metodo incorrecto."; $this->responder($data); return; }
		$datos = json_decode(file_get_contents("php://input"));
		$total = isset($datos->total) ? round((float) $datos->total, 5) : 0;
		$creditoDias = isset($datos->credito_dias) ? filter_var($datos->credito_dias, FILTER_VALIDATE_INT) : false;

		if (!$datos || empty($datos->cliente_id) || empty($datos->moneda_id) || empty(trim((string) ($datos->factura_numero ?? ""))) ||
			!$this->fechaValida($datos->factura_fecha ?? null) || $creditoDias === false || $creditoDias < 0 || $total <= 0) {
			$data["mensaje"] = "Complete y revise los datos obligatorios de la cuenta."; $this->responder($data); return;
		}

		$cliente = $this->Cuenta_cobrar_model->clienteActivo((int) $datos->cliente_id);
		$moneda = $this->Cuenta_cobrar_model->monedaActiva((int) $datos->moneda_id);
		if (!$cliente || !$moneda) { $data["mensaje"] = "El cliente o la moneda no son validos."; $this->responder($data); return; }

		$fechaVence = date("Y-m-d", strtotime($datos->factura_fecha . " +" . $creditoDias . " days"));
		$cuenta = new Cuenta_cobrar_model();
		$guardado = $cuenta->guardar((object) [
			"cliente_id" => (int) $cliente->id,
			"factura_fecha" => $datos->factura_fecha,
			"factura_numero" => trim((string) $datos->factura_numero),
			"factura_documento" => !empty(trim((string) ($datos->factura_documento ?? ""))) ? trim($datos->factura_documento) : null,
			"credito_dias" => (int) $creditoDias,
			"fecha_vence" => $fechaVence,
			"total" => $total,
			"abono" => 0,
			"saldo" => $total,
			"moneda_id" => (int) $moneda->id,
			"venta_id" => null,
			"referencia" => !empty(trim((string) ($datos->referencia ?? ""))) ? trim($datos->referencia) : null,
			"origen" => 1,
			"anulado" => 0
		]);

		if ($guardado) {
			$data["exito"] = 1;
			$data["mensaje"] = "Cuenta por cobrar creada.";
			$data["linea"] = $this->Cuenta_cobrar_model->_buscar(["id" => $cuenta->getPK(), "uno" => true]);
		} else $data["mensaje"] = $cuenta->getMensaje() ?: "No fue posible crear la cuenta.";
		$this->responder($data);
	}

	public function pagar($cuentaId="")
	{
		$data = ["exito" => 0];
		if ($this->input->method() !== "post") { $data["mensaje"] = "Metodo incorrecto."; $this->responder($data); return; }
		$datos = json_decode(file_get_contents("php://input"));
		$monto = isset($datos->total) ? round((float) $datos->total, 5) : 0;
		$documentoFecha = $datos->documento_fecha ?? null;

		if (!$datos || empty($datos->forma_pago_id) || $monto <= 0 || !$this->fechaValida($documentoFecha, true)) {
			$data["mensaje"] = "Revise el monto, la forma de pago y la fecha del documento."; $this->responder($data); return;
		}
		if (!$this->Cuenta_cobrar_model->formaPagoActiva((int) $datos->forma_pago_id)) {
			$data["mensaje"] = "La forma de pago no es valida."; $this->responder($data); return;
		}

		$this->db->trans_begin();
		$cuenta = $this->Cuenta_cobrar_model->bloquear($cuentaId);
		if (!$cuenta) $data["mensaje"] = "La cuenta no existe.";
		else if ((int) $cuenta->anulado === 1) $data["mensaje"] = "No se puede abonar a una cuenta anulada.";
		else if ((float) $cuenta->saldo <= 0) $data["mensaje"] = "La cuenta ya esta pagada.";
		else if ($monto > round((float) $cuenta->saldo, 5)) $data["mensaje"] = "El abono no puede superar el saldo pendiente.";
		else {
			$pago = new Cuenta_cobrar_pago_model();
			$guardado = $pago->guardar((object) [
				"cuenta_cobrar_id" => (int) $cuenta->id,
				"forma_pago_id" => (int) $datos->forma_pago_id,
				"documento_fecha" => $documentoFecha ?: null,
				"documento_numero" => !empty(trim((string) ($datos->documento_numero ?? ""))) ? trim($datos->documento_numero) : null,
				"documento_comprobante" => !empty(trim((string) ($datos->documento_comprobante ?? ""))) ? trim($datos->documento_comprobante) : null,
				"total" => $monto,
				"anulado" => 0
			]);

			if ($guardado) {
				$recibo = $this->Cuenta_cobrar_pago_model->asignarRecibo($pago->getPK(), date("Y-m-d"));
				$guardado = !empty($recibo) && $this->Cuenta_cobrar_model->aplicarAbono($cuenta->id, $monto);
			}

			if ($guardado && $this->db->trans_status() !== FALSE) {
				$this->db->trans_commit();
				$data["exito"] = 1;
				$data["mensaje"] = "Abono registrado con recibo " . $recibo . ".";
				$data["linea"] = $this->Cuenta_cobrar_model->_buscar(["id" => $cuenta->id, "uno" => true]);
				$data["pago"] = $this->Cuenta_cobrar_pago_model->_buscar(["id" => $pago->getPK(), "uno" => true]);
				$this->responder($data);
				return;
			}
			$data["mensaje"] = "No fue posible registrar el abono.";
		}

		$this->db->trans_rollback();
		$this->responder($data);
	}

	public function imprimir($pagoId="")
	{
		$pago = $this->Cuenta_cobrar_pago_model->_buscar(["id" => $pagoId, "uno" => true]);
		if (!$pago) { $this->output->set_status_header(404); $this->responder(["mensaje" => "El recibo no existe."]); return; }
		$empresa = $this->Empresa_model->actual();
		$html = $this->load->view("cxc/recibo", ["empresa" => $empresa, "pago" => $pago], true);
		$bufferLevel = ob_get_level();
		ob_start();
		$mpdf = new \Mpdf\Mpdf(["format" => "Letter", "default_font" => "dejavusanscondensed", "margin_left" => 14, "margin_right" => 14, "margin_top" => 14, "margin_bottom" => 14, "tempDir" => sys_get_temp_dir()]);
		$mpdf->SetTitle($pago->recibo_numero);
		$mpdf->WriteHTML($html);
		$pdf = $mpdf->Output("", \Mpdf\Output\Destination::STRING_RETURN);
		while (ob_get_level() > $bufferLevel) { ob_end_clean(); }
		$this->output->set_content_type("application/pdf")
			->set_header('Content-Disposition: inline; filename="' . $pago->recibo_numero . '.pdf"')->set_output($pdf);
	}
}

/* End of file Cuenta_cobrar.php */
