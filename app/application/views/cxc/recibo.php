<?php defined('BASEPATH') OR exit('No direct script access allowed');
$esc = function ($valor) { return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8'); };
$fecha = function ($valor) { return $valor ? date('d/m/Y', strtotime($valor)) : '-'; };
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<style>
		body { font-family: sans-serif; color: #202124; font-size: 12px; }
		table { width: 100%; border-collapse: collapse; }
		.header td { vertical-align: top; }
		.company { font-size: 17px; font-weight: bold; }
		.title { font-size: 22px; font-weight: bold; text-align: center; }
		.number { text-align: right; line-height: 1.6; }
		.box { margin-top: 18px; border: 1px solid #4b5563; }
		.box td { padding: 7px 9px; border-bottom: 1px solid #d1d5db; }
		.label { width: 28%; color: #6b7280; }
		.amount { margin-top: 18px; background: #374151; color: #fff; font-size: 18px; font-weight: bold; }
		.amount td { padding: 10px; }
		.note { margin-top: 18px; color: #6b7280; line-height: 1.5; }
		.signatures { margin-top: 65px; }
		.signatures td { width: 45%; text-align: center; border-top: 1px solid #111; padding-top: 6px; }
		.signatures .space { width: 10%; border: 0; }
	</style>
</head>
<body>
	<table class="header"><tr>
		<td width="40%"><div class="company"><?= $esc($empresa->nombre ?? 'Empresa') ?></div><div><?= $esc($empresa->direccion ?? '') ?></div><div><?= $esc($empresa->identificacion ?? '') ?></div></td>
		<td width="30%" class="title">RECIBO</td>
		<td width="30%" class="number"><strong><?= $esc($pago->recibo_numero) ?></strong><br>Fecha: <?= $esc($fecha($pago->fecha)) ?></td>
	</tr></table>

	<table class="box">
		<tr><td class="label">Recibimos de</td><td><strong><?= $esc($pago->nombre_cliente) ?></strong></td></tr>
		<tr><td class="label">Identificacion</td><td><?= $esc($pago->identificacion ?: '-') ?></td></tr>
		<tr><td class="label">Factura</td><td><?= $esc($pago->factura_numero) ?></td></tr>
		<tr><td class="label">Forma de pago</td><td><?= $esc($pago->nombre_forma_pago) ?></td></tr>
		<tr><td class="label">Referencia</td><td><?= $esc($pago->documento_numero ?: '-') ?></td></tr>
		<tr><td class="label">Fecha del documento</td><td><?= $esc($fecha($pago->documento_fecha)) ?></td></tr>
		<tr><td class="label">Observacion</td><td><?= $esc($pago->documento_comprobante ?: '-') ?></td></tr>
	</table>

	<table class="amount"><tr><td>Importe recibido</td><td style="text-align:right"><?= $esc($pago->simbolo_moneda) ?> <?= number_format((float) $pago->total, 2) ?> <?= $esc($pago->codigo_moneda) ?></td></tr></table>
	<div class="note">Este recibo corresponde a un abono aplicado a la factura indicada. Documento generado por <?= $esc($pago->nombre_usuario) ?>.</div>
	<table class="signatures"><tr><td>Recibido por</td><td class="space"></td><td>Firma del cliente</td></tr></table>
</body>
</html>
