ALTER TABLE tb_usuarios
    ADD COLUMN id_usuario_logistica INT NULL AFTER id_usuario,
    ADD UNIQUE KEY uq_usuarios_logistica (id_usuario_logistica);

CREATE TABLE tb_sso_nonces (
    nonce CHAR(32) NOT NULL PRIMARY KEY,
    id_usuario_logistica INT NOT NULL,
    fecha_expiracion DATETIME NOT NULL,
    fecha_uso DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sso_expiracion (fecha_expiracion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
