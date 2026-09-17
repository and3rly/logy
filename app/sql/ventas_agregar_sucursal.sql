-- Ejecutar una sola vez sobre db_logy.
-- La tabla venta esta vacia y actualmente no contiene sucursal_id.

USE `db_logy`;

ALTER TABLE `venta`
  ADD COLUMN `sucursal_id` INT NOT NULL AFTER `empresa_id`,
  ADD KEY `idx_venta_sucursal` (`sucursal_id`),
  ADD CONSTRAINT `fk_venta_sucursal`
    FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT;

SHOW COLUMNS FROM `venta` LIKE 'sucursal_id';
