<?php
/**
 * Catálogo público de herramientas y ficha de cada una.
 *
 * Es la parte del sitio pensada para que la encuentren en Google: una página
 * por herramienta, con su propio título, su descripción, lo que pide y lo que
 * entrega. Usar la herramienta sigue requiriendo licencia; aquí solo se
 * explica qué hace.
 *
 *   /herramientas            catálogo completo
 *   /herramientas/<slug>     ficha de una herramienta
 */
require_once __DIR__ . '/includes/bootstrap.php';

$slug = (string) ($_GET['h'] ?? '');
$tool = $slug !== '' ? ai_tool($slug) : null;
if ($slug !== '' && (!$tool || !empty($tool['hidden']))) {
    http_response_code(404);
    $tool = null;
    $slug = '';
    $noEncontrada = true;
}

$categorias = ai_tool_categories();
$todas = ai_tools_visible();
$bodyClass = 'page-tools';

// Cuántas herramientas hay por categoría, para el texto del catálogo
$porCategoria = [];
foreach ($todas as $t) {
    $porCategoria[$t['category']][] = $t;
}

if ($tool) {
    // ------------------------------------------------------------------ ficha
    $campos = array_values(array_filter($tool['fields'], fn($f) => empty($f['hidden'])));
    $relacionadas = array_values(array_filter(
        $todas,
        fn($t) => $t['category'] === $tool['category'] && $t['slug'] !== $tool['slug']
    ));
    shuffle($relacionadas);
    $relacionadas = array_slice($relacionadas, 0, 3);

    $formatos = ['html' => 'HTML', 'pdf' => 'PDF', 'docx' => 'Word', 'pptx' => 'PowerPoint'];
    $exporta = array_values(array_intersect_key($formatos, array_flip((array) $tool['exports'])));

    $pageTitle = seo_title($tool['name'] . ' con IA');
    $pageDescription = $tool['desc'] . ' Herramienta para docentes de Colombia, con descarga en '
        . implode(', ', $exporta) . '.';
    $pageCanonical = 'herramientas/' . $tool['slug'];
    $pageJsonLd = [
        jsonld_organizacion(),
        jsonld_migas([
            ['Inicio', ''],
            ['Herramientas de IA', 'herramientas'],
            [$tool['name'], 'herramientas/' . $tool['slug']],
        ]),
        [
            '@type' => 'HowTo',
            'name' => 'Cómo crear ' . lcfirst($tool['name']) . ' con inteligencia artificial',
            'description' => $tool['desc'],
            'inLanguage' => 'es-CO',
            'step' => [
                ['@type' => 'HowToStep', 'name' => 'Abre la herramienta', 'text' => 'Entra al portal con tu cuenta y elige ' . $tool['name'] . '.'],
                ['@type' => 'HowToStep', 'name' => 'Completa lo mínimo', 'text' => 'Diligencia ' . count($campos) . ' campos: ' . implode(', ', array_map(fn($f) => mb_strtolower($f['label']), array_slice($campos, 0, 4))) . '.'],
                ['@type' => 'HowToStep', 'name' => 'Revisa el borrador', 'text' => 'La IA entrega un borrador; tú lo revisas, lo ajustas y lo apruebas.'],
                ['@type' => 'HowToStep', 'name' => 'Descárgalo', 'text' => 'Guarda el documento en ' . implode(', ', $exporta) . '.'],
            ],
        ],
    ];

    include __DIR__ . '/includes/partials/head.php';
    include __DIR__ . '/includes/partials/nav.php';
    ?>
    <main id="contenido">
        <section class="section section-soft tool-hero">
            <div class="container narrow">
                <nav class="crumbs" aria-label="Ruta">
                    <a href="<?= e(url('')) ?>">Inicio</a> ›
                    <a href="<?= e(url('herramientas')) ?>">Herramientas</a> ›
                    <span><?= e($tool['name']) ?></span>
                </nav>
                <span class="tool-icon c-<?= e($tool['color']) ?> tool-icon-lg"><?= icon($tool['icon'], 32) ?></span>
                <h1><?= e($tool['name']) ?> con inteligencia artificial</h1>
                <p class="lead"><?= e($tool['desc']) ?></p>
                <p class="muted"><strong><?= e($categorias[$tool['category']] ?? '') ?></strong> ·
                    Descarga en <?= e(implode(', ', $exporta)) ?> ·
                    <?= count($campos) ?> datos para empezar</p>
                <div class="hero-ctas">
                    <a class="btn btn-primary btn-lg" href="<?= e(url('portal/herramienta.php?h=' . $tool['slug'])) ?>">Usar la herramienta</a>
                    <a class="btn btn-outline btn-lg" href="<?= e(url('#precios')) ?>">Ver planes</a>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container narrow">
                <h2>Qué te pide</h2>
                <p>Solo lo necesario para que el resultado sirva en tu aula. Cada campo de texto libre se puede
                    escribir con ayuda de la IA o mejorar en redacción con un clic.</p>
                <ul class="checklist">
                    <?php foreach ($campos as $f): ?>
                        <li><strong><?= e($f['label']) ?></strong><?php if (empty($f['required'])): ?> <span class="muted">(opcional)</span><?php endif; ?>
                            <?php if (!empty($f['help'])): ?><br><span class="muted small"><?= e($f['help']) ?></span><?php endif; ?></li>
                    <?php endforeach; ?>
                </ul>

                <h2>Qué recibes</h2>
                <p>Un borrador completo en español, pensado para el contexto colombiano: se apoya en los referentes
                    del Ministerio de Educación (estándares y DBA), en el Diseño Universal para el Aprendizaje y en la
                    evaluación formativa del Decreto 1290 de 2009. Lo revisas, lo ajustas y lo apruebas: la decisión
                    pedagógica siempre es tuya.</p>
                <ul class="checklist">
                    <li>Documento listo para usar o imprimir.</li>
                    <li>Descarga en <?= e(implode(', ', $exporta)) ?>.</li>
                    <li>Queda guardado en tu biblioteca de documentos.</li>
                    <li>Puedes pedir ajustes: más corto, más simple, otro tono o para las familias.</li>
                    <?php if (!empty($tool['consent'])): ?>
                        <li>Trata datos de estudiantes: exige que la institución declare la autorización de las familias.</li>
                    <?php endif; ?>
                </ul>

                <h2>Cómo se usa</h2>
                <ol class="steps-list">
                    <li>Entra al portal con tu cuenta de <?= e(BRAND_NAME) ?>.</li>
                    <li>Abre <strong><?= e($tool['name']) ?></strong> y completa los campos.</li>
                    <li>Espera el borrador: la IA trabaja mientras tú sigues en otra cosa.</li>
                    <li>Revísalo, pídele ajustes si hace falta y descárgalo.</li>
                </ol>

                <?php if ($relacionadas): ?>
                    <h2>Herramientas relacionadas</h2>
                    <div class="tools-grid tools-grid-sm">
                        <?php foreach ($relacionadas as $r): ?>
                            <a class="tool-card" href="<?= e(url('herramientas/' . $r['slug'])) ?>">
                                <span class="tool-icon c-<?= e($r['color']) ?>"><?= icon($r['icon'], 22) ?></span>
                                <h3><?= e($r['name']) ?></h3>
                                <p><?= e($r['desc']) ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p class="center mt-40"><a class="btn btn-primary btn-lg" href="<?= e(url('#precios')) ?>">Ver planes y precios</a></p>
            </div>
        </section>
    </main>
    <?php
    include __DIR__ . '/includes/partials/footer.php';
    exit;
}

