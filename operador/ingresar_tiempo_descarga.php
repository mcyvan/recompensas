<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once('../app/functions/consultas.php');
verificarSesion();

if (isset($_SESSION['mensaje_registro_remision_correcto'])) {
    $mensaje_registro_remision_correcto = $_SESSION['mensaje_registro_remision_correcto'];
    unset($_SESSION['mensaje_registro_remision_correcto']);
} else {
    $mensaje_registro_remision_correcto = null;
}
?>
<!doctype html>
<html lang="es">
<?php include("../app/layout/head.php"); ?>

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse bg-body-tertiary">

    <?php if ($mensaje_registro_remision_correcto): ?>
        <script>
            Swal.fire({
                text: "<?= $mensaje_registro_remision_correcto ?>",
                icon: "success"
            });
        </script>
    <?php endif; ?>

    <div class="app-wrapper">

        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <?php include("../app/layout/navbar.php"); ?>
            </div>
        </nav>

        <main class="app-main">
            <div class="content-wrapper">
                <div class="content-header">
                    <div class="container-fluid">

                        <div class="row justify-content-center">
                            <div class="col-sm-6">

                                <div class="card bg-light m-3">
                                    <form id="formRemision" action="../controller/controller_registrar_remision.php" method="POST">

                                        <div class="card-header">
                                            <b>MENU OPERADOR</b>
                                        </div>

                                        <div class="card-body">

                                            <!-- TELEFONO -->
                                            <div class="m-2">
                                                <label><b>1.</b> Teléfono:</label>
                                                <input type="text"
                                                    class="form-control"
                                                    id="telefono"
                                                    name="telefono"
                                                    inputmode="numeric"
                                                    maxlength="10"
                                                    placeholder="6141234567"
                                                    required>

                                                <small id="clienteInfo"></small>
                                            </div>

                                            <!-- REMISION -->
                                            <div class="m-2">
                                                <label><b>2.</b> Remisión:</label>
                                                <input type="text" class="form-control" id="remision" name="remision" disabled>
                                                <small id="remisionInfo"></small>
                                            </div>

                                            <!-- METROS -->
                                            <div class="m-2">
                                                <label><b>3.</b> Metros:</label>
                                                <input type="number"
                                                    class="form-control"
                                                    id="metros"
                                                    name="volumen"
                                                    min="1"
                                                    max="8"
                                                    step="0.5"
                                                    disabled>

                                                <small id="metrosInfo"></small>
                                            </div>

                                        </div>

                                        <div class="card-footer d-flex justify-content-between">
                                            <a href="menu_operador.php" class="btn btn-secondary">Regresar</a>
                                            <button type="submit" class="btn btn-primary">Iniciar Descarga</button>
                                        </div>

                                    </form>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>

        <?php include("../app/layout/footer.php"); ?>
    </div>

    <?php include("../app/layout/footer_links.php"); ?>

    <script>
        let telefonoValido = false;
        let remisionValida = false;
        let metrosValidos = false;

        let timeoutTelefono = null;
        let timeoutRemision = null;

        const telefonoInput = document.getElementById("telefono");
        const remisionInput = document.getElementById("remision");
        const metrosInput = document.getElementById("metros");

        const clienteInfo = document.getElementById("clienteInfo");
        const remisionInfo = document.getElementById("remisionInfo");
        const metrosInfo = document.getElementById("metrosInfo");

        // bloquear
        remisionInput.disabled = true;
        metrosInput.disabled = true;

        /* ================= TELEFONO ================= */
        telefonoInput.addEventListener("input", function() {

            // 🔥 solo números
            this.value = this.value.replace(/\D/g, '');
            let telefono = this.value;

            clearTimeout(timeoutTelefono);

            if (telefono.length < 10) {
                clienteInfo.innerHTML = "Debe tener 10 dígitos";
                clienteInfo.className = "text-danger";

                telefonoValido = false;
                remisionInput.disabled = true;
                metrosInput.disabled = true;
                return;
            }

            timeoutTelefono = setTimeout(() => {

                let formData = new FormData();
                formData.append("telefono", telefono);

                fetch("../ajax/ajax_validar_telefono.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {

                        if (data.existe) {
                            telefonoValido = true;

                            clienteInfo.innerHTML = "Cliente: " + data.nombre + " " + data.apellido_p + " " + data.apellido_m;
                            clienteInfo.className = "text-success";

                            remisionInput.disabled = false;

                        } else {
                            telefonoValido = false;

                            clienteInfo.innerHTML = "Cliente no encontrado";
                            clienteInfo.className = "text-danger";

                            remisionInput.disabled = true;
                            metrosInput.disabled = true;
                        }

                    })
                    .catch(() => {
                        clienteInfo.innerHTML = "Error al validar";
                        clienteInfo.className = "text-danger";
                    });

            }, 400);
        });

        /* ================= REMISION ================= */
        remisionInput.addEventListener("input", function() {

            let valor = this.value.toUpperCase();
            this.value = valor;

            clearTimeout(timeoutRemision);

            if (!/^RE\d+$/.test(valor)) {
                remisionInfo.innerHTML = "Formato inválido (RE123)";
                remisionValida = false;
                metrosInput.disabled = true;
                return;
            }

            remisionInfo.innerHTML = "Validando...";

            timeoutRemision = setTimeout(() => {

                let formData = new FormData();
                formData.append("remision", valor);

                fetch("../ajax/ajax_validar_remision.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {

                        if (data.existe) {
                            remisionValida = false;
                            remisionInfo.innerHTML = "Ya registrada";
                            remisionInfo.className = "text-danger";
                            metrosInput.disabled = true;

                        } else {
                            remisionValida = true;
                            remisionInfo.innerHTML = "Disponible";
                            remisionInfo.className = "text-success";
                            metrosInput.disabled = false;
                        }

                    });

            }, 400);
        });

        /* ================= METROS ================= */
        metrosInput.addEventListener("input", function() {

            let valor = parseFloat(this.value);

            if (valor < 1 || valor > 8) {
                metrosInfo.innerHTML = "1 a 8 m³";
                metrosValidos = false;
                return;
            }

            if ((valor * 10) % 5 !== 0) {
                metrosInfo.innerHTML = "Solo .0 o .5";
                metrosValidos = false;
                return;
            }

            metrosInfo.innerHTML = "OK";
            metrosInfo.className = "text-success";
            metrosValidos = true;
        });

        /* ================= SUBMIT ================= */
        document.getElementById("formRemision").addEventListener("submit", function(e) {

            if (!telefonoValido || !remisionValida || !metrosValidos) {
                e.preventDefault();
                alert("Completa correctamente los datos");
            }
        });
    </script>

</body>

</html>