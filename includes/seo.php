<?php
/**
 * SEO: metadatos y datos estructurados.
 *
 * Cada página pública declara su título, su descripción y su URL canónica, y
 * de aquí salen las etiquetas que leen Google y las redes sociales. Los datos
 * estructurados (JSON-LD) se arman con el catálogo y los planes reales, para
 * que lo que ve el buscador sea lo mismo que ve el visitante.
 */

/** URL absoluta y canónica de una ruta del sitio. */
function canonical_url(string $path = ''): string
{
    $base = BASE_URL !== '' ? rtrim(BASE_URL, '/') : rtrim(base_url(), '/');
    $path = ltrim($path, '/');
    return $path === '' ? $base . '/' : $base . '/' . $path;
}

/** Título de pestaña: el de la página más la marca, sin pasarse de largo. */
function seo_title(string $titulo): string
{
    $completo = $titulo . ' | ' . BRAND_NAME;
    if (mb_strlen($completo) <= 65) {
        return $completo;
    }
    // Si no cabe con la marca, mejor el título solo que un recorte a media palabra.
    return mb_strlen($titulo) <= 65 ? $titulo : mb_substr($titulo, 0, 64) . '…';
}

/** Recorta una descripción al largo que muestran los buscadores. */
function seo_description(string $texto): string
{
    $texto = trim(preg_replace('/\s+/u', ' ', strip_tags($texto)));
    return mb_strlen($texto) <= 158 ? $texto : mb_substr($texto, 0, 155) . '…';
}

/** Imagen para compartir en redes. */
function seo_image(): string
{
    return canonical_url('assets/img/og-magicschools.png');
}

/** La empresa: se referencia desde los demás bloques con @id. */
function jsonld_organizacion(): array
{
    return [
        '@type' => 'Organization',
        '@id' => canonical_url() . '#organizacion',
        'name' => BRAND_NAME,
        'legalName' => COMPANY_NAME,
        'url' => canonical_url(),
        'logo' => canonical_url('assets/img/favicon.svg'),
        'email' => SUPPORT_EMAIL,
        'areaServed' => ['@type' => 'Country', 'name' => 'Colombia'],
        'contactPoint' => [[
            '@type' => 'ContactPoint',
            'contactType' => 'customer support',
            'email' => SUPPORT_EMAIL,
            'availableLanguage' => ['es'],
        ]],
    ];
}

/** El producto: una aplicación web con sus planes de pago. */
function jsonld_aplicacion(): array
{
    global $PLANS;
    $ofertas = [];
    foreach ($PLANS as $p) {
        $ofertas[] = [
            '@type' => 'Offer',
            'name' => $p['name'],
            'price' => (string) (int) $p['price'],
            'priceCurrency' => CURRENCY,
            'category' => ($p['audience'] ?? 'institucion') === 'docente' ? 'Plan individual' : 'Licencia institucional',
            'url' => canonical_url('#' . (($p['audience'] ?? 'institucion') === 'docente' ? 'docentes' : 'precios')),
            'availability' => 'https://schema.org/InStock',
        ];
    }
    return [
        '@type' => 'SoftwareApplication',
        '@id' => canonical_url() . '#aplicacion',
        'name' => BRAND_NAME,
        'applicationCategory' => 'EducationalApplication',
        'operatingSystem' => 'Navegador web',
        'inLanguage' => 'es-CO',
        'url' => canonical_url(),
        'description' => seo_description(
            'Plataforma de inteligencia artificial para docentes y colegios de Colombia: planea clases, '
            . 'crea evaluaciones, elabora el PIAR y comunícate con las familias en minutos.'
        ),
        'publisher' => ['@id' => canonical_url() . '#organizacion'],
        'offers' => $ofertas,
        'featureList' => array_values(array_map(fn($t) => $t['name'], ai_tools_visible())),
    ];
}

/** Preguntas frecuentes, tal como aparecen en la página. */
function jsonld_faq(array $faqs): array
{
    $items = [];
    foreach ($faqs as [$pregunta, $respuesta]) {
        $items[] = [
            '@type' => 'Question',
            'name' => $pregunta,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $respuesta],
        ];
    }
    return ['@type' => 'FAQPage', '@id' => canonical_url() . '#faq', 'mainEntity' => $items];
}

/** Migas de pan para las páginas internas. */
function jsonld_migas(array $ruta): array
{
    $items = [];
    foreach ($ruta as $i => [$nombre, $enlace]) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $nombre,
            'item' => canonical_url($enlace),
        ];
    }
    return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

/** Lista del catálogo de herramientas, para la página que las reúne. */
function jsonld_catalogo(array $tools): array
{
    $items = [];
    foreach (array_values($tools) as $i => $t) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $t['name'],
            'description' => $t['desc'],
            'url' => canonical_url('herramientas/' . $t['slug']),
        ];
    }
    return [
        '@type' => 'ItemList',
        'name' => 'Herramientas de IA para docentes',
        'numberOfItems' => count($items),
        'itemListElement' => $items,
    ];
}

/** Envuelve los bloques en un solo <script> de JSON-LD. */
function jsonld_tag(array $bloques): string
{
    $bloques = array_values(array_filter($bloques));
    if (!$bloques) {
        return '';
    }
    $grafo = ['@context' => 'https://schema.org', '@graph' => $bloques];
    $json = json_encode($grafo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    // El contenido va dentro de <script>: se neutraliza cualquier cierre de etiqueta.
    $json = str_replace(['<', '>', '&'], ['<', '>', '&'], (string) $json);
    return '<script type="application/ld+json">' . $json . '</script>';
}
