-- Modulo de cuentas por pagar.
-- Permite crear cuentas manuales, generarlas desde ordenes de compra a credito
-- y registrar pagos parciales con comprobante de egreso.

USE `db_logy`;

CREATE TABLE IF NOT EXISTS `cuenta_pagar` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fecha` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `proveedor_id` INT NOT NULL,
  `factura_fecha` DATE NOT NULL,
  `factura_numero` VARCHAR(45) NOT NULL,
  `factura_documento` VARCHAR(100) NULL,
  `credito_dias` INT NOT NULL DEFAULT 0,
  `fecha_vence` DATE NOT NULL,
  `total` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `abono` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `saldo` DECIMAL(15,5) NOT NULL DEFAULT 0.00000,
  `moneda_id` INT NOT NULL,
  `empresa_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `compra_id` INT NULL,
  `referencia` VARCHAR(300) NULL,
  `anulado` TINYINT(1) NOT NULL DEFAULT 0,
  `anulado_fecha` DATETIME NULL,
  `anulado_motivo` VARCHAR(300) NULL,
  `anulado_usuario` INT NULL,
  `origen` INT NOT NULL DEFAULT 1 COMMENT '1=Manual, 2=Orden de compra',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cuenta_pagar_empresa_compra` (`empresa_id`, `compra_id`),
  KEY `idx_cuenta_pagar_proveedor` (`proveedor_id`),
  KEY `idx_cuenta_pagar_empresa_estado` (`empresa_id`, `anulado`, `saldo`),
  KEY `idx_cuenta_pagar_vencimiento` (`empresa_id`, `fecha_vence`),
  KEY `idx_cuenta_pagar_moneda` (`moneda_id`),
  KEY `idx_cuenta_pagar_usuario` (`usuario_id`),
  KEY `idx_cuenta_pagar_compra` (`compra_id`),
  CONSTRAINT `fk_cuenta_pagar_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedor` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_pagar_moneda` FOREIGN KEY (`moneda_id`) REFERENCES `moneda` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_pagar_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_pagar_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_pagar_compra` FOREIGN KEY (`compra_id`) REFERENCES `compra` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_pagar_anulado_usuario` FOREIGN KEY (`anulado_usuario`) REFERENCES `usuario` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `chk_cuenta_pagar_importes` CHECK (`total` > 0 AND `abono` >= 0 AND `saldo` >= 0 AND ROUND(`abono` + `saldo`, 5) = ROUND(`total`, 5)),
  CONSTRAINT `chk_cuenta_pagar_credito_dias` CHECK (`credito_dias` >= 0)
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `cuenta_pagar_pago` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `cuenta_pagar_id` INT NOT NULL,
  `fecha` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `forma_pago_id` INT NOT NULL,
  `comprobante_numero` VARCHAR(30) NULL,
  `documento_fecha` DATE NULL,
  `documento_numero` VARCHAR(30) NULL,
  `documento_comprobante` VARCHAR(250) NULL,
  `total` DECIMAL(15,5) NOT NULL,
  `usuario_id` INT NOT NULL,
  `anulado` TINYINT(1) NOT NULL DEFAULT 0,
  `anulado_fecha` DATETIME NULL,
  `anulado_motivo` VARCHAR(200) NULL,
  `anulado_usuario` INT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cuenta_pagar_pago_comprobante` (`comprobante_numero`),
  KEY `idx_cuenta_pagar_pago_cuenta` (`cuenta_pagar_id`, `anulado`),
  KEY `idx_cuenta_pagar_pago_forma` (`forma_pago_id`),
  KEY `idx_cuenta_pagar_pago_usuario` (`usuario_id`),
  CONSTRAINT `fk_cuenta_pagar_pago_cuenta` FOREIGN KEY (`cuenta_pagar_id`) REFERENCES `cuenta_pagar` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_pagar_pago_forma` FOREIGN KEY (`forma_pago_id`) REFERENCES `forma_pago` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_pagar_pago_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_pagar_pago_anulado_usuario` FOREIGN KEY (`anulado_usuario`) REFERENCES `usuario` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `chk_cuenta_pagar_pago_total` CHECK (`total` > 0)
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

SHOW CREATE TABLE `cuenta_pagar`;
SHOW CREATE TABLE `cuenta_pagar_pago`;
