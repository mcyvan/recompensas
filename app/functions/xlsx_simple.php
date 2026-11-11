<?php

// Generador minimo de archivos .xlsx (sin librerias externas; solo ZipArchive, que ya usa la conciliacion).
//
// Uso:
//   $ruta = xlsxCrear([
//       ['nombre' => 'Obras', 'anchos' => [20, 30], 'cabecera' => 1, 'filtro' => true,
//        'filas' => [ [celda, celda], ... ]],
//   ]);
//   ... enviar el archivo ... unlink($ruta);
//
// Cada celda es un valor simple (texto o numero) o un arreglo:
//   ['v' => valor, 's' => estilo]                        texto o numero con estilo
//   ['fecha' => 'Y-m-d' | 'Y-m-d H:i:s', 's' => estilo]  fecha real de Excel
//   ['url' => 'https://...', 'v' => 'texto', 's' => 'enlace']  enlace
//   ['f' => 'SUBTOTAL(109,G2:G9)', 'v' => 12.5, 's' => estilo] formula con su ultimo valor
// 'cabecera' es el numero de fila con los titulos: se congela y, con 'filtro', lleva autofiltro.
// Los textos se guardan siempre como texto (nunca se interpretan como formulas).

const XLSX_ESTILOS = [
    'normal' => 0, 'cabecera' => 1, 'fecha' => 2, 'num2' => 3, 'entero' => 4, 'texto' => 5,
    'alerta1' => 6, 'alerta2' => 7, 'alerta3' => 8, 'enlace' => 9, 'titulo' => 10, 'negrita' => 11,
    'porcentaje' => 12, 'seccion' => 13, 'fechahora' => 14, 'nota' => 15,
];

function xlsxLimpiar(mixed $texto): string
{
    $texto = (string) $texto;
    // Caracteres de control no permitidos en XML 1.0.
    $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $texto) ?? '';

    return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function xlsxColumna(int $indice): string
{
    $letras = '';
    for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
        $letras = chr(65 + ($n - 1) % 26) . $letras;
    }

    return $letras;
}

function xlsxSerialFecha(string $fecha): ?float
{
    $ts = strtotime($fecha . ' UTC');
    if ($ts === false) {
        return null;
    }

    return $ts / 86400 + 25569;
}

function xlsxEstilos(): string
{
    $borde = '<border><left style="thin"><color rgb="FFD0D7E2"/></left><right style="thin"><color rgb="FFD0D7E2"/></right>'
        . '<top style="thin"><color rgb="FFD0D7E2"/></top><bottom style="thin"><color rgb="FFD0D7E2"/></bottom><diagonal/></border>';
    $xf = static fn(int $numFmt, int $font, int $fill, int $border, string $alineacion = '') =>
        '<xf numFmtId="' . $numFmt . '" fontId="' . $font . '" fillId="' . $fill . '" borderId="' . $border . '" xfId="0"'
        . ($numFmt ? ' applyNumberFormat="1"' : '') . ($font ? ' applyFont="1"' : '') . ($fill ? ' applyFill="1"' : '')
        . ($border ? ' applyBorder="1"' : '') . ($alineacion !== '' ? ' applyAlignment="1">' . $alineacion . '</xf>' : '/>');

    $arriba = '<alignment vertical="top"/>';
    $ajuste = '<alignment vertical="top" wrapText="1"/>';
    $centro = '<alignment horizontal="center" vertical="center" wrapText="1"/>';
    $centroTop = '<alignment horizontal="center" vertical="top"/>';

    $xfs = [
        $xf(0, 0, 0, 0),                    // 0 normal
        $xf(0, 1, 2, 1, $centro),           // 1 cabecera
        $xf(164, 0, 0, 1, $centroTop),      // 2 fecha
        $xf(4, 0, 0, 1, $arriba),           // 3 num2
        $xf(3, 0, 0, 1, $arriba),           // 4 entero
        $xf(0, 0, 0, 1, $ajuste),           // 5 texto
        $xf(0, 4, 3, 1, $centroTop),        // 6 alerta 1
        $xf(0, 4, 4, 1, $centroTop),        // 7 alerta 2
        $xf(0, 4, 5, 1, $centroTop),        // 8 alerta 3
        $xf(0, 2, 0, 1, $arriba),           // 9 enlace
        $xf(0, 3, 0, 0),                    // 10 titulo
        $xf(0, 4, 0, 1, $arriba),           // 11 negrita
        $xf(9, 0, 0, 1, $arriba),           // 12 porcentaje
        $xf(0, 4, 6, 1, $arriba),           // 13 seccion
        $xf(165, 0, 0, 1, $centroTop),      // 14 fecha y hora
        $xf(0, 5, 0, 0),                    // 15 nota
    ];

    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<numFmts count="2"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/><numFmt numFmtId="165" formatCode="dd/mm/yyyy hh:mm"/></numFmts>'
        . '<fonts count="6">'
        . '<font><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><u/><sz val="11"/><color rgb="FF0563C1"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="14"/><color rgb="FF172033"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><b/><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
        . '<font><i/><sz val="10"/><color rgb="FF64748B"/><name val="Calibri"/><family val="2"/></font>'
        . '</fonts>'
        . '<fills count="7">'
        . '<fill><patternFill patternType="none"/></fill>'
        . '<fill><patternFill patternType="gray125"/></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FF174A94"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFE699"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFFFC999"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFF4A6AD"/><bgColor indexed="64"/></patternFill></fill>'
        . '<fill><patternFill patternType="solid"><fgColor rgb="FFE9EEF5"/><bgColor indexed="64"/></patternFill></fill>'
        . '</fills>'
        . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border>' . $borde . '</borders>'
        . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
        . '<cellXfs count="' . count($xfs) . '">' . implode('', $xfs) . '</cellXfs>'
        . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
        . '</styleSheet>';
}

