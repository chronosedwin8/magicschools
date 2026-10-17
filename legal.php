<?php
require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Aviso legal y condiciones | ' . BRAND_NAME;
$bodyClass = 'page-simple';
$espana = is_spanish_law();
include __DIR__ . '/includes/partials/head.php';
include __DIR__ . '/includes/partials/nav.php';
?>
<main class="legal">
    <div class="container narrow card">
        <p class="muted small">Texto base de referencia. Revísalo con tu asesor legal antes de publicar el sitio.</p>

        <h2 id="aviso">Identificación del vendedor</h2>
        <p><?php if ($espana): ?>
            En cumplimiento del artículo 10 de la Ley 34/2002, de Servicios de la Sociedad de la Información y de Comercio Electrónico (LSSI-CE), se informa:
        <?php else: ?>
            En cumplimiento del artículo 50 de la Ley 1480 de 2011 (Estatuto del Consumidor), se informa:
        <?php endif; ?></p>
        <ul>
            <li><strong>Vendedor y responsable de la facturación:</strong> <?= e(COMPANY_NAME) ?> (<?= e(COMPANY_COUNTRY) ?>)</li>
            <?php if (COMPANY_TAX_ID !== ''): ?><li><strong><?= e(tax_label()) ?>:</strong> <?= e(COMPANY_TAX_ID) ?></li><?php endif; ?>
            <?php if (COMPANY_ADDRESS !== ''): ?><li><strong>Domicilio:</strong> <?= e(COMPANY_ADDRESS) ?></li><?php endif; ?>
            <?php if (COMPANY_REGISTRY !== ''): ?><li><strong>Datos registrales:</strong> <?= e(COMPANY_REGISTRY) ?></li><?php endif; ?>
            <?php if (OPERATOR_NAME !== ''): ?>
                <li><strong>Entidad operadora del grupo:</strong> <?= e(OPERATOR_NAME) ?><?= OPERATOR_COUNTRY !== '' ? ' (' . e(OPERATOR_COUNTRY) . ')' : '' ?><?= OPERATOR_TAX_ID !== '' ? ' · ' . e(OPERATOR_TAX_ID) : '' ?>. No factura al cliente final.</li>
            <?php endif; ?>
            <li><strong>Correo electrónico:</strong> <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a></li>
            <li><strong>Sitio web:</strong> <?= e(base_url()) ?></li>
            <li><strong>Actividad:</strong> venta de licencias de software exclusivamente en línea, sin establecimiento abierto al público.</li>
            <?php if (RESELLER_OF !== ''): ?>
                <li><strong>Distribución autorizada:</strong> distribuidor autorizado de <?= e(RESELLER_OF) ?><?= RESELLER_TERRITORY !== '' ? ' en ' . e(RESELLER_TERRITORY) : '' ?>. Las marcas de <?= e(RESELLER_OF) ?> pertenecen a su titular y se usan con su autorización.</li>
            <?php endif; ?>
        </ul>
        <p>Para cualquier comunicación, reclamación o ejercicio de derechos, el canal de contacto es <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a>.</p>

        <h2 id="comercio">Condiciones de compra</h2>
        <p><?= e(COMPANY_NAME) ?> comercializa licencias de <?= e(BRAND_NAME) ?> exclusivamente en línea, a instituciones educativas y clientes de distintos países.</p>
        <ul>
            <li><strong>Producto:</strong> licencia de uso de la plataforma por el número de usuarios y la vigencia indicados en el plan adquirido<?= PLATFORM_URL !== '' ? ', accesible en ' . e(PLATFORM_URL) : '' ?>.</li>
            <li><strong>Precio:</strong> el precio publicado, expresado en pesos colombianos (COP), es el valor total que se cobra, sin cargos adicionales al finalizar la compra.</li>
            <li><strong>Tributos:</strong> los tributos o retenciones que, en su caso, correspondan en el país del comprador se rigen por la legislación de ese país.</li>
            <li><strong>Medios de pago:</strong> el pago se realiza a través de las pasarelas habilitadas en el sitio. <?= e(COMPANY_NAME) ?> no almacena datos de tarjetas.</li>
            <li><strong>Proceso de contratación:</strong> el cliente elige la licencia, registra sus datos, realiza el pago y recibe el acceso en su portal de clientes, donde queda disponible el comprobante de la compra.</li>
            <li><strong>Entrega:</strong> la licencia se activa una vez confirmado el pago, normalmente el mismo día hábil.</li>
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
            <li><?= $espana
                ? 'Para las relaciones de consumo resulta aplicable la normativa española y de la Unión Europea.'
                : 'Para las relaciones de consumo resulta aplicable la Ley 1480 de 2011 y las normas colombianas concordantes.' ?></li>
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
        <p><?= e(COMPANY_NAME) ?> trata los datos personales de administradores, docentes y estudiantes
            <?= $espana
                ? 'conforme al Reglamento (UE) 2016/679 (RGPD) y la Ley Orgánica 3/2018 (LOPDGDD)'
                : 'conforme a la Ley 1581 de 2012, el Decreto 1377 de 2013 y, para titulares en la Unión Europea, al Reglamento (UE) 2016/679 (RGPD)' ?>,
            únicamente para prestar el servicio, gestionar la compra y brindar soporte.</p>
        <ul>
            <li>No vendemos ni compartimos datos personales con terceros para fines comerciales.</li>
            <li>No usamos los datos de la institución para entrenar modelos de inteligencia artificial.</li>
            <li>Puedes ejercer tus derechos de acceso, actualización, rectificación, supresión, oposición y revocatoria de la autorización desde el <a href="<?= e(url('derechos.php')) ?>">formulario de derechos</a> o escribiendo a <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a>. Respondemos en los plazos de ley: 10 días hábiles para consultas y 15 días hábiles para reclamos.</li>
            <li>Los datos de pago son procesados directamente por la pasarela de pago; no almacenamos datos de tarjetas.</li>
            <li><strong>Encargados del tratamiento:</strong> para prestar el servicio usamos proveedores que pueden procesar datos por cuenta nuestra: el proveedor de inteligencia artificial que genera los textos, la pasarela de pago y el proveedor de alojamiento. Solo reciben lo necesario y no pueden usar la información para fines propios.</li>
            <li><strong>Transferencia internacional:</strong> esos proveedores operan servidores fuera de Colombia. Al usar el servicio aceptas esa transferencia, que se realiza con las garantías contractuales correspondientes.</li>
            <li><strong>Conservación:</strong> los datos de la cuenta se conservan mientras la licencia esté vigente y hasta cinco años después por obligaciones contables. Los textos generados con IA se conservan mientras el docente los mantenga en su biblioteca y puede borrarlos cuando quiera.</li>
            <li><strong>Datos de estudiantes y menores:</strong> la institución es responsable de contar con la autorización de los padres o acudientes antes de ingresar datos de un estudiante. El sitio pide esa declaración antes de habilitar las herramientas que lo requieren.</li>
        </ul>

        <h2 id="cookies">Política de cookies</h2>
        <p>Una cookie es un archivo pequeño que el sitio guarda en tu navegador. Usamos las mínimas necesarias y ninguna sirve para publicidad.</p>
        <table class="legal-table">
            <thead><tr><th>Cookie</th><th>Para qué sirve</th><th>Tipo</th><th>Duración</th></tr></thead>
            <tbody>
                <tr><td><code>AMSESSID</code></td><td>Mantener la sesión iniciada en el portal y proteger los formularios</td><td>Necesaria</td><td>Hasta cerrar el navegador</td></tr>
                <tr><td><code>ms_cookies</code></td><td>Recordar tu decisión sobre esta política</td><td>Necesaria</td><td>6 meses</td></tr>
            </tbody>
        </table>
        <p>Hoy <strong>no usamos cookies de analítica, publicidad ni redes sociales</strong>, y por eso el sitio funciona sin pedirte consentimiento para ellas. Si algún día las incorporamos, te las pediremos antes de instalarlas y podrás rechazarlas sin perder ninguna función.</p>
        <p>El sitio carga tipografías desde un servicio externo (Google Fonts), lo que implica que ese proveedor recibe tu dirección IP al mostrar la página. No instala cookies.</p>
        <p>Puedes borrar o bloquear las cookies desde la configuración de tu navegador. Si bloqueas las necesarias, no podrás iniciar sesión en el portal.</p>

        <h2 id="registro">Registro de usuarios</h2>
        <p>Para comprar una licencia o usar el portal hay que crear una cuenta. Al registrarte aceptas lo siguiente:</p>
        <ul>
            <li>Los datos que entregas son verdaderos y están actualizados, y eres responsable de mantenerlos así.</li>
            <li>La contraseña es personal e intransferible. Si sospechas que alguien más la conoce, cámbiala desde tu portal y avísanos.</li>
            <li>Cada cuenta corresponde a una persona. Las licencias institucionales permiten crear cuentas para los docentes que la institución autorice, dentro del número de cupos contratado.</li>
            <li>El administrador institucional responde por el uso que hagan las cuentas que él mismo da de alta.</li>
            <li>Podemos suspender una cuenta que incumpla estos términos, que intente vulnerar la seguridad del servicio o que lo use para fines ilícitos. Si ocurre, te lo informaremos.</li>
            <li>Puedes solicitar la eliminación de tu cuenta en cualquier momento desde el <a href="<?= e(url('derechos.php')) ?>">formulario de derechos</a>.</li>
            <li>Al registrarte aceptas estos términos y el <a href="#privacidad">aviso de privacidad</a>, y autorizas el tratamiento de tus datos para prestarte el servicio.</li>
        </ul>

        <h2 id="propiedad">Propiedad intelectual y derechos de imagen</h2>
        <ul>
            <li><strong>Del sitio:</strong> la marca, el logotipo, los textos, el diseño y el software de <?= e(BRAND_NAME) ?> pertenecen a <?= e(COMPANY_NAME) ?> o a quien nos haya autorizado su uso. No se permite copiarlos, modificarlos ni redistribuirlos sin permiso escrito.</li>
            <li><strong>De lo que generas:</strong> el contenido que produces con las herramientas es tuyo y de tu institución. Puedes usarlo, editarlo, imprimirlo y compartirlo con fines educativos sin pagarnos nada adicional. No reclamamos derechos sobre ese material.</li>
            <li><strong>De lo que aportas:</strong> si subes textos, imágenes o logotipos, declaras que tienes derecho a usarlos. Solo los empleamos para prestarte el servicio.</li>
            <li><strong>Contenido generado con IA:</strong> los resultados son borradores que requieren tu revisión. Pueden contener errores y no garantizamos que sean únicos, ya que un texto similar podría generarse para otra persona.</li>
            <li><strong>Derechos de imagen:</strong> no publicamos fotografías ni videos de docentes o estudiantes. Si alguna vez quisiéramos usar un testimonio, una imagen o el nombre de tu institución con fines de difusión, pediremos antes tu autorización por escrito y podrás revocarla cuando quieras.</li>
            <li><strong>Reclamaciones:</strong> si consideras que algún contenido del sitio vulnera tus derechos, escríbenos a <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a> y lo revisaremos de inmediato.</li>
        </ul>

        <h2 id="accesibilidad">Accesibilidad</h2>
        <p>Trabajamos para que el sitio y la plataforma sean utilizables por todas las personas, siguiendo las pautas WCAG 2.1. Si encuentras una barrera de accesibilidad, escríbenos a <a href="mailto:<?= e(SUPPORT_EMAIL) ?>"><?= e(SUPPORT_EMAIL) ?></a>.</p>
    </div>
</main>
<?php include __DIR__ . '/includes/partials/footer.php'; ?>
