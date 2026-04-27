# RobotSchool Academy Learning (RSAL)

Plataforma web de gestion academica para instituciones de robotica educativa.
Gestiona sedes, usuarios, cursos, grupos, matriculas, pagos, evaluaciones con
rubricas, asistencia, calendario, modulo de extracurriculares y portal de padres.

## Stack

- PHP 8.1 + Apache (mod_rewrite, opcache, gd, intl, pdo_mysql)
- MySQL 8.0
- Bootstrap 5.3.2 (CDN) + CSS propio en `assets/css/rsal.css`
- Sin framework -- PDO directo, sesiones nativas
- Docker / Docker Compose v2

## Despliegue (VPS RobotSchool)

El proyecto se despliega como container detras del reverse proxy compartido
`robotschool_nginx` del stack RobotSchool, sirviendose bajo
`https://academy.miel-robotschool.com`. Convive con `sistema`, `lms`,
`archivos`, `class`, `crafty` en la red Docker
`robotschool-inventory_robotschool`.

```bash
# En el VPS (147.93.114.39)
cd /opt/robotschool/robotschool_academy
cp .env.example .env       # editar con secrets reales (chmod 600)
docker compose -f compose.academy.yml up -d --build
docker compose -f compose.academy.yml ps
```

Para los pasos de DNS, vhost nginx y certificado HTTPS ver
[`60 - Processes/Proceso - Desplegar app nueva en servidor robotschool`](../context/60%20-%20Processes/) en el vault.

## Desarrollo local

### Opcion 1 -- XAMPP (rapido)

1. Copiar el repo a `/Applications/XAMPP/xamppfiles/htdocs/robotschool_academy/`.
2. Importar `Schema/robotschool_academy.sql` en phpMyAdmin.
3. Visitar `http://localhost/robotschool_academy/`.

`config/config.php` cae a defaults locales (DB_HOST=localhost, root, sin
password) si no hay variables de entorno seteadas.

### Opcion 2 -- Docker

```bash
cp .env.example .env       # editar valores
docker compose -f compose.academy.yml up -d --build
docker compose -f compose.academy.yml logs -f academy-app
```

## Variables de entorno

Ver [`.env.example`](.env.example). Las relevantes:

| Variable | Para que |
|---|---|
| `RSAL_ENV` | `production` activa cookie_secure y oculta detalles de errores |
| `RSAL_BASE_URL` | URL publica completa (ej. `https://academy.miel-robotschool.com/`) |
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` | Conexion a la DB |
| `MYSQL_*` | Credenciales del container `academy-mysql` |

## Estructura

```
assets/             CSS, imagenes, fuentes
config/             config.php (bootstrap), auth.php (roles), .htaccess
docente/            Portal docente
includes/           head.php, sidebar.php, ec_helpers.php
modulos/            CRUDs por entidad (sedes, usuarios, cursos, grupos,
                    matriculas, pagos, evaluaciones, asistencia, etc.)
portal/             Portal de padres
public/             Landing y registro publico
Schema/             SQL canonico (robotschool_academy.sql)
uploads/            Archivos subidos (volumen Docker en prod)
Dockerfile          Imagen php:8.1-apache para academy-app
compose.academy.yml Stack academy (app + mysql) en red robotschool
```

## Schema

El archivo canonico es [`Schema/robotschool_academy.sql`](Schema/robotschool_academy.sql)
(35 tablas). El container `academy-mysql` lo carga automaticamente la primera
vez que arranca via `/docker-entrypoint-initdb.d`.

## Roles

| Rol | Acceso |
|---|---|
| `admin_general` | Todo el sistema, todas las sedes |
| `admin_sede` | Solo su sede |
| `coordinador_pedagogico` | Su sede, sin gestion de usuarios |
| `docente` | Sus grupos y evaluaciones |
| `padre` | Solo perfil de sus hijos |

## Seguridad

- `config/.htaccess` deniega acceso externo al directorio.
- `uploads/.htaccess` impide la ejecucion de PHP en archivos subidos.
- Sesiones con `cookie_httponly`, `cookie_samesite=Lax`, `cookie_secure` en prod.
- En produccion `.env` vive en el servidor con permisos 600 -- nunca commiteado.

Mas reglas para agentes y contribuidores en [`AGENTS.md`](AGENTS.md).
