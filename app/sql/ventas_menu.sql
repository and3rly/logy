-- Agrega la opcion de listado al modulo Venta.
USE `db_logy`;

INSERT INTO `menu` (`modulo_id`, `nombre`, `orden`, `icono`, `url`, `activo`)
SELECT modulo_venta.`id`,
       'Ventas',
       COALESCE((SELECT MAX(menu_actual.`orden`)
                 FROM `menu` menu_actual
                 WHERE menu_actual.`modulo_id` = modulo_venta.`id`), 0) + 10,
       'fa fa-cash-register',
       '/venta',
       1
FROM `modulo` modulo_venta
WHERE LOWER(modulo_venta.`nombre`) = 'venta'
  AND NOT EXISTS (
    SELECT 1
    FROM `menu` existente
    WHERE existente.`modulo_id` = modulo_venta.`id`
      AND existente.`url` IN ('venta', '/venta')
  );

UPDATE `menu` menu_opcion
INNER JOIN `modulo` modulo_venta
  ON modulo_venta.`id` = menu_opcion.`modulo_id`
SET menu_opcion.`nombre` = 'Ventas',
    menu_opcion.`icono` = 'fa fa-cash-register',
    menu_opcion.`url` = '/venta',
    menu_opcion.`activo` = 1
WHERE LOWER(modulo_venta.`nombre`) = 'venta'
  AND menu_opcion.`url` IN ('venta', '/venta');

SELECT menu_opcion.*
FROM `menu` menu_opcion
INNER JOIN `modulo` modulo_venta ON modulo_venta.`id` = menu_opcion.`modulo_id`
WHERE LOWER(modulo_venta.`nombre`) = 'venta'
  AND menu_opcion.`url` IN ('venta', '/venta');
