<?php
define('BASEPATH', __DIR__);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../application/libraries/Excel_service.php';

$origen = realpath(__DIR__ . '/../../interfaz/public/plantillas/inventario_inicial.xlsx');
if (!$origen) throw new RuntimeException('No se encontró la plantilla oficial.');

$reader = new PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$libro = $reader->load($origen);
$hoja = $libro->getSheet(0);
$valores = [
	'PRUEBA-001', '000123', 'Producto de prueba', 'Descripción', 'Marca',
	'Clasificación', 'UND', 1.25, 2.50, 1, 'NO', null, 10.50, 'Prueba'
];
foreach ($valores as $indice => $valor) {
	$hoja->setCellValueByColumnAndRow($indice + 1, 2, $valor);
}

$temporal = tempnam(sys_get_temp_dir(), 'logy_excel_') . '.xlsx';
$servicio = new Excel_service();
$servicio->guardarXlsx($libro, $temporal);
$libro->disconnectWorksheets();

$encabezados = [
	'codigo_producto', 'codigo_barra', 'nombre_producto', 'descripcion', 'marca',
	'clasificacion', 'unidad_codigo', 'costo', 'precio', 'existencia_minima',
	'control_vencimiento', 'fecha_vencimiento', 'cantidad', 'observacion'
];
$filas = $servicio->leerPlantillaXlsx($temporal, $encabezados, 1000);
@unlink($temporal);

if (count($filas) !== 1) throw new RuntimeException('La fila de prueba no fue leída.');
if ($filas[0]->codigo_producto !== 'PRUEBA-001') throw new RuntimeException('El código no coincide.');
if ($filas[0]->codigo_barra !== '000123') throw new RuntimeException('Se perdieron ceros iniciales.');
if ((float) $filas[0]->cantidad !== 10.50) throw new RuntimeException('La cantidad no coincide.');

$libroFormula = $reader->load($origen);
$hojaFormula = $libroFormula->getSheet(0);
foreach ($valores as $indice => $valor) {
	$hojaFormula->setCellValueByColumnAndRow($indice + 1, 2, $valor);
}
$hojaFormula->setCellValue('M2', '=1+1');
$temporalFormula = tempnam(sys_get_temp_dir(), 'logy_formula_') . '.xlsx';
$servicio->guardarXlsx($libroFormula, $temporalFormula);
$libroFormula->disconnectWorksheets();
$rechazada = false;
try {
	$servicio->leerPlantillaXlsx($temporalFormula, $encabezados, 1000);
} catch (RuntimeException $error) {
	$rechazada = strpos($error->getMessage(), 'fórmula') !== false;
}
@unlink($temporalFormula);
if (!$rechazada) throw new RuntimeException('La fórmula de prueba no fue rechazada.');

echo "PhpSpreadsheet: plantilla leída y fórmula rechazada correctamente en PHP " . PHP_VERSION . PHP_EOL;
