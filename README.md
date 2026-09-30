# Landing + portal de licencias (PHP / MySQL)

Sitio de venta de licencias educativas con landing, portal de clientes y panel de administración.
El cobro se hace con **links de pago** (uno por plan): el pago ocurre fuera del sitio y la licencia
se activa cuando el administrador confirma el pedido.

## Instalación
1. Copia `config.example.php` como `config.php` y completa base de datos, marca, planes y links de pago.
2. Ejecuta `php install.php` (crea la base de datos, las tablas y la cuenta de administrador).
   En producción el instalador solo funciona por consola.
3. Sitio: `/` · Portal de clientes: `/portal/` · Administración: `/admin/`

## Cobros con links de pago
- Cada plan tiene su link en `$PLANS['...']['payment_link']` (`config.php`).
- Se pueden cambiar en caliente desde **Administración → Precios y planes**; lo guardado en la
  tabla `plan_settings` manda sobre `config.php`.
- El link debe empezar por `https://` y su valor debe coincidir con el precio del plan.

## Flujo de compra
`index.php` (precios) → `checkout.php` (cuenta + pedido `pending`) → `pago/pagar.php` (botón con el
link de pago; el cliente avisa que pagó y el pedido pasa a `review`) → `pago/resultado.php` (estado) →
**Administración → Pedidos y pagos → Confirmar pago** → licencia activa en el portal.

El administrador también puede asignar una licencia sin pago desde **Clientes → Asignar licencia**.

## Antes de producción
- `APP_DEBUG = false` (se activa solo fuera del dominio de producción), HTTPS y `BASE_URL` correcta.
- Completar los datos del vendedor (`COMPANY_*`) y revisar los textos de `legal.php`.
- En CloudPanel/Nginx el `.htaccess` no se aplica: hay reglas equivalentes en el vhost del sitio
  que bloquean `config.php`, `includes/`, `database/`, `storage/`, `_*.php`, `.sql`, `.log` y `.md`.

## Despliegue
El sitio en el servidor es una copia del repositorio: `git pull` dentro de la carpeta del sitio.
`config.php` no se versiona, así que nunca se sobrescribe al actualizar.
