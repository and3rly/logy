-- Modulo de ventas y comprobantes internos.
-- Compatible con MySQL 8.0 y la estructura actual de db_logy.
-- IMPORTANTE: realizar una copia de seguridad antes de ejecutar este archivo.
-- Las sentencias DDL de MySQL realizan commit implicito.
--
-- Alcance:
--   * venta funciona tambien como factura o recibo interno.
--   * venta_serie define el tipo/nombre del comprobante y su correlativo.
--   * Reutiliza el mantenimiento de clientes existente.
--   * No crea cuentas por cobrar ni procesos de facturacion electronica.
--   * El correlativo se asigna al FACTURAR, no al crear la venta.

USE `db_logy`;

-- Catalogo global solicitado para el flujo comercial de la venta.
CREATE TABLE IF NOT EXISTS `venta_estado` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(70) NOT NULL,
  `orden` INT NOT NULL DEFAULT 0,
  `fecha` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `etiqueta` VARCHAR(100) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_venta_estado_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

-- Serie y correlativo del comprobante. La fila debe bloquearse con
-- SELECT ... FOR UPDATE al facturar. correlativo representa el ultimo numero
-- utilizado; cero significa que la serie aun no ha comenzado. El siguiente es
-- inicio cuando correlativo = 0 y correlativo + 1 en los demas casos.
CREATE TABLE IF NOT EXISTS `venta_serie` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fecha` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nombre` VARCHAR(70) NOT NULL,
  `codigo` VARCHAR(5) NOT NULL,
  `inicio` INT NOT NULL DEFAULT 1,
  `fin` INT NOT NULL DEFAULT 999999999,
  `correlativo` INT NOT NULL DEFAULT 0,
  `electronico` TINYINT(1) NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `empresa_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_venta_serie_empresa_codigo` (`empresa_id`, `codigo`),
  UNIQUE KEY `uq_venta_serie_id_empresa` (`id`, `empresa_id`),
  KEY `idx_venta_serie_usuario` (`usuario_id`),
  CONSTRAINT `fk_venta_serie_ref_empresa`
    FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_serie_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

