<?php
/** @var string $pageTitle */
/** @var string $bodyClass */
$pageTitle = $pageTitle ?? BRAND_NAME;
$pageDescription = $pageDescription ?? 'Plataforma de inteligencia artificial segura para colegios, docentes y estudiantes.';
?><!doctype html>
<html lang="es" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#6B3EF2">
    <link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>?v=<?= filemtime(__DIR__ . '/../../assets/css/style.css') ?>">
    <?php if (!empty($extraCss)): ?>
        <link rel="stylesheet" href="<?= e(url($extraCss)) ?>?v=<?= filemtime(__DIR__ . '/../../' . $extraCss) ?>">
    <?php endif; ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
