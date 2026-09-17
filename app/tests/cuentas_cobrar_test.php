<?php
$raiz = dirname(__DIR__);
$sql = file_get_contents($raiz . '/sql/cuentas_cobrar.sql');
$controlador = file_get_contents($raiz . '/application/controllers/cxc/Cuenta_cobrar.php');
$modelo = file_get_contents($raiz . '/application/models/cxc/Cuenta_cobrar_model.php');
$venta = file_get_contents($raiz . '/application/controllers/venta/Venta.php');

function comprobar($condicion, $mensaje) {
	if (!$condicion) {
		fwrite(STDERR, $mensaje . PHP_EOL);
		exit(1);
	}
}

comprobar(strpos($sql, 'ROUND(`abono` + `saldo`, 5) = ROUND(`total`, 5)') !== false,
	'El esquema debe conservar la igualdad total = abono + saldo.');
comprobar(strpos($controlador, '$monto > round((float) $cuenta->saldo, 5)') !== false,
	'El controlador debe rechazar sobrepagos.');
comprobar(strpos($controlador, 'trans_begin()') !== false && strpos($controlador, 'trans_commit()') !== false,
	'El cobro debe ejecutarse dentro de una transaccion.');
comprobar(strpos($modelo, 'FOR UPDATE') !== false,
	'La cuenta debe bloquearse antes de aplicar el abono.');
comprobar(strpos($modelo, 'saldo >=') !== false,
	'La actualizacion atomica debe comprobar nuevamente el saldo.');
comprobar(strpos($venta, '"cxc/Cuenta_cobrar_model"') !== false,
	'Ventas debe cargar el modelo de cuentas por cobrar.');
comprobar(strpos($venta, '$esCredito') !== false && strpos($venta, '"origen"           => 2') !== false,
	'Facturar a credito debe generar una cuenta con origen venta.');
comprobar(strpos($venta, '"venta_id"         => (int) $id') !== false,
	'La cuenta por cobrar debe quedar vinculada a la venta.');

echo "Cuentas por cobrar: invariantes y generación desde ventas a crédito presentes." . PHP_EOL;
