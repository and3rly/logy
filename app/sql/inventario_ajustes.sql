-- Modulo de ajustes de inventario
-- Compatible con el modelo MySQL actual de Logy (INT, utf8_general_ci, InnoDB).
-- IMPORTANTE: realizar una copia de seguridad antes de ejecutar este archivo.
-- Las sentencias DDL de MySQL realizan commit implicito.

USE `db_logy`;

-- Estados del flujo de ajustes. Se mantienen por empresa para seguir el patron
-- de los demas catalogos de Logy.
CREATE TABLE IF NOT EXISTS `inventario_ajuste_estado` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(45) NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `orden` INT NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `empresa_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ajuste_estado_empresa_codigo` (`empresa_id`, `codigo`),
  UNIQUE KEY `uq_ajuste_estado_id_empresa` (`id`, `empresa_id`),
  CONSTRAINT `fk_ajuste_estado_empresa`
    FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

-- Razones de negocio que originan un ajuste. La naturaleza determina si el
-- ajuste suma o resta cantidad en la tabla stock.
CREATE TABLE IF NOT EXISTS `inventario_ajuste_tipo` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(45) NOT NULL,
  `nombre` VARCHAR(150) NOT NULL,
  `naturaleza` ENUM('POSITIVO', 'NEGATIVO') NOT NULL,
  `requiere_observacion` TINYINT(1) NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `empresa_id` INT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ajuste_tipo_empresa_codigo` (`empresa_id`, `codigo`),
  UNIQUE KEY `uq_ajuste_tipo_id_empresa` (`id`, `empresa_id`),
  CONSTRAINT `fk_ajuste_tipo_empresa`
    FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

-- Encabezado del documento de ajuste.
CREATE TABLE IF NOT EXISTS `inventario_ajuste` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `numero` VARCHAR(45) NOT NULL,
  `inventario_ajuste_tipo_id` INT NOT NULL,
  `inventario_ajuste_estado_id` INT NOT NULL,
  `motivo` VARCHAR(150) NULL,
  `observacion` VARCHAR(300) NULL,
  `fecha` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_aplicado` DATETIME NULL,
  `fecha_anulado` DATETIME NULL,
  `empresa_id` INT NOT NULL,
  `sucursal_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `usuario_aplico_id` INT NULL,
  `usuario_anulo_id` INT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventario_ajuste_empresa_numero` (`empresa_id`, `numero`),
  KEY `idx_inventario_ajuste_tipo_empresa` (`inventario_ajuste_tipo_id`, `empresa_id`),
  KEY `idx_inventario_ajuste_estado_empresa` (`inventario_ajuste_estado_id`, `empresa_id`),
  KEY `idx_inventario_ajuste_sucursal` (`sucursal_id`),
  KEY `idx_inventario_ajuste_usuario` (`usuario_id`),
  KEY `idx_inventario_ajuste_usuario_aplico` (`usuario_aplico_id`),
  KEY `idx_inventario_ajuste_usuario_anulo` (`usuario_anulo_id`),
  KEY `idx_inventario_ajuste_fecha` (`fecha`),
  CONSTRAINT `fk_inventario_ajuste_tipo_empresa`
    FOREIGN KEY (`inventario_ajuste_tipo_id`, `empresa_id`)
    REFERENCES `inventario_ajuste_tipo` (`id`, `empresa_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_ajuste_estado_empresa`
    FOREIGN KEY (`inventario_ajuste_estado_id`, `empresa_id`)
    REFERENCES `inventario_ajuste_estado` (`id`, `empresa_id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_ajuste_empresa`
    FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_ajuste_sucursal`
    FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_ajuste_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_ajuste_usuario_aplico`
    FOREIGN KEY (`usuario_aplico_id`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_inventario_ajuste_usuario_anulo`
    FOREIGN KEY (`usuario_anulo_id`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

-- Lineas solicitadas por el usuario. La cantidad siempre se captura positiva;
-- la naturaleza determina si se suma o resta en stock al aplicar el documento.
CREATE TABLE IF NOT EXISTS `inventario_ajuste_detalle` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `inventario_ajuste_id` INT NOT NULL,
  `producto_id` INT NOT NULL,
  `unidad_medida_id` INT NOT NULL,
  `producto_presentacion_id` INT NULL,
  `cantidad` DECIMAL(10,2) NOT NULL,
  `fecha_vence` DATE NULL,
  `observacion` VARCHAR(300) NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_ajuste_detalle_ajuste` (`inventario_ajuste_id`),
  KEY `idx_ajuste_detalle_producto` (`producto_id`),
  KEY `idx_ajuste_detalle_unidad` (`unidad_medida_id`),
  KEY `idx_ajuste_detalle_presentacion` (`producto_presentacion_id`),
  CONSTRAINT `fk_ajuste_detalle_ajuste`
    FOREIGN KEY (`inventario_ajuste_id`) REFERENCES `inventario_ajuste` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ajuste_detalle_producto`
    FOREIGN KEY (`producto_id`) REFERENCES `producto` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ajuste_detalle_unidad`
    FOREIGN KEY (`unidad_medida_id`) REFERENCES `unidad_medida` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_ajuste_detalle_presentacion`
    FOREIGN KEY (`producto_presentacion_id`) REFERENCES `producto_presentacion` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

-- Vincula cada movimiento generado con su documento de ajuste.
-- Esta instruccion debe ejecutarse una sola vez.
ALTER TABLE `movimiento`
  ADD COLUMN `inventario_ajuste_id` INT NULL AFTER `compra_id`,
  ADD KEY `idx_movimiento_inventario_ajuste` (`inventario_ajuste_id`),
  ADD CONSTRAINT `fk_movimiento_inventario_ajuste`
    FOREIGN KEY (`inventario_ajuste_id`) REFERENCES `inventario_ajuste` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT;

-- Estados iniciales para cada empresa. No duplica codigos existentes.
INSERT INTO `inventario_ajuste_estado`
  (`codigo`, `nombre`, `orden`, `activo`, `empresa_id`)
SELECT catalogo.`codigo`, catalogo.`nombre`, catalogo.`orden`, 1, empresa.`id`
FROM `empresa` empresa
CROSS JOIN (
  SELECT 'BORRADOR' AS `codigo`, 'Borrador' AS `nombre`, 10 AS `orden`
  UNION ALL SELECT 'APLICADO', 'Aplicado', 20
  UNION ALL SELECT 'ANULADO', 'Anulado', 30
) catalogo
LEFT JOIN `inventario_ajuste_estado` existente
  ON existente.`empresa_id` = empresa.`id`
 AND existente.`codigo` = catalogo.`codigo`
WHERE existente.`id` IS NULL;

-- Tipos iniciales para cada empresa. Se pueden agregar mas tipos sin cambiar
-- la estructura de las tablas ni el flujo principal del modulo.
INSERT INTO `inventario_ajuste_tipo`
  (`codigo`, `nombre`, `naturaleza`, `requiere_observacion`, `activo`, `empresa_id`)
SELECT catalogo.`codigo`, catalogo.`nombre`, catalogo.`naturaleza`,
       catalogo.`requiere_observacion`, 1, empresa.`id`
FROM `empresa` empresa
CROSS JOIN (
  SELECT 'INI' AS `codigo`, 'Inventario inicial' AS `nombre`, 'POSITIVO' AS `naturaleza`, 0 AS `requiere_observacion`
  UNION ALL SELECT 'SOB', 'Sobrante en conteo', 'POSITIVO', 1
  UNION ALL SELECT 'RCP', 'Recuperacion de producto', 'POSITIVO', 1
  UNION ALL SELECT 'FAL', 'Faltante en conteo', 'NEGATIVO', 1
  UNION ALL SELECT 'MER', 'Merma', 'NEGATIVO', 1
  UNION ALL SELECT 'DAN', 'Producto danado', 'NEGATIVO', 1
  UNION ALL SELECT 'VEN', 'Producto vencido', 'NEGATIVO', 1
  UNION ALL SELECT 'CON', 'Consumo interno', 'NEGATIVO', 1
) catalogo
LEFT JOIN `inventario_ajuste_tipo` existente
  ON existente.`empresa_id` = empresa.`id`
 AND existente.`codigo` = catalogo.`codigo`
WHERE existente.`id` IS NULL;

-- Tipos tecnicos requeridos por el Kardex. No se confunden con la razon de
-- negocio almacenada en inventario_ajuste_tipo.
INSERT INTO `movimiento_tipo` (`codigo`, `nombre`, `activo`, `empresa_id`)
SELECT 'AJP', 'Ajuste positivo', 1, empresa.`id`
FROM `empresa` empresa
WHERE NOT EXISTS (
  SELECT 1
  FROM `movimiento_tipo` existente
  WHERE existente.`empresa_id` = empresa.`id`
    AND existente.`codigo` = 'AJP'
);

INSERT INTO `movimiento_tipo` (`codigo`, `nombre`, `activo`, `empresa_id`)
SELECT 'AJN', 'Ajuste negativo', 1, empresa.`id`
FROM `empresa` empresa
WHERE NOT EXISTS (
  SELECT 1
  FROM `movimiento_tipo` existente
  WHERE existente.`empresa_id` = empresa.`id`
    AND existente.`codigo` = 'AJN'
);

-- Verificacion posterior.
SHOW CREATE TABLE `inventario_ajuste_estado`;
SHOW CREATE TABLE `inventario_ajuste_tipo`;
SHOW CREATE TABLE `inventario_ajuste`;
SHOW CREATE TABLE `inventario_ajuste_detalle`;
SHOW COLUMNS FROM `movimiento` LIKE 'inventario_ajuste_id';

SELECT `empresa_id`, `codigo`, `nombre`, `orden`, `activo`
FROM `inventario_ajuste_estado`
ORDER BY `empresa_id`, `orden`;

SELECT `empresa_id`, `codigo`, `nombre`, `naturaleza`, `activo`
FROM `inventario_ajuste_tipo`
ORDER BY `empresa_id`, `naturaleza`, `nombre`;

SELECT `empresa_id`, `codigo`, `nombre`, `activo`
FROM `movimiento_tipo`
WHERE `codigo` IN ('AJP', 'AJN')
ORDER BY `empresa_id`, `codigo`;
