-- Agrega la opción Ajustes al módulo Inventario si todavía no existe.
-- Ejecutar solamente si la opción /ajuste no aparece en el menú dinámico.

USE `db_logy`;

INSERT INTO `menu` (`modulo_id`, `nombre`, `orden`, `icono`, `url`, `activo`)
SELECT modulo_inventario.`id`,
       'Ajustes',
       COALESCE((SELECT MAX(menu_actual.`orden`)
                 FROM `menu` menu_actual
                 WHERE menu_actual.`modulo_id` = modulo_inventario.`id`), 0) + 10,
       'fas fa-sliders',
       '/ajuste',
       1
FROM `modulo` modulo_inventario
WHERE LOWER(modulo_inventario.`nombre`) = 'inventario'
  AND NOT EXISTS (
    SELECT 1
    FROM `menu` existente
    WHERE existente.`modulo_id` = modulo_inventario.`id`
      AND existente.`url` IN ('ajuste', '/ajuste')
  );

SELECT menu_opcion.*
FROM `menu` menu_opcion
INNER JOIN `modulo` modulo_inventario ON modulo_inventario.`id` = menu_opcion.`modulo_id`
WHERE LOWER(modulo_inventario.`nombre`) = 'inventario'
  AND menu_opcion.`url` IN ('ajuste', '/ajuste');
