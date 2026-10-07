-- Bitacora de llamadas de seguimiento a clientes (pantalla clientes/seguimiento.php):
-- cada clic en "Llamar" guarda quien lo marco y cuando, para saber a quien ya
-- se le aviso de sus puntos y del estado de su tarjeta.
CREATE TABLE IF NOT EXISTS tb_clientes_llamadas (
    id_llamada BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_usuario INT NOT NULL,
    fecha_llamada DATETIME NOT NULL,
    KEY idx_llamada_cliente (id_cliente, fecha_llamada)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
