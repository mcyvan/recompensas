-- Copia local de la hoja de Google "Remisiones Norte/Oeste/Sur" que llena el
-- dosificador (fecha, camion, operador, folio, volumen, etc). Se sincroniza
-- sola via cron/sincronizar_produccion.php y sirve para comparar, por
-- operador, cuantas remisiones deberia haber metido en Recompensas contra
-- cuantas metio de verdad.
CREATE TABLE IF NOT EXISTS tb_produccion_planta (
    id_produccion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    planta VARCHAR(20) NOT NULL,
    fila_origen INT UNSIGNED NOT NULL,
    fecha DATE NULL,
    camion VARCHAR(20) NULL,
    operador VARCHAR(150) NULL,
    folio_remision VARCHAR(20) NULL,
    sello VARCHAR(50) NULL,
    volumen DECIMAL(6,2) NULL,
    resistencia VARCHAR(20) NULL,
    tirado_bombeado VARCHAR(50) NULL,
    notas VARCHAR(255) NULL,
    direccion VARCHAR(255) NULL,
    fecha_sincronizacion DATETIME NOT NULL,
    UNIQUE KEY uq_produccion_planta_fila (planta, fila_origen),
    KEY idx_produccion_folio (folio_remision),
    KEY idx_produccion_planta_fecha (planta, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS tb_produccion_planta_sync (
    planta VARCHAR(20) NOT NULL PRIMARY KEY,
    ultima_fila_sincronizada INT UNSIGNED NOT NULL DEFAULT 0,
    fecha_sincronizacion DATETIME NULL,
    filas_insertadas_ultima_vez INT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_error VARCHAR(500) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
