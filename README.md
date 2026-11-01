# PagosOnline

Plataforma web para que una empresa **facture servicios periódicos a sus clientes, les cobre online con Mercado Pago y emita el comprobante electrónico ante ARCA (ex AFIP)**, con envío de la factura en PDF (con CAE y QR) por correo.

Cada empresa instala y ajusta su propia copia: los datos del emisor (CUIT, certificado, punto de venta) se configuran por instalación, no hay multiempresa dentro de una misma base.

---

## Qué hace

**Usuarios (clientes finales)**
- Se registran con email y contraseña o con Google, y verifican la cuenta con un código de 6 dígitos.
- Vinculan a su cuenta uno o más **números de documento**; ven las facturas pendientes de esos documentos.
- Pagan con **Mercado Pago (Checkout Pro)** y descargan el PDF de cualquier factura.
- Consultan su historial de facturas pagadas.

**Administrador (`/admin`)**
- Clientes (con datos fiscales y de contacto), servicios (precio normal, precio vencido, impuestos, vencimiento, período) y la relación Clientes × Servicios.
- Facturas: generación por lote, generación manual, carga de facturas históricas, registro de pagos manuales, reintento de emisión ARCA, descarga de PDF.
- Estado de cuenta por cliente ("Saldos a fecha") con asiento debe / haber / saldo.
- Configuración ARCA del emisor (condición frente al IVA, tipo de Factura A, puntos de venta).

**ARCA**
- Facturas **A, B y C** (WSFEv1) y **E de exportación** (WSFEXv1), según la condición del emisor y del cliente.
- La autenticación (WSAA) y la lógica de negocio están en Laravel; el pedido de CAE se envía a una API intermedia en Node.
- Reintentos con espera creciente y reintento manual desde el panel.

---

## Stack

| Capa | Tecnología |
|---|---|
| Backend | Laravel 13, PHP 8.3+ (imagen Docker: PHP 8.4) |
| Base de datos | PostgreSQL (el buscador usa `translate()` de PostgreSQL) |
| Front | Blade + Vite + Tailwind |
| Autenticación | Laravel Breeze + Google (Socialite) + códigos por correo |
| Pagos | Mercado Pago SDK (`mercadopago/dx-php`) |
| PDF y QR | `barryvdh/laravel-dompdf` + `endroid/qr-code` |
| ARCA | `SoapClient` nativo (WSAA) + API intermedia Node (WSFE / WSFEX) |
| Deploy | Docker (2 etapas) + Supervisor (PHP-FPM + NGINX) en Render |

---

## Estructura relevante

```
app/
├── Console/Commands/
│   ├── RetryPendingArcaInvoices.php   arca:retry-pending
│   └── Purgeblockedclients.php        clients:purge-blocked
├── Http/Controllers/
│   ├── Admin/                         clientes, servicios, facturas, ARCA
│   ├── DashboardController.php        documentos del usuario
│   ├── DashboardInvoiceController.php pagar, PDF e historial
│   └── MercadoPagoWebhookController.php
├── Mail/InvoicePaidMail.php           correo con la factura en PDF
├── Models/                            Client, Invoice, Service, ArcaConfig, UserDocument...
├── Services/
│   ├── Arca/WsaaClient.php            Token + Sign (wsfe y wsfex)
│   ├── Arca/ComprobanteResolver.php   tipo de comprobante y documento
│   ├── Arca/ArcaApiService.php        emisión, errores y reintentos
│   ├── InvoicePdfService.php          PDF + QR
│   ├── InvoiceReceiptSender.php       envío del PDF por correo
│   └── MercadoPagoService.php         preferencia y consulta de pagos
└── Support/TextSearch.php             búsqueda tolerante (tildes, mayúsculas, varias palabras)
config/arca.php                        ambiente, WSAA, API, CUIT, certificado, reintentos
docker/                                nginx, supervisord y start.sh
public/js/search-dropdown.js           desplegable de sugerencias de los buscadores
z_checkThis/                           documentación interna con diagramas lógicos
```

---

## Regla de negocio central: dos valores por factura

Cada factura guarda siempre **dos precios** (normal y vencido) y un porcentaje de impuestos. Todo el sistema los respeta y siempre suma impuestos:

