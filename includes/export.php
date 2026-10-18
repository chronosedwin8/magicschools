<?php
/**
 * Exportación de documentos: HTML listo para imprimir y PDF.
 *
 * El HTML que se genera aquí sirve para los dos formatos: es la misma hoja que
 * descarga el docente y la que se convierte a PDF.
 */

/** Documento completo con la marca, el encabezado y el aviso de IA. */
function export_document_html(string $title, string $markdown, array $meta = [], bool $conMathJax = true): string
{
    $cuerpo = md_to_html($markdown);
    // Las fórmulas se dibujan con MathJax; en PDF ya vienen convertidas a símbolos.
    $formulas = $conMathJax && tex_present($markdown) ? tex_mathjax_tag() : '';
    $sub = array_filter([
        $meta['herramienta'] ?? '',
        $meta['institucion'] ?? '',
        $meta['autor'] ?? '',
        $meta['fecha'] ?? '',
    ], 'strlen');

    return '<!doctype html>
<html lang="es"><head><meta charset="utf-8">
<title>' . e($title) . '</title>
<style>
  @page { margin: 2cm 1.8cm; }
  body { font-family: "DejaVu Sans", Arial, Helvetica, sans-serif; color: #1B1036; font-size: 11.5pt; line-height: 1.55; margin: 0; }
  .wrap { max-width: 720px; margin: 0 auto; padding: 24px; }
  .doc-header { border-bottom: 2px solid #6B3EF2; padding-bottom: 10px; margin-bottom: 22px; }
  .doc-brand { font-size: 9.5pt; letter-spacing: .08em; text-transform: uppercase; color: #6B3EF2; font-weight: bold; }
  .doc-sub { font-size: 9.5pt; color: #6E6887; margin-top: 4px; }
  h1 { font-size: 19pt; margin: 6px 0 0; }
  h2 { font-size: 14pt; margin: 20px 0 6px; color: #2E1570; }
  h3 { font-size: 12.5pt; margin: 16px 0 4px; }
  h4 { font-size: 11.5pt; margin: 12px 0 4px; color: #3D3358; }
  p { margin: 0 0 9px; }
  ul { margin: 0 0 10px; padding-left: 18px; }
  li { margin-bottom: 4px; }
  strong { color: #1B1036; }
  .doc-footer { margin-top: 26px; border-top: 1px solid #E7E2F3; padding-top: 10px; font-size: 8.5pt; color: #6E6887; }
</style>' . $formulas . '</head>
<body><div class="wrap">
  <div class="doc-header">
    <div class="doc-brand">' . e(BRAND_NAME) . '</div>
    ' . ($sub ? '<div class="doc-sub">' . e(implode(' · ', $sub)) . '</div>' : '') . '
  </div>
  ' . $cuerpo . '
  <div class="doc-footer">Documento preparado con apoyo de inteligencia artificial y revisado por el docente.
    ' . e(BRAND_NAME) . ' · ' . e(COMPANY_NAME) . '</div>
</div></body></html>';
}

/** ¿Está disponible la librería de PDF? */
function export_pdf_available(): bool
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        return false;
    }
    require_once $autoload;
    return class_exists(\Dompdf\Dompdf::class);
}

/** Envía el PDF al navegador. Si la librería no está, cae a HTML. */
function export_pdf(string $html, string $filename): never
{
    if (!export_pdf_available()) {
        app_log('ai', 'PDF solicitado sin librería instalada');
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . preg_replace('/\.pdf$/', '.html', $filename) . '"');
        echo $html;
        exit;
    }

    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', false);      // sin recursos externos
    $options->set('isPhpEnabled', false);
    $options->set('isJavascriptEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');  // tildes y eñes
    $options->set('chroot', realpath(__DIR__ . '/..'));
    $options->set('tempDir', __DIR__ . '/../storage/cache');

    if (!is_dir(__DIR__ . '/../storage/cache')) {
        @mkdir(__DIR__ . '/../storage/cache', 0775, true);
    }

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();

    // Pie con numeración de páginas
    $canvas = $dompdf->getCanvas();
    $canvas->page_text(520, 770, '{PAGE_NUM} / {PAGE_COUNT}', null, 8, [0.43, 0.41, 0.53]);

    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}

/** ¿Están las librerías de Office disponibles? */
function export_office_available(): bool
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        return false;
    }
    require_once $autoload;
    return class_exists(\PhpOffice\PhpWord\PhpWord::class) && extension_loaded('zip');
}

/**
 * Documento de Word a partir del Markdown del resultado.
 * Nunca retorna: envía el archivo al navegador.
 */
function export_docx(string $markdown, string $title, array $meta, string $filename): never
{
    if (!export_office_available()) {
        export_fallback_html($markdown, $title, $meta, $filename);
    }

    $word = new \PhpOffice\PhpWord\PhpWord();
    $word->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language(\PhpOffice\PhpWord\Style\Language::ES_ES));
    $word->setDefaultFontName('Calibri');
    $word->setDefaultFontSize(11);

    $word->addTitleStyle(1, ['size' => 18, 'bold' => true, 'color' => '1B1036'], ['spaceAfter' => 160]);
    $word->addTitleStyle(2, ['size' => 14, 'bold' => true, 'color' => '2E1570'], ['spaceBefore' => 240, 'spaceAfter' => 120]);
    $word->addTitleStyle(3, ['size' => 12, 'bold' => true, 'color' => '3D3358'], ['spaceBefore' => 180, 'spaceAfter' => 80]);

    $section = $word->addSection(['marginTop' => 1134, 'marginBottom' => 1134, 'marginLeft' => 1134, 'marginRight' => 1134]);

    // Encabezado con la marca y los datos del documento
    $section->addText(mb_strtoupper(BRAND_NAME), ['size' => 9, 'bold' => true, 'color' => '6B3EF2']);
    $sub = array_filter([$meta['herramienta'] ?? '', $meta['institucion'] ?? '', $meta['autor'] ?? '', $meta['fecha'] ?? ''], 'strlen');
    if ($sub) {
        $section->addText(implode(' · ', $sub), ['size' => 9, 'color' => '6E6887'], ['spaceAfter' => 240]);
    }

    foreach (md_tokens($markdown) as $tok) {
        if ($tok['type'] === 'li') {
            $par = $section->addListItemRun(0, \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED, ['spaceAfter' => 60]);
            foreach (md_runs($tok['text']) as $run) {
                $par->addText($run['text'], ['bold' => $run['bold'], 'italic' => $run['italic']]);
            }
            continue;
        }
        if (in_array($tok['type'], ['h1', 'h2', 'h3'], true)) {
            $nivel = (int) substr($tok['type'], 1);
            $section->addTitle(strip_tags(preg_replace('/\*+/', '', $tok['text'])), $nivel);
            continue;
        }
        $par = $section->addTextRun(['spaceAfter' => 120, 'align' => 'both']);
        foreach (md_runs($tok['text']) as $run) {
            $par->addText($run['text'], ['bold' => $run['bold'], 'italic' => $run['italic']]);
        }
    }

    $section->addTextBreak(1);
    $section->addText('Documento preparado con apoyo de inteligencia artificial y revisado por el docente. ' . BRAND_NAME,
        ['size' => 8, 'color' => '6E6887', 'italic' => true]);

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    \PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save('php://output');
    exit;
}


/**
 * Presentación en PowerPoint con diseño, no con texto suelto.
 *
 * El lienzo es de 960 x 540 (16:9). Hay tres plantillas: portada y cierre en
 * morado a toda página, y las intermedias en blanco con barra lateral, título
 * acentuado, viñetas aireadas y pie con la numeración. El tamaño del texto se
 * ajusta a la cantidad de viñetas para que la diapositiva nunca quede apretada.
 */
function export_pptx(array $data, string $title, array $meta, string $filename): never
{
    if (!export_office_available() || !class_exists(\PhpOffice\PhpPresentation\PhpPresentation::class)) {
        export_fallback_html(ai_render_markdown(['slug' => 'presentacion'], $data), $title, $meta, $filename);
    }

    $ppt = new \PhpOffice\PhpPresentation\PhpPresentation();
    $ppt->getDocumentProperties()
        ->setCreator(BRAND_NAME)
        ->setTitle(mb_substr($title, 0, 120))
        ->setSubject((string) ($meta['institucion'] ?? ''));
    $ppt->getLayout()->setDocumentLayout(\PhpOffice\PhpPresentation\DocumentLayout::LAYOUT_SCREEN_16X9);

    // Paleta de la marca (ARGB)
    $MORADO   = 'FF4A1FB8';
    $MORADO_2 = 'FF6B3EF2';
    $MORADO_3 = 'FF5A2DE0';
    $LILA     = 'FFC9B8FF';
    $LILA_BG  = 'FFF4EFFF';
    $TINTA    = 'FF3D3358';
    $GRIS     = 'FF6E6887';
    $AMARILLO = 'FFFFC940';
    $BLANCO   = 'FFFFFFFF';

    $color = fn(string $argb) => new \PhpOffice\PhpPresentation\Style\Color($argb);

    /** Caja de texto limpia, sin márgenes internos heredados. */
    $caja = function ($slide, float $x, float $y, float $w, float $h) {
        $s = $slide->createRichTextShape()->setOffsetX($x)->setOffsetY($y)->setWidth($w)->setHeight($h);
        $s->setInsetLeft(0)->setInsetRight(0)->setInsetTop(0)->setInsetBottom(0);
        return $s;
    };
    /** Rectángulo de color: una caja de texto vacía con relleno. */
    $barra = function ($slide, float $x, float $y, float $w, float $h, string $argb) use ($caja, $color) {
        $s = $caja($slide, $x, $y, $w, $h);
        $s->getFill()->setFillType(\PhpOffice\PhpPresentation\Style\Fill::FILL_SOLID)
            ->setStartColor($color($argb))->setEndColor($color($argb));
        return $s;
    };
    $texto = function ($shape, string $contenido, int $size, string $argb, bool $bold = false) use ($color) {
        $run = $shape->createTextRun(mb_substr($contenido, 0, 600));
        $run->getFont()->setName('Trebuchet MS')->setSize($size)->setBold($bold)->setColor($color($argb));
        return $run;
    };

    $diapositivas = array_values(array_filter((array) ($data['diapositivas'] ?? []), 'is_array'));
    if (!$diapositivas) {
        $diapositivas = [['titulo' => $title, 'puntos' => []]];
    }
    $total = count($diapositivas);
    $subtitulo = trim(implode(' · ', array_filter([
        (string) ($meta['grado'] ?? ''),
        (string) ($meta['area'] ?? ''),
        (string) ($meta['institucion'] ?? ''),
    ], 'strlen')));

    foreach ($diapositivas as $i => $d) {
        $slide = $i === 0 ? $ppt->getActiveSlide() : $ppt->createSlide();
        $tituloTexto = trim((string) ($d['titulo'] ?? ''));
        $puntos = array_values(array_filter(array_map(
            fn($p) => trim((string) $p),
            (array) ($d['puntos'] ?? [])
        ), 'strlen'));
        $esPortada = $i === 0;
        $esCierre = !$esPortada && $i === $total - 1 && $total > 2;

        if ($esPortada || $esCierre) {
            // ---- Portada y cierre: morado a toda página --------------------
            $fondo = new \PhpOffice\PhpPresentation\Slide\Background\Color();
            $fondo->setColor($color($MORADO));
            $slide->setBackground($fondo);
            $barra($slide, 648, 0, 312, 540, $MORADO_3);     // banda de profundidad
            $barra($slide, 72, 168, 84, 8, $AMARILLO);       // acento

            $t = $caja($slide, 72, 196, 540, 160);
            $t->getActiveParagraph()->setLineSpacing(105);
            $texto($t, $tituloTexto !== '' ? $tituloTexto : $title, $esPortada ? 40 : 34, $BLANCO, true);

            $sub = $esPortada ? $subtitulo : implode('   ·   ', array_slice($puntos, 0, 3));
            if ($esPortada && $puntos) {
                $sub = trim($puntos[0] . ($sub !== '' ? '  ·  ' . $sub : ''));
            }
            if ($sub !== '') {
                $s = $caja($slide, 72, 368, 540, 60);
                $s->getActiveParagraph()->setLineSpacing(120);
                $texto($s, $sub, 17, $LILA);
            }
            $pie = $caja($slide, 72, 456, 520, 28);
            $texto($pie, BRAND_NAME . ($esPortada ? '' : ' · ¿Preguntas?'), 13, $LILA);
        } else {
            // ---- Diapositiva de contenido ----------------------------------
            $barra($slide, 0, 0, 12, 540, $MORADO_2);        // barra lateral
            $t = $caja($slide, 64, 44, 800, 68);
            $t->getActiveParagraph()->setLineSpacing(105);
            $texto($t, $tituloTexto, 28, $MORADO, true);
            $barra($slide, 64, 120, 72, 5, $AMARILLO);       // subrayado del título

            if ($puntos) {
                // Menos viñetas, letra más grande: la diapositiva se lee de lejos.
                $tam = count($puntos) <= 3 ? 22 : (count($puntos) <= 5 ? 19 : 17);
                $cuerpo = $caja($slide, 64, 156, 760, 250);
                $primer = true;
                foreach ($puntos as $p) {
                    $par = $primer ? $cuerpo->getActiveParagraph() : $cuerpo->createParagraph();
                    $primer = false;
                    $par->getBulletStyle()
                        ->setBulletType(\PhpOffice\PhpPresentation\Style\Bullet::TYPE_BULLET)
                        ->setBulletChar('▪')
                        ->setBulletFont('Trebuchet MS');
                    $par->getAlignment()->setMarginLeft(28)->setIndent(-28);
                    $par->setLineSpacing(125);
                    $par->setSpacingAfter(14);
                    $run = $par->createTextRun(mb_substr($p, 0, 300));
                    $run->getFont()->setName('Trebuchet MS')->setSize($tam)->setColor($color($TINTA));
                }
            }

            if (!empty($d['imagen_sugerida'])) {
                $chip = $barra($slide, 64, 418, 520, 38, $LILA_BG);
                $chip->setInsetLeft(12)->setInsetRight(12)->setInsetTop(8);
                $texto($chip, 'Imagen sugerida: ' . mb_substr((string) $d['imagen_sugerida'], 0, 95), 11, $MORADO);
            }

            $pie = $caja($slide, 64, 486, 620, 22);
            $texto($pie, BRAND_NAME . ' · Borrador generado con IA: revísalo antes de proyectarlo.', 9, $GRIS);

            $num = $caja($slide, 836, 482, 60, 26);
            $num->getActiveParagraph()->getAlignment()
                ->setHorizontal(\PhpOffice\PhpPresentation\Style\Alignment::HORIZONTAL_RIGHT);
            $texto($num, ($i + 1) . ' / ' . $total, 11, $GRIS, true);
        }

        if (!empty($d['nota_docente'])) {
            $slide->getNote()->createRichTextShape()->createTextRun(mb_substr((string) $d['nota_docente'], 0, 900));
        }
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.presentationml.presentation');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    \PhpOffice\PhpPresentation\IOFactory::createWriter($ppt, 'PowerPoint2007')->save('php://output');
    exit;
}

/** Si falta la librería, se entrega el HTML para no dejar al docente sin su documento. */
function export_fallback_html(string $markdown, string $title, array $meta, string $filename): never
{
    app_log('ai', 'Exportación sin librería disponible', ['archivo' => $filename]);
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . preg_replace('/\.(docx|pptx|pdf)$/', '.html', $filename) . '"');
    echo export_document_html($title, $markdown, $meta);
    exit;
}
