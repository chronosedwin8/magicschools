<?php
/**
 * Mapa del sitio para los buscadores.
 * Se arma con el catálogo real: cada herramienta nueva aparece sola.
 */
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');
header('X-Robots-Tag: noindex');

$hoy = date('Y-m-d');
$paginas = [
    ['', '1.0', 'weekly'],
    ['herramientas', '0.9', 'weekly'],
    ['tutor', '0.8', 'monthly'],
    ['contacto.php', '0.5', 'monthly'],
    ['legal.php', '0.4', 'yearly'],
    ['derechos.php', '0.4', 'yearly'],
];
foreach (ai_tools_visible() as $t) {
    $paginas[] = ['herramientas/' . $t['slug'], '0.8', 'monthly'];
}

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($paginas as [$ruta, $prioridad, $frecuencia]) {
    echo "  <url>\n";
    echo '    <loc>', htmlspecialchars(canonical_url($ruta), ENT_XML1), "</loc>\n";
    echo '    <lastmod>', $hoy, "</lastmod>\n";
    echo '    <changefreq>', $frecuencia, "</changefreq>\n";
    echo '    <priority>', $prioridad, "</priority>\n";
    echo "  </url>\n";
}
echo '</urlset>', "\n";
