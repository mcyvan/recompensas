CREATE TABLE tb_canjes (
    id_canje BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    folio VARCHAR(30) NOT NULL,
    id_cliente INT NOT NULL,
    total_puntos DECIMAL(10,2) NOT NULL,
    saldo_antes DECIMAL(10,2) NOT NULL,
    saldo_despues DECIMAL(10,2) NOT NULL,
    estatus ENUM('CONFIRMADO', 'CANCELADO') NOT NULL DEFAULT 'CONFIRMADO',
    id_usuario INT NOT NULL,
    fecha_canje DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_canjes_folio (folio),
    INDEX idx_canjes_cliente_fecha (id_cliente, fecha_canje),
    INDEX idx_canjes_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tb_canje_detalle (
    id_canje_detalle BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_canje BIGINT UNSIGNED NOT NULL,
    id_premio INT NOT NULL,
    premio VARCHAR(150) NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    puntos_unitarios DECIMAL(10,2) NOT NULL,
    puntos_total DECIMAL(10,2) NOT NULL,
    INDEX idx_canje_detalle_canje (id_canje),
    INDEX idx_canje_detalle_premio (id_premio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tb_canje_aplicaciones (
    id_aplicacion BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_canje BIGINT UNSIGNED NOT NULL,
    id_movimiento_acumulacion BIGINT UNSIGNED NOT NULL,
    id_movimiento_canje BIGINT UNSIGNED NOT NULL,
    puntos_aplicados DECIMAL(10,2) NOT NULL,
    fecha_vencimiento DATE NULL,
    INDEX idx_aplicaciones_canje (id_canje),
    INDEX idx_aplicaciones_acumulacion (id_movimiento_acumulacion),
    UNIQUE KEY uq_aplicacion_movimiento_canje (id_movimiento_canje)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
