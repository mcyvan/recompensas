<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
verificarSesion();

$id_remision = $_GET['id'] ?? null;

if (!$id_remision) {
    header("Location: ingresar_tiempo_descarga.php");
    exit();
}

$stmt = $pdo->prepare("SELECT folio_remision, volumen, hora_inicio FROM tb_remisiones WHERE id_remision = ?");
$stmt->execute([$id_remision]);

$remision = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$remision) {
    header("Location: ingresar_tiempo_descarga.php");
    exit();
}

$folio = $remision['folio_remision'];
$volumen = $remision['volumen'];
$hora_inicio = $remision['hora_inicio'];
?>

<!doctype html>
<html lang="es">
<!--begin::Head-->
<?php include("../app/layout/head.php"); ?>
<!--end::Head-->
<!--begin::Body-->

<body class="layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature bg-body-tertiary">

    <div class="app-wrapper">

        <!-- NAVBAR -->
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <?php include("../app/layout/navbar.php"); ?>
            </div>
        </nav>

        <!-- MAIN -->
        <main class="app-main">
            <div class="content-wrapper">

                <div class="content-header">
                    <div class="container-fluid">

                        <div class="row mb-2 justify-content-center">
                            <div class="col-sm-6">

                                <div class="col-sm-4">
                                    <div class="card bg-light m-3">
                                        <div class="card-body text-center">

                                            <h5 class="card-title mb-3">REMISIÓN EN PROCESO</h5>

                                            <p><b>Folio:</b> <?= $folio ?></p>
                                            <p><b>Volumen:</b> <?= $volumen ?> m³</p>

                                            <!-- ⏱️ CRONÓMETRO -->
                                            <h1 id="cronometro" style="font-size: 2.5rem;">00:00:00</h1>
                                            <p class="text-muted">Tiempo límite: 45 minutos</p>

                                            <!-- BOTÓN FINALIZAR -->
                                            <form action="../controller/controller_finalizar_remision.php" method="POST">
                                                <input type="hidden" name="id_remision" value="<?= $id_remision ?>">

                                                <button type="submit" class="btn btn-success btn-square-lg mt-3">
                                                    FINALIZAR
                                                </button>
                                            </form>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <!-- FOOTER -->
        <?php include("../app/layout/footer.php"); ?>
    </div>
    <?php include("../app/layout/footer_links.php"); ?>
    <script>
        let tiempoInicio = "<?= $hora_inicio ?>";

        function iniciarCronometro() {

            let inicio = new Date(tiempoInicio).getTime();

            setInterval(() => {

                let ahora = new Date().getTime();
                let diferencia = ahora - inicio;

                let segundos = Math.floor(diferencia / 1000);
                let minutos = Math.floor(segundos / 60);
                let horas = Math.floor(minutos / 60);

                segundos = segundos % 60;
                minutos = minutos % 60;

                let tiempoFormateado =
                    String(horas).padStart(2, '0') + ":" +
                    String(minutos).padStart(2, '0') + ":" +
                    String(segundos).padStart(2, '0');

                let cronometro = document.getElementById("cronometro");
                cronometro.innerText = tiempoFormateado;

                // 🔴 si pasa 45 min
                let minutosTotales = Math.floor(diferencia / 60000);

                if (minutosTotales >= 45) {
                    cronometro.style.color = "red";
                }

            }, 1000);
        }

        iniciarCronometro();
    </script>

</body>

</html>