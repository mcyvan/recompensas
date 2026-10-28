<?php

// Este reporte se movio a la pestaña "Graficas y resultados" de Remisiones,
// junto con la conciliacion de ventas. Se deja este redirect por si alguien
// tiene el link viejo guardado.
require_once __DIR__ . '/../app/config/config.php';

header('Location: ' . $URL . '/remisiones/index.php?tab=conciliacion');
exit;
