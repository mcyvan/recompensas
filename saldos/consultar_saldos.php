<?php
require_once("../app/config/config.php");

if (isset($_SESSION['mensaje_registro_clientes_correcto'])) {
    $mensaje_registro_clientes_correcto = $_SESSION['mensaje_registro_clientes_correcto'];
    unset($_SESSION['mensaje_registro_clientes_correcto']);
} else {
    $mensaje_registro_clientes_correcto = null;
}


?>
<!DOCTYPE html>
<html lang="es">

<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Recompensas - Consulta</title>
    <style>
        /* Clase para botones cuadrados medianos */
        .btn-square-md {
            width: 100px;
            /* ajusta el tamaño según lo que necesites */
            height: 100px;
            padding: 0;
            /* evita que el texto expanda el botón */
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            /* opcional, quítalo si quieres esquinas rectas */
            font-size: 16px;
        }

        /* Variante más pequeña */
        .btn-square-sm {
            width: 60px;
            height: 60px;
            padding: 0;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 14px;
        }

        /* Variante más grande */
        .btn-square-lg {
            width: 150px;
            height: 150px;
            padding: 0;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-size: 18px;
        }
    </style>
</head>

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
    <?php if ($mensaje_registro_clientes_correcto): ?>
        <script>
            Swal.fire({
                title: "",
                text: "<?php echo $mensaje_registro_clientes_correcto; ?>",
                icon: "success"
            });
        </script>
    <?php endif; ?>

    <div class="wrapper">

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2 justify-content-center">
                        <div class="col-sm-4">
                            <h5 class="text-center mt-2">RECOMPENSAS AMERICAS</h5>
                            <div class="card bg-light m-3 ">
                                <div class="card-body text-center">

                                    <div class="row justify-content-center">
                                        <div class="col-sm-12 mb-3">
                                            <input
                                                type="tel"
                                                class="form-control form-control-lg text-center"
                                                id="telefono_cliente"
                                                placeholder="Ingresa tu teléfono"
                                                maxlength="10"
                                                inputmode="numeric"
                                                autocomplete="tel">
                                            <input type="text" name="website" id="website" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;height:0;width:0;opacity:0;">
                                        </div>

                                        <div class="col-sm-12">
                                            <button class="btn btn-primary btn-lg w-100" id="btnConsultar">
                                                CONSULTAR PUNTOS
                                            </button>
                                        </div>

                                    </div>

                                </div>
                            </div><!-- /.col -->
                            <!-- MODAL -->
                            <div class="modal fade" id="modalPuntos" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">

                                        <div class="modal-header bg-primary text-white">
                                            <h5 class="modal-title">Puntos del Cliente</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body">

                                            <h4 id="nombre_cliente" class="text-center mb-4"></h4>

                                            <div class="text-center">
                                                <h1 id="puntos_cliente" class="display-3 fw-bold text-success"></h1>
                                                <p>Puntos acumulados</p>
                                                <p id="vigencia_puntos" class="text-danger fw-semibold mb-0"></p>
                                            </div>

                                            <hr>

                                            <div id="historial_cliente"></div>

                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div><!-- /.row -->
                        <!-- /.content-header -->
                    </div>
                    <!-- /.content-wrapper -->
                    <?php include('../app/layout/footer.php'); ?>
                </div>
                <!-- ./wrapper -->
                <?php include('../app/layout/footer_links.php'); ?>
            </div>

        </div>
        <script>
            (function() {
                const btnConsultar = document.getElementById('btnConsultar');
                const inputTelefono = document.getElementById('telefono_cliente');
                const honeypot = document.getElementById('website');
                let consultando = false;
                let ultimaConsulta = 0;
                const esperaMinimaMs = 2000;

                btnConsultar.addEventListener('click', function() {
                    const telefono = inputTelefono.value.replace(/\D/g, '');
                    const ahora = Date.now();

                    if (consultando) {
                        return;
                    }

                    if (ahora - ultimaConsulta < esperaMinimaMs) {
                        Swal.fire({
                            icon: 'warning',
                            text: 'Espera un momento antes de consultar de nuevo'
                        });
                        return;
                    }

                    if (telefono.length !== 10) {
                        Swal.fire({
                            icon: 'warning',
                            text: 'Ingresa un teléfono válido de 10 dígitos'
                        });
                        return;
                    }

                    consultando = true;
                    btnConsultar.disabled = true;
                    ultimaConsulta = ahora;

                    const body = new URLSearchParams({
                        telefono: telefono,
                        website: honeypot.value
                    });

                    fetch('buscar_cliente_puntos.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: body.toString()
                        })
                        .then(async function(response) {
                            const data = await response.json().catch(function() {
                                return {
                                    success: false,
                                    message: 'No se pudo procesar la consulta'
                                };
                            });

                            if (!response.ok && !data.message) {
                                data.message = 'Demasiadas consultas. Intenta más tarde.';
                            }

                            return data;
                        })
                        .then(function(data) {
                            if (data.success) {
                                document.getElementById('nombre_cliente').innerText = data.nombre + ' ' + data.apellido_p;
                                document.getElementById('puntos_cliente').innerText = data.puntos;

                                const vigencia = document.getElementById('vigencia_puntos');

                                if (Number(data.puntos) > 0 && data.fecha_vencimiento_texto) {
                                    vigencia.innerText = 'Puedes canjear tus puntos hasta el ' + data.fecha_vencimiento_texto;
                                } else {
                                    vigencia.innerText = '';
                                }

                                let historialHTML = '';

                                /*  data.historial.forEach(function(item) {
                                      historialHTML += `
                                                          <div class="border rounded p-2 mb-2">
                                                              <strong>Remisión:</strong> ${item.folio_remision}<br>
                                                              <strong>Puntos:</strong> ${item.puntos}<br>
                                                              <strong>Fecha:</strong> ${item.fecha}
                                                          </div>
                                                      `;
                                  });

                                  document.getElementById('historial_cliente').innerHTML = historialHTML;*/

                                const modal = new bootstrap.Modal(document.getElementById('modalPuntos'));
                                modal.show();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    text: data.message
                                });
                            }
                        })
                        .catch(function() {
                            Swal.fire({
                                icon: 'error',
                                text: 'Error de conexión. Intenta de nuevo.'
                            });
                        })
                        .finally(function() {
                            consultando = false;
                            btnConsultar.disabled = false;
                        });
                });
            })();
        </script>

</body>

</html>
