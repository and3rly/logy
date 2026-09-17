-- Catalogo de clientes.
-- Compatible con MySQL 8.0 y la estructura actual de db_logy.
-- No incluye los campos exento ni exento_frase_id.
-- IMPORTANTE: realizar una copia de seguridad antes de ejecutar este archivo.
-- Las sentencias DDL de MySQL realizan commit implicito.

USE `db_logy`;

CREATE TABLE IF NOT EXISTS `cliente` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `fecha` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nombre` VARCHAR(150) NOT NULL,
  `razon_social` VARCHAR(150) NULL,
  `identificacion` VARCHAR(20) NULL,
  `codigo` VARCHAR(10) NULL,
  `direccion` VARCHAR(150) NULL,
  `telefono` INT NULL,
  `correo` VARCHAR(70) NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `credito` TINYINT(1) NOT NULL DEFAULT 0,
  `credito_limite` DECIMAL(15,5) NULL,
  `credito_dias` INT NOT NULL DEFAULT 0,
  `empresa_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `municipio_id` INT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cliente_id_empresa` (`id`, `empresa_id`),
  KEY `idx_cliente_empresa` (`empresa_id`),
  KEY `idx_cliente_usuario` (`usuario_id`),
  KEY `idx_cliente_municipio` (`municipio_id`),
  KEY `idx_cliente_identificacion` (`identificacion`),
  KEY `idx_cliente_codigo` (`codigo`),
  CONSTRAINT `fk_cliente_empresa`
    FOREIGN KEY (`empresa_id`) REFERENCES `empresa` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cliente_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_cliente_municipio`
    FOREIGN KEY (`municipio_id`) REFERENCES `municipio` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET=utf8 COLLATE=utf8_general_ci;
