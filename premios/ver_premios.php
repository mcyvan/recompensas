<?php
require_once("../app/config/config.php");
require_once("../app/functions/auth.php");
require_once("../app/functions/consultas.php");

verificarSesion();

$esVendedor = in_array($_SESSION['rol'] ?? '', ['VENDEDOR', 'VENDEDORES'], true);
$premios = array_values(array_filter(obtenerPremios(), static function (array $premio): bool {
    return (int) ($premio['activo'] ?? 0) === 1;
}));

$stmt = $pdo->query(
    "SELECT puntos_por_m3
     FROM tb_configuracion_puntos
     WHERE id_configuracion = 1
     LIMIT 1"
);
$puntosPorM3 = (float) ($stmt->fetchColumn() ?: 0);
?>
<!doctype html>
<html lang="es">

<head>
    <?php include("../app/layout/head.php"); ?>
    <title>Premios - Ver Premios</title>
    <style>
        .premios-carousel-shell {
            max-width: 880px;
            margin: 0 auto;
        }

        .premio-slide {
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 52px 48px;
        }

        .premio-card {
            width: min(100%, 620px);
            border: 1px solid #dde4ee;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.12);
            overflow: hidden;
        }

        .premio-img {
            width: 100%;
            height: 240px;
            object-fit: contain;
            background: #f8fafc;
            padding: 14px;
        }

        .premio-body {
            padding: 22px 26px 24px;
        }

        .premio-nombre {
            font-size: 1.55rem;
            font-weight: 800;
            color: #172033;
            margin-bottom: 4px;
        }

        .premio-categoria {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .premio-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .premio-metric {
            border: 1px solid #e2e8f0;
            border-left: 4px solid #174a94;
            border-radius: 8px;
            padding: 12px 14px;
            background: #f8fafc;
        }

        .premio-metric strong {
            display: block;
            font-size: 1.35rem;
            color: #172033;
            line-height: 1.1;
        }

        .premio-metric span {
            color: #64748b;
            font-size: 0.82rem;
        }

        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            filter: invert(1) grayscale(1);
        }

        .vista-vendedor-premios .app-main,
        .vista-vendedor-premios .app-header,
        .vista-vendedor-premios .app-footer {
            margin-left: 0 !important;
        }

        .vista-vendedor-premios .navbar-nav:first-child {
            display: none;
        }

        .volver-vendedor {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        @media (max-width: 575.98px) {
            .premio-slide {
                min-height: 460px;
                padding: 8px 30px 42px;
            }

            .premio-img {
                height: 190px;
                padding: 10px;
            }

            .premio-meta {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="<?= $esVendedor ? 'vista-vendedor-premios' : 'layout-fixed sidebar-expand-lg sidebar-mini sidebar-collapse sidebar-mini-expand-feature' ?> bg-body-tertiary">
    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <?php include("../app/layout/navbar.php"); ?>
            </div>
        </nav>

        <?php if (!$esVendedor): ?>
            <?php include("../app/layout/menu.php"); ?>
        <?php endif; ?>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">Ver Premios</h3>
                        </div>
                        <?php if ($esVendedor): ?>
                            <div class="col-sm-6 text-sm-end mt-2 mt-sm-0">
                                <a href="../vendedor/menu_vendedor.php" class="volver-vendedor">
                                    <i class="bi bi-arrow-left-circle"></i>
                                    Volver
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid">
                    <div class="premios-carousel-shell">
                        <?php if (empty($premios)): ?>
                            <div class="alert alert-warning">No hay premios activos para mostrar.</div>
                        <?php else: ?>
                            <div id="carouselPremios" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3600">
                                <div class="carousel-indicators">
                                    <?php foreach ($premios as $indice => $premio): ?>
                                        <button type="button" data-bs-target="#carouselPremios" data-bs-slide-to="<?= $indice ?>" class="<?= $indice === 0 ? 'active' : '' ?>" aria-current="<?= $indice === 0 ? 'true' : 'false' ?>" aria-label="Premio <?= $indice + 1 ?>"></button>
                                    <?php endforeach; ?>
                                </div>

                                <div class="carousel-inner">
                                    <?php foreach ($premios as $indice => $premio): ?>
                                        <?php
                                        $puntos = (float) ($premio['puntos_requeridos'] ?? 0);
                                        $metrosNecesarios = $puntosPorM3 > 0 ? $puntos / $puntosPorM3 : 0;
                                        ?>
                                        <div class="carousel-item <?= $indice === 0 ? 'active' : '' ?>">
                                            <div class="premio-slide">
                                                <article class="premio-card">
                                                    <img
                                                        src="<?= htmlspecialchars((string) $premio['imagen'], ENT_QUOTES, 'UTF-8') ?>"
                                                        class="premio-img"
                                                        alt="<?= htmlspecialchars((string) $premio['premio'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <div class="premio-body">
                                                        <div class="premio-nombre"><?= htmlspecialchars((string) $premio['premio'], ENT_QUOTES, 'UTF-8') ?></div>
                                                        <div class="premio-categoria"><?= htmlspecialchars((string) $premio['categoria'], ENT_QUOTES, 'UTF-8') ?></div>
                                                        <p class="text-muted mb-0"><?= htmlspecialchars((string) $premio['descripcion'], ENT_QUOTES, 'UTF-8') ?></p>
                                                        <div class="premio-meta">
                                                            <div class="premio-metric">
                                                                <strong><?= number_format($puntos, 0) ?></strong>
                                                                <span>puntos para completarlo</span>
                                                            </div>
                                                            <div class="premio-metric">
                                                                <strong><?= $puntosPorM3 > 0 ? number_format($metrosNecesarios, 2) : '-' ?> m&sup3;</strong>
                                                                <span>metros aproximados necesarios</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <button class="carousel-control-prev" type="button" data-bs-target="#carouselPremios" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Anterior</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#carouselPremios" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Siguiente</span>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <?php include("../app/layout/footer.php"); ?>
    </div>

    <?php include("../app/layout/footer_links.php"); ?>
</body>

</html>