// -------------------------------------------------------------------- catálogo
$pageTitle = seo_title(count($todas) . ' herramientas de IA para docentes');
$pageDescription = 'Catálogo de ' . count($todas) . ' herramientas de inteligencia artificial para docentes de Colombia: '
    . 'planes de clase, exámenes, rúbricas, PIAR, proyectos ABP, circulares y más, con descarga en PDF, Word y PowerPoint.';
$pageCanonical = 'herramientas';
$pageJsonLd = [
    jsonld_organizacion(),
    jsonld_migas([['Inicio', ''], ['Herramientas de IA', 'herramientas']]),
    jsonld_catalogo($todas),
];

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/nav.php';
?>
<main id="contenido">
    <section class="section section-soft">
        <div class="container narrow center">
            <?php if (!empty($noEncontrada)): ?>
                <p class="alert alert-warning">No encontramos esa herramienta. Mira el catálogo completo.</p>
            <?php endif; ?>
            <span class="eyebrow">Catálogo</span>
            <h1><?= count($todas) ?> herramientas de IA para docentes de Colombia</h1>
            <p class="lead">Planeación, evaluación, apoyo al aprendizaje y comunicación con las familias.
                Cada herramienta pide lo mínimo y entrega un borrador listo para revisar, con descarga en
                PDF, Word, PowerPoint y HTML.</p>
        </div>
    </section>

    <?php foreach ($categorias as $clave => $nombre): if (empty($porCategoria[$clave])) continue; ?>
        <section class="section" id="<?= e($clave) ?>">
            <div class="container">
                <div class="section-head">
                    <h2><?= e($nombre) ?> <span class="muted">(<?= count($porCategoria[$clave]) ?>)</span></h2>
                </div>
                <div class="tools-grid">
                    <?php foreach ($porCategoria[$clave] as $t): ?>
                        <a class="tool-card" href="<?= e(url('herramientas/' . $t['slug'])) ?>">
                            <span class="tool-icon c-<?= e($t['color']) ?>"><?= icon($t['icon'], 22) ?></span>
                            <h3><?= e($t['name']) ?></h3>
                            <p><?= e($t['desc']) ?></p>
                            <span class="tool-link">Ver cómo funciona <span aria-hidden="true">→</span></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endforeach; ?>

    <section class="section section-soft">
        <div class="container narrow center">
            <h2>Todas incluidas en tu licencia</h2>
            <p>No se cobran por separado ni por uso: tu plan trae un cupo mensual de generaciones para toda la
                institución, y cada documento queda guardado en tu biblioteca.</p>
            <a class="btn btn-primary btn-lg" href="<?= e(url('#precios')) ?>">Ver planes y precios</a>
        </div>
    </section>
</main>
<?php include __DIR__ . '/includes/partials/footer.php'; ?>
