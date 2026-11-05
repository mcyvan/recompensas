-- Modulo de prospeccion de obras (prospectos/index.php): cada vendedor captura
-- las obras nuevas que esta cotizando y les da seguimiento hasta que se ganan
-- o se pierden; administracion ve el panorama de todos los vendedores.
-- Es idempotente: se puede correr mas de una vez.
CREATE TABLE IF NOT EXISTS tb_prospectos_obras (
    id_prospecto INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    cliente VARCHAR(150) NOT NULL,
    proyecto VARCHAR(200) NOT NULL,
    tipo_obra VARCHAR(30) NOT NULL,
    tipos_concreto VARCHAR(200) NOT NULL DEFAULT '',
    volumen_m3 DECIMAL(10,2) NOT NULL DEFAULT 0,
    fecha_inicio DATE NULL,
    fecha_fin DATE NULL,
    estatus VARCHAR(30) NOT NULL DEFAULT 'NUEVO',
    competidor VARCHAR(80) NULL,
    motivo_perdida VARCHAR(80) NULL,
    comentarios TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_registro DATETIME NOT NULL,
    fecha_actualizacion DATETIME NOT NULL,
    KEY idx_prospecto_usuario (id_usuario, activo),
    KEY idx_prospecto_estatus (estatus, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bitacora de seguimiento: cada nota o cambio de estatus queda con fecha y autor,
-- para ver como evoluciono la obra y no solo su ultimo estado.
CREATE TABLE IF NOT EXISTS tb_prospectos_seguimiento (
    id_seguimiento BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_prospecto INT UNSIGNED NOT NULL,
    id_usuario INT NOT NULL,
    estatus_anterior VARCHAR(30) NULL,
    estatus_nuevo VARCHAR(30) NOT NULL,
    nota TEXT NULL,
    fecha DATETIME NOT NULL,
    KEY idx_seguimiento_prospecto (id_prospecto, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
