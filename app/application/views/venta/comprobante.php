<?php defined('BASEPATH') OR exit('No direct script access allowed');
$esc = function ($valor) { return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8'); };
$simbolo = $venta->simbolo_moneda ?: 'Q';
$numero = $venta->correlativo ?: 'BORRADOR-' . $venta->id;
$fecha = $venta->factura_fecha ?: $venta->fecha;
$subtotal = 0;
foreach ($detalle as $linea) { $subtotal += (float) $linea->total_precio; }
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<style>
		body{font-family:sans-serif;color:#1f2937;font-size:11px}table{width:100%;border-collapse:collapse}.header td{vertical-align:top}.logo{max-width:115px;max-height:58px;margin-bottom:6px}.company{font-size:17px;font-weight:bold}.muted{color:#6b7280}.title{text-align:center;font-size:21px;font-weight:bold;color:#374151}.document td{padding:2px 3px}.text-right{text-align:right}.text-center{text-align:center}.info{margin-top:14px;border:1px solid #9ca3af}.info td{padding:6px 8px;border-right:1px solid #d1d5db;vertical-align:top}.info td:last-child{border-right:0}.info-label{margin-bottom:3px;color:#6b7280;font-size:9px;text-transform:uppercase}.info-value{font-weight:bold;line-height:1.35}.detail{margin-top:14px;table-layout:fixed}.detail th{padding:6px;background:#4b5563;color:#fff;text-align:left;font-size:10px}.detail td{padding:6px;border-bottom:1px solid #d1d5db}.totals{width:38%;margin:14px 0 0 auto}.totals td{padding:3px 5px}.total td{border-top:1px solid #374151;padding-top:7px;font-size:14px;font-weight:bold}.reference{margin-top:18px;border-top:1px solid #d1d5db;padding-top:9px;line-height:1.5}.status{display:inline-block;margin-top:7px;border:1px solid #9ca3af;background:#f3f4f6;padding:3px 7px;font-size:9px;font-weight:bold}.cancelled{border-color:#dc2626;background:#fef2f2;color:#dc2626}
	</style>
</head>
<body>
	<table class="header"><tr>
		<td width="45%">
			<?php if ($logo): ?><img src="<?= $esc($logo) ?>" class="logo" alt="Logo"><?php endif; ?>
			<div class="company"><?= $esc($empresa->nombre ?? 'Empresa') ?></div>
			<div class="muted"><?= $esc($empresa->direccion ?? '') ?></div>
			<?php if (!empty($empresa->identificacion)): ?><div>Identificación: <?= $esc($empresa->identificacion) ?></div><?php endif; ?>
			<?php if (!empty($empresa->telefono)): ?><div>Tel. <?= $esc($empresa->telefono) ?></div><?php endif; ?>
		</td>
		<td width="30%" class="title">COMPROBANTE DE VENTA</td>
		<td width="25%"><table class="document">
			<tr><td>Fecha</td><td class="text-right"><?= $esc(date('d/m/Y', strtotime($fecha))) ?></td></tr>
			<tr><td>No.</td><td class="text-right"><strong><?= $esc($numero) ?></strong></td></tr>
			<tr><td>Estado</td><td class="text-right"><?= $esc($venta->nombre_estado) ?></td></tr>
		</table></td>
	</tr></table>

	<table class="info"><tr>
		<td width="34%"><div class="info-label">Cliente</div><div class="info-value"><?= $esc($venta->nombre_cliente ?: 'Consumidor final') ?><?php if ($venta->identificacion_cliente): ?><br><?= $esc($venta->identificacion_cliente) ?><?php endif; ?></div></td>
		<td width="22%"><div class="info-label">Forma de pago</div><div class="info-value"><?= $esc($venta->nombre_forma_pago) ?></div></td>
		<td width="22%"><div class="info-label">Vendedor</div><div class="info-value"><?= $esc($venta->nombre_vendedor ?: $venta->nombre_usuario) ?></div></td>
		<td width="22%"><div class="info-label">Sucursal</div><div class="info-value"><?= $esc($venta->nombre_sucursal) ?></div></td>
	</tr></table>

	<table class="detail">
		<thead><tr><th width="14%">Código</th><th width="34%">Descripción</th><th width="12%" class="text-center">Cantidad</th><th width="15%" class="text-right">Precio</th><th width="10%" class="text-right">Desc.</th><th width="15%" class="text-right">Total</th></tr></thead>
		<tbody><?php foreach ($detalle as $linea): ?><tr>
			<td><?= $esc($linea->codigo) ?></td>
			<td><?= $esc($linea->nombre_producto) ?><br><span class="muted"><?= $esc($linea->nombre_unidad) ?></span></td>
			<td class="text-center"><?= number_format((float) $linea->cantidad, 2) ?></td>
			<td class="text-right"><?= $esc($simbolo) ?> <?= number_format((float) $linea->precio, 2) ?></td>
			<td class="text-right"><?= number_format((float) $linea->descuento, 2) ?>%</td>
			<td class="text-right"><strong><?= $esc($simbolo) ?> <?= number_format((float) $linea->base, 2) ?></strong></td>
		</tr><?php endforeach; ?></tbody>
	</table>

	<table class="totals">
		<tr><td>Subtotal</td><td class="text-right"><?= $esc($simbolo) ?> <?= number_format($subtotal, 2) ?></td></tr>
		<tr><td>Descuento</td><td class="text-right">- <?= $esc($simbolo) ?> <?= number_format((float) $venta->descuento, 2) ?></td></tr>
		<tr class="total"><td>TOTAL</td><td class="text-right"><?= $esc($simbolo) ?> <?= number_format((float) $venta->base, 2) ?></td></tr>
	</table>

	<div class="reference">
		<?php if (!empty($venta->referencia)): ?><strong>Referencia:</strong> <?= $esc($venta->referencia) ?><br><?php endif; ?>
		<span class="status <?= !empty($venta->anulado) ? 'cancelled' : '' ?>"><?= $esc(strtoupper($venta->nombre_estado)) ?></span>
	</div>
</body>
</html>
