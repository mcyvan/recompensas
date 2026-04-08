<?php
//RELACIONADO CON USUARIOS

function obtenerRoles()
{
    global $pdo;

    $query = $pdo->query("SELECT * FROM tb_roles;");
    $roles = $query->fetchAll(PDO::FETCH_ASSOC);
    return $roles;
}


function obtenerRolesID($id_rol)
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
                                            tb_usuarios.password,
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
                                            tb_usuarios.id_usuario,
                                            tb_puntos_recompensas.puntos                                           
                                            FROM tb_usuarios 
                                            INNER JOIN tb_clientes ON tb_clientes.id_usuario=tb_usuarios.id_usuario
                                            left JOIN tb_puntos_recompensas ON tb_puntos_recompensas.id_cliente=tb_clientes.id_cliente
                                            WHERE tb_clientes.estatus = '1'
                                            ;");

    $consulta_login->execute();

    // Obtener el resultado
    $resultado = $consulta_login->fetchAll(PDO::FETCH_ASSOC);
    return $resultado;
}

function obtenerClientesID($id_cliente)
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



function obtenerTotalClientes($vendedor)
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

function obtenerUsuario($id_usuario)
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
                tb_usuarios.password,
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

function obtenerDatosClientePorTelefono($telefono)
{
    global $pdo;

    $sql = "SELECT 
    tb_puntos_recompensas.puntos,
    tb_puntos_recompensas.remision,
    tb_clientes.nombres,
    tb_clientes.apellido_p,
    tb_clientes.apellido_m,
    tb_usuarios.usuario
FROM tb_usuarios 
INNER JOIN tb_clientes ON tb_clientes.id_usuario = tb_usuarios.id_usuario
LEFT JOIN tb_puntos_recompensas ON tb_puntos_recompensas.id_cliente = tb_clientes.id_cliente
WHERE tb_clientes.telefono = ? AND tb_clientes.estatus = '1'";

    $query = $pdo->prepare($sql);
    $query->execute([$telefono]);

    $cliente = $query->fetch(PDO::FETCH_ASSOC);

    return $cliente;
}
