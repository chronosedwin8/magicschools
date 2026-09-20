<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = BRAND_NAME . ' | Inteligencia artificial segura para colegios';
$bodyClass = 'page-home';

$tools = [
    ['cat' => 'planeacion', 'icon' => '📊', 'color' => 'purple', 'name' => 'Generador de presentaciones', 'desc' => 'Crea diapositivas completas para tu clase a partir de un tema, un texto o un video.'],
    ['cat' => 'planeacion', 'icon' => '🗂️', 'color' => 'pink',   'name' => 'Plan de clase',               'desc' => 'Diseña planes de clase alineados a estándares y DBA en pocos minutos.'],
    ['cat' => 'evaluacion', 'icon' => '✍️', 'color' => 'orange', 'name' => 'Retroalimentación de escritos', 'desc' => 'Ofrece comentarios específicos y accionables sobre los textos de tus estudiantes.'],
    ['cat' => 'planeacion', 'icon' => '💡', 'color' => 'yellow', 'name' => 'Generador de ideas',          'desc' => 'Lluvia de ideas para actividades, proyectos y dinámicas de aula.'],
    ['cat' => 'comunicacion','icon' => '📝', 'color' => 'green', 'name' => 'Observaciones de boletín',    'desc' => 'Redacta observaciones de desempeño claras, personalizadas y respetuosas.'],
    ['cat' => 'evaluacion', 'icon' => '✅', 'color' => 'blue',   'name' => 'Quiz de selección múltiple',   'desc' => 'Genera evaluaciones con clave de respuestas desde cualquier contenido.'],
    ['cat' => 'apoyo',      'icon' => '🤖', 'color' => 'purple', 'name' => 'Tutor de IA',                 'desc' => 'Un tutor paciente que guía al estudiante sin darle las respuestas.'],
    ['cat' => 'apoyo',      'icon' => '🔁', 'color' => 'pink',   'name' => 'Reescritor de textos',        'desc' => 'Adapta lecturas a distintos niveles de complejidad y necesidades.'],
    ['cat' => 'evaluacion', 'icon' => '📐', 'color' => 'orange', 'name' => 'Generador de rúbricas',       'desc' => 'Construye rúbricas con criterios y niveles de desempeño detallados.'],
    ['cat' => 'apoyo',      'icon' => '🧩', 'color' => 'yellow', 'name' => 'Plan de ajustes razonables (PIAR)', 'desc' => 'Apoya la construcción de ajustes para estudiantes con necesidades diversas.'],
    ['cat' => 'planeacion', 'icon' => '🪜', 'color' => 'green',  'name' => 'Tareas por pasos',            'desc' => 'Crea guías de trabajo con lectura, preguntas y actividades secuenciadas.'],
    ['cat' => 'comunicacion','icon' => '✉️', 'color' => 'blue',  'name' => 'Correo profesional',          'desc' => 'Escribe mensajes a familias y colegas con el tono adecuado.'],
];

$faqs = [
    ['¿Qué incluye la licencia institucional?', 'Acceso a toda la plataforma para el número de usuarios de tu plan durante 12 meses: herramientas para docentes, espacios para estudiantes, panel de administración, capacitación inicial y soporte.'],
    ['¿Cómo se realiza el pago?', 'El pago se procesa de forma segura a través de Mercado Pago. Puedes pagar con tarjeta de crédito o débito, PSE, Efecty y otros medios disponibles en Colombia. Recibes la confirmación de inmediato.'],
    ['¿Qué pasa después de pagar?', 'Serás redirigido automáticamente a tu portal de clientes, donde verás tu licencia, su código, el número de cupos y la vigencia. Desde allí puedes agregar a los docentes y directivos que usarán la licencia.'],
    ['¿Puedo cambiar los usuarios de la licencia?', 'Sí. Desde el portal puedes agregar, retirar o reemplazar usuarios en cualquier momento, siempre dentro del número de cupos de tu plan.'],
    ['¿Cuál es la diferencia entre Licencia Escuela y Licencia por Volumen?', 'La Licencia Escuela está pensada para una sola institución. La Licencia por Volumen agrega más cupos, soporte para varias sedes, reportes por sede, formación ampliada y un gerente de cuenta dedicado.'],
    ['¿Desde dónde se realiza la venta?', 'Somos una empresa establecida en ' . COMPANY_COUNTRY . ' y vendemos en línea bajo la normativa española de comercio electrónico (LSSI-CE). El precio publicado es el valor total que cobramos, sin cargos adicionales al pagar. Los tributos o retenciones que, en su caso, correspondan en el país del comprador se rigen por la legislación de ese país.'],
    ['¿Los datos de mis estudiantes están protegidos?', 'Sí. Tratamos los datos personales conforme al Reglamento General de Protección de Datos (RGPD) y la LOPDGDD, no vendemos información y no usamos los datos de tu institución para entrenar modelos de IA.'],
];

