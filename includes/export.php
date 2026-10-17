<?php
/**
 * Exportación de documentos: HTML listo para imprimir y PDF.
 *
 * El HTML que se genera aquí sirve para los dos formatos: es la misma hoja que
 * descarga el docente y la que se convierte a PDF.
 */

/** Documento completo con la marca, el encabezado y el aviso de IA. */
function export_document_html(string $title, string $markdown, array $meta = []): string
{
    $cuerpo = md_to_html($markdown);
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
</style></head>
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
 * Presentación de PowerPoint a partir de la respuesta estructurada.
 * Nunca retorna.
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

    $morado = new \PhpOffice\PhpPresentation\Style\Color('FF4A1FB8');
    $tinta = new \PhpOffice\PhpPresentation\Style\Color('FF1B1036');
    $primera = true;

    foreach ((array) ($data['diapositivas'] ?? []) as $d) {
        $slide = $primera ? $ppt->getActiveSlide() : $ppt->createSlide();
        $primera = false;

        $tituloBox = $slide->createRichTextShape()->setHeight(80)->setWidth(860)->setOffsetX(60)->setOffsetY(50);
        $run = $tituloBox->createTextRun(mb_substr((string) ($d['titulo'] ?? ''), 0, 120));
        $run->getFont()->setBold(true)->setSize(26)->setColor($morado);

        $puntos = array_filter((array) ($d['puntos'] ?? []), 'strlen');
        if ($puntos) {
            $cuerpo = $slide->createRichTextShape()->setHeight(360)->setWidth(860)->setOffsetX(60)->setOffsetY(150);
            $cuerpo->getActiveParagraph()->getBulletStyle()
                ->setBulletType(\PhpOffice\PhpPresentation\Style\Bullet::TYPE_BULLET)
                ->setBulletChar('•');
            $i = 0;
            foreach ($puntos as $p) {
                if ($i++ > 0) {
                    $cuerpo->createParagraph()->getBulletStyle()
                        ->setBulletType(\PhpOffice\PhpPresentation\Style\Bullet::TYPE_BULLET)->setBulletChar('•');
                }
                $t = $cuerpo->createTextRun(mb_substr((string) $p, 0, 300));
                $t->getFont()->setSize(16)->setColor($tinta);
            }
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
