<p align="center"><img src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white" alt="Laravel 13"></p>

<p align="center">
  <a href="#"><img src="https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white" alt="PHP 8.3"></a>
  <a href="#"><img src="https://img.shields.io/badge/PostgreSQL-4169E1?logo=postgresql&logoColor=white" alt="PostgreSQL"></a>
  <a href="#"><img src="https://img.shields.io/badge/Sanctum-4.0-FF2D20?logo=laravel&logoColor=white" alt="Laravel Sanctum"></a>
  <a href="#"><img src="https://img.shields.io/badge/tests-PHPUnit%2027%20passing-brightgreen?logo=php&logoColor=white" alt="Tests PHPUnit"></a>
</p>

---

# Komi — API Backend (Komi-Core)

> Backend de **Komi**, una plataforma social moderna y ligera para comunidades que quieren conversar con marca propia. API REST JSON centrada en usuarios, publicaciones con **soft delete**, comentarios en hilo, reacciones, comunidades y mensajería, protegida con **Laravel Sanctum**.

## Descripción del Proyecto

Komi es una red social enfocada en comunidades de nicho. Cada usuario publica contenido, reacciona y comenta en hilos; los administradores gestionan comunidades, reportes y apelaciones. El backend expone una **API REST JSON** consumida por el cliente Flutter (`komi`), y está diseñada con **arquitectura por capas** (rutas → controllers → modelos Eloquent), pruebas de integración sobre base de datos en memoria y un modelo de eliminación **no destructivo**.

## Stack Tecnológico

| Capa | Tecnología |
|------|------------|
| Lenguaje | **PHP 8.3+** |
| Framework | **Laravel 13** (Eloquent ORM, routing, migrations) |
| Base de datos | **PostgreSQL** (SQLite en pruebas) |
| Autenticación | **Laravel Sanctum** (tokens personales de API) |
| OAuth2 social | **Laravel Socialite** (Google, Facebook, Twitter) |
| Testing | **PHPUnit / Pest PHP** (`php artisan test`) |
| Estilo de código | **Laravel Pint** (`vendor/bin/pint`) |
| Herramientas extra | Laravel Tinker, Laravel Pail (logs), Collision |

## Arquitectura y API

La API está versionada bajo el prefijo `/api`. Autenticación por token **Bearer** (Sanctum) salvo los endpoints públicos de registro/login. El rate limiting protege login y OTP (`throttle:5,1` / `throttle:6,1`) para mitigar fuerza bruta.

### Endpoints públicos

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/api/register` | Registrar usuario y generar OTP |
| `POST` | `/api/verify-otp` | Verificar el OTP y emitir token Sanctum |
| `POST` | `/api/login` | Login con credenciales → token Sanctum |
| `POST` | `/api/auth/social-login` | Login social (Google/Facebook/Twitter) por token OAuth2 |

### Endpoints protegidos (`auth:sanctum`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/me` | Perfil del usuario autenticado |
| `POST` | `/api/logout` | Revocar el token actual |
| `GET` | `/api/posts` | Feed de publicaciones |
| `POST` | `/api/posts` | Crear publicación |
| `POST` | `/api/posts/{post}/like` | Dar / quitar like |
| `DELETE` | `/api/posts/{post}` | Eliminar publicación (solo propietario) |
| `GET` | `/api/posts/{post}/comments` | Listar comentarios |
| `POST` | `/api/posts/{post}/comments` | Crear comentario (anidado vía `parent_id`) |
| `PUT` | `/api/user/profile` | Actualizar perfil del usuario autenticado |

### Estrategia de Soft Delete en publicaciones

Las publicaciones se eliminan de forma **lógica** (columna `deleted_at`) mediante el trait Eloquent `SoftDeletes` del modelo `Post`:

1. `DELETE /api/posts/{post}` solo ejecuta `$post->delete()` si el usuario autenticado **es el propietario** (403 si no lo es).
2. Los posts borrados dejan de aparecer en el feed y en las búsquedas (las consultas globales de Eloquent los excluyen automáticamente).
3. La fila permanece en BD, actuando como **máscara de "publicación no disponible"**: se pueden restaurar o depurar sin perder el contenido ni sus comentarios.

```php
// app/Models/Post.php
class Post extends Model
{
    use SoftDeletes; // elimina pubs de forma lógica (máscara de indisponibilidad)
}
```

> Los comentarios también usan `SoftDeletes` con soporte de respuestas anidadas (`parent_id`).

## Estructura del Repositorio

```
Komi-Core/
├── komi/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/     # AuthController, PostController, CommentController…
│   │   │   ├── Requests/        # Validación (SocialAuthRequest, etc.)
│   │   │   └── Resources/       # UserResource (serialización API)
│   │   ├── Models/              # Post, Comment, Community, User, Reaction…
│   │   └── HttpMiddleware/      # (aplicado vía Laravel por defecto)
│   ├── config/                  # services.php (Socialite), auth.php (Sanctum)…
│   ├── database/
│   │   ├── migrations/          # 21 migraciones (posts, comments, tags, messages…)
│   │   └── seeders/             # Seeders de datos de demostración
│   ├── routes/
│   │   └── api.php              # Definición completa de endpoints
│   ├── tests/
│   │   ├── Feature/             # 27 tests: PostApiTest, SocialAuthApiTest…
│   │   └── Unit/
│   ├── .env.example
│   ├── composer.json
│   └── phpunit.xml
├── LICENSE
└── README.md
```

## Guía de Instalación y Ejecución

### Prerrequisitos

- **PHP 8.3+** con extensiones `pdo_pgsql` / `pdo_sqlite`, `mbstring`, `openssl`
- **Composer 2.x**
- **PostgreSQL** (o SQLite para desarrollo rápido / pruebas)
- **Node + npm** (solo si usarás Vite para build de assets)

### Pasos

```bash
# 1. Entrar al proyecto Laravel
cd komi

# 2. Instalar dependencias
composer install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Configurar la BD en .env (ejemplo PostgreSQL)
#    DB_CONNECTION=pgsql
#    DB_HOST=127.0.0.1
#    DB_PORT=5432
#    DB_DATABASE=komi
#    DB_USERNAME=postgres
#    DB_PASSWORD=secret

# 5. Migrar y sembrar datos de demostración
php artisan migrate --seed

# 6. Levantar el servidor de desarrollo
php artisan serve
```

> Opcional: claves de OAuth en `.env` → `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT`, y equivalentes para Facebook/Twitter (ver `config/services.php`).

### Testing y Calidad

```bash
# Suite completa (27 tests, 131 assertions) sobre SQLite en memoria
php artisan test

# Estilo de código con Pint
vendor/bin/pint --test

# Recomendado (script composer)
composer test
```

## Licencia

MIT — ver [LICENSE](LICENSE).