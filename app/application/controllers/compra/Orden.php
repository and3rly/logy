<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Orden extends CI_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->model([
			"compra/Compra_model",
			"compra/Compra_detalle_model",
			"inventario/Stock_model",
			"inventario/Movimiento_model",
			"mnt/Empresa_model",
			"mnt/Producto_model"
		]);

		$this->output->set_content_type("application/json");
	}

	public function index()
	{
		$this->output->set_status_header("404");
	}

	public function buscar()
	{
		$data = [
			"lista" => $this->Compra_model->_buscar($_GET)
		];

		$this->output->set_output(json_encode($data));
	}

	public function get_datos()
	{
		$data = [
			"cat" => [
				"marcas"     => $this->catalogo->verMarcas(),
				"categorias" => $this->catalogo->verCategorias(),
				"productos"  => $this->Producto_model->_buscar(),
				"proveedores" => $this->catalogo->verProveedores(),
				"formas_pago" => $this->catalogo->verFormasPago(),
				"monedas"     => $this->catalogo->verMonedas(),
				"sucursales"  => $this->catalogo->verSucursales()
			]
		];

		$this->output->set_output(json_encode($data));
	}

	public function guardar($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$datos = json_decode(file_get_contents("php://input"));

			if (!empty($id)) {
				$actual = $this->Compra_model->_buscar([
					"id"  => $id,
					"uno" => true
				]);

				if (!$actual) {
					$data["mensaje"] = "La orden de compra no existe.";
					$this->output->set_output(json_encode($data));
					return;
				}

				if ((int) $actual->compra_estado_id !== 1 || (int) $actual->anulado === 1) {
					$data["mensaje"] = "Solo puede modificar órdenes de compra en estado Creada.";
					$this->output->set_output(json_encode($data));
					return;
				}
			}

			if (verPropiedad($datos, "proveedor_id") &&
				verPropiedad($datos, "forma_pago_id") &&
				verPropiedad($datos, "moneda_id") &&
				verPropiedad($datos, "sucursal_id") &&
				verPropiedad($datos, "detalle")) {

				$total = 0;

				foreach ($datos->detalle as $linea) {
					$linea->total_costo = $linea->cantidad * $linea->precio_costo;

					if (!verPropiedad($linea, "anulado", 0)) {
						$total += $linea->total_costo;
					}
				}

				$datos->total_costo = $total;

				$this->db->trans_begin();

				$compra = new Compra_model($id);
				$guardado = $compra->guardar($datos);

				if (!$guardado && !empty($id) && $this->db->trans_status() !== FALSE) {
					$guardado = true;
				}

				if ($guardado) {
					foreach ($datos->detalle as $linea) {
						$detalleId = verPropiedad($linea, "id", "");
						$detalle = new Compra_detalle_model($detalleId);
						$linea->compra_id = $compra->getPK();
						$linea->anulado = verPropiedad($linea, "anulado", 0);
						$detalleGuardado = $detalle->guardar($linea);

						if (!$detalleGuardado &&
							(empty($detalleId) || $this->db->trans_status() === FALSE)) {
							$guardado = false;
							$data["mensaje"] = $detalle->getMensaje();
							break;
						}
					}
				} else {
					$data["mensaje"] = $compra->getMensaje();
				}

				if ($guardado && $this->db->trans_status() !== FALSE) {
					$this->db->trans_commit();

					$data["exito"] = 1;
					$data["mensaje"] = "Orden de compra guardada con éxito.";
					$data["linea"] = $this->Compra_model->_buscar([
						"id"  => $compra->getPK(),
						"uno" => true
					]);
				} else {
					$this->db->trans_rollback();
				}
			} else {
				$data["mensaje"] = "Complete los campos marcados con *.";
			}
		} else {
			$data["mensaje"] = "Método incorrecto.";
		}

		$this->output->set_output(json_encode($data));
	}

	public function anular($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$this->db->trans_begin();

			$compra = new Compra_model($id);

			if ($compra->anular() && $this->db->trans_status() !== FALSE) {
				$this->db->trans_commit();

				$data["exito"] = 1;
				$data["mensaje"] = "Orden de compra anulada con éxito.";
				$data["linea"] = $this->Compra_model->_buscar([
					"id"  => $id,
					"uno" => true
				]);
			} else {
				$this->db->trans_rollback();
				$data["mensaje"] = $compra->getMensaje();
			}
		} else {
			$data["mensaje"] = "Método de envio incorrecto.";
		}		

		$this->output->set_output(json_encode($data));
	}

	public function recibir($id="")
	{
		$data = ["exito" => 0];

		if ($this->input->method() === "post") {
			$this->db->trans_begin();

			$compra    = new Compra_model($id);
			$continuar = $compra->puedeRecibir();

			$tipo      = $this->catalogo->verTiposMovimiento([
				"codigo" => "REC",
				"uno"    => true
			]);

			$detalle = $this->Compra_detalle_model->_buscar([
				"compra_id" => $id
			]);

			if (!$continuar) {
				$data["mensaje"] = $compra->getMensaje();
			} else if (!$tipo) {
				$continuar = false;
				$data["mensaje"] = "Configure el tipo de movimiento REC.";
			} else if (empty($detalle)) {
				$continuar = false;
				$data["mensaje"] = "La orden de compra no tiene detalle para recibir.";
			}

			if ($continuar) {
				foreach ($detalle as $linea) {
					$stock = new Stock_model();
					$stockGuardado = $stock->guardar([
						"producto_id"              => $linea->producto_id,
						"unidad_medida_id"         => $linea->unidad_medida_id,
						"producto_presentacion_id" => $linea->producto_presentacion_id,
						"fecha_vence"              => $linea->fecha_vence,
						"cantidad"                 => $linea->cantidad,
						"activo"                   => 1,
						"sucursal_id"              => $compra->sucursal_id
					]);

					if ($stockGuardado) {
						$movimiento = new Movimiento_model();
						$continuar = $movimiento->guardar([
							"stock_id"           => $stock->getPK(),
							"movimiento_tipo_id" => $tipo->id,
							"cantidad"           => $linea->cantidad,
							"compra_id"          => $compra->getPK(),
							"observacion"        => "Recepción de orden " . $compra->numero
						]);
					} else {
						$continuar = false;
						$data["mensaje"] = $stock->getMensaje();
					}

					if (!$continuar) {
						if (empty($data["mensaje"])) {
							$data["mensaje"] = $movimiento->getMensaje();
						}
						break;
					}
				}
			}

			if ($continuar) {
				$continuar = $compra->recibir();

				if (!$continuar) {
					$data["mensaje"] = $compra->getMensaje();
				}
			}

			if ($continuar && $this->db->trans_status() !== FALSE) {
				$this->db->trans_commit();

				$data["exito"] = 1;
				$data["mensaje"] = "Orden de compra recibida con éxito.";
				$data["linea"] = $this->Compra_model->_buscar([
					"id"  => $id,
					"uno" => true
				]);
			} else {
				$this->db->trans_rollback();
			}
		} else {
			$data["mensaje"] = "Método de envío incorrecto.";
		}

		$this->output->set_output(json_encode($data));
	}

	public function imprimir($id="")
	{
		$compra = $this->Compra_model->_buscar([
			"id"  => $id,
			"uno" => true
		]);

		$empresa = $this->Empresa_model->actual();

		$logo = !empty($empresa->logo)
			? "https://lh3.googleusercontent.com/d/" . rawurlencode($empresa->logo)
			: null;

		$detalle = $this->Compra_detalle_model->_buscar([
			"compra_id" => $compra->id
		]);

		$html = $this->load->view("compra/orden", [
			"empresa" => $empresa,
			"logo"    => $logo,
			"compra"  => $compra,
			"detalle" => $detalle
		], true);

		$mpdf = new \Mpdf\Mpdf([
			"format"        => "Letter",
			"margin_left"   => 12,
			"margin_right"  => 12,
			"margin_top"    => 12,
			"margin_bottom" => 18,
			"tempDir"       => sys_get_temp_dir()
		]);

		$mpdf->SetTitle($compra->numero);
		$mpdf->SetAuthor($empresa->nombre ?? "Logy");
		$mpdf->SetHTMLFooter('
			<table width="100%" style="font-size: 9px; color: #6b7280;">
				<tr>
					<td>Orden de compra ' . htmlspecialchars($compra->numero) . '</td>
					<td align="right">Página {PAGENO} de {nbpg}</td>
				</tr>
			</table>
		');
		$mpdf->WriteHTML($html);

		$pdf = $mpdf->Output("", \Mpdf\Output\Destination::STRING_RETURN);
		$archivo = preg_replace('/[^A-Za-z0-9_-]/', '_', $compra->numero) . ".pdf";

		$this->output
		->set_content_type("application/pdf")
		->set_header('Content-Disposition: inline; filename="' . $archivo . '"')
		->set_output($pdf);
	}
}

/* End of file Orden.php */
/* Location: ./application/controllers/compra/Orden.php */ ?>
