-- Agrega Cuentas por pagar al menu dinamico.

USE `db_logy`;

INSERT INTO `modulo` (`nombre`, `orden`, `icono`, `url`, `activo`, `detalle`)
SELECT 'Compras', COALESCE((SELECT MAX(m.`orden`) FROM `modulo` m), 0) + 10,
       'fas fa-cart-shopping', NULL, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `modulo` existente WHERE LOWER(existente.`nombre`) IN ('compra', 'compras'));

INSERT INTO `menu` (`modulo_id`, `nombre`, `orden`, `icono`, `url`, `activo`)
SELECT compras.`id`, 'Cuentas por pagar',
       COALESCE((SELECT MAX(actual.`orden`) FROM `menu` actual WHERE actual.`modulo_id` = compras.`id`), 0) + 10,
       'fas fa-money-check-dollar', '/cuenta-pagar', 1
FROM `modulo` compras
WHERE LOWER(compras.`nombre`) IN ('compra', 'compras')
  AND NOT EXISTS (
    SELECT 1 FROM `menu` existente
    WHERE existente.`modulo_id` = compras.`id`
      AND existente.`url` IN ('cuenta-pagar', '/cuenta-pagar')
  );