$usecases = [
    ['role' => 'Docente de primaria', 'color' => 'purple', 'before' => '3 horas planeando la semana', 'after' => 'Planes, fichas y quiz listos en 30 minutos', 'text' => 'Parte del plan de área, genera una secuencia didáctica, adapta la lectura a tres niveles y crea el quiz de cierre con su clave.'],
    ['role' => 'Coordinación académica', 'color' => 'pink', 'before' => 'Observaciones de boletín hasta la madrugada', 'after' => 'Observaciones personalizadas en una tarde', 'text' => 'Los docentes redactan observaciones claras y respetuosas a partir de sus notas, con un tono coherente en toda la institución.'],
    ['role' => 'Docente de apoyo', 'color' => 'orange', 'before' => 'Ajustes razonables hechos a mano', 'after' => 'Borradores de PIAR para revisar y mejorar', 'text' => 'Genera propuestas de ajustes y materiales adaptados que el equipo revisa, ajusta y aprueba antes de usarlos.'],
    ['role' => 'Rectoría', 'color' => 'green', 'before' => 'Sin visibilidad sobre el uso de IA', 'after' => 'Panel con uso y adopción por sede', 'text' => 'Define qué herramientas están disponibles para docentes y estudiantes y consulta reportes de adopción en tiempo real.'],
];

