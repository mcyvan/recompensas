<?php

// Copia este archivo como google_sheets.local.php y llena los valores reales.
// google_sheets.local.php NUNCA se sube a git (ver .gitignore).
return [
    // Del archivo .json que descargas al crear la clave de la cuenta de servicio en
    // Google Cloud Console (APIs y servicios > Credenciales > Cuenta de servicio > Claves).
    'client_email' => '',
    'private_key' => '',

    // ID de la hoja de calculo (esta en la URL: .../spreadsheets/d/ESTE_ID/edit).
    'spreadsheet_id' => '',

    // Nombre de las pestañas por planta dentro de esa hoja.
    'hojas_por_planta' => [
        'NORTE' => 'Remisiones Norte',
        'OESTE' => 'Remisiones Oeste',
        'SUR' => 'Remisiones Sur',
    ],
];
