-- Series iniciales para comprobantes internos de venta.
-- Empresa: FerroAgro San Bernardino (id 1)
-- Usuario responsable: Administrador (id 1)

USE `db_logy`;

INSERT INTO `venta_serie`
  (`nombre`, `codigo`, `inicio`, `fin`, `correlativo`, `electronico`,
   `activo`, `empresa_id`, `usuario_id`)
VALUES
  ('Factura interna', 'FAC', 1, 999999999, 0, 0, 1, 1, 1),
  ('Recibo interno',  'REC', 1, 999999999, 0, 0, 1, 1, 1)
ON DUPLICATE KEY UPDATE
  `nombre` = VALUES(`nombre`),
  `electronico` = VALUES(`electronico`),
  `activo` = VALUES(`activo`),
  `usuario_id` = VALUES(`usuario_id`);

SELECT ROW_COUNT() AS `filas_insertadas_o_actualizadas`;

SELECT
  `id`, `fecha`, `nombre`, `codigo`, `inicio`, `fin`, `correlativo`,
  `electronico`, `activo`, `empresa_id`, `usuario_id`
FROM `venta_serie`
WHERE `empresa_id` = 1
ORDER BY `nombre`, `codigo`;
