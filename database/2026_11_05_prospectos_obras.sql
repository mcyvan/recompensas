-- Modulo de prospeccion de obras (prospectos/index.php): cada vendedor captura
-- las obras nuevas que esta cotizando y les da seguimiento hasta que se ganan
-- o se pierden; administracion ve el panorama de todos los vendedores.
-- Es idempotente: se puede correr mas de una vez.
CREATE TABLE IF NOT EXISTS tb_prospectos_obras (
    id_prospecto INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    cliente VARCHAR(150) NOT NULL,
    proyecto VARCHAR(200) NOT NULL,
    tipo_obra VARCHAR(30) NOT NULL,
    tipos_concreto VARCHAR(200) NOT NULL DEFAULT '',
    volumen_m3 DECIMAL(10,2) NOT NULL DEFAULT 0,
    fecha_inicio DATE NULL,
    fecha_fin DATE NULL,
    estatus VARCHAR(30) NOT NULL DEFAULT 'NUEVO',
    competidor VARCHAR(80) NULL,
    motivo_perdida VARCHAR(80) NULL,
    comentarios TEXT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_registro DATETIME NOT NULL,
    fecha_actualizacion DATETIME NOT NULL,
    KEY idx_prospecto_usuario (id_usuario, activo),
    KEY idx_prospecto_estatus (estatus, activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bitacora de seguimiento: cada nota o cambio de estatus queda con fecha y autor,
-- para ver como evoluciono la obra y no solo su ultimo estado.
CREATE TABLE IF NOT EXISTS tb_prospectos_seguimiento (
    id_seguimiento BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_prospecto INT UNSIGNED NOT NULL,
    id_usuario INT NOT NULL,
    estatus_anterior VARCHAR(30) NULL,
    estatus_nuevo VARCHAR(30) NOT NULL,
    nota TEXT NULL,
    fecha DATETIME NOT NULL,
    KEY idx_seguimiento_prospecto (id_prospecto, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Direccion y ubicacion de la obra (Google Maps). Latitud/longitud quedan guardadas
-- por separado para poder dibujar despues un mapa de las obras que se prospectan.
ALTER TABLE tb_prospectos_obras
    ADD COLUMN IF NOT EXISTS direccion VARCHAR(255) NULL AFTER proyecto,
    ADD COLUMN IF NOT EXISTS latitud DECIMAL(10,7) NULL AFTER direccion,
    ADD COLUMN IF NOT EXISTS longitud DECIMAL(10,7) NULL AFTER latitud;

-- Motivos de perdida estandar (lista fija). Solo OTRO lleva un texto libre en motivo_detalle.
ALTER TABLE tb_prospectos_obras
    ADD COLUMN IF NOT EXISTS motivo_detalle VARCHAR(150) NULL AFTER motivo_perdida;

-- Si ya habia obras perdidas con los motivos de la primera version, se pasan a la lista nueva.
-- (Los dos que ya no existen quedan como OTRO conservando el texto original.)
UPDATE tb_prospectos_obras SET motivo_perdida = 'CREDITO / CONDICIONES DE PAGO' WHERE motivo_perdida = 'PLAZO DE PAGO O CREDITO';
UPDATE tb_prospectos_obras SET motivo_perdida = 'PROYECTO CANCELADO O DETENIDO' WHERE motivo_perdida = 'PROYECTO CANCELADO O POSPUESTO';
UPDATE tb_prospectos_obras SET motivo_perdida = 'NO SE LOGRO CONTACTAR AL RESPONSABLE' WHERE motivo_perdida = 'SIN RESPUESTA DEL CLIENTE';
UPDATE tb_prospectos_obras SET motivo_detalle = motivo_perdida, motivo_perdida = 'OTRO'
    WHERE motivo_perdida IN ('SE LA DIERON A UN COMPETIDOR', 'EL CLIENTE LO RESUELVE POR OTRO MEDIO');

-- Telefono de contacto de la obra (solo digitos, 10).
ALTER TABLE tb_prospectos_obras
    ADD COLUMN IF NOT EXISTS telefono VARCHAR(20) NULL AFTER cliente;
