-- Agrega Cuentas por cobrar al menu dinamico.

USE `db_logy`;

INSERT INTO `modulo` (`nombre`, `orden`, `icono`, `url`, `activo`, `detalle`)
SELECT 'Ventas',
       COALESCE((SELECT MAX(m.`orden`) FROM `modulo` m), 0) + 10,
       'fas fa-cash-register',
       NULL,
       1,
       1
WHERE NOT EXISTS (
  SELECT 1 FROM `modulo` existente WHERE LOWER(existente.`nombre`) = 'ventas'
);

INSERT INTO `menu` (`modulo_id`, `nombre`, `orden`, `icono`, `url`, `activo`)
SELECT ventas.`id`,
       'Cuentas por cobrar',
       COALESCE((SELECT MAX(actual.`orden`) FROM `menu` actual
                 WHERE actual.`modulo_id` = ventas.`id`), 0) + 10,
       'fas fa-file-invoice-dollar',
       '/cuenta-cobrar',
       1
FROM `modulo` ventas
WHERE LOWER(ventas.`nombre`) = 'ventas'
  AND NOT EXISTS (
    SELECT 1 FROM `menu` existente
    WHERE existente.`modulo_id` = ventas.`id`
      AND existente.`url` IN ('cuenta-cobrar', '/cuenta-cobrar')
  );
