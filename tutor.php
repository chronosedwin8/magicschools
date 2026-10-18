<?php
/**
 * Página pública del tutor con avatar.
 *
 * Explica a los docentes qué es Daniel, qué hace y qué no hace, con el video
 * en el que él mismo se presenta. Es también la página que puede encontrarse
 * en Google por "tutor con avatar para docentes".
 */
require_once __DIR__ . '/includes/bootstrap.php';

$video = 'assets/video/tutor-daniel.mp4';
$hayVideo = is_file(__DIR__ . '/' . $video);
$poster = 'assets/img/tutor-daniel.jpg';
$hayPoster = is_file(__DIR__ . '/' . $poster);

$faqs = [
    ['¿Daniel es una persona real?', 'No. Daniel es una inteligencia artificial con apariencia y voz humanas. Si se lo preguntas, lo dice con claridad. No sustituye a un colega, a un tutor humano ni al equipo de apoyo de tu colegio.'],
    ['¿Qué puedo hablar con él?', 'Lo que prepararías con un colega: el tema de la próxima clase, qué hacer con una actividad que no funcionó, cómo explicar algo difícil, cómo revisar un plan o una evaluación. Él pregunta, propone alternativas y te devuelve la decisión.'],
    ['¿Puede diagnosticar a un estudiante?', 'No, y no lo hará aunque se lo pidas. No diagnostica, no habla de medicamentos y no hace pronósticos. Si le describes un caso, trabaja contigo los ajustes razonables del aula y te remite al equipo de apoyo y a orientación escolar.'],
    ['¿Se graba la conversación?', 'No. La grabación está desactivada en cada llamada. Queda el registro de cuándo fue y cuántos minutos duró, para el control de consumo de tu institución.'],
    ['¿Cuánto dura una conversación?', 'Hasta ' . TAVUS_MAX_MINUTES . ' minutos. Además se cierra sola si pasan ' . (int) round(TAVUS_IDLE_SECONDS / 60) . ' minutos sin interacción, para que no se gasten minutos con la llamada abierta y nadie delante.'],
    ['¿Qué necesito para usarlo?', 'Un computador o celular con cámara y micrófono, conexión estable y un plan que incluya minutos de tutor. Se abre desde el portal, en el navegador: no hay que instalar nada.'],
];

$pageTitle = seo_title('Tutor con avatar para docentes');
$pageDescription = 'Daniel es un tutor con inteligencia artificial que conversa contigo sobre tu clase: te hace preguntas, '
    . 'propone alternativas y te devuelve la decisión pedagógica. Hasta ' . TAVUS_MAX_MINUTES . ' minutos por conversación.';
