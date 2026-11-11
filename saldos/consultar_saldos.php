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

        .marca-consulta {
            width: 180px;
            height: 180px;
            margin: 1.5rem auto 0;
            padding: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .12);
        }

        .marca-consulta img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        @media (max-width: 576px) {
            .marca-consulta {
                width: 156px;
                height: 156px;
                padding: 24px;
            }
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
                                            <div class="input-group input-group-lg">
                                                <input
                                                    type="tel"
                                                    class="form-control text-center"
                                                    id="telefono_cliente"
                                                    placeholder="Ingresa tu teléfono"
                                                    maxlength="10"
                                                    inputmode="numeric"
                                                    autocomplete="tel">
                                                <button class="btn btn-outline-secondary" type="button" id="btnEscanearClienteQr">
                                                    <i class="bi bi-qr-code-scan"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" id="id_cliente_qr" name="id_cliente_qr" value="">
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
                            <div class="marca-consulta" aria-label="Concretos Americas">
                                <img src="../app/img/marca/logo_concretos_americas.png" alt="Concretos Americas">
                            </div>
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

                            <div class="modal fade" id="modalQrCliente" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Escanear credencial</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                        </div>
                                        <div class="modal-body">
                                            <video id="qrVideoCliente" class="w-100 rounded bg-dark" autoplay muted playsinline></video>
                                            <small id="qrClienteInfo" class="d-block mt-2 text-muted">
                                                Apunta la camara al QR de la credencial.
                                            </small>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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
                const btnEscanearClienteQr = document.getElementById('btnEscanearClienteQr');
                const inputTelefono = document.getElementById('telefono_cliente');
                const idClienteQr = document.getElementById('id_cliente_qr');
                const honeypot = document.getElementById('website');
                const modalQrClienteElemento = document.getElementById('modalQrCliente');
                const qrVideoCliente = document.getElementById('qrVideoCliente');
                const qrClienteInfo = document.getElementById('qrClienteInfo');
                let consultando = false;
                let ultimaConsulta = 0;
                let qrModalCliente = null;
                let qrStreamCliente = null;
                let qrDetectorCliente = null;
                let qrEscaneandoCliente = false;
                const esperaMinimaMs = 2000;

                function extraerIdClienteQr(textoQr) {
                    const texto = String(textoQr || '').toUpperCase();
                    const porEtiqueta = texto.match(/(?:CLIENTE_ID|ID_CLIENTE|ID)\s*:?\s*([0-9]+)/);
                    return porEtiqueta ? porEtiqueta[1] : '';
                }

                function extraerTelefonoQr(textoQr) {
                    const texto = String(textoQr || '').toUpperCase();
                    const porEtiqueta = texto.match(/(?:TEL|TELEFONO|TELÉFONO)\s*:?\s*([0-9]{10})/);
                    if (porEtiqueta) {
                        return porEtiqueta[1];
                    }

                    const porDigitos = texto.match(/[0-9]{10}/);
                    return porDigitos ? porDigitos[0] : '';
                }

                function detenerEscanerCliente() {
                    qrEscaneandoCliente = false;

                    if (qrStreamCliente) {
                        qrStreamCliente.getTracks().forEach(function(track) {
                            track.stop();
                        });
                        qrStreamCliente = null;
                    }

                    if (qrVideoCliente) {
                        qrVideoCliente.srcObject = null;
                    }
                }

                function consultarPuntos() {
                    const telefono = inputTelefono.value.replace(/\D/g, '');
                    const idQr = idClienteQr.value.replace(/\D/g, '');
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

                    if (!idQr && telefono.length !== 10) {
                        Swal.fire({
                            icon: 'warning',
                            text: 'Ingresa un teléfono válido de 10 dígitos'
                        });
                        return;
                    }

                    inputTelefono.value = telefono;
                    idClienteQr.value = idQr;
                    consultando = true;
                    btnConsultar.disabled = true;
                    ultimaConsulta = ahora;

                    const body = new URLSearchParams({
                        telefono: telefono,
                        id_cliente_qr: idClienteQr.value,
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
                }

                async function abrirEscanerCliente() {
                    if (!window.isSecureContext) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Camara bloqueada',
                            text: 'Para escanear desde celular, abre el sistema con HTTPS.'
                        });
                        return;
                    }

                    if (!('BarcodeDetector' in window)) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Camara QR no disponible',
                            text: 'Este navegador no soporta lectura QR directa. Puedes escribir el telefono manualmente.'
                        });
                        return;
                    }

                    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Camara no disponible',
                            text: 'El navegador no permite usar la camara en esta pagina.'
                        });
                        return;
                    }

                    try {
                        qrModalCliente = qrModalCliente || new bootstrap.Modal(modalQrClienteElemento);
                        qrModalCliente.show();

                        qrDetectorCliente = qrDetectorCliente || new BarcodeDetector({
                            formats: ['qr_code']
                        });

                        qrStreamCliente = await navigator.mediaDevices.getUserMedia({
                            video: {
                                facingMode: {
                                    ideal: 'environment'
                                }
                            },
                            audio: false
                        });

                        qrVideoCliente.srcObject = qrStreamCliente;
                        await qrVideoCliente.play();

                        qrEscaneandoCliente = true;
                        qrClienteInfo.innerHTML = 'Buscando QR...';
                        qrClienteInfo.className = 'd-block mt-2 text-muted';

                        const escanear = async function() {
                            if (!qrEscaneandoCliente) {
                                return;
                            }

                            try {
                                const codigos = await qrDetectorCliente.detect(qrVideoCliente);

                                if (codigos.length > 0) {
                                    const textoQr = codigos[0].rawValue || '';
                                    const idQr = extraerIdClienteQr(textoQr);
                                    const telefonoQr = extraerTelefonoQr(textoQr);

                                    if (!idQr && !telefonoQr) {
                                        qrClienteInfo.innerHTML = 'El QR no trae datos de cliente validos.';
                                        qrClienteInfo.className = 'd-block mt-2 text-danger';
                                        requestAnimationFrame(escanear);
                                        return;
                                    }

                                    idClienteQr.value = idQr;
                                    inputTelefono.value = telefonoQr;
                                    qrClienteInfo.innerHTML = 'Cliente detectado.';
                                    qrClienteInfo.className = 'd-block mt-2 text-success';
                                    detenerEscanerCliente();
                                    qrModalCliente.hide();
                                    consultarPuntos();
                                    return;
                                }
                            } catch (error) {
                                qrClienteInfo.innerHTML = 'No fue posible leer el QR. Intenta acercar la camara.';
                                qrClienteInfo.className = 'd-block mt-2 text-danger';
                            }

                            requestAnimationFrame(escanear);
                        };

                        requestAnimationFrame(escanear);
                    } catch (error) {
                        detenerEscanerCliente();
                        Swal.fire({
                            icon: 'error',
                            title: 'No se pudo abrir la camara',
                            text: 'Revisa permisos del navegador o escribe el telefono manualmente.'
                        });
                    }
                }

                inputTelefono.addEventListener('input', function() {
                    this.value = this.value.replace(/\D/g, '').slice(0, 10);
                    idClienteQr.value = '';
                });

                btnConsultar.addEventListener('click', consultarPuntos);
                btnEscanearClienteQr.addEventListener('click', abrirEscanerCliente);
                modalQrClienteElemento.addEventListener('hidden.bs.modal', detenerEscanerCliente);
            })();
        </script>

</body>

</html>