function xlsxCelda(string $ref, mixed $celda): string
{
    if (!is_array($celda)) {
        $celda = ['v' => $celda];
    }

    $estilo = $celda['s'] ?? null;
    $indiceEstilo = is_string($estilo) ? (XLSX_ESTILOS[$estilo] ?? 0) : (int) $estilo;

    if (isset($celda['fecha'])) {
        $serial = $celda['fecha'] !== '' ? xlsxSerialFecha((string) $celda['fecha']) : null;
        if ($serial === null) {
            return '<c r="' . $ref . '" s="' . $indiceEstilo . '"/>';
        }

        return '<c r="' . $ref . '" s="' . $indiceEstilo . '"><v>' . $serial . '</v></c>';
    }

    if (isset($celda['url'])) {
        $url = str_replace('"', '""', (string) $celda['url']);
        $texto = (string) ($celda['v'] ?? 'Abrir');

        return '<c r="' . $ref . '" s="' . $indiceEstilo . '" t="str"><f>HYPERLINK("' . xlsxLimpiar($url) . '","'
            . xlsxLimpiar(str_replace('"', '""', $texto)) . '")</f><v>' . xlsxLimpiar($texto) . '</v></c>';
    }

    if (isset($celda['f'])) {
        return '<c r="' . $ref . '" s="' . $indiceEstilo . '"><f>' . xlsxLimpiar($celda['f']) . '</f><v>'
            . (float) ($celda['v'] ?? 0) . '</v></c>';
    }

    $valor = $celda['v'] ?? null;
    if ($valor === null || $valor === '') {
        return '<c r="' . $ref . '" s="' . $indiceEstilo . '"/>';
    }

    if (is_int($valor) || is_float($valor)) {
        return '<c r="' . $ref . '" s="' . $indiceEstilo . '"><v>' . $valor . '</v></c>';
    }

    return '<c r="' . $ref . '" s="' . $indiceEstilo . '" t="inlineStr"><is><t xml:space="preserve">'
        . xlsxLimpiar($valor) . '</t></is></c>';
}

function xlsxHoja(array $hoja): string
{
    $filas = $hoja['filas'] ?? [];
    $anchos = $hoja['anchos'] ?? [];
    $cabecera = (int) ($hoja['cabecera'] ?? 0);
    $maxColumnas = max(1, $anchos ? count($anchos) : 1);
    foreach ($filas as $fila) {
        $maxColumnas = max($maxColumnas, count($fila));
    }
    $ultimaColumna = xlsxColumna($maxColumnas - 1);
    $ultimaFila = max(1, count($filas));

    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<dimension ref="A1:' . $ultimaColumna . $ultimaFila . '"/>'
        . '<sheetViews><sheetView workbookViewId="0"' . (!empty($hoja['activa']) ? ' tabSelected="1"' : '') . '>';
    if ($cabecera > 0) {
        $xml .= '<pane ySplit="' . $cabecera . '" topLeftCell="A' . ($cabecera + 1) . '" activePane="bottomLeft" state="frozen"/>'
            . '<selection pane="bottomLeft" activeCell="A' . ($cabecera + 1) . '" sqref="A' . ($cabecera + 1) . '"/>';
    }
    $xml .= '</sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/>';

    if ($anchos) {
        $xml .= '<cols>';
        foreach ($anchos as $i => $ancho) {
            $xml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float) $ancho . '" customWidth="1"/>';
        }
        $xml .= '</cols>';
    }

    $xml .= '<sheetData>';
    foreach ($filas as $r => $fila) {
        $numero = $r + 1;
        $alto = ($cabecera === $numero) ? ' ht="30" customHeight="1"' : '';
        $xml .= '<row r="' . $numero . '"' . $alto . '>';
        foreach (array_values($fila) as $c => $celda) {
            $xml .= xlsxCelda(xlsxColumna($c) . $numero, $celda);
        }
        $xml .= '</row>';
    }
    $xml .= '</sheetData>';

    if ($cabecera > 0 && !empty($hoja['filtro']) && !empty($hoja['filtro_hasta'])) {
        $xml .= '<autoFilter ref="A' . $cabecera . ':' . $ultimaColumna . (int) $hoja['filtro_hasta'] . '"/>';
    }

    return $xml . '</worksheet>';
}

// Crea el .xlsx en un archivo temporal y devuelve su ruta (quien lo llama debe borrarlo).
function xlsxCrear(array $hojas): string
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('El servidor no tiene habilitada la extension zip para crear el Excel.');
    }

    $ruta = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    if ($zip->open($ruta, ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('No se pudo crear el archivo de Excel.');
    }

    $tipos = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
    $libro = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
        . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>';
    $relaciones = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';

    foreach (array_values($hojas) as $i => $hoja) {
        $n = $i + 1;
        $nombre = mb_substr(preg_replace('/[\[\]\:\*\?\/\\\\]/u', ' ', (string) $hoja['nombre']) ?? 'Hoja', 0, 31);
        $hoja['activa'] = $i === 0;
        $zip->addFromString("xl/worksheets/sheet{$n}.xml", xlsxHoja($hoja));
        $tipos .= '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        $libro .= '<sheet name="' . xlsxLimpiar($nombre) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
        $relaciones .= '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>';
    }

    $estilosId = count($hojas) + 1;
    $relaciones .= '<Relationship Id="rId' . $estilosId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    $libro .= '</sheets></workbook>';
    $tipos .= '</Types>';

    $zip->addFromString('[Content_Types].xml', $tipos);
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>');
    $zip->addFromString('xl/workbook.xml', $libro);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $relaciones);
    $zip->addFromString('xl/styles.xml', xlsxEstilos());
    $zip->close();

    return $ruta;
}
