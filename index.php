<?php
require_once __DIR__ . '/includes/bootstrap.php';

// Metadatos para buscadores y redes: el título lleva lo que la gente busca.
$pageTitle = 'IA para docentes y colegios en Colombia | ' . BRAND_NAME;
$pageDescription = 'Planea clases, crea exámenes y rúbricas, elabora el PIAR y comunícate con las familias '
    . 'con inteligencia artificial. Herramientas en español para docentes y colegios de Colombia, con '
    . 'descarga en PDF, Word y PowerPoint.';
$pageCanonical = '';
$bodyClass = 'page-home';

// Las herramientas se leen del catálogo real del portal: una sola fuente
$tools = array_map(static fn(array $t): array => [
    'cat' => $t['category'], 'icon' => $t['icon'], 'color' => $t['color'],
    'name' => $t['name'], 'desc' => $t['desc'], 'slug' => $t['slug'],
], array_values(ai_tools_visible()));


$faqs = [
    ['¿Qué incluye la licencia institucional?', 'Acceso a toda la plataforma para el número de usuarios de tu plan durante 12 meses: herramientas para docentes, espacios para estudiantes, panel de administración, capacitación inicial y soporte.'],
    ['¿Cómo se realiza el pago?', 'Al elegir tu plan te damos un link de pago seguro de ' . PAYMENT_PROVIDER . '. Desde ahí pagas con tarjeta de crédito o débito, PSE, efectivo y otros medios disponibles en Colombia.'],
    ['¿Qué pasa después de pagar?', 'Nos avisas desde la misma pantalla y verificamos el pago. Al confirmarlo activamos tu licencia, normalmente el mismo día hábil: en tu portal de clientes verás el código, los cupos y la vigencia, y podrás agregar a tus docentes y directivos.'],
    ['¿Puedo cambiar los usuarios de la licencia?', 'Sí. Desde el portal puedes agregar, retirar o reemplazar usuarios en cualquier momento, siempre dentro del número de cupos de tu plan.'],
    ['¿Cuál es la diferencia entre Licencia Escuela y Licencia por Volumen?', 'La Licencia Escuela está pensada para una sola institución. La Licencia por Volumen agrega más cupos, soporte para varias sedes, reportes por sede, formación ampliada y un gerente de cuenta dedicado.'],
    ['¿Quién vende y desde dónde?', 'La venta la realiza ' . COMPANY_NAME . ', con sede en ' . COMPANY_COUNTRY . ', de forma exclusivamente en línea. El precio publicado es el valor total que cobramos, sin cargos adicionales al pagar. Los tributos o retenciones que, en su caso, correspondan en el país del comprador se rigen por la legislación de ese país.'],
    ['¿Los datos de mis estudiantes están protegidos?', 'Sí. Tratamos los datos personales conforme al Reglamento General de Protección de Datos (RGPD) y la LOPDGDD, no vendemos información y no usamos los datos de tu institución para entrenar modelos de IA.'],
];

$usecases = [
    ['role' => 'Docente de primaria', 'color' => 'purple', 'before' => '3 horas planeando la semana', 'after' => 'Planes, fichas y quiz listos en 30 minutos', 'text' => 'Parte del plan de área, genera una secuencia didáctica, adapta la lectura a tres niveles y crea el quiz de cierre con su clave.'],
    ['role' => 'Coordinación académica', 'color' => 'pink', 'before' => 'Observaciones de boletín hasta la madrugada', 'after' => 'Observaciones personalizadas en una tarde', 'text' => 'Los docentes redactan observaciones claras y respetuosas a partir de sus notas, con un tono coherente en toda la institución.'],
    ['role' => 'Docente de apoyo', 'color' => 'orange', 'before' => 'Ajustes razonables hechos a mano', 'after' => 'Borradores de PIAR para revisar y mejorar', 'text' => 'Genera propuestas de ajustes y materiales adaptados que el equipo revisa, ajusta y aprueba antes de usarlos.'],
    ['role' => 'Rectoría', 'color' => 'green', 'before' => 'Sin visibilidad sobre el uso de IA', 'after' => 'Panel con uso y adopción por sede', 'text' => 'Define qué herramientas están disponibles para docentes y estudiantes y consulta reportes de adopción en tiempo real.'],
];

