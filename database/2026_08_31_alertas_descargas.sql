CREATE TABLE IF NOT EXISTS tb_configuracion_alertas (
    id_configuracion TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    habilitado TINYINT(1) NOT NULL DEFAULT 0,
    umbral_minutos INT UNSIGNED NOT NULL DEFAULT 120,
    correos TEXT NOT NULL,
    id_usuario_actualizacion INT NULL,
    fecha_actualizacion DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

INSERT INTO tb_configuracion_alertas
    (id_configuracion, habilitado, umbral_minutos, correos, fecha_actualizacion)
VALUES (1, 0, 120, '', NOW())
ON DUPLICATE KEY UPDATE id_configuracion = VALUES(id_configuracion);

CREATE TABLE IF NOT EXISTS tb_alertas_remisiones (
    id_alerta BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_remision INT NOT NULL,
    tipo VARCHAR(50) NOT NULL DEFAULT 'DESCARGA_PROLONGADA',
    fecha_envio DATETIME NULL,
    intentos INT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_error VARCHAR(500) NULL,
    fecha_ultimo_intento DATETIME NULL,
    UNIQUE KEY uq_alerta_remision_tipo (id_remision, tipo),
    KEY idx_alertas_fecha_envio (fecha_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;