-- Encabezado comercial y comprobante de la venta.
CREATE TABLE IF NOT EXISTS `venta` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fecha` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `empresa_id` INT NOT NULL,
  `sucursal_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `moneda_id` INT NOT NULL,
  `cliente_id` INT NULL,
  `forma_pago_id` INT NOT NULL,
  `venta_estado_id` INT NOT NULL,
  `venta_serie_id` INT NOT NULL,
  `vendedor_id` INT NULL COMMENT 'Sin FK hasta definir el catalogo de vendedores',
  `total_precio` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `total_costo` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `ganancia` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `base` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `iva` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `isr` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `descuento` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `tipo_cambio` DECIMAL(15,5) NOT NULL DEFAULT 1.00000,
  `correlativo` VARCHAR(15) NULL,
  `referencia` VARCHAR(300) NULL,
  `factura_fecha` DATE NULL,
  `factura_numero` VARCHAR(20) NULL,
  `factura_serie` VARCHAR(20) NULL,
  `factura_uuid` VARCHAR(50) NULL,
  `certificada` TINYINT(1) NOT NULL DEFAULT 0,
  `certificada_fecha` DATETIME NULL,
  `certificada_usuario` INT NULL,
  `anulado` TINYINT(1) NOT NULL DEFAULT 0,
  `anulado_fecha` DATETIME NULL,
  `anulado_usuario` INT NULL,
  `anulado_motivo` VARCHAR(500) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_venta_empresa_correlativo` (`empresa_id`, `correlativo`),
  KEY `idx_venta_estado` (`venta_estado_id`),
  KEY `idx_venta_serie_empresa` (`venta_serie_id`, `empresa_id`),
  KEY `idx_venta_sucursal` (`sucursal_id`),
  KEY `idx_venta_usuario` (`usuario_id`),
  KEY `idx_venta_moneda` (`moneda_id`),
  KEY `idx_venta_cliente` (`cliente_id`),
  KEY `idx_venta_forma_pago` (`forma_pago_id`),
  KEY `idx_venta_vendedor` (`vendedor_id`),
  KEY `idx_venta_certificada_usuario` (`certificada_usuario`),
  KEY `idx_venta_anulado_usuario` (`anulado_usuario`),
  CONSTRAINT `fk_venta_estado`
    FOREIGN KEY (`venta_estado_id`) REFERENCES `venta_estado` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_serie_empresa`
    FOREIGN KEY (`venta_serie_id`, `empresa_id`)
    REFERENCES `venta_serie` (`id`, `empresa_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_empresa`
    FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_sucursal`
    FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_forma_pago`
    FOREIGN KEY (`forma_pago_id`) REFERENCES `forma_pago` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_moneda`
    FOREIGN KEY (`moneda_id`) REFERENCES `moneda` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_cliente_empresa`
    FOREIGN KEY (`cliente_id`, `empresa_id`) REFERENCES `cliente` (`id`, `empresa_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_certificada_usuario`
    FOREIGN KEY (`certificada_usuario`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_anulado_usuario`
    FOREIGN KEY (`anulado_usuario`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

-- Si venta ya existia, CREATE TABLE IF NOT EXISTS no agrega columnas nuevas.
-- Este bloque instala sucursal_id sin afectar una instalacion actualizada.
DROP PROCEDURE IF EXISTS `instalar_sucursal_venta`;
DELIMITER $$
CREATE PROCEDURE `instalar_sucursal_venta`()
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'venta'
      AND `COLUMN_NAME` = 'sucursal_id'
  ) THEN
    ALTER TABLE `venta`
      ADD COLUMN `sucursal_id` INT NOT NULL AFTER `empresa_id`;
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM `information_schema`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'venta'
      AND `INDEX_NAME` = 'idx_venta_sucursal'
  ) THEN
    ALTER TABLE `venta`
      ADD KEY `idx_venta_sucursal` (`sucursal_id`);
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM `information_schema`.`TABLE_CONSTRAINTS`
    WHERE `CONSTRAINT_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'venta'
      AND `CONSTRAINT_NAME` = 'fk_venta_sucursal'
  ) THEN
    ALTER TABLE `venta`
      ADD CONSTRAINT `fk_venta_sucursal`
      FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`)
      ON UPDATE CASCADE ON DELETE RESTRICT;
  END IF;
END$$
DELIMITER ;

CALL `instalar_sucursal_venta`();
DROP PROCEDURE `instalar_sucursal_venta`;

-- Detalle solicitado para precios, costos, impuestos y descuentos por linea.
CREATE TABLE IF NOT EXISTS `venta_detalle` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `venta_id` INT NOT NULL,
  `producto_id` INT NOT NULL,
  `unidad_medida_id` INT NOT NULL,
  `producto_precio_costo_id` INT NULL COMMENT 'Sin FK hasta implementar la tabla producto_precio_costo',
  `cantidad` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `precio` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `costo` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `total_precio` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `total_costo` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `ganancia` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `base` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `iva` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `isr` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `descuento` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `descuento_total` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `anulado` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_venta_detalle_venta` (`venta_id`),
  KEY `idx_venta_detalle_producto` (`producto_id`),
  KEY `idx_venta_detalle_unidad` (`unidad_medida_id`),
  KEY `idx_venta_detalle_precio_costo` (`producto_precio_costo_id`),
  CONSTRAINT `fk_venta_detalle_venta`
    FOREIGN KEY (`venta_id`) REFERENCES `venta` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_detalle_producto`
    FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_venta_detalle_unidad`
    FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

-- Relaciona cada salida o reversion con la linea de venta. Una linea puede
-- generar varios movimientos cuando consume varios lotes de stock.
DROP PROCEDURE IF EXISTS `instalar_relacion_movimiento_venta`;
DELIMITER $$
CREATE PROCEDURE `instalar_relacion_movimiento_venta`()
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'movimiento'
      AND `COLUMN_NAME` = 'venta_detalle_id'
  ) THEN
    ALTER TABLE `movimiento`
      ADD COLUMN `venta_detalle_id` INT NULL AFTER `inventario_det_id`;
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM `information_schema`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'movimiento'
      AND `INDEX_NAME` = 'idx_movimiento_venta_detalle'
  ) THEN
    ALTER TABLE `movimiento`
      ADD KEY `idx_movimiento_venta_detalle` (`venta_detalle_id`);
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM `information_schema`.`TABLE_CONSTRAINTS`
    WHERE `CONSTRAINT_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'movimiento'
      AND `CONSTRAINT_NAME` = 'fk_movimiento_venta_detalle'
  ) THEN
    ALTER TABLE `movimiento`
      ADD CONSTRAINT `fk_movimiento_venta_detalle`
      FOREIGN KEY (`venta_detalle_id`) REFERENCES `venta_detalle` (`id`)
      ON UPDATE CASCADE ON DELETE RESTRICT;
  END IF;
END$$
DELIMITER ;

CALL `instalar_relacion_movimiento_venta`();
DROP PROCEDURE `instalar_relacion_movimiento_venta`;

-- Estados solicitados. La insercion es repetible y conserva IDs existentes.
INSERT INTO `venta_estado` (`nombre`, `orden`, `fecha`, `activo`, `etiqueta`)
SELECT catalogo.`nombre`, catalogo.`orden`, CURRENT_TIMESTAMP, 1, catalogo.`etiqueta`
FROM (
  SELECT 'Creado' AS `nombre`, 1 AS `orden`, 'badge bg-warning' AS `etiqueta`
  UNION ALL SELECT 'Facturada', 2, 'badge bg-green'
  UNION ALL SELECT 'Pagada', 3, 'badge bg-primary'
  UNION ALL SELECT 'Anulada', 4, 'badge bg-danger'
) catalogo
LEFT JOIN `venta_estado` existente
  ON LOWER(existente.`nombre`) = LOWER(catalogo.`nombre`)
WHERE existente.`id` IS NULL;

-- No se insertan series automaticamente porque nombre, codigo, rango,
-- empresa y usuario deben corresponder a una configuracion aprobada.
-- Ejemplo de configuracion interna:
-- INSERT INTO `venta_serie`
--   (`nombre`, `codigo`, `inicio`, `fin`, `correlativo`, `electronico`,
--    `activo`, `empresa_id`, `usuario_id`)
-- VALUES
--   ('Factura interna', 'FAC', 1, 999999999, 0, 0, 1, 1, 1);

-- Tipos tecnicos para Kardex. VTA registra salidas negativas y VAN las
-- reversiones positivas originadas por anulacion de una venta facturada.
INSERT INTO `movimiento_tipo` (`codigo`, `nombre`, `activo`, `empresa_id`)
SELECT 'VTA', 'Venta', 1, empresa.`id`
FROM `empresa` empresa
WHERE NOT EXISTS (
  SELECT 1
  FROM `movimiento_tipo` existente
  WHERE existente.`empresa_id` = empresa.`id`
    AND existente.`codigo` = 'VTA'
);

INSERT INTO `movimiento_tipo` (`codigo`, `nombre`, `activo`, `empresa_id`)
SELECT 'VAN', 'Anulacion de venta', 1, empresa.`id`
FROM `empresa` empresa
WHERE NOT EXISTS (
  SELECT 1
  FROM `movimiento_tipo` existente
  WHERE existente.`empresa_id` = empresa.`id`
    AND existente.`codigo` = 'VAN'
);

-- Verificacion posterior.
SHOW CREATE TABLE `venta_estado`;
SHOW CREATE TABLE `venta_serie`;
SHOW CREATE TABLE `venta`;
SHOW CREATE TABLE `venta_detalle`;
SHOW COLUMNS FROM `movimiento` LIKE 'venta_detalle_id';

SELECT `id`, `nombre`, `orden`, `fecha`, `activo`, `etiqueta`
FROM `venta_estado`
ORDER BY `orden`;

SELECT `id`, `fecha`, `nombre`, `codigo`, `inicio`, `fin`, `correlativo`,
       `electronico`, `activo`, `empresa_id`, `usuario_id`
FROM `venta_serie`
ORDER BY `empresa_id`, `nombre`, `codigo`;

SELECT `empresa_id`, `codigo`, `nombre`, `activo`
FROM `movimiento_tipo`
WHERE `codigo` IN ('VTA', 'VAN')
ORDER BY `empresa_id`, `codigo`;
