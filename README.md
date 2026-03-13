# TarfíaDB — Gestión de socios y familias

Aplicación web para la gestión de socios y familias de una AMPA. Backend en Django, frontend en Nuxt 3, desplegable en un único servidor (ideal para Oracle Cloud Free Tier).

## Stack

| Capa | Tecnología |
|------|-----------|
| Backend | Django 5 + Django REST Framework |
| Autenticación | django-allauth — MFA (TOTP), Google, GitHub |
| Base de datos | PostgreSQL 16 |
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
├── docker-compose.yml      # Producción
├── docker-compose.dev.yml  # Override para desarrollo
└── .env.example
```

## Requisitos

- Docker y Docker Compose
- Un dominio con DNS apuntando al servidor
- Certificado SSL (instrucciones abajo)

## Puesta en marcha

### 1. Clonar y configurar entorno

```bash
git clone https://github.com/fjjimenezrios/tarfia-socios.git
cd tarfia-socios
cp .env.example .env
```

Editar `.env` con los valores reales:

```env
SECRET_KEY=genera-una-clave-con-openssl-rand-base64-50
DB_PASS=contraseña-segura
ALLOWED_HOSTS=tudominio.com
CORS_ALLOWED_ORIGINS=https://tudominio.com
API_BASE=https://tudominio.com/api
```

### 2. Obtener certificado SSL (Let's Encrypt)

```bash
sudo apt install certbot
sudo certbot certonly --standalone -d tudominio.com
```

Ajustar el dominio en `nginx/nginx.conf`.

### 3. Arrancar en producción

```bash
docker compose up -d --build
```

### 4. Crear superusuario

```bash
docker compose exec backend python manage.py createsuperuser
```

El panel de administración estará disponible en `https://tudominio.com/admin/`.

---

## Desarrollo local

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up
```

- Frontend: `http://localhost:3000`
- Backend API: `http://localhost:8000/api/`
- Django admin: `http://localhost:8000/admin/`

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
