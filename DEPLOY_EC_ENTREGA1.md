# Extracurriculares &mdash; Entrega 1: Cimientos + CRUD de Clientes

## Que trae esta entrega

Primera entrega de un modulo grande que se construye en 7 partes. Hoy solo la base:

- **Esquema SQL completo** con las 9 tablas del modulo (ec_clientes ec_contratos ec_programas ec_sesiones ec_asignaciones ec_estudiantes ec_asistencia ec_evaluaciones ec_evaluacion_detalle ec_desplazamientos_cache)
- **Panel del modulo** con 4 KPIs y estado de entregas pendientes
- **CRUD de clientes** completo: listado con filtros (tipo ciudad estado busqueda), formulario con mapa Leaflet OpenStreetMap para ubicacion exacta clickeable, contactos multiples institucional y principal
- **Nueva seccion Extracurriculares en el sidebar** visible para coordinador admin_sede y admin_general

Las tablas para contratos programas sesiones etc. quedan creadas desde ya para que las siguientes entregas solo agreguen CRUDs encima del esquema ya estable.

## Pasos en XAMPP

### Paso 1 &mdash; Backup de seguridad

```bash
cd /Applications/XAMPP/xamppfiles/htdocs
cp -r robotschool_academy robotschool_academy_backup_$(date +%Y%m%d_%H%M)
```

Backup de BD en phpMyAdmin: selecciona la base robotschool_academy -> Exportar -> Rapido -> Continuar.

### Paso 2 &mdash; Correr el SQL de migracion

En phpMyAdmin selecciona la base y entra a la pestana SQL. Abre el archivo `Schema/migration_v5_extracurriculares.sql` del ZIP (con TextEdit o VS Code), copia todo el contenido y ejecutalo.

**Verificacion:** refresca el panel izquierdo. Deben aparecer 9 tablas nuevas que empiezan con `ec_`.

### Paso 3 &mdash; Copiar y descomprimir los archivos

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/robotschool_academy
cp ~/Downloads/ec_entrega1_cimientos.zip .
unzip -o ec_entrega1_cimientos.zip
rm ec_entrega1_cimientos.zip
```

### Paso 4 &mdash; Verificar estructura

```bash
ls modulos/extracurriculares/
```

Debe mostrar:
- `index.php` (panel del modulo)
- `clientes/` (carpeta con CRUD)

```bash
ls modulos/extracurriculares/clientes/
```

Debe mostrar `index.php`, `form.php`, `eliminar.php`.

### Paso 5 &mdash; Probar en el navegador

Como admin_general o coordinador_pedagogico:

1. Mira el sidebar: debe aparecer nueva seccion **Extracurriculares** con 2 links
2. Click en **Panel extracurriculares** -> debe mostrar 4 KPIs en cero y el plan de 7 entregas
3. Click en **Clientes** -> listado vacio con boton "Crear primer cliente"
4. Click en "Nuevo cliente" -> formulario con mapa interactivo
5. Llena el formulario basico (tipo colegio + nombre)
6. Haz clic en el mapa sobre la ubicacion del cliente -> debe aparecer un pin y actualizarse las coordenadas abajo
7. Guarda el cliente
8. Regresas al listado -> tu cliente debe aparecer como tarjeta con icono segun tipo
9. Haz clic en la tarjeta para editar -> debe cargar con el mapa mostrando el pin donde lo dejaste

### Paso 6 &mdash; Verificar el mapa Leaflet

El mapa usa OpenStreetMap gratis sin API key. Si por alguna razon no carga:

- Verifica que XAMPP tiene internet activo (el mapa descarga tiles de openstreetmap.org)
- Abre la consola del navegador (F12) y revisa si hay errores de red
- Confirma que `https://unpkg.com/leaflet@1.9.4/dist/leaflet.js` carga sin bloqueos

## Archivos nuevos

- `Schema/migration_v5_extracurriculares.sql`
- `modulos/extracurriculares/index.php`
- `modulos/extracurriculares/clientes/index.php`
- `modulos/extracurriculares/clientes/form.php`
- `modulos/extracurriculares/clientes/eliminar.php`

## Archivos modificados

- `includes/sidebar.php` (nueva seccion Extracurriculares)

## Rollback

```bash
cd /Applications/XAMPP/xamppfiles/htdocs
rm -rf robotschool_academy
mv robotschool_academy_backup_YYYYMMDD_HHMM robotschool_academy
```

En phpMyAdmin para quitar las tablas nuevas:

```sql
DROP TABLE IF EXISTS ec_evaluacion_detalle;
DROP TABLE IF EXISTS ec_evaluaciones;
DROP TABLE IF EXISTS ec_asistencia;
DROP TABLE IF EXISTS ec_estudiantes;
DROP TABLE IF EXISTS ec_asignaciones;
DROP TABLE IF EXISTS ec_sesiones;
DROP TABLE IF EXISTS ec_programas;
DROP TABLE IF EXISTS ec_contratos;
DROP TABLE IF EXISTS ec_clientes;
DROP TABLE IF EXISTS ec_desplazamientos_cache;
```

## Proxima entrega

**Entrega 2: Contratos y programas.** Tendras el CRUD de contratos ligados a clientes y dentro de cada contrato los programas con curso RSAL asociado, horarios, equipos y valor. Esto prepara el terreno para el calendario de Entrega 3.
