# AGENTS.md -- RobotSchool Academy Learning (RSAL)

## Que es este proyecto

Plataforma web de gestion academica para instituciones de robotica educativa.
Gestiona sedes, docentes, estudiantes, grupos, evaluaciones con rubricas,
asistencia, calendario, modulo de extracurriculares y comunicacion con padres.

## Stack

- PHP 8.1 + Apache (sin framework, PDO directo)
- MySQL 8.0
- Bootstrap 5.3.2 CDN + CSS propio en `assets/css/rsal.css`
- Docker / Docker Compose v2

## Roles del sistema

| Rol | Acceso |
|---|---|
| `admin_general` | Todo el sistema, todas las sedes |
| `admin_sede` | Solo su sede |
| `coordinador_pedagogico` | Su sede, sin gestion de usuarios |
| `docente` | Sus grupos y evaluaciones |
| `padre` | Solo perfil de sus hijos |

## Estructura de directorios

```
/assets/    -> CSS e imagenes
/config/    -> config.php (bootstrap), auth.php (roles), .htaccess (deny all)
/docente/   -> Portal de docentes
/includes/  -> head.php, sidebar.php, ec_helpers.php compartidos
/modulos/   -> Modulos por entidad (sedes, usuarios, cursos, grupos,
              matriculas, pagos, evaluaciones, asistencia, calendario,
              extracurriculares, etc.)
/portal/    -> Portal de padres
/public/    -> Sitio publico (registro, landing)
/Schema/    -> robotschool_academy.sql (schema canonico) + migraciones
/uploads/   -> Archivos subidos (volumen Docker en prod, .htaccess bloquea PHP)
```

## Reglas criticas para agentes

1. **ASCII puro** en todos los archivos PHP -- sin UTF-8 BOM ni chars > 127.
   Usar HTML entities (`&aacute;`, `&ntilde;`, `&mdash;`, etc.) para acentos.
   Razon: bug historico con XAMPP en Mac que corrompia los archivos.
2. **Siempre filtrar por `sede_id`** en queries -- nunca mezclar datos de sedes.
   `getSedeFiltro()` en `config/auth.php` devuelve null para admin_general /
   coordinador_pedagogico (ven todo) o el sede_id del usuario.
3. La sesion expone: `$_SESSION['usuario_id']`, `['usuario_rol']`, `['sede_id']`.
4. `cursos` NO tiene sede_id -- la relacion sede va por grupos o matriculas.
5. `pagos` usa: `padre_id`, `fecha_limite`, `observaciones` (NO `concepto` ni
   `fecha_vencimiento`).
6. Todas las queries deben usar **PDO prepared statements** -- prohibido
   concatenar valores en SQL.
7. **`BASE_URL` y credenciales DB vienen de variables de entorno** con fallback
   a defaults XAMPP. No hardcodear rutas ni passwords. Ver `config/config.php`.

## Comandos Docker

```bash
# Levantar el stack (academy-app + academy-mysql)
docker compose -f compose.academy.yml up -d --build

# Ver logs
docker compose -f compose.academy.yml logs -f academy-app

# Estado
docker compose -f compose.academy.yml ps

# Shell en el container PHP
docker exec -it academy-app bash
```

## Inicializacion de BD

El schema canonico (`Schema/robotschool_academy.sql`, 35 tablas) se carga
automaticamente la primera vez que arranca el container `academy-mysql` via
`/docker-entrypoint-initdb.d`. Para reimportar manualmente:

```bash
docker exec -i academy-mysql mysql -u root -p"$MYSQL_ROOT_PASSWORD" \
  robotschool_academy < Schema/robotschool_academy.sql
```

## Pendientes documentados

- [ ] CSRF protection en formularios POST criticos.
- [ ] Validacion MIME real en uploads con fileinfo (no solo extension).
- [ ] Estrategia de backups automaticos de la DB academy.
- [ ] Auditar `setup_admin.php` y `diagnostico.php` -- limitar acceso en prod.
- [ ] Migrar `login.php` propio al SSO unificado cuando ese proyecto avance.

## Recursos

- Vault del equipo: `../context/` (Obsidian, ver `CLAUDE.md`).
- Proyecto activo: `20 - Projects/Active/RobotSchool - Academy Deploy.md`.
- SOP de deploy: `60 - Processes/Proceso - Desplegar app nueva en servidor
  robotschool (subdominio + docker).md`.
