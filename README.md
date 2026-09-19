<p align="center"><img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13"></p>

<p align="center">
  <a href="#"><img src="https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white" alt="PHP 8.3"></a>
  <a href="#"><img src="https://img.shields.io/badge/SQLite-003B57?logo=sqlite&logoColor=white" alt="SQLite"></a>
  <a href="#"><img src="https://img.shields.io/badge/Sanctum-4.0-FF2D20?logo=laravel&logoColor=white" alt="Laravel Sanctum"></a>
  <a href="#"><img src="https://img.shields.io/badge/Vue-3.5-4FC08D?logo=vuedotjs&logoColor=white" alt="Vue 3"></a>
  <a href="#"><img src="https://img.shields.io/badge/tests-32%20passing-brightgreen?logo=php&logoColor=white" alt="Tests PHPUnit"></a>
</p>

---

# Komi — API Backend + Admin Panel (Komi-Core)

> Backend de **Komi**, una plataforma social moderna y ligera para comunidades que quieren conversar con marca propia. API REST JSON centrada en usuarios, publicaciones con **soft delete**, comentarios en hilo, reacciones, comunidades, votaciones estilo Reddit y mensajería, protegida con **Laravel Sanctum**. Incluye un **panel de administración completo** construido con Vue 3.

## Descripción del Proyecto

Komi es una red social enfocada en comunidades de nicho. Cada usuario publica contenido (texto, imágenes, encuestas), reacciona, comenta en hilos y comparte publicaciones; los administradores gestionan usuarios, reportes, apelaciones y moderación desde un panel de administración completo.

El proyecto tiene dos interfaces:

- **API REST JSON** consumida por el cliente Flutter (`komi`), con autenticación por tokens Sanctum
- **Admin Panel SPA** construido con Vue 3, accesible en `/admin`, con autenticación por sesión y 2FA

## Stack Tecnológico

| Capa | Tecnología |
|------|------------|
| Lenguaje | **PHP 8.3+** |
| Framework | **Laravel 13** (Eloquent ORM, routing, migrations) |
| Base de datos | **SQLite** (default) / PostgreSQL |
| Autenticación API | **Laravel Sanctum** (tokens personales) |
| Autenticación Admin | Sesiones + **Google2FA** (2FA opcional) |
| OAuth2 social | **Laravel Socialite** (Google, Facebook, Twitter) |
| Frontend Admin | **Vue 3.5**, Vue Router 5, Vue I18n 9, Chart.js 4, Tailwind CSS 4 |
| Icons | **Heroicons** (@heroicons/vue) |
| Build | **Vite 8** |
| Testing | **PHPUnit** (`php artisan test`) |
| Herramientas | Laravel Tinker, Laravel Pail (logs), Collision |
## Panel de Administración

Panel de administración completo construido como SPA con Vue 3, accesible en `/admin`. Incluye autenticación por sesión, soporte para 2FA con Google Authenticator, y protección CSRF.

### Funcionalidades

- **Dashboard** con KPIs, gráficos de actividad (posts/usuarios/reacciones por día), tags trending, posts recientes
- **Gestión de usuarios** con búsqueda, sort, paginación, exportación CSV, detalle completo con historial de moderación
- **Gestión de publicaciones** con filtro por tipo, vista activos/eliminados, edición inline, preview
- **Sistema de moderación** con cola avanzada (filtros por fecha, estado, tipo, usuario, reportero, mín. reportes)
- **Reportes y apelaciones** con acciones de resolver/descartar/aprobar/rechazar
- **Auto-moderación** con palabras clave configurables (flag/block/delete)
- **Analytics** con gráficos de tendencias, comparación entre períodos, engagement rate
- **Configuración general** (nombre, descripción, límites, registro, mantenimiento)
- **Gestión de jobs** (pendientes, fallidos, retry, limpiar)
- **Gestión de sesiones** activas con cierre remoto
- **Health check** del sistema (DB, cache, disco, errores)
- **Logs de administración** con audit trail completo
- **Dark mode** toggle
- **Multi-idioma** (Español / Inglés) con vue-i18n
- **Búsqueda global** con atajo de teclado (Ctrl+K)
- **Notificaciones** en tiempo real (reportes pendientes + apelaciones)
- **Confirmación de acciones** con diálogos modales (ConfirmDialog) e iconos en todos los botones destructivos

