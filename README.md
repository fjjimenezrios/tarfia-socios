# TarfíaDB — Gestión de socios y familias

Aplicación web para la gestión de socios y familias de una AMPA. Backend en Django, frontend en Nuxt 3, desplegable en un único servidor (ideal para Oracle Cloud Free Tier).

## Stack

| Capa | Tecnología |
|------|-----------|
| Backend | Django 6 + Django REST Framework |
| Autenticación | django-allauth — MFA (TOTP), Google, GitHub |
| Base de datos | PostgreSQL 17 |
| Frontend | Nuxt 3 + Nuxt UI (Tailwind CSS) |
| Estado | Pinia |
| Servidor | Nginx + Gunicorn |
| Contenedores | Docker + Docker Compose |

## Estructura

```
tarfia-socios/
├── backend/
│   ├── apps/
│   │   ├── accounts/       # Usuario personalizado
│   │   ├── socios/         # Socios, niveles y estadísticas
│   │   └── familias/       # Familias y tutores
│   ├── config/
│   │   ├── settings/
│   │   │   ├── base.py
│   │   │   ├── development.py
│   │   │   └── production.py
│   │   └── urls.py
│   └── requirements/
│       ├── base.txt
│       ├── development.txt
│       └── production.txt
├── frontend/
│   ├── pages/
│   │   ├── auth/           # Login, MFA TOTP
│   │   ├── socios/         # Listado y gestión
│   │   └── familias/       # Listado y gestión
│   ├── stores/             # Pinia (auth + JWT)
│   ├── composables/        # useApi con refresh automático
│   └── layouts/            # default (sidebar) + auth
├── nginx/
│   └── nginx.conf          # HTTPS, proxy, headers de seguridad
├── utils/
│   ├── generate_env.py     # Genera .env con secretos aleatorios
│   └── mdb_to_postgres.py  # Migración desde Access (.mdb)
├── Makefile
├── docker-compose.yml      # Producción
├── docker-compose.dev.yml  # Override para desarrollo
└── .env.example
```

## Requisitos

- Docker y Docker Compose
- Python 3.11+ (solo para los scripts de `utils/`)
- Un dominio con DNS apuntando al servidor
- Certificado SSL (instrucciones abajo)

---

## Puesta en marcha

### 1. Clonar el repositorio

```bash
git clone https://github.com/fjjimenezrios/tarfia-socios.git
cd tarfia-socios
```

### 2. Inicializar el entorno

```bash
make init
```

Este comando:
- Genera `.env` con `SECRET_KEY`, `DB_PASS` y todos los secretos de forma aleatoria
- Construye las imágenes Docker
- Arranca PostgreSQL y espera a que esté listo
- Aplica las migraciones de Django

> Para regenerar los secretos (⚠ borra los anteriores): `make init-force`

### 3. Editar el .env generado

El `.env` se crea con valores funcionales pero debes ajustar:

```env
ALLOWED_HOSTS=tudominio.com,www.tudominio.com
CORS_ALLOWED_ORIGINS=https://tudominio.com
API_BASE=https://tudominio.com/api
DEFAULT_FROM_EMAIL=noreply@tudominio.com

# Email SMTP
EMAIL_HOST_USER=tu@gmail.com
EMAIL_HOST_PASSWORD=contraseña-app

# Social login (opcional)
GOOGLE_CLIENT_ID=...
GITHUB_CLIENT_ID=...
```

### 4. Obtener certificado SSL (Let's Encrypt)

```bash
sudo apt install certbot
sudo certbot certonly --standalone -d tudominio.com
```

Ajustar el dominio en `nginx/nginx.conf`.

### 5. Crear superusuario y arrancar

```bash
make superuser
make up
```

El panel de administración estará en `https://tudominio.com/admin/`.

---

## Comandos disponibles (Makefile)

```bash
make help           # Lista todos los comandos
```

