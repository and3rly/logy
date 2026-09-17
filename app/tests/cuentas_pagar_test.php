<?php
$raiz = dirname(__DIR__);
$sql = file_get_contents($raiz . '/sql/cuentas_pagar.sql');
$controlador = file_get_contents($raiz . '/application/controllers/cxp/Cuenta_pagar.php');
$modelo = file_get_contents($raiz . '/application/models/cxp/Cuenta_pagar_model.php');
$compra = file_get_contents($raiz . '/application/controllers/compra/Orden.php');

function comprobar_cxp($condicion, $mensaje) {
	if (!$condicion) { fwrite(STDERR, $mensaje . PHP_EOL); exit(1); }
}

comprobar_cxp(strpos($sql, 'ROUND(`abono` + `saldo`, 5) = ROUND(`total`, 5)') !== false,
	'El esquema debe conservar la igualdad total = abono + saldo.');
comprobar_cxp(strpos($sql, 'uq_cuenta_pagar_empresa_compra') !== false,
	'Cada orden debe generar como máximo una cuenta por pagar.');
comprobar_cxp(strpos($controlador, '$monto > round((float) $cuenta->saldo, 5)') !== false,
	'El controlador debe rechazar sobrepagos.');
comprobar_cxp(strpos($controlador, 'trans_begin()') !== false && strpos($controlador, 'trans_commit()') !== false,
	'El pago debe ejecutarse dentro de una transacción.');
comprobar_cxp(strpos($modelo, 'FOR UPDATE') !== false && strpos($modelo, 'saldo >=') !== false,
	'La cuenta debe bloquearse y comprobar nuevamente el saldo.');
comprobar_cxp(strpos($compra, '"cxp/Cuenta_pagar_model"') !== false,
	'Compras debe cargar el modelo de cuentas por pagar.');
comprobar_cxp(strpos($compra, '$esCredito') !== false && strpos($compra, 'sincronizarCompra') !== false,
	'Guardar una compra a crédito debe generar o sincronizar la cuenta.');
comprobar_cxp(strpos($modelo, '"compra_id" => (int) $compra->getPK()') !== false,
	'La cuenta por pagar debe quedar vinculada a la orden.');
comprobar_cxp(strpos($compra, 'anularPorCompra') !== false,
	'Anular una orden debe anular también su cuenta sin pagos.');

echo "Cuentas por pagar: invariantes, pagos y generación desde compras a crédito presentes." . PHP_EOL;
