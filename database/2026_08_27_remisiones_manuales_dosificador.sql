CREATE TABLE IF NOT EXISTS tb_configuracion_modulos (
    clave VARCHAR(100) NOT NULL PRIMARY KEY,
    habilitado TINYINT(1) NOT NULL DEFAULT 0,
    id_usuario_actualizacion INT NULL,
    fecha_actualizacion DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

INSERT INTO tb_configuracion_modulos
    (clave, habilitado, id_usuario_actualizacion, fecha_actualizacion)
VALUES
    ('remisiones_manuales_dosificador', 0, NULL, NOW())
ON DUPLICATE KEY UPDATE clave = VALUES(clave);

INSERT INTO tb_roles (rol, fecha_registro, estatus)
SELECT 'DOSIFICADOR', NOW(), 1
WHERE NOT EXISTS (
    SELECT 1 FROM tb_roles WHERE rol = 'DOSIFICADOR'
);
