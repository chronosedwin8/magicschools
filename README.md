# Landing + portal de licencias (PHP / MySQL / Mercado Pago)

## Instalación
1. Ajusta `config.php` (BD, marca, planes).
2. Abre `http://localhost:8080/magicschool/install.php` (o `php install.php`) para crear la BD `magicschool`.
3. Sitio: `http://localhost:8080/magicschool/` · Portal: `/portal/`

## Mercado Pago
En `config.php`:
- `MP_ACCESS_TOKEN`, `MP_PUBLIC_KEY` (credenciales de la app en el panel de desarrolladores).
- `MP_SANDBOX = true` para credenciales de prueba, `false` en producción.
- `MP_WEBHOOK_SECRET`: clave de la sección Webhooks (valida la firma `x-signature`).
- Webhook en el panel: `{BASE_URL}/pago/webhook.php`, evento **Pagos**.

Mientras `MP_ACCESS_TOKEN` esté vacío y `DEMO_MODE = true`, el pago se hace con un simulador local
(`pago/simulador.php`). En producción pon `DEMO_MODE = false`.

Mercado Pago no acepta `auto_return` ni `notification_url` con URLs locales; en localhost el comprador
debe pulsar "Volver al sitio" tras pagar. En un dominio público (HTTPS) ambos se envían automáticamente.

## Pantalla de cobro propia (`MP_CHECKOUT_MODE = 'custom'`)
`checkout.php` → `pago/pagar.php` (tarjeta con Secure Fields, PSE, Efecty) → `pago/procesar.php` crea el pago
con Checkout API → aprobado: portal · 3-D Secure: iframe del banco + `pago/estado.php` · PSE: banco y vuelta a
`pago/retorno.php` · Efecty/pendiente: `pago/resultado.php`. Con `'pro'` se vuelve a usar Checkout Pro.
PSE exige `callback_url` pública (no funciona en localhost).

## Flujo
`checkout.php` → crea usuario + orden → preferencia Checkout Pro → pago →
`pago/retorno.php` (verifica el pago contra la API) / `pago/webhook.php` → licencia activa →
portal (`portal/licencia.php`) para agregar usuarios (individual, masivo o CSV) dentro de los cupos.

## Antes de producción
- `APP_DEBUG = false`, `DEMO_MODE = false`, eliminar `install.php`.
- Usar HTTPS y fijar `BASE_URL`.
- Reemplazar textos legales en `legal.php` y datos de contacto en `config.php`.
