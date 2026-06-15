ALTER TABLE tb_canjes
    ADD COLUMN motivo_cancelacion VARCHAR(500) NULL AFTER estatus,
    ADD COLUMN id_usuario_cancelacion INT NULL AFTER id_usuario,
    ADD COLUMN fecha_cancelacion DATETIME NULL AFTER fecha_canje,
    ADD INDEX idx_canjes_usuario_cancelacion (id_usuario_cancelacion);
