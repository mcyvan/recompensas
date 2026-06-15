<?php
require_once('../app/config/config.php');
require_once('../app/functions/auth.php');
require_once('../app/functions/canjes.php');

verificarSesion();
verificarPermisoCanjes();

if (empty($_SESSION['csrf_canjes'])) {
    $_SESSION['csrf_canjes'] = bin2hex(random_bytes(32));
}

$mensajeCorrecto = $_SESSION['mensaje_canje_correcto'] ?? null;
$mensajeError = $_SESSION['mensaje_canje_error'] ?? null;
$canjeRealizado = $_SESSION['canje_realizado'] ?? null;
$puedeAdministrarCanjes = puedeAdministrarCanjes();
unset($_SESSION['mensaje_canje_correcto'], $_SESSION['mensaje_canje_error'], $_SESSION['canje_realizado']);
?>
<!doctype html>
<html lang="es">
<head>
    <?php include('../app/layout/head.php'); ?>
    <title>Canje de Premios</title>
    <style>
        .premio-imagen-contenedor {
            height: 220px;
            padding: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, .08);
        }
        .premio-imagen {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
        }
        .premio-no-disponible { opacity: .55; }
        .carrito-sticky { position: sticky; top: 1rem; }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid"><?php include('../app/layout/navbar.php'); ?></div>
    </nav>
    <?php include('../app/layout/menu.php'); ?>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid"><h3 class="mb-0">Canje de Premios</h3></div>
        </div>

        <div class="app-content">
            <div class="container-fluid">
                <?php if ($mensajeCorrecto): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($mensajeCorrecto); ?>
                        <?php if ($canjeRealizado): ?>
                            <div><b>Folio:</b> <?php echo htmlspecialchars($canjeRealizado['folio']); ?></div>
                            <div><b>Saldo restante:</b> <?php echo number_format((float) $canjeRealizado['saldo_despues'], 2); ?> puntos</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($mensajeError): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($mensajeError); ?></div>
                <?php endif; ?>

                <div class="card card-primary card-outline mb-4">
                    <div class="card-header"><b>1. Buscar cliente</b></div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <input type="tel" id="telefono" class="form-control form-control-lg" maxlength="10" inputmode="numeric" placeholder="Numero celular de 10 digitos">
                            </div>
                            <div class="col-md-3">
                                <button type="button" id="btnBuscar" class="btn btn-primary btn-lg w-100">Consultar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="contenidoCanje" class="d-none">
                    <div class="alert alert-info d-flex flex-wrap justify-content-between gap-2">
                        <div><b>Cliente:</b> <span id="clienteNombre"></span></div>
                        <div><b>Puntos disponibles:</b> <span id="clienteSaldo">0</span></div>
                    </div>

                    <div class="row">
                        <div class="col-lg-8">
                            <h5>2. Seleccionar premios</h5>
                            <div id="catalogoPremios" class="row g-3 mb-4"></div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card carrito-sticky">
                                <div class="card-header"><b>3. Resumen del canje</b></div>
                                <div class="card-body">
                                    <div id="carritoVacio" class="text-muted">No hay premios seleccionados.</div>
                                    <div id="carritoItems"></div>
                                    <hr>
                                    <div class="d-flex justify-content-between"><b>Total:</b><b><span id="carritoTotal">0</span> puntos</b></div>
                                    <div class="d-flex justify-content-between"><span>Saldo restante:</span><span id="saldoRestante">0</span></div>
                                </div>
                                <div class="card-footer">
                                    <form id="formCanje" action="../controller/controller_confirmar_canje.php" method="post">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_canjes']); ?>">
                                        <input type="hidden" name="telefono" id="telefonoCanje">
                                        <input type="hidden" name="items" id="itemsCanje">
                                        <button type="submit" id="btnConfirmar" class="btn btn-success w-100" disabled>Confirmar canje</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3 mb-4">
                        <div class="card-header"><b>Historial del cliente</b></div>
                        <div class="card-body" id="historialCanjes"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <?php include('../app/layout/footer.php'); ?>
