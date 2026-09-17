-- Modulo de inventarios fisicos: inicial, ciclico y futuros tipos.
-- Compatible con MySQL 8.0 y la estructura actual de db_logy.
-- IMPORTANTE: realizar una copia de seguridad antes de ejecutar este archivo.
-- Las sentencias DDL de MySQL realizan commit implicito.

USE `db_logy`;

-- Catalogo extensible. Agregar nuevos tipos no requiere modificar
-- inventario_enc ni inventario_det.
CREATE TABLE IF NOT EXISTS `inventario_tipo` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(45) NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `descripcion` VARCHAR(300) NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `empresa_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventario_tipo_empresa_codigo` (`empresa_id`, `codigo`),
  UNIQUE KEY `uq_inventario_tipo_id_empresa` (`id`, `empresa_id`),
  CONSTRAINT `fk_inventario_tipo_empresa`
    FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `inventario_estado` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(45) NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `orden` INT NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `empresa_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventario_estado_empresa_codigo` (`empresa_id`, `codigo`),
  UNIQUE KEY `uq_inventario_estado_id_empresa` (`id`, `empresa_id`),
  CONSTRAINT `fk_inventario_estado_empresa`
    FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

-- Encabezado del inventario fisico.
CREATE TABLE IF NOT EXISTS `inventario_enc` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `numero` VARCHAR(45) NOT NULL,
  `inventario_tipo_id` INT NOT NULL,
  `inventario_estado_id` INT NOT NULL,
  `fecha` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_corte` DATETIME NULL,
  `observacion` VARCHAR(300) NULL,
  `archivo_nombre` VARCHAR(255) NULL,
  `archivo_hash` CHAR(64) NULL,
  `fecha_procesado` DATETIME NULL,
  `fecha_anulado` DATETIME NULL,
  `empresa_id` INT NOT NULL,
  `sucursal_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `usuario_proceso_id` INT NULL,
  `usuario_anulo_id` INT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventario_enc_empresa_numero` (`empresa_id`, `numero`),
  KEY `idx_inventario_enc_tipo_empresa` (`inventario_tipo_id`, `empresa_id`),
  KEY `idx_inventario_enc_estado_empresa` (`inventario_estado_id`, `empresa_id`),
  KEY `idx_inventario_enc_sucursal` (`sucursal_id`),
  KEY `idx_inventario_enc_usuario` (`usuario_id`),
  KEY `idx_inventario_enc_usuario_proceso` (`usuario_proceso_id`),
  KEY `idx_inventario_enc_usuario_anulo` (`usuario_anulo_id`),
  KEY `idx_inventario_enc_fecha` (`fecha`),
  KEY `idx_inventario_enc_fecha_corte` (`fecha_corte`),
  KEY `idx_inventario_enc_archivo_hash` (`empresa_id`, `archivo_hash`),
  CONSTRAINT `fk_inventario_enc_tipo_empresa`
    FOREIGN KEY (`inventario_tipo_id`, `empresa_id`)
    REFERENCES `inventario_tipo` (`id`, `empresa_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_enc_estado_empresa`
    FOREIGN KEY (`inventario_estado_id`, `empresa_id`)
    REFERENCES `inventario_estado` (`id`, `empresa_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_enc_empresa`
    FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_enc_sucursal`
    FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_enc_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_enc_usuario_proceso`
    FOREIGN KEY (`usuario_proceso_id`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_enc_usuario_anulo`
    FOREIGN KEY (`usuario_anulo_id`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

-- Detalle definitivo. producto_id se asigna despues de resolver o crear el
-- producto importado y antes de guardar el documento.
CREATE TABLE IF NOT EXISTS `inventario_det` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `inventario_enc_id` INT NOT NULL,
  `producto_id` INT NOT NULL,
  `unidad_medida_id` INT NOT NULL,
  `producto_presentacion_id` INT NULL,
  `fecha_vence` DATETIME NULL,
  `cantidad_sistema` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `cantidad_fisica` DECIMAL(10,2) NOT NULL,
  `diferencia` DECIMAL(10,2) NOT NULL,
  `costo` DECIMAL(10,5) NULL,
  `observacion` VARCHAR(300) NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_inventario_det_enc` (`inventario_enc_id`),
  KEY `idx_inventario_det_producto` (`producto_id`),
  KEY `idx_inventario_det_unidad` (`unidad_medida_id`),
  KEY `idx_inventario_det_presentacion` (`producto_presentacion_id`),
  CONSTRAINT `chk_inventario_det_cantidad_sistema`
    CHECK (`cantidad_sistema` >= 0),
  CONSTRAINT `chk_inventario_det_cantidad_fisica`
    CHECK (`cantidad_fisica` >= 0),
  CONSTRAINT `chk_inventario_det_diferencia`
    CHECK (`diferencia` = `cantidad_fisica` - `cantidad_sistema`),
  CONSTRAINT `fk_inventario_det_enc`
    FOREIGN KEY (`inventario_enc_id`) REFERENCES `inventario_enc` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_det_producto`
    FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_det_unidad`
    FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_det_presentacion`
    FOREIGN KEY (`producto_presentacion_id`) REFERENCES `producto_presentacion` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

-- Relacion exacta entre cada movimiento y la linea de inventario que lo
-- genero. El procedimiento permite ejecutar nuevamente este script.
DROP PROCEDURE IF EXISTS `instalar_relacion_movimiento_inventario`;
DELIMITER $$
CREATE PROCEDURE `instalar_relacion_movimiento_inventario`()
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'movimiento'
      AND `COLUMN_NAME` = 'inventario_det_id'
  ) THEN
    ALTER TABLE `movimiento`
      ADD COLUMN `inventario_det_id` INT NULL AFTER `inventario_ajuste_id`;
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM `information_schema`.`STATISTICS`
    WHERE `TABLE_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'movimiento'
      AND `INDEX_NAME` = 'idx_movimiento_inventario_det'
  ) THEN
    ALTER TABLE `movimiento`
      ADD KEY `idx_movimiento_inventario_det` (`inventario_det_id`);
  END IF;

  IF NOT EXISTS (
    SELECT 1
    FROM `information_schema`.`TABLE_CONSTRAINTS`
    WHERE `CONSTRAINT_SCHEMA` = DATABASE()
      AND `TABLE_NAME` = 'movimiento'
      AND `CONSTRAINT_NAME` = 'fk_movimiento_inventario_det'
  ) THEN
    ALTER TABLE `movimiento`
      ADD CONSTRAINT `fk_movimiento_inventario_det`
      FOREIGN KEY (`inventario_det_id`) REFERENCES `inventario_det` (`id`)
      ON UPDATE CASCADE ON DELETE RESTRICT;
  END IF;
END$$
DELIMITER ;

CALL `instalar_relacion_movimiento_inventario`();
DROP PROCEDURE `instalar_relacion_movimiento_inventario`;

-- Tipos iniciales por empresa.
INSERT INTO `inventario_tipo`
  (`codigo`, `nombre`, `descripcion`, `activo`, `empresa_id`)
SELECT catalogo.`codigo`, catalogo.`nombre`, catalogo.`descripcion`, 1, empresa.`id`
FROM `empresa` empresa
CROSS JOIN (
  SELECT 'INICIAL' AS `codigo`, 'Inventario inicial' AS `nombre`,
         'Carga de las existencias iniciales de una sucursal.' AS `descripcion`
  UNION ALL
  SELECT 'CICLICO', 'Inventario ciclico',
         'Conteo fisico parcial o periodico para determinar diferencias.'
) catalogo
LEFT JOIN `inventario_tipo` existente
  ON existente.`empresa_id` = empresa.`id`
 AND existente.`codigo` = catalogo.`codigo`
WHERE existente.`id` IS NULL;

-- Estados iniciales por empresa.
INSERT INTO `inventario_estado`
  (`codigo`, `nombre`, `orden`, `activo`, `empresa_id`)
SELECT catalogo.`codigo`, catalogo.`nombre`, catalogo.`orden`, 1, empresa.`id`
FROM `empresa` empresa
CROSS JOIN (
  SELECT 'BORRADOR' AS `codigo`, 'Borrador' AS `nombre`, 10 AS `orden`
  UNION ALL SELECT 'VALIDADO', 'Validado', 20
  UNION ALL SELECT 'PROCESADO', 'Procesado', 30
  UNION ALL SELECT 'ANULADO', 'Anulado', 40
) catalogo
LEFT JOIN `inventario_estado` existente
  ON existente.`empresa_id` = empresa.`id`
 AND existente.`codigo` = catalogo.`codigo`
WHERE existente.`id` IS NULL;

-- Tipos tecnicos del Kardex. La diferencia de cada linea determina cual usar.
INSERT INTO `movimiento_tipo` (`codigo`, `nombre`, `activo`, `empresa_id`)
SELECT 'IVP', 'Inventario positivo', 1, empresa.`id`
FROM `empresa` empresa
WHERE NOT EXISTS (
  SELECT 1
  FROM `movimiento_tipo` existente
  WHERE existente.`empresa_id` = empresa.`id`
    AND existente.`codigo` = 'IVP'
);

INSERT INTO `movimiento_tipo` (`codigo`, `nombre`, `activo`, `empresa_id`)
SELECT 'IVN', 'Inventario negativo', 1, empresa.`id`
FROM `empresa` empresa
WHERE NOT EXISTS (
  SELECT 1
  FROM `movimiento_tipo` existente
  WHERE existente.`empresa_id` = empresa.`id`
    AND existente.`codigo` = 'IVN'
);

-- Verificacion posterior.
SHOW CREATE TABLE `inventario_tipo`;
SHOW CREATE TABLE `inventario_estado`;
SHOW CREATE TABLE `inventario_enc`;
SHOW CREATE TABLE `inventario_det`;
SHOW COLUMNS FROM `movimiento` LIKE 'inventario_det_id';

SELECT `empresa_id`, `codigo`, `nombre`, `activo`
FROM `inventario_tipo`
ORDER BY `empresa_id`, `nombre`;

SELECT `empresa_id`, `codigo`, `nombre`, `orden`, `activo`
FROM `inventario_estado`
ORDER BY `empresa_id`, `orden`;

SELECT `empresa_id`, `codigo`, `nombre`, `activo`
FROM `movimiento_tipo`
WHERE `codigo` IN ('IVP', 'IVN')
ORDER BY `empresa_id`, `codigo`;
