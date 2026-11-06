ALTER TABLE tb_remisiones
    ADD COLUMN id_camion_logistica INT NULL AFTER id_operador,
    ADD COLUMN camion_logistica VARCHAR(50) NULL AFTER id_camion_logistica;