include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/nav.php';
?>
<main>
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
                <h1>IA segura para <span class="text-gradient">toda tu institución</span>, pensada para quienes enseñan</h1>
                <p class="lead">Ahorra horas de trabajo cada semana, despierta la creatividad en el aula y lleva la inteligencia artificial a docentes y estudiantes con control, privacidad y acompañamiento.</p>
                <div class="hero-ctas">
                    <a href="#precios" class="btn btn-primary btn-lg">Comprar licencia <span aria-hidden="true">→</span></a>
                    <a href="#contacto" class="btn btn-outline btn-lg">Hablar con un asesor</a>
                </div>
                <ul class="hero-checks">
                    <li>Más de 80 herramientas</li>
                    <li>Pago seguro con Mercado Pago</li>
                    <li>Activación inmediata</li>
                </ul>
            </div>
            <div class="hero-visual reveal" aria-hidden="true">
                <div class="mock-window">
                    <div class="mock-bar"><span></span><span></span><span></span><em><?= e(BRAND_SHORT) ?> · Herramientas</em></div>
                    <div class="mock-body">
                        <div class="mock-search">🔍 &nbsp;Buscar entre 80+ herramientas…</div>
                        <div class="mock-grid">
                            <div class="mock-card c-purple"><b>📊</b><span>Presentaciones</span></div>
                            <div class="mock-card c-pink"><b>🗂️</b><span>Plan de clase</span></div>
                            <div class="mock-card c-orange"><b>📐</b><span>Rúbricas</span></div>
                            <div class="mock-card c-green"><b>✅</b><span>Quiz</span></div>
                            <div class="mock-card c-yellow"><b>💡</b><span>Ideas</span></div>
                            <div class="mock-card c-blue"><b>✉️</b><span>Correos</span></div>
                        </div>
                        <div class="mock-output">
                            <div class="mock-line w80"></div>
                            <div class="mock-line w95"></div>
                            <div class="mock-line w60"></div>
                            <div class="typing">Generando plan de clase<span>.</span><span>.</span><span>.</span></div>
                        </div>
                    </div>
                </div>
                <div class="float-card fc-1"><span class="fc-icon">⏱️</span><div><strong>Horas recuperadas</strong><small>cada semana por docente</small></div></div>
                <div class="float-card fc-2"><span class="fc-icon">🛡️</span><div><strong>Datos protegidos</strong><small>Conforme al RGPD</small></div></div>
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
                    <article class="tool-card reveal" data-cat="<?= e($t['cat']) ?>">
                        <span class="tool-icon c-<?= e($t['color']) ?>"><?= $t['icon'] ?></span>
                        <h3><?= e($t['name']) ?></h3>
                        <p><?= e($t['desc']) ?></p>
                        <span class="tool-link">Probar herramienta <span aria-hidden="true">→</span></span>
                    </article>
                <?php endforeach; ?>
            </div>
            <div class="center mt-40">
                <a href="#precios" class="btn btn-primary">Ver todas las herramientas con tu licencia <span aria-hidden="true">→</span></a>
            </div>
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
                    <span class="pillar-badge">🏫 IA para colegios</span>
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
                    <span class="pillar-badge">🍎 IA para docentes</span>
                    <h3>Más tiempo para enseñar, menos para el papeleo</h3>
                    <ul class="checklist">
                        <li>Más de 80 herramientas para planear, evaluar y comunicar</li>
                        <li>Asistente conversacional pedagógico</li>
                        <li>Exportación a documentos, presentaciones y formularios</li>
                        <li>Formación y certificación incluidas</li>
                    </ul>
                    <a href="#herramientas" class="link-arrow">Ver herramientas <span>→</span></a>
                </article>
                <article class="pillar p-orange reveal">
                    <span class="pillar-badge">🎒 IA para estudiantes</span>
                    <h3>Aprendizaje guiado, seguro y supervisado</h3>
                    <ul class="checklist">
                        <li>Más de 50 herramientas diseñadas para estudiantes</li>
                        <li>Actividades creadas y supervisadas por el docente</li>
                        <li>Moderación de contenido y alertas de seguridad</li>
                        <li>Historial visible para el docente en todo momento</li>
                    </ul>
                    <a href="#seguridad" class="link-arrow">Cómo protegemos a los estudiantes <span>→</span></a>
                </article>
            </div>
            <div class="stats reveal">
                <div class="stat"><strong data-count="80">0</strong><span>+ herramientas para docentes</span></div>
                <div class="stat"><strong data-count="50">0</strong><span>+ herramientas para estudiantes</span></div>
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
                <li class="step reveal"><span class="step-n">1</span><h3>Elige y paga tu licencia</h3><p>Selecciona el plan ideal y paga de forma segura con Mercado Pago: tarjeta, PSE o efectivo.</p></li>
                <li class="step reveal"><span class="step-n">2</span><h3>Entra a tu portal</h3><p>Tras el pago llegas directo a tu portal de clientes con tu código de licencia y cupos disponibles.</p></li>
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
                        <button class="acc-head"><span>🔒 Seguridad de nivel institucional</span><i></i></button>
                        <div class="acc-body"><p>Cifrado de la información en tránsito y en reposo, controles de acceso por rol, inicio de sesión con cuentas institucionales y monitoreo permanente de la plataforma.</p></div>
                    </div>
                    <div class="acc-item">
                        <button class="acc-head"><span>🛡️ Privacidad de los datos</span><i></i></button>
                        <div class="acc-body"><p>Tratamos los datos personales conforme al RGPD (UE) 2016/679 y la Ley Orgánica 3/2018 (LOPDGDD). No vendemos información y no usamos los datos de docentes ni estudiantes para entrenar modelos de IA.</p></div>
                    </div>
                    <div class="acc-item">
                        <button class="acc-head"><span>🤝 IA responsable</span><i></i></button>
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
                <article class="feature-card reveal"><span class="fc-emoji c-purple">🎓</span><h3>Cursos de certificación</h3><p>Rutas de aprendizaje autónomas para que cada docente domine la IA en el aula y obtenga su certificado.</p></article>
                <article class="feature-card reveal"><span class="fc-emoji c-pink">👩‍🏫</span><h3>Formación profesional</h3><p>Talleres en vivo para tu equipo, adaptados a tu institución, incluidos en todas las licencias.</p></article>
                <article class="feature-card reveal"><span class="fc-emoji c-orange">🚀</span><h3>Comunidad de pioneros</h3><p>Conecta con educadores de todo el país que comparten prácticas, recursos y experiencias con IA.</p></article>
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
                <p>Precio total en pesos colombianos, pago único por 12 meses, sin cargos adicionales al pagar. Paga con tarjeta, PSE o efectivo.</p>
            </div>
            <div class="pricing">
                <?php foreach ($PLANS as $p): ?>
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
                <span>💳 Tarjetas</span><span>🏦 PSE</span><span>💵 Efecty</span><span>🔐 Transacción cifrada</span>
            </div>
            <div class="reveal"><?php include __DIR__ . '/includes/partials/commerce_notice.php'; ?></div>
            <p class="center muted small mt-24">¿Necesitas más de 250 usuarios o una propuesta a la medida? <a href="#contacto">Contáctanos</a>.</p>
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
