-- Permite que tb_ventas_empresa se llene sola desde la pestaña "MICROSIP
-- REMISIONES" del Google Sheet, en vez de subir el archivo XLSX a mano.
-- fila_origen es solo informativo (para depurar); el reemplazo sigue siendo
-- por fecha, igual que la carga manual (ver controller_importar_ventas.php).
ALTER TABLE tb_ventas_empresa
    ADD COLUMN IF NOT EXISTS fuente VARCHAR(20) NOT NULL DEFAULT 'MANUAL' AFTER id_carga,
    ADD COLUMN IF NOT EXISTS fila_origen INT UNSIGNED NULL AFTER fuente;

CREATE TABLE IF NOT EXISTS tb_ventas_sync (
    fuente VARCHAR(50) NOT NULL PRIMARY KEY,
    ultima_fila_sincronizada INT UNSIGNED NOT NULL DEFAULT 0,
    fecha_sincronizacion DATETIME NULL,
    filas_procesadas_ultima_vez INT UNSIGNED NOT NULL DEFAULT 0,
    dias_reemplazados_ultima_vez INT UNSIGNED NOT NULL DEFAULT 0,
    ultimo_error VARCHAR(500) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
