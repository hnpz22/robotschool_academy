# RSAL &mdash; Entrega 1: Panel Coordinador y Visibilidad Transversal

## Que trae esta entrega

El rol coordinador_pedagogico ahora tiene:

- **Dashboard academico propio** con 6 KPIs clickeables, contenido pedagogico, estudiantes por sede, top cursos y ultimas evaluaciones
- **Vista 100% transversal**: ve todas las sedes automaticamente (como admin_general) en todos los listados existentes
- **Panel de Talleristas**: listado de docentes con filtros (sede, activo, buscar) y metricas por tallerista
- **Detalle de Tallerista**: vista individual con 7 metricas y tabla de grupos, sesiones, evaluaciones
- **Estudiantes por curso**: vista agrupada curso -> grupos con cupos, horarios y docentes asignados
- **Acceso a Reportes** (los pedagogicos, no cartera)
- **Redireccion automatica** al dashboard academico al hacer login

## NO hay SQL nuevo que correr

A diferencia de la entrega anterior, esta solo toca codigo PHP. No se crean tablas, no se modifica la base de datos. El deploy es mas simple.

## Pasos para XAMPP

### Paso 1 &mdash; Backup de seguridad

En Terminal:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs
cp -r robotschool_academy robotschool_academy_backup_$(date +%Y%m%d_%H%M)
```

### Paso 2 &mdash; Copiar y descomprimir el ZIP

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/robotschool_academy
cp ~/Downloads/entrega1_coordinador.zip .
unzip -o entrega1_coordinador.zip
rm entrega1_coordinador.zip
```

El flag `-o` sobreescribe sin preguntar. Por eso importa el backup del Paso 1.

### Paso 3 &mdash; Verificar que los archivos nuevos estan en su lugar

```bash
ls modulos/academico/
```

Debes ver estos archivos y carpetas:
- `actividades/`  `asistencia/`  `cursos/`  `evaluaciones/`  `grupos/`  `rubricas/`  `temas/`
- `dashboard.php` (nuevo)
- `estudiantes_por_curso.php` (nuevo)
- `tallerista_ver.php` (nuevo)
- `talleristas.php` (nuevo)

### Paso 4 &mdash; Probar en el navegador

Asegurate de que Apache y MySQL esten corriendo en XAMPP.

**Prueba A &mdash; Login como admin_general:**

1. `http://localhost/robotschool_academy/login.php`
2. Entra con tu usuario admin
3. Debe llevarte a `dashboard.php` como siempre (sin cambios para admin)
4. El sidebar se ve igual, con todas las secciones

**Prueba B &mdash; Login como coordinador_pedagogico:**

Si no tienes un usuario coordinador creado, creas uno desde Usuarios -> Nuevo usuario -> rol "Coordinador Pedagogico".

1. Cierra sesion
2. Entra con el coordinador
3. Debe redirigirte automaticamente a `modulos/academico/dashboard.php`
4. Verifica que el sidebar muestre estas secciones:
   - **Principal** -> Dashboard Academico
   - **Academico . Diseno curricular** -> Cursos, Temas, Actividades, Rubricas
   - **Academico . Aula** -> Grupos y Horarios, Asistencia, Evaluaciones
   - **Matricula** -> Estudiantes, Estudiantes por curso
   - **Seguimiento** -> Talleristas, Reportes
   - NO debe aparecer: Finanzas, Administracion

**Prueba C &mdash; Navegar los 4 modulos nuevos:**

1. Click en **Dashboard Academico** -> ver KPIs, contenido pedagogico, estudiantes por sede
2. Click en **Talleristas** -> ver listado con filtros
3. Click en cualquier tarjeta de tallerista -> ver detalle con grupos asignados
4. Click en **Estudiantes por curso** -> ver cursos agrupados con sus grupos
5. Click en **Reportes** -> debe funcionar como cuando entra un admin (pero sin Cartera)

**Prueba D &mdash; Validar transversalidad:**

Como coordinador entra a Cursos, Grupos, Evaluaciones, Asistencia, Rubricas. En cada uno debe ver datos de **todas las sedes** (no solo la suya).

## Archivos que se sobreescriben

Archivos existentes que se modifican (el backup te protege):

- `config/auth.php` &mdash; `getSedeFiltro()` ahora null para coordinador
- `dashboard.php` &mdash; redireccion automatica del coordinador
- `includes/sidebar.php` &mdash; nuevas secciones para coordinador
- `modulos/reportes/index.php` &mdash; abierto al coordinador
- `modulos/reportes/exportar.php` &mdash; abierto al coordinador

Archivos nuevos:

- `modulos/academico/dashboard.php`
- `modulos/academico/talleristas.php`
- `modulos/academico/tallerista_ver.php`
- `modulos/academico/estudiantes_por_curso.php`

## Rollback si algo falla

```bash
cd /Applications/XAMPP/xamppfiles/htdocs
rm -rf robotschool_academy
mv robotschool_academy_backup_YYYYMMDD_HHMM robotschool_academy
```

(Reemplaza YYYYMMDD_HHMM por el timestamp real de tu backup)

Como no hubo cambios en la base de datos, no hay nada que revertir en phpMyAdmin.

## Si hay error 500

Revisa el log:

```bash
tail -30 /Applications/XAMPP/xamppfiles/logs/error_log
```

Me pegas lo que salga y te ayudo a diagnosticar.

## Proximos pasos

Cuando confirmes que Entrega 1 funciona en XAMPP:

- **Entrega 2**: Informe academico del estudiante (boletin por periodo con asistencia, promedios por rubrica, observaciones, PDF descargable)
- **Entrega 3**: Generador de certificaciones (PDF por curso/estudiante con plantillas y firmas digitales)