</div>
<?php include('../app/layout/footer_links.php'); ?>
<script>
(function () {
    const csrfToken = <?php echo json_encode($_SESSION['csrf_canjes']); ?>;
    const puedeAdministrarCanjes = <?php echo $puedeAdministrarCanjes ? 'true' : 'false'; ?>;
    const estado = { saldo: 0, premios: {}, carrito: {} };
    const dinero = valor => Number(valor).toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    const escapar = valor => String(valor ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));

    document.getElementById('btnBuscar').addEventListener('click', buscarCliente);
    document.getElementById('telefono').addEventListener('keydown', e => { if (e.key === 'Enter') buscarCliente(); });

    async function buscarCliente() {
        const telefono = document.getElementById('telefono').value.replace(/\D/g, '');
        if (telefono.length !== 10) {
            Swal.fire({ icon: 'warning', text: 'Ingresa un telefono valido de 10 digitos.' });
            return;
        }

        const boton = document.getElementById('btnBuscar');
        boton.disabled = true;
        const body = new URLSearchParams({ telefono, csrf_token: csrfToken });

        try {
            const respuesta = await fetch('buscar_cliente.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            });
            const data = await respuesta.json();
            if (!data.success) throw new Error(data.message || 'No fue posible consultar al cliente.');

            estado.saldo = Number(data.cliente.saldo);
            estado.premios = Object.fromEntries(data.premios.map(p => [String(p.id_premio), p]));
            estado.carrito = {};
            document.getElementById('clienteNombre').textContent = data.cliente.nombre;
            document.getElementById('clienteSaldo').textContent = dinero(estado.saldo);
            document.getElementById('telefonoCanje').value = telefono;
            renderCatalogo(data.premios);
            renderHistorial(data.historial);
            renderCarrito();
            document.getElementById('contenidoCanje').classList.remove('d-none');
        } catch (error) {
            document.getElementById('contenidoCanje').classList.add('d-none');
            Swal.fire({ icon: 'error', text: error.message });
        } finally {
            boton.disabled = false;
        }
    }

    function renderCatalogo(premios) {
        const contenedor = document.getElementById('catalogoPremios');
        if (!premios.length) {
            contenedor.innerHTML = '<div class="col-12"><div class="alert alert-warning">No hay premios activos con existencia.</div></div>';
            return;
        }

        contenedor.innerHTML = premios.map(premio => {
            const alcanza = estado.saldo >= Number(premio.puntos_requeridos);
            return `<div class="col-md-6 col-xl-4">
                <div class="card h-100 ${alcanza ? '' : 'premio-no-disponible'}">
                    <div class="premio-imagen-contenedor">
                        <img src="${escapar(premio.imagen || '')}" class="premio-imagen" alt="${escapar(premio.premio)}">
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${escapar(premio.premio)}</h5>
                        <p class="small text-muted mb-2">${escapar(premio.categoria)}</p>
                        <p class="card-text small flex-grow-1">${escapar(premio.descripcion)}</p>
                        <div><b>${dinero(premio.puntos_requeridos)} puntos</b></div>
                        <div class="small mb-2">Existencia: ${Number(premio.stock)}</div>
                        <button type="button" class="btn btn-outline-success agregar-premio" data-id="${premio.id_premio}" ${alcanza ? '' : 'disabled'}>Agregar</button>
                    </div>
                </div>
            </div>`;
        }).join('');

        contenedor.querySelectorAll('.agregar-premio').forEach(boton => {
            boton.addEventListener('click', () => agregarPremio(boton.dataset.id));
        });
    }

    function agregarPremio(id) {
        const premio = estado.premios[id];
        const cantidadActual = estado.carrito[id] || 0;
        if (cantidadActual >= Number(premio.stock)) return;
        estado.carrito[id] = cantidadActual + 1;
        renderCarrito();
    }

    function cambiarCantidad(id, cambio) {
        const premio = estado.premios[id];
        const nueva = (estado.carrito[id] || 0) + cambio;
        if (nueva <= 0) delete estado.carrito[id];
        else if (nueva <= Number(premio.stock)) estado.carrito[id] = nueva;
        renderCarrito();
    }

    function renderCarrito() {
        const items = Object.entries(estado.carrito);
        const contenedor = document.getElementById('carritoItems');
        document.getElementById('carritoVacio').classList.toggle('d-none', items.length > 0);
        let total = 0;

        contenedor.innerHTML = items.map(([id, cantidad]) => {
            const premio = estado.premios[id];
            const subtotal = Number(premio.puntos_requeridos) * cantidad;
            total += subtotal;
            return `<div class="border-bottom py-2">
                <div class="fw-semibold">${escapar(premio.premio)}</div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary cambiar-cantidad" data-id="${id}" data-cambio="-1">-</button>
                        <span class="btn btn-light disabled">${cantidad}</span>
                        <button type="button" class="btn btn-outline-secondary cambiar-cantidad" data-id="${id}" data-cambio="1">+</button>
                    </div>
                    <span>${dinero(subtotal)} pts</span>
                </div>
            </div>`;
        }).join('');

        contenedor.querySelectorAll('.cambiar-cantidad').forEach(boton => {
            boton.addEventListener('click', () => cambiarCantidad(boton.dataset.id, Number(boton.dataset.cambio)));
        });

        const restante = estado.saldo - total;
        document.getElementById('carritoTotal').textContent = dinero(total);
        document.getElementById('saldoRestante').textContent = dinero(restante);
        document.getElementById('saldoRestante').className = restante < 0 ? 'text-danger fw-bold' : 'text-success fw-bold';
        document.getElementById('itemsCanje').value = JSON.stringify(items.map(([id, cantidad]) => ({ id_premio: Number(id), cantidad })));
        document.getElementById('btnConfirmar').disabled = items.length === 0 || total <= 0 || restante < 0;
    }

    function renderHistorial(historial) {
        const contenedor = document.getElementById('historialCanjes');
        if (!historial.length) {
            contenedor.innerHTML = '<p class="text-muted mb-0">El cliente aun no tiene canjes.</p>';
            return;
        }
        contenedor.innerHTML = historial.map(canje => `<div class="border rounded p-3 mb-2">
            <div class="d-flex flex-wrap justify-content-between"><b>${escapar(canje.folio)}</b><span>${escapar(canje.fecha_canje)}</span></div>
            <div>${canje.detalles.map(d => `${Number(d.cantidad)} x ${escapar(d.premio)}`).join('<br>')}</div>
            <div class="mt-1"><b>Total:</b> ${dinero(canje.total_puntos)} puntos | <b>Saldo posterior:</b> ${dinero(canje.saldo_despues)}</div>
            <div class="mt-1"><b>Estatus:</b> <span class="badge ${canje.estatus === 'CANCELADO' ? 'text-bg-danger' : 'text-bg-success'}">${escapar(canje.estatus)}</span></div>
            ${canje.estatus === 'CANCELADO' && canje.motivo_cancelacion
                ? `<div class="small text-muted mt-1"><b>Motivo:</b> ${escapar(canje.motivo_cancelacion)}</div>`
                : ''}
            ${puedeAdministrarCanjes && canje.estatus === 'CONFIRMADO'
                ? `<button type="button" class="btn btn-outline-danger btn-sm mt-2 cancelar-canje" data-id="${canje.id_canje}" data-folio="${escapar(canje.folio)}">Cancelar canje</button>`
                : ''}
        </div>`).join('');

        contenedor.querySelectorAll('.cancelar-canje').forEach(boton => {
            boton.addEventListener('click', () => solicitarCancelacion(boton.dataset.id, boton.dataset.folio));
        });
    }

    function solicitarCancelacion(idCanje, folio) {
        Swal.fire({
            icon: 'warning',
            title: `Cancelar ${folio}`,
            input: 'textarea',
            inputLabel: 'Motivo de la cancelacion',
            inputPlaceholder: 'Describe por que se cancela este canje...',
            inputAttributes: { maxlength: 500 },
            showCancelButton: true,
            confirmButtonText: 'Si, cancelar canje',
            cancelButtonText: 'Regresar',
            confirmButtonColor: '#dc3545',
            showLoaderOnConfirm: true,
            inputValidator: valor => {
                const motivo = (valor || '').trim();
                if (motivo.length < 10) return 'Escribe un motivo de al menos 10 caracteres.';
            },
            preConfirm: motivo => {
                const formulario = document.createElement('form');
                formulario.method = 'post';
                formulario.action = '../controller/controller_cancelar_canje.php';

                const campos = { id_canje: idCanje, motivo: (motivo || '').trim(), csrf_token: csrfToken };
                Object.entries(campos).forEach(([nombre, valor]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = nombre;
                    input.value = valor;
                    formulario.appendChild(input);
                });

                document.body.appendChild(formulario);
                formulario.submit();
                return false;
            }
        });
    }

    document.getElementById('formCanje').addEventListener('submit', function (evento) {
        evento.preventDefault();
        Swal.fire({
            icon: 'question',
            title: 'Confirmar canje',
            text: 'Se descontaran los puntos y la existencia de los premios.',
            showCancelButton: true,
            confirmButtonText: 'Si, confirmar',
            cancelButtonText: 'Cancelar'
        }).then(resultado => { if (resultado.isConfirmed) evento.target.submit(); });
    });
})();
</script>
</body>
</html>
