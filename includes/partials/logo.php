<?php $logoLight = $logoLight ?? false; ?>
<span class="logo<?= $logoLight ? ' logo-light' : '' ?>">
    <svg class="logo-mark" viewBox="0 0 40 40" aria-hidden="true">
        <defs>
            <linearGradient id="lg-mark" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#8B5CF6"/>
                <stop offset=".55" stop-color="#6B3EF2"/>
                <stop offset="1" stop-color="#FF4FA3"/>
            </linearGradient>
        </defs>
        <rect width="40" height="40" rx="11" fill="url(#lg-mark)"/>
        <path d="M20 8.5l2.7 7.3 7.3 2.7-7.3 2.7L20 28.5l-2.7-7.3-7.3-2.7 7.3-2.7z" fill="#fff"/>
        <circle cx="29.5" cy="28.5" r="2.4" fill="#FFC940"/>
        <circle cx="11" cy="29" r="1.6" fill="#fff" opacity=".8"/>
    </svg>
    <span class="logo-text"><?= e(BRAND_SHORT) ?><strong>IA</strong></span>
</span>
