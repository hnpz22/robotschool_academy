# RSAL &mdash; Entrega 2: Informe Academico del Estudiante

## Que trae esta entrega

Un boletin academico completo por estudiante y periodo:

- **Listado de matriculas por periodo** (coordinador): filtros por sede, curso, busqueda
- **Informe HTML imprimible** con 6 secciones: datos estudiante/curso, asistencia detallada, evaluaciones por rubrica, observaciones del docente, temas desarrollados, escala de valoracion
- **Descarga como PDF**: boton "Imprimir / PDF" usa la funcion nativa del navegador
- **Acceso multi-rol**: coordinador, admin_sede, admin_general, docentes del grupo, y padres del estudiante
- **Boton nuevo en el portal del padre**: link "Informe academico" en cada matricula
- **Boton actualizado en portal docente**: apunta al informe completo

## NO hay SQL nuevo que correr

Esta entrega es 100% codigo PHP. Usa tablas que ya existen (matriculas, asistencia, sesiones, evaluaciones, evaluacion_detalle, rubricas, rubrica_criterios, temas).

## Pasos para XAMPP

### Paso 1 &mdash; Backup de seguridad

```bash
cd /Applications/XAMPP/xamppfiles/htdocs
cp -r robotschool_academy robotschool_academy_backup_$(date +%Y%m%d_%H%M)
```

### Paso 2 &mdash; Copiar y descomprimir

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/robotschool_academy
cp ~/Downloads/entrega2_informes.zip .
unzip -o entrega2_informes.zip
rm entrega2_informes.zip
```

### Paso 3 &mdash; Verificar estructura

```bash
ls modulos/academico/informes/
```

Debe mostrar:
- `index.php` (listado)
- `ver.php` (informe imprimible)

### Paso 4 &mdash; Probar con diferentes roles

**Como coordinador_pedagogico:**

1. Sidebar -> Seguimiento -> **Informes academicos**
2. Se abre el listado con el periodo mas reciente preseleccionado
3. Cambia el periodo (2026-1, 2026-2) y verifica que filtra
4. Filtra por sede y por curso
5. Busca por nombre de un estudiante
6. Haz clic en "Ver informe" de cualquier estudiante
7. El informe se abre en nueva pestana con el diseno RSAL
8. Haz clic en "Imprimir / PDF" -> debe abrir el dialogo del navegador con el PDF

**Como padre:**

1. Cierra sesion
2. Entra con un usuario padre
3. Tab "Mis hijos" -> cada tarjeta de matricula ahora muestra 2 botones:
   - **Informe academico** (naranja, destacado)
   - **Hoja de vida** (outline naranja)
4. Clic en "Informe academico" -> debe abrirse el informe completo

**Como docente:**

1. Entra con un usuario docente con grupos asignados
2. Tab "Estudiantes" -> cada estudiante tiene boton "Informe" (nuevo diseno)
3. Clic -> abre el informe academico completo (antes abria la hoja de vida del docente)

### Paso 5 &mdash; Validar el contenido del informe

Escoge un estudiante con datos: que tenga asistencias registradas y al menos una evaluacion.

El informe debe mostrar en orden:

1. **Encabezado negro** con logo RSAL y "INFORME ACADEMICO"
2. **Franja azul** con avatar, nombre, curso, sede y periodo
3. **Seccion 1**: datos del estudiante (edad, genero, colegio, acudiente, horario, tallerista, modalidad)
4. **Seccion 2**: 4 cajas de asistencia (presente/tarde/ausente/excusa) + barra de porcentaje + tabla con ultimas 15 sesiones
5. **Seccion 3**: promedios por criterio de rubrica con barras de color + caja verde con promedio general y nivel (Superior/Alto/Basico/Bajo)
6. **Seccion 4** (si aplica): observaciones amarillas del docente por evaluacion
7. **Seccion 5** (si aplica): temas del curso con cuenta de actividades
8. **Seccion 6**: escala de valoracion explicativa
9. **3 firmas**: tallerista, coordinacion, acudiente
10. **Pie de pagina** con datos institucionales

## Archivos que se sobreescriben

- `includes/sidebar.php` (nuevo link "Informes academicos" para coordinador)
- `portal/index.php` (nuevo boton "Informe academico" en tarjetas de matricula)
- `docente/index.php` (link de "Informe" apunta ahora al informe academico completo)

## Archivos nuevos

- `modulos/academico/informes/index.php`
- `modulos/academico/informes/ver.php`

## Rollback

```bash
cd /Applications/XAMPP/xamppfiles/htdocs
rm -rf robotschool_academy
mv robotschool_academy_backup_YYYYMMDD_HHMM robotschool_academy
```

No hay cambios en base de datos que revertir.

## Proximo paso

Cuando confirmes que Entrega 2 funciona:

**Entrega 3 &mdash; Generador de certificaciones.** Para esta necesito que me compartas:

- Una referencia visual de como quieren que se vean los certificados de RSAL (si ya tienes uno anterior, una foto o PDF sirve perfecto)
- Que texto institucional llevan (ej: "ROBOTSchool certifica que el estudiante X curs&oacute; y aprob&oacute; el nivel Y...")
- Quien firma (coordinador, director general, nombres espec&iacute;ficos)
- Si llevan sello digital, logo adicional, algun elemento decorativo especial
- Criterios automaticos para certificar (ej: solo si asistencia >= 80% y promedio >= 60%)
