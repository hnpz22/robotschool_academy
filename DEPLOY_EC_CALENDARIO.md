# Extracurriculares &mdash; Calendario Visual

## Que trae esta entrega

Vista de calendario para todas las sesiones de extracurriculares:

- **Vista mensual por defecto** con toggle a vista semanal
- **Grilla de lunes a domingo** (estandar Colombia)
- **Navegacion prev / hoy / next**
- **Cada sesion aparece como pastilla coloreada** con el color del programa
- **Deteccion automatica de conflictos de horario**: si un tallerista tiene 2 sesiones que se traslapan el mismo dia las pastillas se pintan en rojo
- **Distancia haversine en km** entre sesiones consecutivas del mismo tallerista (solo vista semanal por ahora)
- **Filtros por cliente por tallerista por estado**
- **Click en pastilla** lleva directo a tomar asistencia
- **Link en sidebar** seccion Extracurriculares

## Sin SQL nuevo

Esta entrega NO requiere correr ningun ALTER ni migracion. Usa tablas que ya existen: `ec_sesiones`, `ec_programas`, `ec_contratos`, `ec_clientes`, `ec_asignaciones`, `usuarios`.

Si no tienes la tabla `ec_asignaciones` con datos (la llenaremos en la proxima entrega), el calendario muestra sesiones pero sin talleristas y sin conflictos &mdash; es el comportamiento esperado.

## Deploy en XAMPP

### Paso 1 &mdash; Backup

```bash
cd /Applications/XAMPP/xamppfiles/htdocs
cp -r robotschool_academy robotschool_academy_backup_$(date +%Y%m%d_%H%M)
```

### Paso 2 &mdash; Copiar archivos

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/robotschool_academy
cp ~/Downloads/ec_calendario.zip .
unzip -o ec_calendario.zip
rm ec_calendario.zip
```

### Paso 3 &mdash; Probar

1. Entra como coordinador
2. Sidebar -> Extracurriculares -> **Calendario** (link nuevo)
3. Veras el mes actual con las sesiones que ya generaste
4. Click en una pastilla -> te lleva a tomar asistencia de esa sesion
5. Toggle **Semana** para ver la vista semanal con horas 7am-7pm en el eje izquierdo
6. Filtros arriba: prueba filtrar por cliente o tallerista
7. Prev / Hoy / Next para navegar en el tiempo

## Lo que veras ahora

Las sesiones aparecen como pastillas con el color del programa. Si aun no has asignado talleristas no veras los rojos de conflicto &mdash; eso empieza a funcionar cuando se pueblen las asignaciones.

## Que sigue

La siguiente entrega es **asignacion de talleristas a sesiones**:

- Desde la vista del programa o desde el calendario clic en sesion -> modal para asignar tallerista principal + apoyo
- Validacion de bloqueo por conflicto horario (si ya esta dictando en otra parte)
- Al guardar el calendario automaticamente muestra los rojos si hay traslapes
- Panel "Mis sesiones EC" del tallerista empezara a filtrar correctamente

## Archivos nuevos

- `includes/ec_helpers.php` (funciones haversine y helpers de fechas)
- `modulos/extracurriculares/calendario/index.php`

## Archivos modificados

- `includes/sidebar.php` (link Calendario en seccion Extracurriculares)