| Comando | Descripción |
|---------|-------------|
| `make init` | Primera instalación: genera .env, build, migra |
| `make up` | Arranca todos los servicios |
| `make down` | Para todos los servicios |
| `make build` | Reconstruye imágenes sin caché |
| `make logs` | Logs en tiempo real |
| `make migrate` | Aplica migraciones de Django |
| `make makemigrations` | Crea nuevas migraciones |
| `make superuser` | Crea superusuario |
| `make shell` | Shell interactivo de Django |
| `make db-shell` | Consola psql |
| `make db-dump` | Exporta backup de la BD |
| `make db-restore FILE=...` | Restaura un dump |
| `make dev` | Entorno de desarrollo con hot-reload |
| `make test` | Ejecuta tests de Django |
| `make import-mdb MDB=...` | Migra un .mdb a PostgreSQL |
| `make clean` | ⚠ Elimina contenedores y volúmenes |

---

## Desarrollo local

```bash
make dev
```

- Frontend: `http://localhost:3000`
- Backend API: `http://localhost:8000/api/`
- Django admin: `http://localhost:8000/admin/`

---

## Migración desde Access (.mdb)

Si tienes datos en un archivo Access (.mdb), puedes migrarlos a PostgreSQL:

```bash
# Requisito: mdbtools
sudo apt install mdbtools    # Linux
brew install mdbtools         # macOS

# Migrar
make import-mdb MDB=socios-tarfia.mdb
```

El script lee la conexión automáticamente desde el `.env` y crea un schema `mdb_import` en PostgreSQL con todas las tablas del .mdb. Desde ahí puedes revisar los datos y transformarlos al schema de Django con un script de migración propio.

---

## Autenticación y MFA

La autenticación usa [django-allauth](https://docs.allauth.org) en modo headless (API REST pura, sin redirecciones HTML).

### Flujo de login
1. `POST /api/auth/token/` → devuelve `access` + `refresh` JWT
2. Si el usuario tiene MFA activo → redirige a `/auth/mfa` para introducir el código TOTP
3. Token de acceso válido 15 minutos, refresh 7 días (rotación automática)

### Activar MFA (desde el perfil)
Una vez logado, el usuario puede activar TOTP desde `/api/auth/browser/v1/account/2fa/totp/` — genera un QR para escanear con Google Authenticator, Authy, etc. Se generan también 10 códigos de recuperación de un solo uso.

### Login social
Configurar en `.env`:

```env
# Google — console.developers.google.com
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...

# GitHub — github.com/settings/developers
GITHUB_CLIENT_ID=...
GITHUB_CLIENT_SECRET=...
```

---

## API

La API sigue convenciones REST estándar. Todos los endpoints requieren el header:

```
Authorization: Bearer <access_token>
```

### Endpoints principales

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/api/socios/` | Listado paginado de socios |
| `POST` | `/api/socios/` | Crear socio |
| `GET/PUT/DELETE` | `/api/socios/{id}/` | Detalle, edición, baja |
| `GET` | `/api/socios/estadisticas/` | Resumen del dashboard |
| `GET` | `/api/socios/niveles/` | Listado de niveles/cursos |
| `GET` | `/api/familias/` | Listado paginado de familias |
| `POST` | `/api/familias/` | Crear familia |
| `GET/PUT/DELETE` | `/api/familias/{id}/` | Detalle, edición, eliminación |

Filtros disponibles en socios: `?estado=activo`, `?nivel=1`, `?cuota_pagada=true`, `?search=nombre`

---

## Despliegue en Oracle Cloud Free Tier

Oracle ofrece una VM Ampere A1 **siempre gratuita** con 4 vCPU y 24 GB de RAM — más que suficiente para esta aplicación.

1. Crear instancia ARM en Oracle Cloud (imagen: Ubuntu 22.04)
2. Abrir puertos 80 y 443 en las reglas de seguridad de la VCN
3. Apuntar el DNS del dominio a la IP pública de la instancia
4. Instalar Docker:
   ```bash
   curl -fsSL https://get.docker.com | sh
   sudo usermod -aG docker $USER
   ```
5. Seguir los pasos de [Puesta en marcha](#puesta-en-marcha)

---

## Licencia

MIT
