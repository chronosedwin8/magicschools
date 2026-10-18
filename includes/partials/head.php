<?php
/**
 * Cabecera común de todas las páginas.
 *
 * Variables que puede definir cada página antes de incluirla:
 *   $pageTitle, $pageDescription, $pageCanonical (ruta relativa),
 *   $pageNoIndex (true para que no se indexe), $pageJsonLd (array de bloques),
 *   $extraCss, $bodyClass.
 *
 * @var string $pageTitle
 * @var string $bodyClass
 */
$pageTitle = $pageTitle ?? BRAND_NAME;
$pageDescription = seo_description($pageDescription
    ?? 'Plataforma de inteligencia artificial para docentes y colegios de Colombia: planea clases, evalúa y comunícate con las familias en minutos.');
$canonical = canonical_url($pageCanonical ?? ltrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/'));
$noIndex = !empty($pageNoIndex);
?><!doctype html>
<html lang="es-CO" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta name="robots" content="<?= $noIndex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large, max-snippet:-1' ?>">
    <meta name="theme-color" content="#6B3EF2">
    <meta name="author" content="<?= e(COMPANY_NAME) ?>">

    <!-- Al compartir el enlace (WhatsApp, Facebook, LinkedIn, X) -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(BRAND_NAME) ?>">
    <meta property="og:locale" content="es_CO">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:image" content="<?= e(seo_image()) ?>">
    <meta property="og:image:alt" content="<?= e(BRAND_NAME) ?>, herramientas de inteligencia artificial para docentes">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($pageTitle) ?>">
    <meta name="twitter:description" content="<?= e($pageDescription) ?>">
    <meta name="twitter:image" content="<?= e(seo_image()) ?>">

    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/style.css') ?>">
    <?php foreach ((array) ($extraCss ?? []) as $sheet): if ($sheet === '') continue; ?>
        <link rel="stylesheet" href="<?= e(url($sheet)) ?>?v=<?= filemtime(__DIR__ . '/../../' . $sheet) ?>">
    <?php endforeach; ?>
    <?= jsonld_tag((array) ($pageJsonLd ?? [])) ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<a class="skip-link" href="#contenido">Saltar al contenido</a>
