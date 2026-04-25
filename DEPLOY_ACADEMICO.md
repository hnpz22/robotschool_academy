# RSAL &mdash; Deploy m&oacute;dulo Acad&eacute;mico

## Resumen de cambios

Esta actualizaci&oacute;n reestructura los m&oacute;dulos de **Cursos, R&uacute;bricas, Evaluaciones, Asistencia y Grupos** bajo una carpeta paraguas `modulos/academico/`, y agrega dos m&oacute;dulos nuevos: **Temas** y **Actividades**.

## Orden de ejecuci&oacute;n en Hostinger

### Paso 1 &mdash; Backup de seguridad
Antes de nada, haz backup de la carpeta `public_html/` (o el directorio de la app) y export de la BD desde phpMyAdmin. Esto es irreversible.

### Paso 2 &mdash; Correr la migraci&oacute;n SQL
En phpMyAdmin, selecciona la base de datos `robotschool_academy` y ejecuta:

```
Schema/migration_v4_academico.sql
```

Esto crea las tablas `temas` y `actividades`. Las tablas existentes (`rubricas`, `evaluaciones`, `cursos`, etc.) NO se tocan.

### Paso 3 &mdash; Eliminar carpetas viejas en Hostinger
En el File Manager de Hostinger, elimina estas 5 carpetas (se movieron a `modulos/academico/`):

- `modulos/cursos/`
- `modulos/rubricas/`
- `modulos/evaluaciones/`
- `modulos/asistencia/`
- `modulos/grupos/`

**IMPORTANTE**: si no las eliminas primero quedar&aacute;n duplicadas y el sistema seguir&aacute; apuntando a las rutas nuevas, dejando las viejas como basura inaccesible.

### Paso 4 &mdash; Subir el ZIP
Sube `academico_deploy.zip` y desc&oacute;mpalo en la ra&iacute;z del proyecto. El ZIP contiene:

- `modulos/academico/` &mdash; completa (6 subm&oacute;dulos)
- `includes/sidebar.php` &mdash; reagrupado
- `dashboard.php` &mdash; links actualizados
- `docente/index.php` &mdash; links actualizados
- `modulos/matriculas/avance.php` &mdash; link actualizado
- `Schema/migration_v4_academico.sql` &mdash; migraci&oacute;n (ya ejecutada en Paso 2)

### Paso 5 &mdash; Verificaci&oacute;n

Entra al sistema y verifica:

1. Sidebar muestra tres secciones: **Acad&eacute;mico &middot; Dise&ntilde;o curricular**, **Acad&eacute;mico &middot; Aula**, **Matr&iacute;cula**
2. Click en "Cursos" &rarr; funciona
3. Click en "Temas" &rarr; listado vac&iacute;o con bot&oacute;n "Crear primer tema"
4. Crear un tema de prueba
5. Click en "Actividades" &rarr; listado vac&iacute;o
6. Crear una actividad asociada al tema
7. Click en "R&uacute;bricas", "Evaluaciones", "Asistencia", "Grupos" &rarr; todos funcionan
8. Login como docente &rarr; solo ve "Acad&eacute;mico &middot; Aula" (sin Dise&ntilde;o curricular)
9. Login como coordinador &rarr; ve Dise&ntilde;o curricular y Matr&iacute;cula (sin Finanzas ni Administraci&oacute;n)

## Estructura nueva

```
modulos/academico/
  cursos/          (movido de modulos/cursos/)
  temas/           NUEVO &mdash; unidades pedag&oacute;gicas
  actividades/     NUEVO &mdash; tareas por tema
  rubricas/        (movido)
  grupos/          (movido)
  asistencia/      (movido)
  evaluaciones/    (movido)
```

## Permisos por rol (nuevos)

| Rol | Dise&ntilde;o curricular | Aula | Matr&iacute;cula | Finanzas |
|---|---|---|---|---|
| admin_general | Si | Si | Si | Si |
| admin_sede | Si | Si | Si | Si |
| coordinador_pedagogico | Si | Si | Solo lectura | NO |
| docente | NO | Si | NO | NO |
| padre | (portal padres) | | | |

## Rollback (si algo falla)

1. Restaurar el backup de `public_html/`
2. En phpMyAdmin: `DROP TABLE actividades; DROP TABLE temas;`
3. El sistema vuelve al estado anterior sin p&eacute;rdida de datos.
