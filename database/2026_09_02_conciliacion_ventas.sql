CREATE TABLE IF NOT EXISTS tb_cargas_ventas (
    id_carga BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    archivos TEXT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    dias_reemplazados INT UNSIGNED NOT NULL DEFAULT 0,
    filas_importadas INT UNSIGNED NOT NULL DEFAULT 0,
    id_usuario INT NULL,
    fecha_carga DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cargas_ventas_fecha (fecha_carga),
    KEY idx_cargas_ventas_rango (fecha_inicio, fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_ventas_empresa (
    id_venta BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_carga BIGINT UNSIGNED NOT NULL,
    fecha DATE NOT NULL,
    remision VARCHAR(50) NOT NULL,
    pedido VARCHAR(80) NULL,
    factura VARCHAR(80) NULL,
    planta VARCHAR(100) NULL,
    vendedor VARCHAR(180) NULL,
    cliente VARCHAR(255) NULL,
    articulo VARCHAR(255) NOT NULL,
    cantidad DECIMAL(14,3) NOT NULL DEFAULT 0,
    precio_unitario DECIMAL(14,6) NOT NULL DEFAULT 0,
    es_concreto TINYINT(1) NOT NULL DEFAULT 0,
    fecha_importacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ventas_empresa_fecha (fecha),
    KEY idx_ventas_empresa_remision (remision),
    KEY idx_ventas_empresa_concreto_fecha (es_concreto, fecha),
    KEY idx_ventas_empresa_carga (id_carga),
    CONSTRAINT fk_ventas_empresa_carga
        FOREIGN KEY (id_carga) REFERENCES tb_cargas_ventas (id_carga)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
