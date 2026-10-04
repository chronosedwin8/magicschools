<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Aviso legal y condiciones | ' . BRAND_NAME;
$bodyClass = 'page-simple';
include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/nav.php';
?>
<main class="legal">
    <div class="container narrow card">
        <p class="muted small">Texto base de referencia. Revísalo con tu asesor legal antes de publicar el sitio.</p>

        <h2 id="aviso">Aviso legal e identificación del prestador</h2>
        <p>En cumplimiento del artículo 10 de la Ley 34/2002, de 11 de julio, de Servicios de la Sociedad de la Información y de Comercio Electrónico (LSSI-CE), se informa:</p>
        <ul>
            <li><strong>Titular:</strong> <?= e(COMPANY_NAME) ?></li>
            <?php if (COMPANY_NIF !== ''): ?><li><strong>NIF:</strong> <?= e(COMPANY_NIF) ?></li><?php endif; ?>
            <?php if (COMPANY_ADDRESS !== ''): ?><li><strong>Domicilio:</strong> <?= e(COMPANY_ADDRESS) ?></li><?php endif; ?>
            <?php if (COMPANY_REGISTRY !== ''): ?><li><strong>Datos registrales:</strong> <?= e(COMPANY_REGISTRY) ?></li><?php endif; ?>
            <li><strong>Correo electrónico:</strong> <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a></li>
            <li><strong>Sitio web:</strong> <?= e(base_url()) ?></li>
            <li><strong>Actividad:</strong> venta de licencias exclusivamente en línea, sin establecimiento abierto al público.</li>
        </ul>
        <p>Para cualquier comunicación, reclamación o ejercicio de derechos, el canal de contacto es <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a>.</p>

        <h2 id="comercio">Comercio electrónico internacional y condiciones de compra</h2>
        <p><?= e(COMPANY_NAME) ?> es una empresa establecida en <?= e(COMPANY_COUNTRY) ?> que comercializa licencias de <?= e(BRAND_NAME) ?> exclusivamente en línea, a instituciones y clientes de distintos países.</p>
        <ul>
            <li><strong>Precio:</strong> el precio publicado, expresado en pesos colombianos (COP), es el valor total que cobra <?= e(COMPANY_NAME) ?>, sin cargos adicionales al finalizar la compra.</li>
            <li><strong>Tributos en el país del comprador:</strong> los tributos, retenciones u otras obligaciones que, en su caso, correspondan en el país del comprador se rigen por la legislación de ese país.</li>
            <li><strong>Pago:</strong> los pagos se procesan de forma segura a través de Mercado Pago. <?= e(COMPANY_NAME) ?> no almacena datos de tarjetas.</li>
            <li><strong>Proceso de contratación:</strong> el cliente elige la licencia, registra sus datos, realiza el pago y recibe acceso inmediato al portal de clientes, donde queda disponible el comprobante de su compra.</li>
            <li><strong>Idioma:</strong> el contrato se formaliza en español.</li>
        </ul>

        <h2 id="terminos">Términos del servicio</h2>
        <p><?= e(COMPANY_NAME) ?> otorga a la institución compradora una licencia de uso de <?= e(BRAND_NAME) ?>, no exclusiva e intransferible, por el número de usuarios y la vigencia del plan adquirido.</p>
        <ul>
            <li>La licencia se activa cuando se confirma el pago aprobado.</li>
            <li>La institución administra a sus usuarios desde el portal de clientes y es responsable de su buen uso.</li>
            <li>Los cupos pueden reasignarse durante la vigencia; no son acumulables entre licencias.</li>
            <li>Al finalizar la vigencia, la licencia puede renovarse adquiriendo un nuevo plan.</li>
            <li>Las solicitudes de cancelación o reembolso se atienden según la <a href="#reembolsos">política de reembolsos</a>.</li>
        </ul>

        <h2 id="reembolsos">Política de reembolsos</h2>
        <p>Queremos que cada institución quede satisfecha con <?= e(BRAND_NAME) ?>. Si el servicio no cumple lo prometido, devolvemos el dinero conforme a esta política.</p>
        <ul>
            <li><strong>Plazo:</strong> puedes solicitar el reembolso completo dentro de los <strong>14 días naturales</strong> siguientes a la fecha de pago.</li>
            <li><strong>Cómo solicitarlo:</strong> escribe a <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a> indicando el número de pedido (aparece en tu portal de clientes) y el motivo. No exigimos ninguna justificación especial.</li>
            <li><strong>Respuesta:</strong> confirmamos la solicitud en un plazo máximo de 5 días hábiles.</li>
            <li><strong>Devolución del dinero:</strong> se realiza por el mismo medio de pago utilizado en la compra, dentro de los 14 días siguientes a la aprobación. El tiempo que tarde en reflejarse depende de tu banco o entidad de pago.</li>
            <li><strong>Reembolsos parciales:</strong> pasados los 14 días no procede la devolución del periodo ya disfrutado. Si cancelas antes de que termine la vigencia, podemos valorar un reembolso proporcional por los meses no utilizados.</li>
            <li><strong>Efecto sobre la licencia:</strong> al aprobarse el reembolso, la licencia y los accesos de los usuarios asociados quedan desactivados.</li>
            <li><strong>Problemas con el servicio:</strong> si el servicio no está disponible por causas atribuibles a <?= e(COMPANY_NAME) ?>, puedes solicitar el reembolso en cualquier momento de la vigencia.</li>
        </ul>
        <p>Cualquier duda sobre un pago o un reembolso se atiende en <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a><?= SUPPORT_PHONE !== '' ? ' o en el ' . e(SUPPORT_PHONE) : '' ?>.</p>

        <h2 id="privacidad">Aviso de privacidad</h2>
        <p><?= e(COMPANY_NAME) ?> trata los datos personales de administradores, docentes y estudiantes conforme al Reglamento (UE) 2016/679 (RGPD) y la Ley Orgánica 3/2018 (LOPDGDD), únicamente para prestar el servicio, gestionar la compra y brindar soporte.</p>
        <ul>
            <li>No vendemos ni compartimos datos personales con terceros para fines comerciales.</li>
            <li>No usamos los datos de la institución para entrenar modelos de inteligencia artificial.</li>
            <li>Puedes ejercer tus derechos de acceso, rectificación, supresión, oposición, limitación y portabilidad escribiendo a <?= e(SUPPORT_EMAIL) ?>.</li>
            <li>Los datos de pago son procesados directamente por Mercado Pago; no almacenamos datos de tarjetas.</li>
        </ul>

        <h2 id="accesibilidad">Accesibilidad</h2>
        <p>Trabajamos para que el sitio y la plataforma sean utilizables por todas las personas, siguiendo las pautas WCAG 2.1. Si encuentras una barrera de accesibilidad, escríbenos a <?= e(SUPPORT_EMAIL) ?>.</p>
    </div>
</main>
<?php include __DIR__ . '/includes/partials/footer.php'; ?>