$pageCanonical = 'tutor';
$bodyClass = 'page-tutor';
$pageJsonLd = [
    jsonld_organizacion(),
    jsonld_migas([['Inicio', ''], ['Tutor con avatar', 'tutor']]),
    jsonld_faq($faqs),
];
if ($hayVideo) {
    $pageJsonLd[] = [
        '@type' => 'VideoObject',
        'name' => 'Daniel, el tutor con avatar de ' . BRAND_NAME,
        'description' => 'Daniel se presenta y explica cómo acompaña a los docentes en la preparación de sus clases.',
        'contentUrl' => canonical_url($video),
        'thumbnailUrl' => canonical_url($hayPoster ? $poster : 'assets/img/og-magicschools.png'),
        'uploadDate' => date('Y-m-d', filemtime(__DIR__ . '/' . $video)),
        'inLanguage' => 'es-CO',
        'publisher' => ['@id' => canonical_url() . '#organizacion'],
    ];
}

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/nav.php';
?>
<main id="contenido">

    <section class="section tutor-top">
        <div class="container tutor-grid">
            <div class="reveal">
                <nav class="crumbs" aria-label="Ruta">
                    <a href="<?= e(url('')) ?>">Inicio</a> › <span>Tutor con avatar</span>
                </nav>
                <span class="eyebrow"><span class="dot"></span> Incluido en los planes con tutor</span>
                <h1>Daniel, tu <span class="text-gradient">tutor con avatar</span></h1>
                <p class="lead">Un tutor con inteligencia artificial que conversa contigo, en video, sobre la clase que
                    vas a dar. Te hace preguntas, te propone alternativas y te devuelve la decisión: el aula es tuya.</p>
                <ul class="hero-checks">
                    <li>Conversación en video, en español</li>
                    <li>Hasta <?= (int) TAVUS_MAX_MINUTES ?> minutos</li>
                    <li>Sin grabación</li>
                </ul>
                <div class="hero-ctas">
                    <a class="btn btn-primary btn-lg" href="<?= e(url('portal/tutor.php')) ?>">Hablar con Daniel</a>
                    <a class="btn btn-outline btn-lg" href="<?= e(url('#docentes')) ?>">Ver planes con tutor</a>
                </div>
            </div>

            <figure class="tutor-video reveal">
                <?php if ($hayVideo): ?>
                    <video controls preload="metadata" playsinline <?= $hayPoster ? 'poster="' . e(url($poster)) . '"' : '' ?>>
                        <source src="<?= e(url($video)) ?>" type="video/mp4">
                        Tu navegador no puede reproducir este video.
                    </video>
                    <figcaption>Daniel se presenta. Es una inteligencia artificial, no una persona.</figcaption>
                <?php else: ?>
                    <div class="tutor-video-placeholder">
                        <span><?= icon('tutor', 40) ?></span>
                        <p>El video de presentación estará disponible en unos minutos.</p>
                    </div>
                <?php endif; ?>
            </figure>
        </div>
    </section>

    <section class="section section-soft">
        <div class="container">
            <div class="section-head reveal">
                <span class="eyebrow">Cómo se usa</span>
                <h2>Como hablar con un colega en la <span class="text-gradient">sala de profesores</span></h2>
            </div>
            <div class="use-grid reveal">
                <article class="use-card">
                    <h3>1. Dile qué vas a trabajar</h3>
                    <p>Escribes en una línea el tema o la situación: "cómo evaluar fracciones en quinto sin desmotivar
                        al grupo". Con eso entra en tema desde el saludo.</p>
                </article>
                <article class="use-card">
                    <h3>2. Conversen de verdad</h3>
                    <p>Cuéntale qué pasó en la última clase, qué actividad no funcionó o qué explicación se te enreda.
                        Él pregunta, propone alternativas y te deja ensayar en voz alta.</p>
                </article>
                <article class="use-card">
                    <h3>3. Cierra con un acuerdo</h3>
                    <p>Antes de terminar te pide un compromiso concreto para la próxima clase. Si se acaba el tiempo,
                        te avisa dos minutos antes.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container two-col-wide">
            <div class="reveal">
                <h2>Lo que hace</h2>
                <ul class="checklist">
                    <li>Prepara contigo la clase, el taller o la evaluación.</li>
                    <li>Te da alternativas cuando algo no funcionó en el aula.</li>
                    <li>Te deja ensayar la explicación difícil antes de darla.</li>
                    <li>Te hace preguntas para revisar un plan o una rúbrica.</li>
                    <li>Se apoya en el marco colombiano: estándares y DBA del MEN, DUA y Decreto 1421 de 2017.</li>
                </ul>
            </div>
            <div class="reveal">
                <h2>Lo que no hace</h2>
                <ul class="checklist checklist-no">
                    <li>No se hace pasar por una persona: dice que es una inteligencia artificial.</li>
                    <li>No diagnostica estudiantes ni habla de medicamentos o terapias.</li>
                    <li>No reemplaza al equipo de apoyo, a orientación escolar ni a un profesional de la salud.</li>
                    <li>No toma decisiones por ti ni califica a tus estudiantes.</li>
                    <li>No se graba, y no habla de precios ni de asuntos comerciales.</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="container narrow">
            <div class="section-head reveal">
                <span class="eyebrow">Costos bajo control</span>
                <h2>Por qué no se te va a <span class="text-gradient">disparar la cuenta</span></h2>
                <p>El tutor se cobra por minuto de conversación, así que el sistema está hecho para cerrarla sola.</p>
            </div>
            <ol class="steps-list reveal">
                <li><strong>Tope por conversación.</strong> Ninguna llamada pasa de <?= (int) TAVUS_MAX_MINUTES ?> minutos.</li>
                <li><strong>Corte por inactividad.</strong> A los <?= (int) round(TAVUS_IDLE_SECONDS / 60) ?> minutos sin
                    interacción se cierra sola. El navegador te avisa medio minuto antes por si sigues ahí.</li>
                <li><strong>Minutos por plan y por docente.</strong> Cada licencia trae su cupo mensual y un tope por
                    persona, para que nadie se lleve los minutos de todos.</li>
                <li><strong>Presupuesto de la plataforma.</strong> Si se alcanza el tope del mes, no se abren más
                    conversaciones hasta el mes siguiente.</li>
                <li><strong>Vigilancia del servidor.</strong> Cada minuto se revisa que no haya quedado ninguna
                    conversación abierta, aunque se caiga el navegador o se vaya la luz.</li>
            </ol>
        </div>
    </section>

    <section class="section">
        <div class="container narrow">
            <div class="section-head reveal">
                <span class="eyebrow">Preguntas frecuentes</span>
                <h2>Lo que suelen <span class="text-gradient">preguntarnos</span></h2>
            </div>
            <div class="accordion faq reveal" data-accordion>
                <?php foreach ($faqs as $i => [$q, $a]): ?>
                    <div class="acc-item<?= $i === 0 ? ' open' : '' ?>">
                        <button class="acc-head"><span><?= e($q) ?></span><i></i></button>
                        <div class="acc-body"><p><?= e($a) ?></p></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container narrow center">
            <h2>¿Lo probamos?</h2>
            <p class="lead">Si tu licencia incluye minutos de tutor, puedes entrar ahora mismo. Si no, escríbenos y lo
                añadimos a tu plan.</p>
            <div class="hero-ctas center-ctas">
                <a class="btn btn-primary btn-lg" href="<?= e(url('portal/tutor.php')) ?>">Hablar con Daniel</a>
                <a class="btn btn-outline btn-lg" href="mailto:<?= e(SUPPORT_EMAIL) ?>">Escribirnos</a>
            </div>
        </div>
    </section>
</main>
<?php include __DIR__ . '/includes/partials/footer.php'; ?>
