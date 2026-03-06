# reservaTickets

Sistema de reserva de entradas online. Gestión de eventos, venues, asientos y reservas con panel de administración e informes.

---

## Stack

- **Laravel 12** · PHP 8.2
- **Vite** para assets
- **DomPDF** para tickets e informes en PDF
- **Endroid QR** para códigos en entradas
- **Google reCAPTCHA** en formularios públicos

## Funcionalidades

- Catálogo de eventos y selección de asientos por venue
- Reservas con flujo de checkout y confirmación
- Entradas en PDF con código QR y plantillas personalizables
- Envío de tickets por email (cola de jobs)
- Panel admin: eventos, venues, asientos, reservas, usuarios, plantillas de ticket
- Informes PDF (ventas, clientes, entradas, auditoría)
- Newsletter y formulario de contacto
- Auditoría de cambios en reservas

## Requisitos

- PHP 8.2+
- Composer
- Node.js (para compilar assets)
- MySQL / MariaDB o compatible

## Instalación

```bash
git clone https://github.com/lquenta/reservaTickets.git
cd reservaTickets
composer install
cp .env.example .env
php artisan key:generate
```

Configurar en `.env`: `DB_*`, `MAIL_*`, `RECAPTCHA_*` (y `QUEUE_CONNECTION` si usas colas). Luego:

```bash
php artisan migrate
npm install && npm run build
php artisan serve
```

Panel admin: acceder con un usuario que tenga `is_admin = true` en base de datos.

## Licencia

GPL-3.0. Ver [LICENSE](LICENSE).
