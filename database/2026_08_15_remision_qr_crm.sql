ALTER TABLE tb_remisiones
    ADD COLUMN qr_origen VARCHAR(50) NULL AFTER estatus,
    ADD COLUMN qr_texto LONGTEXT NULL AFTER qr_origen,
    ADD COLUMN qr_datos_json LONGTEXT NULL AFTER qr_texto,
    ADD COLUMN factura_crm VARCHAR(100) NULL AFTER qr_datos_json,
    ADD COLUMN pedido_crm VARCHAR(100) NULL AFTER factura_crm,
    ADD COLUMN fecha_crm DATE NULL AFTER pedido_crm,
    ADD COLUMN planta_crm VARCHAR(100) NULL AFTER fecha_crm,
    ADD COLUMN vendedor_crm VARCHAR(150) NULL AFTER planta_crm,
    ADD COLUMN articulo_crm VARCHAR(255) NULL AFTER vendedor_crm,
    ADD COLUMN cantidad_crm DECIMAL(10,2) NULL AFTER articulo_crm,
    ADD COLUMN precio_crm DECIMAL(10,2) NULL AFTER cantidad_crm,
    ADD COLUMN cliente_crm VARCHAR(255) NULL AFTER precio_crm;
