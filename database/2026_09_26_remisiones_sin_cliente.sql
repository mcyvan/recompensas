-- Permite registrar una remision aunque el cliente todavia no este dado de alta.
-- id_cliente vacio = pendiente de ligar. Los tres campos nuevos permiten medir
-- cuantas veces pasa (sin_cliente_captura) y quien/cuando ligo el cliente despues.
ALTER TABLE tb_remisiones
    MODIFY id_cliente INT(11) NULL,
    ADD COLUMN IF NOT EXISTS sin_cliente_captura TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS id_usuario_liga INT NULL,
    ADD COLUMN IF NOT EXISTS fecha_liga DATETIME NULL,
    ADD INDEX IF NOT EXISTS idx_remision_sin_cliente (sin_cliente_captura, id_cliente);
