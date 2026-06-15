UPDATE tb_roles SET estatus = 1 WHERE rol IN ('CANJE', 'ADMIN CANJE');

INSERT INTO tb_roles (rol, fecha_registro, estatus)
SELECT 'CANJE', CURRENT_DATE, 1
WHERE NOT EXISTS (
    SELECT 1 FROM tb_roles WHERE rol = 'CANJE'
);

INSERT INTO tb_roles (rol, fecha_registro, estatus)
SELECT 'ADMIN CANJE', CURRENT_DATE, 1
WHERE NOT EXISTS (
    SELECT 1 FROM tb_roles WHERE rol = 'ADMIN CANJE'
);