### Vistas del Admin

| Ruta | Vista | Descripción |
|------|-------|-------------|
| `/admin` | Dashboard | KPIs, gráficos, actividad reciente |
| `/admin/users` | Users | Lista de usuarios con DataTable |
| `/admin/users/:id` | UserDetail | Perfil completo + acciones de moderación |
| `/admin/posts` | Posts | Publicaciones con filtro y edición inline |
| `/admin/tags` | Tags | CRUD de tags con edición inline |
| `/admin/communities` | Communities | Lista de comunidades |
| `/admin/reports` | Reports | Gestión de reportes |
| `/admin/moderation` | ModerationQueue | Cola de moderación avanzada |
| `/admin/appeals` | Appeals | Gestión de apelaciones |
| `/admin/analytics` | Analytics | Gráficos y métricas |
| `/admin/logs` | AdminLog | Audit trail de administradores |
| `/admin/system` | SystemHealth | Health check + errores de la app |
| `/admin/auto-mod` | AutoMod | Palabras clave de auto-moderación |
| `/admin/settings` | Settings | Configuración de 2FA |
| `/admin/general-settings` | GeneralSettings | Configuración de la plataforma |
| `/admin/jobs` | JobsQueue | Cola de jobs pendientes/fallidos |
| `/admin/sessions` | Sessions | Sesiones activas |

### Componentes Reutilizables

| Componente | Descripción |
|-----------|-------------|
| `AdminLayout.vue` | Layout principal con sidebar colapsable, top nav, búsqueda, notificaciones, dark mode, i18n |
| `DataTable.vue` | Tabla con sorting, paginación, búsqueda, exportación CSV, loading, empty state |
| `ConfirmDialog.vue` | Diálogo modal con icono, título, subtítulo, textarea de motivo, botones de confirmar/cancelar |
## API Endpoints

La API está versionada bajo el prefijo `/api`. Autenticación por token **Bearer** (Sanctum) salvo los endpoints públicos. Rate limiting en login y OTP (`throttle:5,1` / `throttle:6,1`).

### Endpoints públicos

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/register` | Registrar usuario y generar OTP |
| `POST` | `/api/verify-otp` | Verificar el OTP y emitir token Sanctum |
| `POST` | `/api/login` | Login con credenciales → token Sanctum |
| `POST` | `/api/auth/social-login` | Login social (Google/Facebook/Twitter) por token OAuth2 |
| `GET` | `/api/tags/trending` | Tags más populares |
| `POST` | `/api/telemetry/logs` | Recibir logs de errores del cliente |

### Endpoints protegidos (`auth:sanctum`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/me` | Perfil del usuario autenticado |
| `POST` | `/api/logout` | Revocar el token actual |
| `GET` | `/api/posts` | Feed de publicaciones (paginado) |
| `POST` | `/api/posts` | Crear publicación (con tags, encuesta, imagen) |
| `DELETE` | `/api/posts/{post}` | Eliminar publicación (solo propietario, soft delete) |
| `POST` | `/api/posts/{post}/like` | Dar / quitar like |
| `POST` | `/api/posts/{post}/vote` | Votar en publicación (upvote/downvote, estilo Reddit) |
| `POST` | `/api/posts/{post}/bookmark` | Agregar / quitar de favoritos |
| `POST` | `/api/posts/{post}/repost` | Repostear publicación (con comentario opcional) |
| `POST` | `/api/posts/{post}/poll/vote` | Votar en encuesta (permite cambiar voto) |
| `GET` | `/api/posts/{post}/comments` | Listar comentarios (anidados, hasta 3 niveles) |
| `POST` | `/api/posts/{post}/comments` | Crear comentario (con `parent_id` para respuestas) |
| `GET` | `/api/me/bookmarks` | Lista de favoritos del usuario |
| `PUT` | `/api/user/profile` | Actualizar perfil |

### Admin API (sesión + middleware `admin`)

