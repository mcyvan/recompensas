-- Marca de la ultima vez que se imprimio la credencial fisica de un cliente.
-- Se sobrescribe en cada reimpresion (no guarda historial, solo la mas reciente).
-- Sirve para que el administrador sepa a cuales clientes ya les imprimio su
-- tarjeta PVC y a cuales les falta.
ALTER TABLE tb_clientes
    ADD COLUMN IF NOT EXISTS credencial_impresa_fecha DATETIME NULL,
    ADD COLUMN IF NOT EXISTS credencial_impresa_por INT NULL;
