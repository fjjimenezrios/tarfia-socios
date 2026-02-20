# Tarfia Socios

Sistema de gestión de socios y familias para asociaciones, clubes deportivos y AMPAs.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php)
![MariaDB](https://img.shields.io/badge/MariaDB-10.x-003545?logo=mariadb)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?logo=bootstrap)
![License](https://img.shields.io/badge/License-Propietario-red)

## Características

- **Gestión de Socios**: Alta, baja, edición y consulta de socios
- **Gestión de Familias**: Agrupa socios por familia para cuotas compartidas
- **Control de Cuotas**: Cálculo automático de ingresos mensuales/anuales
- **Informes PDF**: Exportación de listados personalizados
- **Etiquetas Postales**: Generación de etiquetas para envíos
- **Dashboard**: Estadísticas y gráficos en tiempo real
- **DataTables**: Tablas con paginación servidor, filtros y ordenación
- **PWA**: Instalable como app en móviles
- **Modo Oscuro**: Tema claro/oscuro
- **Responsive**: Adaptado a móviles y tablets
- **Caché**: Sistema de caché para rendimiento óptimo

---

## Requisitos

- PHP 8.0 o superior
- MariaDB 10.x / MySQL 8.x
- Extensiones PHP: `pdo_mysql`, `gd` (para iconos), `json`
- Servidor web: Apache/Nginx (o Synology Web Station)

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/tarfia-socios.git
cd tarfia-socios
```

### 2. Configurar la base de datos

Copia el archivo de configuración de ejemplo:

```bash
cp config/config.example.php config/config.php
```

Edita `config/config.php` con tus credenciales:

```php
<?php
define('DB_HOST', 'localhost');     // Host de la base de datos
define('DB_PORT', 3306);            // Puerto (3306 por defecto)
define('DB_NAME', 'TarfiaSocios');  // Nombre de la base de datos
define('DB_USER', 'tu_usuario');    // Usuario
define('DB_PASS', 'tu_contraseña'); // Contraseña
```

### 3. Crear la base de datos

Ejecuta los scripts SQL en orden:

```bash
mysql -u root -p < sql/crear_base_datos.sql
mysql -u root -p TarfiaSocios < sql/crear_indices.sql
```

### 4. Generar iconos PWA

Accede a `/generar-iconos.php` desde el navegador y luego elimina el archivo.

### 5. Permisos

```bash
chmod 755 cache/
chmod 644 config/config.php
```

---

## Estructura de la Base de Datos

### Diagrama Entidad-Relación

```
┌─────────────────────┐         ┌─────────────────────┐
│   Familias Socios   │         │    Niveles-Cursos   │
├─────────────────────┤         ├─────────────────────┤
│ Id (PK)             │         │ Nivel (PK)          │
│ Apellidos           │         │ Curso               │
│ Nombre padre        │         └─────────────────────┘
│ Apellidos padre     │                   │
│ Nombre madre        │                   │
│ Apellidos madre     │                   │
│ Dirección           │                   │
│ Localidad           │                   │
│ Teléfono            │                   │
│ Movil Padre         │                   │
│ Movil Madre         │                   │
│ e-mail              │                   │
└─────────────────────┘                   │
          │                               │
          │ 1:N                           │ 1:N
          ▼                               ▼
┌─────────────────────────────────────────────────────┐
│                       Socios                         │
├─────────────────────────────────────────────────────┤
│ Id (PK)              - Identificador único           │
│ Nombre               - Nombre del socio              │
│ IdFamilia (FK)       - Referencia a familia          │
│ Nivel (FK)           - Referencia a nivel/curso      │
│ Cuota                - Cuota mensual (€)             │
│ Socio/Ex Socio       - Estado: 'Socio' o 'Ex Socio'  │
│ Móvil del socio      - Teléfono de contacto          │
│ Fecha de admisión    - Fecha de alta                 │
│ Observaciones        - Notas adicionales             │
└─────────────────────────────────────────────────────┘
```

### Tabla: `Familias Socios`

Almacena los datos de las familias. Cada familia puede tener múltiples socios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `Id` | INT AUTO_INCREMENT | Identificador único (PK) |
| `Apellidos` | VARCHAR(100) | Apellidos de la familia |
| `Nombre padre` | VARCHAR(50) | Nombre del padre |
| `Apellidos padre` | VARCHAR(100) | Apellidos del padre |
| `Nombre madre` | VARCHAR(50) | Nombre de la madre |
| `Apellidos madre` | VARCHAR(100) | Apellidos de la madre |
| `Dirección` | VARCHAR(200) | Dirección postal |
| `Localidad` | VARCHAR(100) | Ciudad/Localidad |
| `Teléfono` | VARCHAR(20) | Teléfono fijo |
| `Movil Padre` | VARCHAR(20) | Móvil del padre |
| `Movil Madre` | VARCHAR(20) | Móvil de la madre |
| `e-mail` | VARCHAR(100) | Email de contacto |

### Tabla: `Socios`

Almacena los datos individuales de cada socio (normalmente hijos).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `Id` | INT AUTO_INCREMENT | Identificador único (PK) |
| `Nombre` | VARCHAR(100) | Nombre del socio |
| `IdFamilia` | INT | FK → `Familias Socios`.`Id` |
| `Nivel` | INT | FK → `Niveles-Cursos`.`Nivel` |
| `Cuota` | DECIMAL(10,2) | Cuota mensual en euros |
| `Socio/Ex Socio` | VARCHAR(20) | 'Socio' o 'Ex Socio' |
| `Móvil del socio` | VARCHAR(20) | Teléfono del socio |
| `Fecha de admisión` | DATE | Fecha de alta |
| `Observaciones` | TEXT | Notas adicionales |

### Tabla: `Niveles-Cursos`

Catálogo de niveles educativos/deportivos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `Nivel` | INT | Identificador numérico (PK) |
| `Curso` | VARCHAR(20) | Nombre del curso |

**Valores por defecto:**

| Nivel | Curso |
|-------|-------|
| 0 | 4º EPO |
| 1 | 5º EPO |
| 2 | 6º EPO |
| 3 | 1º ESO |
| 4 | 2º ESO |
| 5 | 3º ESO |
| 6 | 4º ESO |
| 7 | 1º BACH |
| 8 | 2º BACH |
| 9 | UNIV |

> **Nota**: Los niveles 0-8 son los que pagan cuota. El nivel 9 (UNIV) no paga.

### Tabla: `usuarios` (opcional)

Para autenticación de administradores.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | INT AUTO_INCREMENT | PK |
| `username` | VARCHAR(50) | Nombre de usuario |
| `password` | VARCHAR(255) | Hash bcrypt |
| `created_at` | TIMESTAMP | Fecha de creación |

---

## Índices Recomendados

Para rendimiento óptimo, ejecuta `sql/crear_indices.sql`:

```sql
-- Socios
CREATE INDEX idx_socios_familia ON `Socios` (`IdFamilia`);
CREATE INDEX idx_socios_nivel ON `Socios` (`Nivel`);
CREATE INDEX idx_socios_estado ON `Socios` (`Socio/Ex Socio`(20));
CREATE INDEX idx_socios_nombre ON `Socios` (`Nombre`(50));
CREATE INDEX idx_socios_nivel_estado ON `Socios` (`Nivel`, `Socio/Ex Socio`(20));

-- Familias
CREATE INDEX idx_familias_apellidos ON `Familias Socios` (`Apellidos`(50));
CREATE INDEX idx_familias_localidad ON `Familias Socios` (`Localidad`(50));
```

---

## Estructura de Archivos

```
tarfia-socios/
├── api/                    # APIs para DataTables y exportación
│   ├── socios.php          # API paginación socios
│   ├── familias.php        # API paginación familias
│   ├── export-socios.php   # Exportación CSV
│   └── socio-baja.php      # Dar de baja socio
├── assets/
│   ├── css/app.css         # Estilos personalizados
│   ├── js/app.js           # JavaScript (toasts, navegación)
│   └── img/                # Imágenes y logos
├── cache/                  # Caché de la aplicación
├── config/
│   ├── config.example.php  # Plantilla de configuración
│   └── config.php          # Configuración local (no en git)
├── includes/
│   ├── auth.php            # Autenticación
│   ├── db.php              # Conexión PDO
│   ├── cache.php           # Sistema de caché
│   ├── header.php          # Cabecera HTML
│   └── footer.php          # Pie HTML
├── informes/               # Generación de informes PDF
│   ├── pdf-socios.php
│   ├── pdf-familias.php
│   ├── pdf-resumen.php
│   └── etiquetas.php
├── sql/                    # Scripts SQL
│   ├── crear_base_datos.sql
│   ├── crear_indices.sql
│   └── optimizacion_mariadb.sql
├── home.php                # Dashboard principal
├── socios.php              # Listado de socios
├── socios-alta.php         # Alta de socio
├── socios-editar.php       # Editar socio
├── socio-detalle.php       # Detalle de socio
├── familias.php            # Listado de familias
├── familias-alta.php       # Alta de familia
├── familias-editar.php     # Editar familia
├── familia-detalle.php     # Detalle de familia
├── cuotas.php              # Resumen de cuotas
├── informes.php            # Generador de informes
├── login.php               # Inicio de sesión
├── logout.php              # Cerrar sesión
├── manifest.json           # PWA manifest
├── sw.js                   # Service Worker
└── README.md               # Este archivo
```

---

## Personalización para Otros Clubes

### Cambiar el nombre y colores

1. **Nombre**: Buscar y reemplazar "Tarfia" en todos los archivos
2. **Colores**: Editar variables CSS en `assets/css/app.css`:

```css
:root {
    --tarfia-primary: #1a2744;    /* Color principal (navy) */
    --tarfia-accent: #ea580c;     /* Color de acento (naranja) */
    --tarfia-link: #0ea5e9;       /* Color de enlaces */
}
```

3. **Logo**: Reemplazar archivos en `assets/img/`
4. **Niveles**: Modificar la tabla `Niveles-Cursos` según las categorías del club

### Adaptar para diferentes tipos de asociaciones

El sistema está diseñado para ser flexible:

- **AMPA**: Niveles = Cursos escolares
- **Club deportivo**: Niveles = Categorías (Benjamín, Alevín, Infantil...)
- **Asociación cultural**: Niveles = Grupos o secciones
- **Peña**: Niveles = Tipos de socio (Fundador, Numerario, Juvenil...)

---

## Licencia

Software propietario. Todos los derechos reservados.

Para licencias comerciales, contactar con el desarrollador.

---

## Soporte

Para soporte técnico o personalización:
- Email: [tu-email@ejemplo.com]
- Web: [tu-web.com]
