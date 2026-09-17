<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class Excel_service {

	public function leerPlantillaXlsx($ruta, $encabezados, $maxFilas=1000)
	{
		if (!is_string($ruta) || !is_file($ruta) || !is_readable($ruta)) {
			throw new RuntimeException("No fue posible leer el archivo temporal.");
		}

		$reader = new XlsxReader();
		$reader->setReadDataOnly(false);
		$nombres = $reader->listWorksheetNames($ruta);
		if (empty($nombres)) {
			throw new RuntimeException("El libro no contiene hojas.");
		}
		$reader->setLoadSheetsOnly($nombres[0]);
		$libro = $reader->load($ruta);

		try {
			$hoja = $libro->getSheet(0);
			$ultimaFila = $hoja->getHighestDataRow();
			$ultimaColumna = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
				$hoja->getHighestDataColumn()
			);

			if ($ultimaFila > $maxFilas + 1) {
				throw new RuntimeException("El archivo supera el máximo de {$maxFilas} filas.");
			}

			$recibidos = [];
			$columnas = max(count($encabezados), $ultimaColumna);
			for ($columna = 1; $columna <= $columnas; $columna++) {
				$recibidos[] = mb_strtolower(trim((string) $this->valorCelda(
					$hoja->getCellByColumnAndRow($columna, 1)
				)), "UTF-8");
			}
			while (!empty($recibidos) && end($recibidos) === "") array_pop($recibidos);

			if ($recibidos !== array_values($encabezados)) {
				throw new RuntimeException("Los encabezados o su orden no coinciden con la plantilla oficial.");
			}

			$filas = [];
			for ($numero = 2; $numero <= $ultimaFila; $numero++) {
				$fila = ["_fila" => $numero];
				$tieneDatos = false;
				foreach ($encabezados as $indice => $campo) {
					$valor = $this->valorCelda($hoja->getCellByColumnAndRow($indice + 1, $numero));
					$fila[$campo] = $valor;
					if ($valor !== null && trim((string) $valor) !== "") $tieneDatos = true;
				}
				if ($tieneDatos) $filas[] = (object) $fila;
			}

			if (empty($filas)) {
				throw new RuntimeException("El archivo no contiene filas de inventario.");
			}

			return $filas;
		} finally {
			$libro->disconnectWorksheets();
			unset($libro);
		}
	}

	private function valorCelda($celda)
	{
		if ($celda->getDataType() === DataType::TYPE_FORMULA) {
			throw new RuntimeException("La celda {$celda->getCoordinate()} contiene una fórmula; reemplácela por su valor.");
		}
		if ($celda->getDataType() === DataType::TYPE_ERROR) {
			throw new RuntimeException("La celda {$celda->getCoordinate()} contiene un error de Excel.");
		}

		$valor = $celda->getValue();
		if ($valor instanceof RichText) return $valor->getPlainText();
		if ($valor !== null && Date::isDateTime($celda) && is_numeric($valor)) {
			return Date::excelToDateTimeObject($valor)->format("Y-m-d");
		}
		if (is_bool($valor)) return $valor ? 1 : 0;
		if (is_scalar($valor) || $valor === null) return $valor;

		throw new RuntimeException("La celda {$celda->getCoordinate()} contiene un valor no admitido.");
	}

	public function nuevoLibro()
	{
		return new Spreadsheet();
	}

	public function guardarXlsx(Spreadsheet $libro, $ruta)
	{
		$writer = new XlsxWriter($libro);
		$writer->save($ruta);
	}
}

/* End of file Excel_service.php */
/* Location: ./application/libraries/Excel_service.php */
