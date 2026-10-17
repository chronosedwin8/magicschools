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
