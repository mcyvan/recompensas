-- Las pantallas de remisiones filtran por rango de fecha de inicio de la descarga.
ALTER TABLE tb_remisiones
    ADD INDEX IF NOT EXISTS idx_remision_hora_inicio (hora_inicio);
