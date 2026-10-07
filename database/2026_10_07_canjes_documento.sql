-- Folio de venta o remision con el que se hizo el canje, para poder rastrear
-- el documento que respalda cada canje. Los canjes anteriores quedan en NULL.
ALTER TABLE tb_canjes
    ADD COLUMN IF NOT EXISTS documento_folio VARCHAR(60) NULL AFTER id_cliente;
