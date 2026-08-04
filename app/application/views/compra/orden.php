<?php defined('BASEPATH') OR exit('No direct script access allowed');

$esc = function ($valor) {
	return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};

$simbolo = $compra->simbolo_moneda ?? '';
$filasMinimas = 14;
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<style>
		body { font-family: sans-serif; color: #262626; font-size: 11.5px; }
		table { width: 100%; border-collapse: collapse; }
		.bg-neutral { background: #4b5563; color: #fff; }
		.logo { max-width: 115px; max-height: 58px; margin-bottom: 6px; }
		.company { font-size: 16px; font-weight: bold; }
		.title { font-size: 21px; font-weight: bold; text-align: center; color: #374151; }
		.header td { vertical-align: top; }
		.address { line-height: 1.45; margin-top: 4px; }
		.document-data td { padding: 1px 3px; }
		.section { margin-top: 13px; }
		.section-box { width: 48%; vertical-align: top; }
		.section-space { width: 4%; }
		.section-title { padding: 4px 6px; font-size: 14px; font-weight: bold; }
		.section-content { min-height: 73px; padding: 6px 2px; line-height: 1.4; }
		.meta { margin-top: 8px; }
		.meta th { border: 1px solid #374151; padding: 4px 5px; text-align: left; background: #4b5563; color: #fff; font-size: 10.5px; }
		.meta td { border: 1px solid #111; height: 18px; padding: 3px 4px; }
		.detail { margin-top: 10px; table-layout: fixed; }
		.detail th { border: 1px solid #374151; padding: 5px; background: #4b5563; color: #fff; text-align: left; font-size: 10.5px; }
		.detail td { border: 1px solid #4b5563; height: 18px; padding: 4px 5px; }
		.text-center { text-align: center !important; }
		.text-right { text-align: right !important; }
		.bottom { margin-top: 15px; }
		.instructions-title { margin-bottom: 4px; }
		.instructions td { border: 1px solid #111; height: 62px; padding: 6px; }
		.totals td { padding: 2px 4px; }
		.total-final td { font-weight: bold; padding-top: 4px; }
		.authorization { margin-top: 15px; width: 270px; }
		.authorization td { vertical-align: middle; }
		.authorization-box { border: 1px solid #111; height: 24px; }
	</style>
</head>
<body>
	<table class="header">
		<tr>
			<td width="48%">
				<?php if (!empty($logo)): ?><img src="<?= $esc($logo) ?>" class="logo" alt="Logo"><?php endif; ?>
				<div class="company"><?= $esc($empresa->nombre ?? 'Empresa') ?></div>
				<div class="address">
					<?php if (!empty($empresa->direccion)): ?><?= $esc($empresa->direccion) ?><br><?php endif; ?>
					<?php if (!empty($empresa->ciudad)): ?><?= $esc($empresa->ciudad) ?><br><?php endif; ?>
					<?php if (!empty($empresa->codigo_postal)): ?><?= $esc($empresa->codigo_postal) ?><br><?php endif; ?>
					<?php if (!empty($empresa->telefono)): ?>Tel. <?= $esc($empresa->telefono) ?><?php endif; ?>
				</div>
			</td>
			<td width="30%" class="title">ORDEN DE COMPRA</td>
			<td width="22%">
				<table class="document-data">
					<tr><td>Fecha</td><td class="text-right"><?= $esc(date('d/m/Y', strtotime($compra->fecha))) ?></td></tr>
					<tr><td>No.</td><td class="text-right"><strong><?= $esc($compra->numero) ?></strong></td></tr>
				</table>
			</td>
		</tr>
	</table>

	<table class="section">
		<tr>
			<td class="section-box">
				<div class="section-title bg-neutral">Proveedor</div>
				<div class="section-content">
					<strong><?= $esc($compra->nombre_proveedor) ?></strong><br>
					<?= $esc($compra->direccion_proveedor) ?><br>
					<?php if ($compra->identificacion_proveedor): ?>Identificación: <?= $esc($compra->identificacion_proveedor) ?><br><?php endif; ?>
					<?php if ($compra->telefono_proveedor): ?>Tel. <?= $esc($compra->telefono_proveedor) ?><br><?php endif; ?>
					<?= $esc($compra->correo_proveedor) ?>
				</div>
			</td>
			<td class="section-space"></td>
			<td class="section-box">
				<div class="section-title bg-neutral">Dirección de entrega</div>
				<div class="section-content">
					<strong><?= $esc($compra->nombre_sucursal) ?></strong><br>
					<?= $esc($compra->direccion_sucursal) ?><br>
					<?php if ($compra->telefono_sucursal): ?>Tel. <?= $esc($compra->telefono_sucursal) ?><?php endif; ?>
				</div>
			</td>
		</tr>
	</table>

	<table class="meta">
		<thead>
			<tr>
				<th width="25%">Entrega</th>
				<th width="25%">Términos de pago</th>
				<th width="25%">Solicitado por</th>
				<th width="25%">Departamento</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?= $compra->factura_fecha ? $esc(date('d/m/Y', strtotime($compra->factura_fecha))) : '' ?></td>
				<td><?= $esc($compra->nombre_forma_pago) ?></td>
				<td><?= $esc($compra->nombre_usuario) ?></td>
				<td><?= $esc($compra->nombre_sucursal) ?></td>
			</tr>
		</tbody>
	</table>

	<table class="detail">
		<thead>
			<tr>
				<th width="12%">Código</th>
				<th width="31%">Descripción</th>
				<th width="13%" class="text-center">Vencimiento</th>
				<th width="14%" class="text-center">Cantidad</th>
				<th width="15%" class="text-right">Precio unitario</th>
				<th width="15%" class="text-right">Totales</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($detalle as $linea): ?>
			<tr>
				<td><?= $esc($linea->codigo) ?></td>
				<td><?= $esc($linea->nombre_producto) ?> (<?= $esc($linea->nombre_um) ?>)</td>
				<td class="text-center"><?= !empty($linea->fecha_vence) ? $esc(date('d/m/Y', strtotime($linea->fecha_vence))) : '-' ?></td>
				<td class="text-right"><?= number_format((float) $linea->cantidad, 2) ?></td>
				<td class="text-right"><?= $esc($simbolo) ?> <?= number_format((float) $linea->precio_costo, 2) ?></td>
				<td class="text-right"><?= $esc($simbolo) ?> <?= number_format((float) $linea->total_costo, 2) ?></td>
			</tr>
			<?php endforeach; ?>
			<?php for ($i = count($detalle); $i < $filasMinimas; $i++): ?>
			<tr><td></td><td></td><td></td><td></td><td></td><td></td></tr>
			<?php endfor; ?>
		</tbody>
	</table>

	<table class="bottom">
		<tr>
			<td width="55%" valign="top">
				<div class="instructions-title">Instrucciones</div>
				<table class="instructions"><tr><td>&nbsp;</td></tr></table>
			</td>
			<td width="10%"></td>
			<td width="35%" valign="top">
				<table class="totals">
					<tr><td>Sub-total</td><td class="text-right"><?= $esc($simbolo) ?> <?= number_format((float) $compra->total_costo, 2) ?></td></tr>
					<tr><td>Entrega</td><td class="text-right"><?= $esc($simbolo) ?> 0.00</td></tr>
					<tr><td>IVA</td><td class="text-right"><?= $esc($simbolo) ?> 0.00</td></tr>
					<tr class="total-final"><td>TOTAL</td><td class="text-right"><?= $esc($simbolo) ?> <?= number_format((float) $compra->total_costo, 2) ?></td></tr>
				</table>
			</td>
		</tr>
	</table>

	<table class="authorization">
		<tr>
			<td width="32%">Autorizado por</td>
			<td width="68%" class="authorization-box">&nbsp;</td>
		</tr>
	</table>
</body>
</html>
