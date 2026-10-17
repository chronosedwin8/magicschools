<?php
/**
 * Íconos de línea propios del sitio. Todos usan currentColor y el mismo grosor,
 * para que el conjunto se vea homogéneo y profesional.
 *
 * Uso:  <?= icon('plan') ?>   o   <?= icon('escudo', 32) ?>
 */
function icon(string $name, int $size = 24): string
{
    $paths = [
        // --- Herramientas -------------------------------------------------
        'presentacion' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M12 16v4M8 20h8M7 12V9M11 12V7M15 12v-2"/>',
        'plan'         => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 3v4M16 3v4M8 12h8M8 16h5"/>',
        'escritos'     => '<path d="M4 20h16"/><path d="M14.5 4.5a2.1 2.1 0 0 1 3 3L9 16l-4 1 1-4z"/>',
        'idea'         => '<path d="M9 18h6M10 21h4"/><path d="M12 3a6 6 0 0 0-3.5 10.9c.6.5.9 1.2 1 1.9h5c.1-.7.4-1.4 1-1.9A6 6 0 0 0 12 3z"/>',
        'boletin'      => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h4"/>',
        'quiz'         => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9.5l2 2 3.5-3.5M7 15.5l2 2 3.5-3.5M16 10h3M16 16h3"/>',
        'tutor'        => '<rect x="4" y="7" width="16" height="12" rx="3"/><path d="M12 3v4M9 12.5h.01M15 12.5h.01M10 16h4"/>',
        'reescribir'   => '<path d="M4 8h12a4 4 0 0 1 0 8H8"/><path d="M7 5L4 8l3 3M11 19l-3-3 3-3"/>',
        'rubrica'      => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 10h18M3 15h18M9 4v16M15 4v16"/>',
        'inclusion'    => '<circle cx="12" cy="7" r="3"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/><path d="M3 12h2M19 12h2"/>',
        'pasos'        => '<path d="M4 20h4v-4h4v-4h4V8h4"/><path d="M4 20v-4"/>',
        'correo'       => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 7l8.5 6 8.5-6"/>',
        // --- Soluciones y confianza ---------------------------------------
        'colegio'      => '<path d="M3 21h18M5 21V10l7-5 7 5v11"/><path d="M10 21v-5h4v5"/>',
        'docente'      => '<circle cx="12" cy="8" r="3.5"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0"/>',
        'estudiante'   => '<path d="M3 9l9-4 9 4-9 4-9-4z"/><path d="M7 11.5V16c0 1.4 2.2 2.5 5 2.5s5-1.1 5-2.5v-4.5"/>',
        'reloj'        => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'escudo'       => '<path d="M12 3l8 3v6c0 5-3.4 8.4-8 9.8C7.4 20.4 4 17 4 12V6z"/><path d="M9 12l2 2 4-4.5"/>',
        'candado'      => '<rect x="4.5" y="10" width="15" height="10" rx="2"/><path d="M8 10V7.5a4 4 0 0 1 8 0V10"/>',
        'manos'        => '<path d="M3 12l4-4 5 5 5-5 4 4"/><path d="M7 16l5 4 5-4"/>',
        'certificado'  => '<circle cx="12" cy="9.5" r="5"/><path d="M9 14l-1.5 6L12 18l4.5 2L15 14"/>',
        'formacion'    => '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>',
        'comunidad'    => '<circle cx="8" cy="9" r="3"/><circle cx="16" cy="9" r="3"/><path d="M2.5 19a5.5 5.5 0 0 1 11 0M12.5 19a5.5 5.5 0 0 1 9 0"/>',
        // --- Documentos y pago --------------------------------------------
        'documento'    => '<path d="M6 3h8l5 5v13H6z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
        'privacidad'   => '<path d="M12 3l8 3v6c0 5-3.4 8.4-8 9.8C7.4 20.4 4 17 4 12V6z"/><circle cx="12" cy="11" r="2"/><path d="M12 13v3"/>',
        'reembolso'    => '<path d="M4 12a8 8 0 1 1 2.5 5.8"/><path d="M4 7v5h5"/>',
        'tarjeta'      => '<rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19M6 15h4"/>',
        'banco'        => '<path d="M3 10l9-5 9 5"/><path d="M5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 21h18"/>',
        'efectivo'     => '<rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/>',
        'cifrado'      => '<rect x="4.5" y="10" width="15" height="10" rx="2"/><path d="M8 10V7.5a4 4 0 0 1 8 0V10M12 14v2"/>',
        'globo'        => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 2.5 15.4 0 18-2.5-2.6-2.5-15.4 0-18z"/>',
        'chispa'       => '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/>',
        'flecha'       => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    ];

    $d = $paths[$name] ?? $paths['chispa'];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" '
        . 'stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" '
        . 'aria-hidden="true" focusable="false">' . $d . '</svg>';
}
