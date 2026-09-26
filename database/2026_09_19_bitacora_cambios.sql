CREATE TABLE IF NOT EXISTS tb_bitacora_cambios (
    id_bitacora BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    entidad VARCHAR(30) NOT NULL,
    id_registro INT NOT NULL,
    accion VARCHAR(30) NOT NULL,
    id_usuario_autor INT NULL,
    usuario_autor VARCHAR(100) NULL,
    rol_autor VARCHAR(50) NULL,
    cambios TEXT NOT NULL,
    ip VARCHAR(45) NULL,
    fecha_cambio DATETIME NOT NULL,
    KEY idx_bitacora_entidad (entidad, id_registro),
    KEY idx_bitacora_autor (id_usuario_autor),
    KEY idx_bitacora_fecha (fecha_cambio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
