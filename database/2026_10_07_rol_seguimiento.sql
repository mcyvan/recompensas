-- Rol para el usuario de seguimiento a clientes: solo ve la pantalla
-- clientes/seguimiento.php (clientes, puntos y estado de su tarjeta) para
-- llamarles o escribirles por WhatsApp.
INSERT INTO tb_roles (rol, fecha_registro, estatus)
SELECT 'SEGUIMIENTO', CURRENT_DATE, 1
WHERE NOT EXISTS (
    SELECT 1 FROM tb_roles WHERE rol = 'SEGUIMIENTO'
);

UPDATE tb_roles SET estatus = 1 WHERE rol = 'SEGUIMIENTO';
