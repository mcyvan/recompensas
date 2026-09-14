<?php
//RELACIONADO CON USUARIOS

function obtenerRoles()
{
    global $pdo;

    $query = $pdo->query("SELECT * FROM tb_roles;");
    $roles = $query->fetchAll(PDO::FETCH_ASSOC);
    return $roles;
}


function obtenerRolesID(int $id_rol)
{
    global $pdo;

    $query = $pdo->prepare("SELECT tb_roles.id_rol, tb_roles.rol, tb_roles.estatus FROM tb_roles WHERE id_rol = ?");
    $query->execute([$id_rol]);
    $roles = $query->fetchAll(PDO::FETCH_ASSOC);
    return $roles;
}

function obtenerUsuarios()
{
    global $pdo;

    $consulta_login = $pdo->prepare("SELECT 
                                            tb_usuarios_detalle.nombres,
                                            tb_usuarios_detalle.apellido_p,
                                            tb_usuarios_detalle.apellido_m,                                            
                                            tb_usuarios_detalle.telefono,                                            
                                            tb_usuarios_detalle.fecha_registro,
                                            tb_usuarios.estatus,
                                            tb_usuarios.usuario,
                                            tb_usuarios.id_usuario,                                           
                                            tb_roles.rol,
                                            tb_roles.id_rol
                                            FROM tb_usuarios 
                                            INNER JOIN tb_usuarios_detalle ON tb_usuarios_detalle.id_usuario=tb_usuarios.id_usuario
                                            INNER JOIN tb_roles ON tb_roles.id_rol=tb_usuarios_detalle.id_rol;");

    $consulta_login->execute();

    // Obtener el resultado
    $resultado = $consulta_login->fetchAll(PDO::FETCH_ASSOC);
    return $resultado;
}


function obtenerClientes()
{
    global $pdo;

    $consulta_login = $pdo->prepare("SELECT 
                                            tb_clientes.id_cliente,
                                            tb_clientes.nombres,
                                            tb_clientes.apellido_p,
                                            tb_clientes.apellido_m,                                            
                                            tb_clientes.telefono,                                            
                                            tb_clientes.correo,
                                            tb_clientes.fecha_nacimiento,
                                            tb_clientes.fecha_registro,
                                            tb_clientes.estatus,
                                            tb_usuarios.usuario,
                                            tb_usuarios.id_usuario                                                                                      
                                            FROM tb_usuarios 
                                            left JOIN tb_clientes ON tb_clientes.id_usuario=tb_usuarios.id_usuario                                    
                                            WHERE tb_clientes.estatus in ('1','0')
                                            ;");

    $consulta_login->execute();

    // Obtener el resultado
    $resultado = $consulta_login->fetchAll(PDO::FETCH_ASSOC);
    return $resultado;
}

function obtenerClientesPorUsuario(int $id_usuario)
{
    global $pdo;

    $consulta = $pdo->prepare("SELECT
                                    tb_clientes.id_cliente,
                                    tb_clientes.nombres,
                                    tb_clientes.apellido_p,
                                    tb_clientes.apellido_m,
                                    tb_clientes.telefono,
                                    tb_clientes.correo,
                                    tb_clientes.fecha_nacimiento,
                                    tb_clientes.fecha_registro,
                                    tb_clientes.estatus,
                                    tb_usuarios.usuario,
                                    tb_usuarios.id_usuario
                                FROM tb_clientes
                                INNER JOIN tb_usuarios
                                    ON tb_usuarios.id_usuario = tb_clientes.id_usuario
                                WHERE tb_clientes.id_usuario = ?
                                  AND tb_clientes.estatus IN (1, 0)");

    $consulta->execute([$id_usuario]);

    return $consulta->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerPuntoClientes()
{
    global $pdo;

    $consulta_login = $pdo->prepare("SELECT
    tb_clientes.id_cliente,
    tb_clientes.nombres,
    tb_clientes.apellido_p,
    tb_clientes.apellido_m,
    tb_clientes.telefono,
    tb_clientes.correo,
    tb_clientes.fecha_nacimiento,
    tb_clientes.fecha_registro,
    tb_clientes.estatus,
    tb_usuarios.usuario,
    tb_usuarios.id_usuario,
    COALESCE(SUM(
        CASE
            WHEN tb_movimientos_puntos.tipo = 'ACUMULACION'
                 AND (
                     tb_movimientos_puntos.fecha_vencimiento IS NULL
                     OR tb_movimientos_puntos.fecha_vencimiento >= CURRENT_DATE
                 )
                THEN tb_movimientos_puntos.puntos

            WHEN tb_movimientos_puntos.tipo IN ('CANJE', 'AJUSTE')
                 AND (
                     tb_movimientos_puntos.fecha_vencimiento IS NULL
                     OR tb_movimientos_puntos.fecha_vencimiento >= CURRENT_DATE
                 )
                THEN tb_movimientos_puntos.puntos

            ELSE 0
        END
    ), 0) AS puntos
FROM tb_usuarios
INNER JOIN tb_clientes
    ON tb_clientes.id_usuario = tb_usuarios.id_usuario
LEFT JOIN tb_movimientos_puntos
    ON tb_movimientos_puntos.id_cliente = tb_clientes.id_cliente
WHERE tb_clientes.estatus = 1
GROUP BY
    tb_clientes.id_cliente,
    tb_clientes.nombres,
    tb_clientes.apellido_p,
    tb_clientes.apellido_m,
    tb_clientes.telefono,
    tb_clientes.correo,
    tb_clientes.fecha_nacimiento,
    tb_clientes.fecha_registro,
    tb_clientes.estatus,
    tb_usuarios.usuario,
    tb_usuarios.id_usuario;");

    $consulta_login->execute();

    // Obtener el resultado
    $resultado = $consulta_login->fetchAll(PDO::FETCH_ASSOC);
    return $resultado;
}

function obtenerClientesID(int $id_cliente)
{
    global $pdo;

    $consulta_login = $pdo->prepare("SELECT 
                                            tb_clientes.id_cliente,
                                            tb_clientes.nombres,
                                            tb_clientes.apellido_p,
                                            tb_clientes.apellido_m,                                            
                                            tb_clientes.telefono,                                            
                                            tb_clientes.correo,
                                            tb_clientes.fecha_nacimiento,
                                            tb_clientes.fecha_registro,
                                            tb_clientes.estatus,
                                            tb_usuarios.usuario,
                                            tb_usuarios.id_usuario                                           
                                            FROM tb_usuarios 
                                            INNER JOIN tb_clientes ON tb_clientes.id_usuario=tb_usuarios.id_usuario
                                            WHERE tb_clientes.id_cliente = ?");
    $consulta_login->execute([$id_cliente]);

    // Obtener el resultado
    $resultado = $consulta_login->fetchAll(PDO::FETCH_ASSOC);
    return $resultado;
}

function obtenerVendedores()
{
    global $pdo;

    $consulta_login = $pdo->prepare("SELECT 
                                            tb_usuarios_detalle.nombres,
                                            tb_usuarios_detalle.apellido_p,
                                            tb_usuarios_detalle.apellido_m,                                            
                                            tb_usuarios_detalle.telefono,                                            
                                            tb_usuarios_detalle.fecha_registro,
                                            tb_usuarios.estatus,
                                            tb_usuarios.usuario,
                                            tb_usuarios.id_usuario,                                            
                                            tb_roles.rol,
                                            tb_roles.id_rol
                                            FROM tb_usuarios 
                                            INNER JOIN tb_usuarios_detalle ON tb_usuarios_detalle.id_usuario=tb_usuarios.id_usuario
                                            INNER JOIN tb_roles ON tb_roles.id_rol=tb_usuarios_detalle.id_rol
                                            WHERE tb_roles.id_rol = 2;");

    $consulta_login->execute();

    // Obtener el resultado
    $resultado = $consulta_login->fetchAll(PDO::FETCH_ASSOC);
    return $resultado;
}

function obtenerTotalClientes(string $vendedor)
{
    global $pdo;

    $consulta_login = $pdo->prepare("SELECT COUNT(*) as total_clientes FROM tb_usuarios 
                                            INNER JOIN tb_clientes ON tb_clientes.id_usuario=tb_usuarios.id_usuario
                                            WHERE tb_usuarios.usuario = ?");

    $consulta_login->execute([$vendedor]);

    // Obtener el resultado
    $resultado = $consulta_login->fetch(PDO::FETCH_ASSOC);
    return $resultado['total_clientes'];
}
function obtenerTotalClientesxVendedor()
{
    global $pdo;

    $consulta_login = $pdo->prepare("SELECT count(id_cliente) as total_clientes, 
                                        tb_usuarios.id_usuario,
                                        tb_usuarios.usuario,
                                        tb_usuarios_detalle.nombres,
                                        tb_usuarios_detalle.apellido_p
                                        FROM tb_usuarios 
                                        INNER JOIN tb_clientes ON tb_clientes.id_usuario=tb_usuarios.id_usuario
                                        LEFT join tb_usuarios_detalle ON tb_usuarios.id_usuario=tb_usuarios_detalle.id_usuario
                                        WHERE tb_clientes.estatus = '1'
                                        GROUP BY tb_clientes.id_usuario");

    $consulta_login->execute();

    // Obtener el resultado
    $resultado = $consulta_login->fetchAll(PDO::FETCH_ASSOC);
    return $resultado;
}

function obtenerResumenDashboardClientes(): array
{
    global $pdo;

    $sqlClientes = "SELECT
            COUNT(*) AS total_clientes,
            SUM(CASE WHEN estatus = 1 THEN 1 ELSE 0 END) AS clientes_activos,
            SUM(CASE WHEN estatus = 0 THEN 1 ELSE 0 END) AS clientes_inactivos,
            SUM(CASE WHEN fecha_registro >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END) AS clientes_mes
        FROM tb_clientes";
    $clientes = $pdo->query($sqlClientes)->fetch(PDO::FETCH_ASSOC) ?: [];

    $sqlActividad = "SELECT
            COUNT(DISTINCT CASE WHEN r.estatus <> 'CANCELADO' THEN c.id_cliente END) AS clientes_con_remision,
            COUNT(DISTINCT CASE WHEN r.estatus <> 'CANCELADO' AND r.puntos > 0 THEN c.id_cliente END) AS clientes_con_puntos,
            COUNT(CASE WHEN r.estatus <> 'CANCELADO' THEN r.id_remision END) AS remisiones_validas,
            COALESCE(SUM(CASE WHEN r.estatus <> 'CANCELADO' THEN r.volumen ELSE 0 END), 0) AS total_metros,
            COALESCE(SUM(CASE WHEN r.estatus <> 'CANCELADO' THEN r.puntos ELSE 0 END), 0) AS total_puntos
        FROM tb_clientes c
        LEFT JOIN tb_remisiones r ON r.id_cliente = c.id_cliente";
    $actividad = $pdo->query($sqlActividad)->fetch(PDO::FETCH_ASSOC) ?: [];

    return array_merge($clientes, $actividad);
}

function obtenerDashboardClientesPorVendedor(): array
{
    global $pdo;

    $sql = "SELECT
            u.id_usuario,
            u.usuario AS vendedor,
            COUNT(DISTINCT c.id_cliente) AS total_clientes,
            COUNT(DISTINCT CASE WHEN c.estatus = 1 THEN c.id_cliente END) AS clientes_activos,
            COUNT(DISTINCT CASE WHEN c.fecha_registro >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN c.id_cliente END) AS clientes_mes,
            COUNT(DISTINCT CASE WHEN r.estatus <> 'CANCELADO' THEN c.id_cliente END) AS clientes_con_remision,
            COUNT(DISTINCT CASE WHEN r.estatus <> 'CANCELADO' AND r.puntos > 0 THEN c.id_cliente END) AS clientes_con_puntos,
            COUNT(CASE WHEN r.estatus <> 'CANCELADO' THEN r.id_remision END) AS remisiones_validas,
            COALESCE(SUM(CASE WHEN r.estatus <> 'CANCELADO' THEN r.volumen ELSE 0 END), 0) AS total_metros,
            COALESCE(SUM(CASE WHEN r.estatus <> 'CANCELADO' THEN r.puntos ELSE 0 END), 0) AS total_puntos,
            MAX(CASE WHEN r.estatus <> 'CANCELADO' THEN r.hora_inicio ELSE NULL END) AS ultima_remision
        FROM tb_usuarios u
        INNER JOIN tb_usuarios_detalle ud ON ud.id_usuario = u.id_usuario
        INNER JOIN tb_roles rol ON rol.id_rol = ud.id_rol
        LEFT JOIN tb_clientes c ON c.id_usuario = u.id_usuario
        LEFT JOIN tb_remisiones r ON r.id_cliente = c.id_cliente
        WHERE rol.rol = 'VENDEDOR'
        GROUP BY u.id_usuario, u.usuario
        ORDER BY total_metros DESC, clientes_con_puntos DESC, total_clientes DESC";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerClientesSinActividadPorVendedor(): array
{
    global $pdo;

    $sql = "SELECT
            u.usuario AS vendedor,
            COUNT(c.id_cliente) AS clientes_sin_actividad
        FROM tb_usuarios u
        INNER JOIN tb_usuarios_detalle ud ON ud.id_usuario = u.id_usuario
        INNER JOIN tb_roles rol ON rol.id_rol = ud.id_rol
        LEFT JOIN tb_clientes c ON c.id_usuario = u.id_usuario
        LEFT JOIN tb_remisiones r ON r.id_cliente = c.id_cliente AND r.estatus <> 'CANCELADO'
        WHERE rol.rol = 'VENDEDOR'
          AND c.estatus = 1
          AND r.id_remision IS NULL
        GROUP BY u.id_usuario, u.usuario
        HAVING clientes_sin_actividad > 0
        ORDER BY clientes_sin_actividad DESC, u.usuario";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerUsuario(int $id_usuario)
{
    global $pdo;

    // 1. Usamos placeholders (?) para evitar inyección SQL
    $sql = "SELECT 
                tb_usuarios_detalle.nombres,
                tb_usuarios_detalle.apellido_p,
                tb_usuarios_detalle.apellido_m,                                            
                tb_usuarios_detalle.telefono,                                            
                tb_usuarios_detalle.fecha_registro,
                tb_usuarios.estatus,
                tb_usuarios.usuario,
                tb_usuarios.id_usuario,                
                tb_roles.rol,
                tb_roles.id_rol
            FROM tb_usuarios 
            INNER JOIN tb_usuarios_detalle ON tb_usuarios_detalle.id_usuario = tb_usuarios.id_usuario
            INNER JOIN tb_roles ON tb_roles.id_rol = tb_usuarios_detalle.id_rol
            WHERE  tb_usuarios.id_usuario = ?";

    $query = $pdo->prepare($sql);
    $query->execute([$id_usuario]); // 2. Pasamos el ID aquí de forma segura

    // 3. Usamos fetch() porque solo esperamos un resultado
    $usuario = $query->fetchAll(PDO::FETCH_ASSOC);

    return $usuario;
}

function obtenerDatosClientePorTelefono(int $telefono)
{
    return obtenerDatosClientePorCampo('tb_clientes.telefono', $telefono);
}

function obtenerDatosClientePorId(int $idCliente)
{
    return obtenerDatosClientePorCampo('tb_clientes.id_cliente', $idCliente);
}

function obtenerDatosClientePorCampo(string $campo, $valor)
{
    global $pdo;

    $camposPermitidos = ['tb_clientes.telefono', 'tb_clientes.id_cliente'];
    if (!in_array($campo, $camposPermitidos, true)) {
        return false;
    }

    $sql = "SELECT 
    COALESCE(SUM(tb_remisiones.puntos), 0) as puntos,
    MAX(tb_remisiones.folio_remision) as folio_remision,
    tb_clientes.id_cliente,
    tb_clientes.telefono,
    tb_clientes.nombres,
    tb_clientes.apellido_p,
    tb_clientes.apellido_m,
    tb_usuarios.usuario
FROM tb_usuarios 
INNER JOIN tb_clientes ON tb_clientes.id_usuario = tb_usuarios.id_usuario
LEFT JOIN tb_remisiones ON tb_remisiones.id_cliente = tb_clientes.id_cliente
WHERE $campo = ? AND tb_clientes.estatus = '1'
GROUP BY tb_clientes.id_cliente, tb_clientes.telefono, tb_clientes.nombres, tb_clientes.apellido_p, tb_clientes.apellido_m, tb_usuarios.usuario";

    $query = $pdo->prepare($sql);
    $query->execute([$valor]);

    $cliente = $query->fetch(PDO::FETCH_ASSOC);

    return $cliente;
}

function obtenerPremios()
{
    global $pdo;

    $sql = "select 
            tb_premios.id_premio,
            tb_premios.premio,
            tb_premios.descripcion,
            tb_premios.puntos_requeridos,
            tb_premios.stock,
            tb_premios.activo,
            tb_premios.fecha_registro,
            tb_premios.imagen,
            tb_premios.id_categoria,
            tb_categorias.categoria
            from tb_premios
            inner join tb_categorias on tb_premios.id_categoria = tb_categorias.id_categoria";
    $query = $pdo->prepare($sql);
    $query->execute();

    $premios = $query->fetchAll(PDO::FETCH_ASSOC);

    return $premios;
}

function obtenerPremiosxId(int $id_premio)
{
    global $pdo;

    $sql = "select 
            tb_premios.id_premio,
            tb_premios.premio,
            tb_premios.descripcion,
            tb_premios.puntos_requeridos,
            tb_premios.stock,
            tb_premios.activo,
            tb_premios.fecha_registro,
            tb_premios.imagen,
            tb_premios.id_categoria,
            tb_categorias.categoria
            from tb_premios
            inner join tb_categorias on tb_premios.id_categoria = tb_categorias.id_categoria
            where tb_premios.id_premio = ?";
    $query = $pdo->prepare($sql);
    $query->execute([$id_premio]);

    $premio = $query->fetch(PDO::FETCH_ASSOC);

    return $premio;
}
function obtenerCategorias()
{
    global $pdo;

    $sql = "select * from tb_categorias";
    $query = $pdo->prepare($sql);
    $query->execute();

    $categorias = $query->fetchAll(PDO::FETCH_ASSOC);

    return $categorias;
}