// Datos estructurados: empresa, producto con sus planes y preguntas frecuentes
$pageJsonLd = [jsonld_organizacion(), jsonld_aplicacion(), jsonld_faq($faqs)];

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/nav.php';
?>
<main id="contenido">
    <!-- HERO ============================================================ -->
    <section class="hero">
        <div class="hero-bg" aria-hidden="true">
            <span class="blob blob-1"></span><span class="blob blob-2"></span><span class="blob blob-3"></span>
            <svg class="spark s1" viewBox="0 0 24 24"><path d="M12 0l3 9 9 3-9 3-3 9-3-9-9-3 9-3z"/></svg>
            <svg class="spark s2" viewBox="0 0 24 24"><path d="M12 0l3 9 9 3-9 3-3 9-3-9-9-3 9-3z"/></svg>
            <svg class="spark s3" viewBox="0 0 24 24"><path d="M12 0l3 9 9 3-9 3-3 9-3-9-9-3 9-3z"/></svg>
        </div>
        <div class="container hero-grid">
            <div class="hero-copy reveal">
                <span class="eyebrow"><span class="dot"></span> Plataforma de IA para la educación</span>
                <h1>Inteligencia artificial para <span class="text-gradient">docentes y colegios</span> de Colombia</h1>
                <p class="lead">Planea tus clases, arma exámenes y rúbricas, construye el PIAR y escribe a las familias
                    en minutos. <?= count($tools) ?> herramientas en español, pensadas para el aula colombiana, con descarga
                    en PDF, Word y PowerPoint.</p>
                <div class="hero-ctas">
                    <a href="#precios" class="btn btn-primary btn-lg">Comprar licencia <span aria-hidden="true">→</span></a>
                    <a href="#contacto" class="btn btn-outline btn-lg">Hablar con un asesor</a>
                </div>
                <?php if (RESELLER_OF !== ''): ?>
                    <p class="reseller-note">✔️ <strong>Distribuidor autorizado de <?= e(RESELLER_OF) ?></strong><?= RESELLER_TERRITORY !== '' ? ' en ' . e(RESELLER_TERRITORY) : '' ?>.</p>
                <?php endif; ?>
                <ul class="hero-checks">
                    <li><?= count($tools) ?> herramientas de IA</li>
                    <li>Pago seguro con <?= e(PAYMENT_PROVIDER) ?></li>
                    <li>Activación inmediata</li>
                </ul>
            </div>
            <div class="hero-visual reveal" aria-hidden="true">
                <div class="mock-window">
                    <div class="mock-bar"><span></span><span></span><span></span><em><?= e(BRAND_SHORT) ?> · Herramientas</em></div>
                    <div class="mock-body">
                        <div class="mock-search"><?= icon('chispa', 16) ?> Buscar entre <?= count($tools) ?> herramientas…</div>
                        <div class="mock-grid">
                            <div class="mock-card c-purple"><b><?= icon('presentacion', 20) ?></b><span>Presentaciones</span></div>
                            <div class="mock-card c-pink"><b><?= icon('plan', 20) ?></b><span>Plan de clase</span></div>
                            <div class="mock-card c-orange"><b><?= icon('rubrica', 20) ?></b><span>Rúbricas</span></div>
                            <div class="mock-card c-green"><b><?= icon('quiz', 20) ?></b><span>Quiz</span></div>
                            <div class="mock-card c-yellow"><b><?= icon('idea', 20) ?></b><span>Ideas</span></div>
                            <div class="mock-card c-blue"><b><?= icon('correo', 20) ?></b><span>Correos</span></div>
                        </div>
                        <div class="mock-output">
                            <div class="mock-line w80"></div>
                            <div class="mock-line w95"></div>
                            <div class="mock-line w60"></div>
                            <div class="typing">Generando plan de clase<span>.</span><span>.</span><span>.</span></div>
                        </div>
                    </div>
                </div>
                <div class="float-card fc-1"><span class="fc-icon"><?= icon('reloj', 20) ?></span><div><strong>Horas recuperadas</strong><small>cada semana por docente</small></div></div>
                <div class="float-card fc-2"><span class="fc-icon"><?= icon('escudo', 20) ?></span><div><strong>Datos protegidos</strong><small>Conforme al RGPD</small></div></div>
            </div>
        </div>
    </section>

    <!-- TRUST STRIP ===================================================== -->
    <section class="trust">
        <div class="container">
            <p class="trust-title">Diseñada para todo tipo de comunidades educativas</p>
        </div>
        <div class="marquee">
            <div class="marquee-track">
                <?php $orgs = ['Colegios oficiales', 'Colegios privados', 'Secretarías de Educación', 'Redes de colegios', 'Instituciones rurales', 'Colegios bilingües', 'Escuelas normales', 'Institutos técnicos', 'Fundaciones educativas', 'Cajas de compensación'];
                for ($r = 0; $r < 2; $r++): foreach ($orgs as $o): ?>
                    <span class="marquee-item"><svg viewBox="0 0 24 24" width="18" height="18"><path d="M12 3L1 9l11 6 9-4.9V17h2V9L12 3zm-6 9.2v4L12 20l6-3.8v-4L12 16l-6-3.8z" fill="currentColor"/></svg><?= e($o) ?></span>
                <?php endforeach; endfor; ?>
            </div>
        </div>
    </section>

    <!-- TOOLS =========================================================== -->
    <section class="section" id="herramientas">
        <div class="container">
            <div class="section-head reveal">
                <span class="eyebrow">Herramientas</span>
                <h2>Multiplica tu impacto con <span class="text-gradient">herramientas de IA</span> hechas para el aula</h2>
                <p>Desde la planeación hasta la evaluación y la comunicación con las familias: todo lo que un docente necesita, en un solo lugar.</p>
            </div>
            <div class="tool-tabs reveal" role="tablist">
                <button class="tab active" data-filter="all" role="tab">Todas</button>
                <button class="tab" data-filter="planeacion" role="tab">Planeación</button>
                <button class="tab" data-filter="evaluacion" role="tab">Evaluación</button>
                <button class="tab" data-filter="apoyo" role="tab">Apoyo al aprendizaje</button>
                <button class="tab" data-filter="comunicacion" role="tab">Comunicación</button>
            </div>
            <div class="tools-grid">
                <?php foreach ($tools as $t): ?>
                    <a class="tool-card reveal" data-cat="<?= e($t['cat']) ?>" href="<?= e(url('herramientas/' . $t['slug'])) ?>">
                        <span class="tool-icon c-<?= e($t['color']) ?>"><?= icon($t['icon'], 22) ?></span>
                        <h3><?= e($t['name']) ?></h3>
                        <p><?= e($t['desc']) ?></p>
                        <span class="tool-link">Cómo funciona <span aria-hidden="true">→</span></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="center mt-40">
                <a href="<?= e(url('herramientas')) ?>" class="btn btn-primary">Ver las <?= count($tools) ?> herramientas en detalle <span aria-hidden="true">→</span></a>
                <a href="#precios" class="btn btn-outline">Ver precios</a>
            </div>
        </div>
    </section>

    <!-- QUÉ RESUELVE (contenido para buscadores y para quien compara) ==== -->
    <section class="section section-soft" id="para-que-sirve">
        <div class="container">
            <div class="section-head reveal">
                <span class="eyebrow">Para qué sirve</span>
                <h2>Lo que un docente resuelve con <span class="text-gradient">MagicSchools</span></h2>
                <p>Cada herramienta pide lo mínimo y devuelve un borrador que tú revisas y apruebas.</p>
            </div>
            <div class="use-grid reveal">
                <article class="use-card">
                    <h3>Planear clases y unidades</h3>
                    <p>Un plan de clase con objetivo, momentos y evaluación en menos de un minuto; unidades completas
                        semana por semana y clases con el modelo 5E. Todo articulado con los estándares y los DBA del
                        Ministerio de Educación.</p>
                    <p><a class="link-arrow" href="<?= e(url('herramientas/plan-de-clase')) ?>">Plan de clase <span>→</span></a>
                       <a class="link-arrow" href="<?= e(url('herramientas/unidad')) ?>">Planeador de unidad <span>→</span></a></p>
                </article>
                <article class="use-card">
                    <h3>Evaluar sin pasar el fin de semana calificando</h3>
                    <p>Exámenes con hoja de respuestas y solucionario, quices, rúbricas por niveles de desempeño del
                        Decreto 1290 y retroalimentación de escritos. Las fórmulas de matemáticas y ciencias salen
                        bien escritas, en LaTeX.</p>
                    <p><a class="link-arrow" href="<?= e(url('herramientas/examen')) ?>">Generador de exámenes <span>→</span></a>
                       <a class="link-arrow" href="<?= e(url('herramientas/rubrica')) ?>">Rúbricas <span>→</span></a></p>
                </article>
                <article class="use-card">
                    <h3>Atender la diversidad del aula</h3>
                    <p>El PIAR según el Decreto 1421 de 2017, metas de apoyo medibles, apoyo conductual positivo,
                        planes para estudiantes con talentos excepcionales y tableros de opciones con Diseño Universal
                        para el Aprendizaje.</p>
                    <p><a class="link-arrow" href="<?= e(url('herramientas/piar')) ?>">PIAR <span>→</span></a>
                       <a class="link-arrow" href="<?= e(url('herramientas/tablero-opciones')) ?>">Tablero DUA <span>→</span></a></p>
                </article>
                <article class="use-card">
                    <h3>Trabajar por proyectos</h3>
                    <p>Un proyecto de aula completo y listo para imprimir: pregunta orientadora, producto público,
                        todas las sesiones, rúbrica, cronograma, plan B y anexos para fotocopiar.</p>
                    <p><a class="link-arrow" href="<?= e(url('herramientas/abp')) ?>">Proyecto ABP <span>→</span></a></p>
                </article>
                <article class="use-card">
                    <h3>Comunicarte con las familias</h3>
                    <p>Circulares del curso, observaciones de boletín, correos difíciles bien escritos y publicaciones
                        para las redes del colegio, siempre cuidando los datos y la imagen de los menores.</p>
                    <p><a class="link-arrow" href="<?= e(url('herramientas/circular')) ?>">Circular para familias <span>→</span></a>
                       <a class="link-arrow" href="<?= e(url('herramientas/boletin')) ?>">Observaciones de boletín <span>→</span></a></p>
                </article>
                <article class="use-card">
                    <h3>Practicar con un tutor que te escucha</h3>
                    <p>Daniel, el tutor con avatar, conversa contigo sobre tu clase: te hace preguntas, propone
                        alternativas y te devuelve la decisión pedagógica. Se cierra solo a los dos minutos sin
                        interacción, para que no gastes minutos de más.</p>
                    <p><a class="link-arrow" href="#docentes">Planes con tutor <span>→</span></a></p>
                </article>
            </div>
        </div>
    </section>
    <!-- TUTOR CON AVATAR ================================================ -->
    <section class="section" id="tutor">
        <div class="container tutor-promo">
            <div class="reveal">
                <span class="eyebrow"><span class="dot"></span> Novedad</span>
                <h2>Conversa con <span class="text-gradient">Daniel</span>, tu tutor con avatar</h2>
                <p class="lead">No es un chat de texto: es una conversación en video sobre la clase que vas a dar.
                    Cuéntale qué tema sigue o qué actividad no funcionó, y lo piensan juntos.</p>
                <ul class="checklist">
                    <li>Te hace preguntas y te propone alternativas, sin decidir por ti.</li>
                    <li>Dice siempre que es una inteligencia artificial, no una persona.</li>
                    <li>No diagnostica estudiantes: para eso remite al equipo de apoyo.</li>
                    <li>Hasta <?= (int) TAVUS_MAX_MINUTES ?> minutos por conversación y sin grabación.</li>
                    <li>Se cierra sola tras <?= (int) round(TAVUS_IDLE_SECONDS / 60) ?> minutos sin interacción.</li>
                </ul>
                <div class="hero-ctas">
                    <a class="btn btn-primary" href="<?= e(url('tutor')) ?>">Conocer al tutor <span aria-hidden="true">→</span></a>
                    <a class="btn btn-outline" href="#docentes">Planes con tutor</a>
                </div>
            </div>
            <figure class="tutor-video reveal">
                <?php $videoHome = 'assets/video/tutor-daniel.mp4'; $posterHome = 'assets/img/tutor-daniel.jpg'; ?>
                <?php if (is_file(__DIR__ . '/' . $videoHome)): ?>
                    <video controls preload="metadata" playsinline
                           <?= is_file(__DIR__ . '/' . $posterHome) ? 'poster="' . e(url($posterHome)) . '"' : '' ?>>
                        <source src="<?= e(url($videoHome)) ?>" type="video/mp4">
                        Tu navegador no puede reproducir este video.
                    </video>
                    <figcaption>Daniel se presenta en 50 segundos.</figcaption>
                <?php else: ?>
                    <div class="tutor-video-placeholder">
                        <span><?= icon('tutor', 40) ?></span>
                        <p>Video de presentación disponible en unos minutos.</p>
                    </div>
                <?php endif; ?>
            </figure>
        </div>
    </section>
    <!-- SOLUTIONS ======================================================= -->
    <section class="section section-soft" id="soluciones">
        <div class="container">
            <div class="section-head reveal">
                <span class="eyebrow">Soluciones</span>
                <h2>Una plataforma, <span class="text-gradient">tres experiencias</span></h2>
                <p>Cada miembro de la comunidad educativa obtiene exactamente lo que necesita, con la institución siempre al mando.</p>
            </div>
            <div class="pillars">
                <article class="pillar p-purple reveal">
                    <span class="pillar-badge"><?= icon('colegio', 16) ?> IA para colegios</span>
                    <h3>Control, seguridad y visibilidad para directivos</h3>
                    <ul class="checklist">
                        <li>Administración centralizada de usuarios y licencias</li>
                        <li>Paneles de uso y adopción por sede</li>
                        <li>Herramientas personalizadas para tu PEI</li>
                        <li>Políticas de uso responsable configurables</li>
                    </ul>
                    <a href="#precios" class="link-arrow">Conocer planes <span>→</span></a>
                </article>
                <article class="pillar p-pink reveal">
                    <span class="pillar-badge"><?= icon('docente', 16) ?> IA para docentes</span>
                    <h3>Más tiempo para enseñar, menos para el papeleo</h3>
                    <ul class="checklist">
                        <li><?= count($tools) ?> herramientas para planear, evaluar y comunicar</li>
                        <li>Asistente conversacional pedagógico</li>
                        <li>Exportación a documentos, presentaciones y formularios</li>
                        <li>Formación y certificación incluidas</li>
                    </ul>
                    <a href="#herramientas" class="link-arrow">Ver herramientas <span>→</span></a>
                </article>
                <article class="pillar p-orange reveal">
                    <span class="pillar-badge"><?= icon('estudiante', 16) ?> IA para estudiantes</span>
                    <h3>Aprendizaje guiado, seguro y supervisado</h3>
                    <ul class="checklist">
                        <li>Material listo para entregar a los estudiantes</li>
                        <li>Actividades creadas y supervisadas por el docente</li>
                        <li>Moderación de contenido y alertas de seguridad</li>
                        <li>Historial visible para el docente en todo momento</li>
                    </ul>
                    <a href="#seguridad" class="link-arrow">Cómo protegemos a los estudiantes <span>→</span></a>
                </article>
            </div>
            <div class="stats reveal">
                <div class="stat"><strong data-count="<?= count($tools) ?>">0</strong><span>herramientas para docentes</span></div>
                <div class="stat"><strong data-count="4">0</strong><span>formatos de descarga: PDF, Word, PowerPoint y HTML</span></div>
                <div class="stat"><strong data-count="30">0</strong><span>+ idiomas disponibles</span></div>
                <div class="stat"><strong data-count="12">0</strong><span>meses de acceso por licencia</span></div>
            </div>
        </div>
    </section>

    <!-- USE CASES ======================================================= -->
    <section class="section" id="casos">
        <div class="container">
            <div class="section-head reveal">
                <span class="eyebrow">Casos de uso</span>
                <h2>Así se ve un día con <span class="text-gradient"><?= e(BRAND_SHORT) ?></span></h2>
                <p>Situaciones reales del día a día escolar que la IA ayuda a resolver.</p>
            </div>
            <div class="usecases">
                <?php foreach ($usecases as $u): ?>
                    <article class="usecase reveal">
                        <span class="usecase-role c-<?= e($u['color']) ?>"><?= e($u['role']) ?></span>
                        <div class="usecase-compare">
                            <div class="uc-before"><small>Antes</small><?= e($u['before']) ?></div>
                            <div class="uc-arrow">→</div>
                            <div class="uc-after"><small>Con IA</small><?= e($u['after']) ?></div>
                        </div>
                        <p><?= e($u['text']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS ==================================================== -->
    <section class="section section-dark">
        <div class="container">
            <div class="section-head light reveal">
                <span class="eyebrow eyebrow-light">Cómo funciona</span>
                <h2>De la compra al aula en <span class="text-gradient-light">tres pasos</span></h2>
            </div>
            <ol class="steps">
                <li class="step reveal"><span class="step-n">1</span><h3>Elige y paga tu licencia</h3><p>Selecciona el plan ideal y paga con el link seguro de <?= e(PAYMENT_PROVIDER) ?>: tarjeta, PSE o efectivo.</p></li>
                <li class="step reveal"><span class="step-n">2</span><h3>Activamos tu licencia</h3><p>Verificamos el pago y activamos tu licencia. En tu portal encuentras el código y los cupos disponibles.</p></li>
                <li class="step reveal"><span class="step-n">3</span><h3>Invita a tu equipo</h3><p>Agrega a tus docentes uno a uno o de forma masiva y empieza a transformar tus clases hoy.</p></li>
            </ol>
        </div>
    </section>

    <!-- SAFETY ========================================================== -->
    <section class="section" id="seguridad">
        <div class="container safety-grid">
            <div class="reveal">
                <span class="eyebrow">Confianza y seguridad</span>
                <h2>Construida para proteger a tu <span class="text-gradient">comunidad educativa</span></h2>
                <p class="muted">La seguridad no es un complemento: es la base de todo lo que hacemos.</p>
                <div class="accordion" data-accordion>
                    <div class="acc-item open">
                        <button class="acc-head"><span><?= icon('candado', 20) ?> Seguridad de nivel institucional</span><i></i></button>
                        <div class="acc-body"><p>Cifrado de la información en tránsito y en reposo, controles de acceso por rol, inicio de sesión con cuentas institucionales y monitoreo permanente de la plataforma.</p></div>
                    </div>
                    <div class="acc-item">
                        <button class="acc-head"><span><?= icon('escudo', 20) ?> Privacidad de los datos</span><i></i></button>
                        <div class="acc-body"><p>Tratamos los datos personales conforme al RGPD (UE) 2016/679 y la Ley Orgánica 3/2018 (LOPDGDD). No vendemos información y no usamos los datos de docentes ni estudiantes para entrenar modelos de IA.</p></div>
                    </div>
                    <div class="acc-item">
                        <button class="acc-head"><span><?= icon('manos', 20) ?> IA responsable</span><i></i></button>
                        <div class="acc-body"><p>Filtros de contenido adecuados a la edad, alertas para temas sensibles, supervisión docente de todas las interacciones estudiantiles y recordatorios constantes para verificar la información generada.</p></div>
                    </div>
                </div>
            </div>
            <div class="badges-card reveal">
                <div class="shield">
                    <svg viewBox="0 0 120 140" aria-hidden="true"><defs><linearGradient id="shg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#8B5CF6"/><stop offset="1" stop-color="#FF4FA3"/></linearGradient></defs><path d="M60 4l52 18v42c0 34-22 60-52 72C30 124 8 98 8 64V22z" fill="url(#shg)"/><path d="M38 70l15 15 30-32" fill="none" stroke="#fff" stroke-width="10" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </div>
                <div class="badges">
                    <span>RGPD</span><span>LOPDGDD</span><span>Cifrado TLS</span><span>Control por roles</span><span>Sin entrenamiento con tus datos</span><span>Moderación de contenido</span><span>Supervisión docente</span><span>Copias de seguridad</span>
                </div>
            </div>
        </div>
    </section>

    <!-- COMMUNITY ======================================================= -->
    <section class="section section-soft" id="comunidad">
        <div class="container">
            <div class="section-head reveal">
                <span class="eyebrow">Comunidad y formación</span>
                <h2>Aprende, crece y <span class="text-gradient">lidera</span> con IA</h2>
            </div>
            <div class="cards-3">
                <article class="feature-card reveal"><span class="fc-emoji c-purple"><?= icon('certificado', 26) ?></span><h3>Cursos de certificación</h3><p>Rutas de aprendizaje autónomas para que cada docente domine la IA en el aula y obtenga su certificado.</p></article>
                <article class="feature-card reveal"><span class="fc-emoji c-pink"><?= icon('formacion', 26) ?></span><h3>Formación profesional</h3><p>Talleres en vivo para tu equipo, adaptados a tu institución, incluidos en todas las licencias.</p></article>
                <article class="feature-card reveal"><span class="fc-emoji c-orange"><?= icon('comunidad', 26) ?></span><h3>Comunidad de pioneros</h3><p>Conecta con educadores de todo el país que comparten prácticas, recursos y experiencias con IA.</p></article>
            </div>
        </div>
    </section>

    <!-- INTEGRATIONS ==================================================== -->
    <section class="section" id="integraciones">
        <div class="container integrations">
            <div class="reveal">
                <span class="eyebrow">Integraciones</span>
                <h2>Funciona con las herramientas que <span class="text-gradient">ya usas</span></h2>
                <p class="muted">Exporta tus creaciones e inicia sesión con las plataformas que tu institución ya tiene. Sin instalaciones complicadas.</p>
                <a href="#contacto" class="btn btn-outline mt-24">Consultar integraciones</a>
            </div>
            <div class="int-grid reveal">
                <?php foreach (['Google Workspace' => '#4285F4', 'Microsoft 365' => '#F25022', 'Canvas' => '#E13F2B', 'Moodle' => '#F98012', 'Classroom' => '#0F9D58', 'Teams' => '#5059C9'] as $n => $c): ?>
                    <div class="int-item"><span class="int-dot" style="background:<?= $c ?>"></span><?= e($n) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- PRICING ========================================================= -->
    <section class="section section-pricing" id="precios">
        <div class="container">
            <div class="section-head reveal">
                <span class="eyebrow">Precios</span>
                <h2>Licencias para <span class="text-gradient">toda tu institución</span></h2>
                <p>Precio total en pesos colombianos, pago único por 12 meses, sin cargos adicionales al pagar. Paga con tarjeta, PSE o efectivo mediante un link seguro.</p>
            </div>
            <div class="pricing">
                <?php foreach ($PLANS as $p): if (($p['audience'] ?? 'institucion') !== 'institucion') continue; ?>
                    <article class="price-card<?= $p['featured'] ? ' featured' : '' ?> reveal">
                        <?php if ($p['featured']): ?><span class="ribbon">Mejor valor por usuario</span><?php endif; ?>
                        <h3><?= e($p['name']) ?></h3>
                        <p class="price-tag"><?= e($p['tagline']) ?></p>
                        <div class="price">
                            <span class="amount"><?= money_short($p['price']) ?></span>
                            <span class="per"><?= e(CURRENCY) ?> / <?= (int) $p['months'] ?> meses</span>
                        </div>
                        <p class="price-note">Equivale a <?= money_short(round($p['price'] / $p['seats'])) ?> por usuario al año</p>
                        <a class="btn <?= $p['featured'] ? 'btn-light' : 'btn-primary' ?> btn-block btn-lg" href="<?= e(url('checkout.php?plan=' . $p['code'])) ?>">Comprar <?= e($p['name']) ?></a>
                        <ul class="checklist">
                            <?php foreach ($p['features'] as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
                        </ul>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="pay-methods reveal">
                <span class="mp-badge"><svg viewBox="0 0 48 32" width="34" height="24" aria-hidden="true"><ellipse cx="24" cy="16" rx="23" ry="15" fill="#00B1EA"/><path d="M11 17c3-4 7-6 13-6s10 2 13 6c-3 3-7 5-13 5s-10-2-13-5z" fill="#fff"/><path d="M17 16l4 2 6-4 4 3" fill="none" stroke="#00B1EA" stroke-width="2" stroke-linecap="round"/></svg> Pagos procesados por <strong>Mercado Pago</strong></span>
                <span><?= icon('tarjeta', 18) ?> Tarjetas</span><span><?= icon('banco', 18) ?> PSE</span><span><?= icon('efectivo', 18) ?> Efecty</span><span><?= icon('cifrado', 18) ?> Transacción cifrada</span>
            </div>
            <div class="reveal"><?php include __DIR__ . '/includes/partials/commerce_notice.php'; ?></div>
            <p class="center muted small mt-24">¿Necesitas más de 250 usuarios o una propuesta a la medida? <a href="#contacto">Contáctanos</a>.</p>
        </div>
    </section>

    <!-- PLANES DOCENTES ================================================= -->
    <section class="section section-soft" id="docentes">
        <div class="container">
            <div class="section-head reveal">
                <span class="eyebrow">Para docentes</span>
                <h2>¿Tu colegio todavía no tiene licencia? <span class="text-gradient">Empieza tú</span></h2>
                <p>Planes individuales con cobro mensual. Las mismas herramientas, para un solo docente.</p>
            </div>
            <div class="pricing pricing-3">
                <?php foreach ($PLANS as $p): if (($p['audience'] ?? 'institucion') !== 'docente') continue; ?>
                    <article class="price-card<?= $p['featured'] ? ' featured' : '' ?> reveal">
                        <?php if ($p['featured']): ?><span class="ribbon">El más elegido</span><?php endif; ?>
                        <h3><?= e($p['name']) ?></h3>
                        <p class="price-tag"><?= e($p['tagline']) ?></p>
                        <div class="price">
                            <span class="amount"><?= money_short($p['price']) ?></span>
                            <span class="per"><?= e(CURRENCY) ?> / mes</span>
                        </div>
                        <?php if (!empty($p['payment_link'])): ?>
                            <a class="btn <?= $p['featured'] ? 'btn-light' : 'btn-primary' ?> btn-block btn-lg" href="<?= e(url('checkout.php?plan=' . $p['code'])) ?>">Empezar con <?= e($p['name']) ?></a>
                        <?php else: ?>
                            <a class="btn btn-outline btn-block btn-lg" href="#contacto">Avísame cuando esté disponible</a>
                        <?php endif; ?>
                        <ul class="checklist">
                            <?php foreach ($p['features'] as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
                        </ul>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="center muted small mt-24">Puedes cancelar cuando quieras: el plan vence al final del mes pagado y no se renueva solo.</p>
        </div>
    </section>
    <!-- FAQ ============================================================= -->
    <section class="section" id="faq">
        <div class="container narrow">
            <div class="section-head reveal">
                <span class="eyebrow">Preguntas frecuentes</span>
                <h2>Resolvemos tus <span class="text-gradient">dudas</span></h2>
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

    <!-- LEGAL ========================================================== -->
    <section class="section section-soft" id="legal">
        <div class="container">
            <div class="section-head reveal">
                <span class="eyebrow">Información legal</span>
                <h2>Compra con <span class="text-gradient">todo claro</span></h2>
                <p>Estas son nuestras condiciones de compra, de uso y de tratamiento de datos. Puedes consultarlas antes de pagar.</p>
            </div>
            <div class="cards-3">
                <a class="feature-card legal-card reveal" href="<?= e(url('legal.php#terminos')) ?>">
                    <span class="fc-emoji c-purple"><?= icon('documento', 26) ?></span>
                    <h3>Términos del servicio</h3>
                    <p>Qué incluye la licencia, su vigencia, cómo se activa y las condiciones de uso de la plataforma.</p>
                    <span class="link-arrow">Leer los términos <span aria-hidden="true">→</span></span>
                </a>
                <a class="feature-card legal-card reveal" href="<?= e(url('legal.php#privacidad')) ?>">
                    <span class="fc-emoji c-pink"><?= icon('privacidad', 26) ?></span>
                    <h3>Aviso de privacidad</h3>
                    <p>Qué datos tratamos, con qué fin, durante cuánto tiempo y cómo ejerces tus derechos bajo el RGPD.</p>
                    <span class="link-arrow">Leer el aviso <span aria-hidden="true">→</span></span>
                </a>
                <a class="feature-card legal-card reveal" href="<?= e(url('legal.php#reembolsos')) ?>">
                    <span class="fc-emoji c-green"><?= icon('reembolso', 26) ?></span>
                    <h3>Política de reembolsos</h3>
                    <p>Devolución del 100 % dentro de los 14 días naturales siguientes al pago, por el mismo medio de pago.</p>
                    <span class="link-arrow">Leer la política <span aria-hidden="true">→</span></span>
                </a>
            </div>
            <p class="center muted small mt-24">
                Vendedor: <?= e(COMPANY_NAME) ?><?= COMPANY_TAX_ID !== '' ? ' · ' . e(tax_label()) . ' ' . e(COMPANY_TAX_ID) : '' ?> · Venta en línea desde <?= e(COMPANY_COUNTRY) ?> ·
                Soporte: <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a><?= SUPPORT_PHONE !== '' ? ' · ' . e(SUPPORT_PHONE) : '' ?>
            </p>
        </div>
    </section>

    <!-- CONTACT / CTA =================================================== -->
    <section class="cta" id="contacto">
        <div class="container cta-grid">
            <div class="cta-copy reveal">
                <h2>Hagamos que enseñar vuelva a ser una alegría</h2>
                <p>Cuéntanos sobre tu institución y un asesor te acompañará a elegir la licencia ideal, resolver dudas y preparar la implementación.</p>
                <div class="hero-ctas">
                    <a href="#precios" class="btn btn-light btn-lg">Comprar licencia</a>
                    <a href="<?= e(url('portal/login.php')) ?>" class="btn btn-outline-light btn-lg">Portal de clientes</a>
                </div>
            </div>
            <form class="cta-form reveal" method="post" action="<?= e(url('contacto.php')) ?>">
                <?= csrf_field() ?>
                <?= render_flashes() ?>
                <h3>Solicita una asesoría</h3>
                <div class="form-row">
                    <label>Nombre<input name="name" required maxlength="120"></label>
                    <label>Correo<input type="email" name="email" required maxlength="190"></label>
                </div>
                <div class="form-row">
                    <label>Institución<input name="institution" maxlength="190"></label>
                    <label>Teléfono<input name="phone" maxlength="30"></label>
                </div>
                <label>Mensaje<textarea name="message" rows="3" maxlength="2000"></textarea></label>
                <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
                <button class="btn btn-primary btn-block">Enviar solicitud</button>
            </form>
        </div>
    </section>
</main>
<?php include __DIR__ . '/includes/partials/footer.php'; ?>
