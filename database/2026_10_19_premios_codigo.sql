-- Codigo de producto de cada premio (lo captura el administrador; opcional).
ALTER TABLE tb_premios
    ADD COLUMN IF NOT EXISTS codigo VARCHAR(50) NULL AFTER premio;
