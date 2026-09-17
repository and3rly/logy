-- Modulo basico de cuentas por cobrar.
-- Permite crear cuentas manuales y registrar abonos parciales por cuenta.
-- Los anticipos y la aplicacion de un recibo a varias cuentas quedan para una
-- segunda fase, pues requieren separar el cobro de sus aplicaciones.

USE `db_logy`;

CREATE TABLE IF NOT EXISTS `cuenta_cobrar` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fecha` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cliente_id` INT NOT NULL,
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
  `venta_id` INT NULL,
  `referencia` VARCHAR(300) NULL,
  `anulado` TINYINT(1) NOT NULL DEFAULT 0,
  `anulado_fecha` DATETIME NULL,
  `anulado_motivo` VARCHAR(300) NULL,
  `anulado_usuario` INT NULL,
  `origen` INT NOT NULL DEFAULT 1 COMMENT '1=Manual, 2=Venta',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cuenta_cobrar_empresa_venta` (`empresa_id`, `venta_id`),
  KEY `idx_cuenta_cobrar_cliente` (`cliente_id`),
  KEY `idx_cuenta_cobrar_empresa_estado` (`empresa_id`, `anulado`, `saldo`),
  KEY `idx_cuenta_cobrar_vencimiento` (`empresa_id`, `fecha_vence`),
  KEY `idx_cuenta_cobrar_moneda` (`moneda_id`),
  KEY `idx_cuenta_cobrar_usuario` (`usuario_id`),
  CONSTRAINT `fk_cuenta_cobrar_cliente`
    FOREIGN KEY (`cliente_id`) REFERENCES `cliente` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_cobrar_moneda`
    FOREIGN KEY (`moneda_id`) REFERENCES `moneda` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_cobrar_empresa`
    FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_cobrar_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_cobrar_anulado_usuario`
    FOREIGN KEY (`anulado_usuario`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `chk_cuenta_cobrar_importes`
    CHECK (`total` > 0 AND `abono` >= 0 AND `saldo` >= 0
      AND ROUND(`abono` + `saldo`, 5) = ROUND(`total`, 5)),
  CONSTRAINT `chk_cuenta_cobrar_credito_dias` CHECK (`credito_dias` >= 0)
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `cuenta_cobrar_pago` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `cuenta_cobrar_id` INT NOT NULL,
  `fecha` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `forma_pago_id` INT NOT NULL,
  `recibo_numero` VARCHAR(30) NULL,
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
  UNIQUE KEY `uq_cuenta_cobrar_pago_recibo` (`recibo_numero`),
  KEY `idx_cuenta_cobrar_pago_cuenta` (`cuenta_cobrar_id`, `anulado`),
  KEY `idx_cuenta_cobrar_pago_forma` (`forma_pago_id`),
  KEY `idx_cuenta_cobrar_pago_usuario` (`usuario_id`),
  CONSTRAINT `fk_cuenta_cobrar_pago_cuenta`
    FOREIGN KEY (`cuenta_cobrar_id`) REFERENCES `cuenta_cobrar` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_cobrar_pago_forma`
    FOREIGN KEY (`forma_pago_id`) REFERENCES `forma_pago` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_cobrar_pago_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cuenta_cobrar_pago_anulado_usuario`
    FOREIGN KEY (`anulado_usuario`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `chk_cuenta_cobrar_pago_total` CHECK (`total` > 0)
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;

SHOW CREATE TABLE `cuenta_cobrar`;
SHOW CREATE TABLE `cuenta_cobrar_pago`;