- Hasta `due_date` inclusive rige el precio normal; desde el día siguiente, el vencido.
- Lo que se cobra (Mercado Pago o pago manual) se decide **por la fecha de pago**, no se ingresa a mano.
- El saldo de un cliente es el mismo en el listado de clientes y en el asiento "Saldos a fecha", y da 0 cuando todo está pagado.
- ARCA siempre se emite **después** del pago y por el importe realmente cobrado.

---

## Variables de entorno

Ver `.env.example`. Las principales:

| Grupo | Variables |
|---|---|
| App | `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL` |
| Base de datos | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` |
| Admin inicial | `ADMIN_EMAIL`, `ADMIN_PASSWORD` |
| Mercado Pago | `MERCADOPAGO_ACCESS_TOKEN`, `MERCADOPAGO_WEBHOOK_SECRET`, `MERCADOPAGO_WEBHOOK_URL` |
| ARCA | `ARCA_AMBIENTE` (`homologacion` / `produccion`), `ARCA_CUIT`, `ARCA_CERTIFICATE_CRT` (Base64), `ARCA_PRIVATE_KEY` (Base64), `ARCA_API_URL`, `ARCA_API_KEY` |
| Reintentos ARCA (opcionales) | `ARCA_RETRY_DELAY_MINUTES`, `ARCA_RETRY_MAX_BACKOFF_MINUTES`, `ARCA_RETRY_MAX_ATTEMPTS`, `ARCA_RETRY_MAX_PER_RUN` |
| Google | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT` |
| Correo | `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` |
| Sesión y logs | `SESSION_SECURE_COOKIE=true` en HTTPS, `LOG_CHANNEL=stderr` en Render |

El certificado y la clave privada de ARCA se guardan en Base64 en variables de entorno: el disco de Render es efímero y nunca se escriben de forma permanente.

---

## Puesta en marcha

**Producción (Render + Docker):** el `Dockerfile` compila los assets y arma la imagen PHP; `docker/start.sh` migra la base, ejecuta los seeders y levanta PHP-FPM + NGINX en el puerto 10000. Detalle en `z_checkThis/deploy-render-docker.txt`.

**Local:**

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

Requiere las extensiones PHP `soap`, `openssl`, `gd`, `intl`, `bcmath` y `pdo_pgsql`. La ruta `/debug-extensions` informa si `soap` y `openssl` están cargadas.

Los seeders crean el usuario administrador (`ADMIN_EMAIL` / `ADMIN_PASSWORD`) y, solo si no hay clientes, datos de demostración en español (10 servicios, 200 clientes y 2 servicios asignados a cada uno).

---

## Comandos

| Comando | Qué hace |
|---|---|
| `php artisan arca:retry-pending` | Reintenta la emisión ARCA de facturas pagadas sin CAE cuyo reintento ya corresponde |
| `php artisan clients:purge-blocked` | Borra los clientes bloqueados hace 5 años o más (cliente + servicios asignados; nunca las facturas) |

`clients:purge-blocked` también se ejecuta cada vez que el admin entra al listado de clientes. `arca:retry-pending` **no está programado** (no hay scheduler ni cron): hoy solo funciona el botón manual "Reintentar ARCA".

---

## Documentación interna

Todo está en `z_checkThis/`, un archivo por apartado:

| Archivo | Contenido |
|---|---|
| `logic-parte 1.txt` | Registro, login, códigos de verificación, bloqueos y sesiones |
| `logic-parte 2.txt` | Mapa de la base de datos y flujos generales del panel admin |
| `admin-clientes-y-saldos.txt` | Clientes, bloqueo legal, servicios, Clientes × Servicios y estado de cuenta |
| `admin-facturas.txt` | Listado, generación, histórica, pago manual, eliminación y reintento ARCA |
| `ARCA` | Reparto de responsabilidades Laravel / API ARCA |
| `ARCA-Laravel-detalle.txt` | Implementación de ARCA en Laravel: A/B/C/E, reintentos, PDF, QR y correo |
| `mercado-pago-implementacion.txt` | Preferencia, webhook y validaciones |
| `mercado-pago-UnLogic` | Problema del webhook (snake_case / camelCase) y su solución |
| `usuario-dashboard-historial.txt` | Dashboard, documentos, pago, PDF e historial del usuario |
| `deploy-render-docker.txt` | Docker, Render, arranque y variables de entorno |

---

## Licencia

Distribuido bajo licencia [MIT](LICENSE).