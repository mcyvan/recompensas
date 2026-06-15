CREATE TABLE tb_configuracion_puntos (
    id_configuracion TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    puntos_por_m3 DECIMAL(10,2) NOT NULL,
    id_usuario_actualizacion INT NULL,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tb_configuracion_puntos
    (id_configuracion, puntos_por_m3, id_usuario_actualizacion, fecha_actualizacion)
VALUES
    (1, 5.00, NULL, NOW());
