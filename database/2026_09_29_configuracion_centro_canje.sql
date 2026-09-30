CREATE TABLE tb_configuracion_centro_canje (
    id_configuracion TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    nombre_centro VARCHAR(150) NOT NULL,
    direccion VARCHAR(200) NOT NULL,
    telefono VARCHAR(40) NOT NULL,
    id_usuario_actualizacion INT NULL,
    fecha_actualizacion DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tb_configuracion_centro_canje
    (id_configuracion, nombre_centro, direccion, telefono, id_usuario_actualizacion, fecha_actualizacion)
VALUES
    (1, 'Bianca Ruiz', 'Av Americas #411, Col. Las Granjas', '614-893-00-00', NULL, NOW());
