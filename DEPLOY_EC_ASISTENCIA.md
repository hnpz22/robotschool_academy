# Extracurriculares &mdash; Entrega Asistencia

## Que trae esta entrega

Todo lo necesario para tomar asistencia en extracurriculares:

- **Generador automatico de las 4 sesiones** por programa a partir de fecha inicio del contrato
- **Vista detalle del programa** con las 4 sesiones y sus estados + listado de estudiantes con porcentaje de asistencia
- **CRUD de estudiantes** individual + carga masiva pegando lista
- **Los estudiantes pueden ingresarse en cualquier momento** (campo fecha_ingreso &mdash; no cuentan en sesiones anteriores a su ingreso)
- **Toma de asistencia visual** con botones P/T/A/E (presente tarde ausente excusa) + observacion opcional
- **Panel "Mis sesiones EC"** para el tallerista desde su portal (para coordinador/admin es vista completa)
- **Coordinador y tallerista pueden tomar asistencia** (si uno falta el otro cubre)

## SQL minimo

Nueva columna en `ec_estudiantes`: `fecha_ingreso`.

### En phpMyAdmin corre este SQL:

```sql
ALTER TABLE `ec_estudiantes`
  ADD COLUMN IF NOT EXISTS `fecha_ingreso` DATE DEFAULT NULL
    COMMENT 'Desde cuando este nino participa en el programa'
    AFTER `edad`;

UPDATE `ec_estudiantes`
   SET `fecha_ingreso` = DATE(`created_at`)
 WHERE `fecha_ingreso` IS NULL;
```

Esto es un ALTER no destructivo. Si ya tienes estudiantes cargados se les asigna la fecha de creacion como fecha_ingreso.

## Pasos en XAMPP

### Paso 1 &mdash; Backup

```bash
cd /Applications/XAMPP/xamppfiles/htdocs
cp -r robotschool_academy robotschool_academy_backup_$(date +%Y%m%d_%H%M)
```

### Paso 2 &mdash; Correr el ALTER en phpMyAdmin

Usa el SQL de arriba o abre el archivo `Schema/migration_v6_ec_asistencia.sql` del ZIP.

### Paso 3 &mdash; Copiar archivos

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/robotschool_academy
cp ~/Downloads/ec_asistencia.zip .
unzip -o ec_asistencia.zip
rm ec_asistencia.zip
```

### Paso 4 &mdash; Flujo de prueba completo

Como **coordinador**:

1. Extracurriculares -> Contratos -> entra al contrato que ya creaste
2. En la tarjeta del programa clic en "Ver" (ahora aparece ese boton)
3. En la vista del programa veras 3 bloques: header con resumen, bloque Sesiones (vacio con boton "Generar 4 sesiones automaticamente"), bloque Estudiantes
4. Clic en "Generar 4 sesiones automaticamente" -> confirma -> aparecen 4 sesiones con sus fechas calculadas desde el inicio del contrato los dias de la semana del programa
5. En el bloque Estudiantes clic en "Carga masiva"
6. Pega una lista tipo:
   ```
   Juan Perez, 3o, 9
   Maria Lopez, 3o
   Carlos Ruiz, 4o, 10
   Laura Castro
   ```
7. Clic en "Previsualizar lista" -> ve la tabla -> "Confirmar e insertar"
8. Vuelves a la vista del programa con 4 estudiantes inscritos
9. En el bloque Sesiones clic en "Asistencia" de la sesion #1
10. Se abre la pantalla con los 4 estudiantes en una tabla
11. Prueba los botones de arriba: "Marcar todos como Presentes" -> todos quedan verdes
12. Cambia uno a Ausente (A rojo)
13. Escribe una observacion en otro
14. "Guardar asistencia" -> vuelve a la vista del programa con mensaje verde

### Paso 5 &mdash; Probar agregar estudiante en medio del programa

1. Toma asistencia de la sesion #2 para los 4 estudiantes
2. Vuelve a la vista del programa -> clic en "Agregar uno"
3. Crea "Pedro Gomez" con fecha de ingreso = fecha de la sesion #3
4. Ve a tomar asistencia de la sesion #1 -> **no aparece Pedro** (porque ingreso despues)
5. Ve a tomar asistencia de la sesion #3 -> **Pedro aparece**
6. Esto evita que Pedro tenga "ausencias" en sesiones donde no estaba inscrito

### Paso 6 &mdash; Probar como tallerista

1. Cierra sesion
2. Entra con un usuario con rol `docente`
3. Sidebar -> Mi Portal -> **Mis sesiones EC** (link nuevo)
4. Por ahora estara vacio hasta que implementemos asignaciones en Entrega 3
5. Mientras tanto el coordinador puede tomar asistencia desde la vista del programa

**Nota sobre asignaciones:** las sesiones tienen una tabla `ec_asignaciones` pero el CRUD de asignar tallerista a sesion llega en la siguiente entrega. Por ahora cualquier docente que entre al link tomar.php con la URL directa puede registrar asistencia.

## Archivos nuevos

- `Schema/migration_v6_ec_asistencia.sql`
- `modulos/extracurriculares/sesiones/generar.php`
- `modulos/extracurriculares/programas/ver.php`
- `modulos/extracurriculares/estudiantes/form.php`
- `modulos/extracurriculares/estudiantes/masivo.php`
- `modulos/extracurriculares/asistencia/tomar.php`
- `modulos/extracurriculares/asistencia/mis_sesiones.php`

## Archivos modificados

- `modulos/extracurriculares/contratos/ver.php` (boton Ver programa)
- `modulos/extracurriculares/programas/index.php` (boton Ver)
- `includes/sidebar.php` (link Mis sesiones EC para docente)

## Funcionalidades listas

1. Generar sesiones automaticamente (boton)
2. Crear estudiantes uno por uno
3. Crear estudiantes en masa pegando lista
4. Agregar estudiantes en medio del programa sin afectar asistencia previa
5. Tomar asistencia con interfaz visual
6. Marcar todos presentes/ausentes con un click
7. Ver porcentaje de asistencia por estudiante
8. Portal tallerista para ver sus sesiones (panel)
9. Coordinador y tallerista pueden registrar asistencia (redundancia)

## Proxima entrega logica

**Asignacion de talleristas a sesiones.** Es lo que falta para completar el ciclo: un formulario donde el coordinador puede decir "esta sesion la dicta el tallerista X, apoyo Y". Asi el tallerista ve en "Mis sesiones EC" unicamente lo que le corresponde.