Todas las rutas admin están bajo `/admin/api/` y requieren sesión de admin autenticado con protección CSRF.

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/admin/api/dashboard` | Estadísticas y gráficos del dashboard |
| `GET` | `/admin/api/users` | Lista de usuarios (sort, paginación, búsqueda) |
| `GET` | `/admin/api/users/{id}` | Detalle de usuario + historial de moderación |
| `PUT` | `/admin/api/users/{id}` | Actualizar estado del usuario |
| `POST` | `/admin/api/users/{id}/shadowban` | Toggle shadowban |
| `POST` | `/admin/api/users/{id}/temp-ban` | Ban temporal (con duración en días) |
| `POST` | `/admin/api/users/bulk` | Acciones masivas (suspend, ban, activate) |
| `GET` | `/admin/api/posts` | Lista de publicaciones |
| `PUT` | `/admin/api/posts/{id}` | Editar publicación |
| `DELETE` | `/admin/api/posts/{id}` | Eliminar publicación (soft delete) |
| `GET` | `/admin/api/posts/trashed` | Publicaciones eliminadas |
| `POST` | `/admin/api/posts/{id}/restore` | Restaurar publicación |
| `GET` | `/admin/api/tags` | Lista de tags |
| `POST` | `/admin/api/tags` | Crear tag |
| `PUT` | `/admin/api/tags/{id}` | Editar tag |
| `DELETE` | `/admin/api/tags/{id}` | Eliminar tag |
| `GET` | `/admin/api/communities` | Lista de comunidades |
| `DELETE` | `/admin/api/communities/{id}` | Eliminar comunidad |
| `GET` | `/admin/api/reports` | Lista de reportes |
| `PUT` | `/admin/api/reports/{id}` | Resolver/descartar reporte |
| `GET` | `/admin/api/moderation/search` | Búsqueda avanzada de moderación |
| `POST` | `/admin/api/moderation/{id}/action` | Acción de moderación |
| `GET` | `/admin/api/appeals` | Lista de apelaciones |
| `PUT` | `/admin/api/appeals/{id}` | Aprobar/rechazar apelación |
| `GET` | `/admin/api/auto-mod` | Lista de keywords de auto-moderación |
| `POST` | `/admin/api/auto-mod` | Crear keyword |
| `PUT` | `/admin/api/auto-mod/{id}` | Editar keyword |
| `DELETE` | `/admin/api/auto-mod/{id}` | Eliminar keyword |
| `GET` | `/admin/api/logs` | Logs de actividad de admin |
| `GET` | `/admin/api/analytics` | Datos de analytics |
| `GET` | `/admin/api/system/health` | Health check |
| `GET` | `/admin/api/system/errors` | Errores de la aplicación |
| `GET` | `/admin/api/system/jobs` | Jobs pendientes y fallidos |
| `POST` | `/admin/api/system/jobs/{id}/retry` | Reintentar job fallido |
| `DELETE` | `/admin/api/system/jobs/{id}` | Eliminar job |
| `POST` | `/admin/api/system/jobs/clear` | Limpiar todos los jobs fallidos |
| `POST` | `/admin/api/system/cache/clear` | Limpiar cache |
| `POST` | `/admin/api/2fa/setup` | Configurar 2FA (genera QR) |
| `POST` | `/admin/api/2fa/enable` | Habilitar 2FA |
| `POST` | `/admin/api/2fa/disable` | Deshabilitar 2FA |
| `GET` | `/admin/api/export/users` | Exportar usuarios (CSV) |
| `GET` | `/admin/api/export/posts` | Exportar publicaciones (CSV) |
| `GET` | `/admin/api/export/reports` | Exportar reportes (CSV) |
| `GET` | `/admin/api/settings` | Configuración general |
| `PUT` | `/admin/api/settings` | Actualizar configuración |
| `GET` | `/admin/api/sessions` | Sesiones activas |
| `DELETE` | `/admin/api/sessions/{id}` | Cerrar sesión |
| `GET` | `/admin/api/search` | Búsqueda global |
| `GET` | `/admin/api/notifications/unread` | Conteo de notificaciones pendientes |
## Sistema de Moderación

Komi incluye un sistema de moderación completo con las siguientes capas:

### Reportes y Apelaciones

- Los usuarios pueden **reportar** publicaciones o comentarios con un motivo
- Los admins revisan reportes desde la **cola de moderación** con filtros avanzados
- Las **apelaciones** permiten a los usuarios apelar decisiones de moderación
- Estados: `pending`, `resolved`, `dismissed`

### Acciones de Moderación

| Acción | Descripción |
|--------|-------------|
| **Eliminar publicación** | Soft delete del contenido reportado |
| **Advertir usuario** | Incrementa `warnings_count` del usuario |
| **Banear usuario** | Ban permanente (status = `banned`) |
| **Ban temporal** | Ban con expiración (`banned_until`), auto-desban cada minuto |
| **Shadowban** | El contenido del usuario es invisible para otros |
| **Suspender** | Desactiva la cuenta temporalmente |

### Auto-Moderación

Sistema de palabras clave configurables que puede:

- **Flag** — marcar publicaciones para revisión manual
- **Block** — bloquear la publicación automáticamente
- **Delete** — eliminar la publicación automáticamente

### Audit Trail

Cada acción de administración se registra en `admin_logs` con: admin ejecutor, acción, target, valores anteriores/nuevos, IP, y timestamp.

### Auto-Unban

El job `UnbanExpiredUsersJob` se ejecuta cada minuto y restaura automáticamente los usuarios cuyo ban temporal ha expirado.

## Modelos Principales

| Modelo | Descripción | Relaciones clave |
|--------|-------------|------------------|
| **User** | Usuarios de la plataforma | posts, comments, reactions, appeals, reports, communities |
| **Post** | Publicaciones (texto/imagen/encuesta/repost) | user, community, tags, comments, reactions, pollOptions, votes, bookmarks |
| **Comment** | Comentarios anidados (hasta 3 niveles) | user, post, parent, replies |
| **Community** | Comunidades | posts, members (users) |
| **Tag** | Hashtags | posts (pivot: post_tags) |
| **Reaction** | Likes en publicaciones | user, post |
| **Vote** | Votos estilo Reddit (upvote/downvote) | user, post |
| **PollOption / PollVote** | Opciones y votos de encuestas | post / user, pollOption |
| **Bookmark** | Favoritos | user, post |
| **Report** | Reportes de contenido | reporter, reportable (polymorphic) |
| **Appeal** | Apelaciones de moderación | user, reviewer |
| **AdminLog** | Audit trail de administración | admin |
| **AdminSetting** | Configuración general (key-value) | — |
| **AutoModKeyword** | Palabras clave de auto-moderación | — |
| **ClientLog** | Errores del cliente (deduplicados) | — |

## Estrategia de Soft Delete

Las publicaciones y comentarios se eliminan de forma **lógica** (columna `deleted_at`) mediante el trait Eloquent `SoftDeletes`:

1. `DELETE /api/posts/{post}` solo ejecuta `$post->delete()` si el usuario autenticado **es el propietario** (403 si no lo es).
2. Los posts borrados dejan de aparecer en el feed y en las búsquedas.
3. La fila permanece en BD, actuando como máscara de "publicación no disponible": se pueden restaurar sin perder el contenido ni sus comentarios.
## Estructura del Repositorio

```
Komi-Core/
├── komi/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── AuthController.php          # Registro, login, OTP
│   │   │   │   ├── SocialAuthController.php     # Login social (OAuth2)
│   │   │   │   ├── PostController.php           # CRUD publicaciones
│   │   │   │   ├── CommentController.php        # Comentarios anidados
│   │   │   │   ├── VoteController.php           # Upvote/downvote
│   │   │   │   ├── PollController.php           # Votación de encuestas
│   │   │   │   ├── BookmarkController.php       # Favoritos
│   │   │   │   ├── RepostController.php         # Reposts
│   │   │   │   ├── TagController.php            # Tags trending
│   │   │   │   ├── UserController.php           # Perfil de usuario
│   │   │   │   ├── TelemetryController.php      # Logs del cliente
│   │   │   │   └── Admin/
│   │   │   │       ├── AdminAuthController.php       # Login admin + 2FA
│   │   │   │       ├── AdminDashboardController.php  # API central del admin
│   │   │   │       ├── AdminModerationController.php # Moderación avanzada
│   │   │   │       ├── AdminAutoModController.php    # Auto-moderación
│   │   │   │       ├── Admin2FAController.php        # Gestión 2FA
│   │   │   │       ├── AdminExportController.php     # Exportación CSV
│   │   │   │       ├── AdminConfigController.php     # Configuración
│   │   │   │       ├── AdminPostController.php       # Posts trashed
│   │   │   │       ├── AdminTagController.php        # CRUD tags
│   │   │   │       ├── AdminSearchController.php     # Búsqueda global
│   │   │   │       ├── AdminSessionController.php    # Sesiones activas
│   │   │   │       └── AdminNotificationController.php # Notificaciones
│   │   │   ├── Middleware/
│   │   │   │   └── CheckAdminMiddleware.php     # Verifica is_global_admin
│   │   │   ├── Requests/                       # Validación (39 Form Requests)
│   │   │   └── Resources/                      # PostResource, UserResource
│   │   ├── Jobs/
│   │   │   └── UnbanExpiredUsersJob.php         # Auto-desban cada minuto
│   │   ├── Mail/
│   │   │   └── SendOtpMail.php                  # Email de OTP
│   │   ├── Models/                              # 27 modelos Eloquent
│   │   └── Providers/
│   ├── config/                                  # Configuración Laravel
│   ├── database/
│   │   ├── migrations/                          # 36 migraciones
│   │   ├── factories/                           # User, Post, Comment, Reaction
│   │   └── seeders/                             # Datos de demostración
│   ├── resources/
│   │   ├── views/
│   │   │   ├── welcome.blade.php                # Landing page
│   │   │   └── admin/
│   │   │       ├── app.blade.php                # Shell del SPA admin
│   │   │       ├── login.blade.php              # Login admin
│   │   │       └── 2fa.blade.php                # Verificación 2FA
│   │   └── js/admin/
│   │       ├── app.js                           # Entry point Vue
│   │       ├── App.vue                          # Root component
│   │       ├── api.js                           # Fetch wrapper con CSRF
│   │       ├── router/index.js                  # Rutas Vue Router
│   │       ├── components/
│   │       │   ├── AdminLayout.vue              # Layout con sidebar
│   │       │   ├── DataTable.vue                # Tabla reutilizable
│   │       │   └── ConfirmDialog.vue            # Diálogo de confirmación
│   │       ├── views/                           # 17 vistas del admin
│   │       └── locales/                         # es.json, en.json
│   ├── routes/
│   │   ├── api.php                              # API pública + autenticada
│   │   ├── web.php                              # Admin rutas + SPA catch-all
│   │   └── console.php                          # Scheduler (UnbanExpiredUsersJob)
│   ├── tests/                                   # 32 tests, 154 assertions
│   ├── .env.example
│   ├── composer.json
│   ├── package.json
│   ├── phpunit.xml
│   └── vite.config.js
├── LICENSE
└── README.md
```

## Guía de Instalación y Ejecución

### Prerrequisitos

- **PHP 8.3+** con extensiones `pdo_sqlite`, `mbstring`, `openssl`
- **Composer 2.x**
- **Node.js + npm** (para el admin panel)
- **SQLite** (default) o PostgreSQL

### Instalación rápida

```bash
# 1. Entrar al proyecto
cd komi

# 2. Instalar dependencias PHP
composer install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Migrar y sembrar datos de demostración
php artisan migrate --seed

# 5. Instalar dependencias JS y compilar admin panel
npm install
npm run build

# 6. Levantar servidor de desarrollo
php artisan serve
```

### Scripts de Composer disponibles

```bash
composer setup    # Instala todo: composer install, .env, key, migrate, npm install, npm build
composer dev      # Ejecuta artisan serve, queue:listen, pail, npm run dev en paralelo
composer test     # Limpia config y ejecuta php artisan test
```

### Acceso al Admin Panel

1. Crear un usuario admin en la base de datos (`is_global_admin = true`)
2. Navegar a `http://localhost:8000/admin/login`
3. Iniciar sesión con las credenciales del admin
4. (Opcional) Configurar 2FA desde `/admin/settings`

### Variables de entorno importantes

```env
# Base de datos (SQLite default)
DB_CONNECTION=sqlite

# OAuth (opcional)
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT=

# Mail (para OTP)
MAIL_MAILER=log
```

## Testing y Calidad

```bash
# Suite completa (32 tests, 154 assertions) sobre SQLite en memoria
php artisan test

# Recomendado (script composer)
composer test
```

## Licencia

MIT — ver [LICENSE](LICENSE).
