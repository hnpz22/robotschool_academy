# Extracurriculares &mdash; Entregas 1 + 2 CONSOLIDADAS v2

## Importante leer primero

Este paquete REEMPLAZA todo lo enviado antes en las entregas 1 y 2 de extracurriculares. Ajusta el modelo comercial al real:

- **El cobro NO es por sesion dictada** &mdash; es **por nino inscrito**
- **Tarifa tipica: 120.000 COP por nino** (paquete completo de 4 sesiones)
- **4 sesiones fijas por programa** &mdash; 1 sesion semanal &mdash; aprox 1 mes
- **Minimo viable por programa** configurable (default 10 ninos) solo alerta no bloquea
- **Tarifa varia por cliente y por programa** (LEGO SPIKE vs Arduino distinto precio)
- **Se cobra cantidad_ninos planeada** aunque al final asistan menos

## Pasos en XAMPP (empezar desde cero)

### Paso 1 &mdash; Si ya desplegaste entregas anteriores borrar tablas

Si corriste antes la migracion v5 debes eliminar las tablas ec_ para empezar limpio. En phpMyAdmin ejecuta:

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

Si NO has corrido nada antes salta al Paso 2.

### Paso 2 &mdash; Backup de seguridad

```bash
cd /Applications/XAMPP/xamppfiles/htdocs
cp -r robotschool_academy robotschool_academy_backup_$(date +%Y%m%d_%H%M)
```

### Paso 3 &mdash; Correr la migracion v5 ajustada

En phpMyAdmin selecciona la base `robotschool_academy` -> pestana SQL -> abre el archivo `Schema/migration_v5_extracurriculares.sql` del ZIP, copia todo el contenido y ejecutalo.

**Verificacion:** deben aparecer 9 tablas nuevas con prefijo `ec_`. En la tabla `ec_programas` las columnas clave son:
- `cantidad_ninos` (int)
- `minimo_ninos` (int, default 10)
- `valor_por_nino` (decimal, default 120000.00)
- `total_sesiones` (int, default 4)

### Paso 4 &mdash; Copiar los archivos

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/robotschool_academy
cp ~/Downloads/ec_entregas1y2_consolidado_v2.zip .
unzip -o ec_entregas1y2_consolidado_v2.zip
rm ec_entregas1y2_consolidado_v2.zip
```

### Paso 5 &mdash; Probar como coordinador_pedagogico

1. Sidebar -> seccion **Extracurriculares** -> 4 links: Panel, Clientes, Contratos, Programas
2. **Crear un cliente** (ej. Gimnasio Moderno) con el mapa Leaflet
3. **Crear un contrato** para ese cliente:
   - Nombre: "Robotica 2026-1"
   - Fechas: 2026-02-01 a 2026-06-15
   - Valor total: deja 0 o escribe un estimado manual
   - Condiciones de pago: "Mensual contra factura"
   - Estado: Vigente
4. En la vista detalle del contrato **agregar programa**:
   - Nombre: "LEGO SPIKE 3o y 4o"
   - Curso RSAL: (opcional) seleccionar uno
   - Equipos: "LEGO SPIKE Prime"
   - Grados 3o a 5o, edades 8-11
   - **Cantidad ninos: 20** / **Minimo: 10**
   - Dia: Martes, 14:00 a 15:30
   - **Valor por nino: 120000**
   - Color morado
5. Observa el **preview en vivo**: muestra "$2.400.000" con formula "20 ninos x $120.000 x 4 sesiones"
6. **Guardar** -> vuelve a la vista detalle con mensaje verde
7. En la tarjeta del programa veras:
   - Horario
   - `20 / min 10` (cantidad / minimo)
   - `$120.000/nino`
   - `$2.400.000` (total)
8. **Agregar segundo programa** con cantidad_ninos: 6 (bajo el minimo)
9. La tarjeta mostrara **badge amarillo "Bajo minimo"** y el texto de ninos en color naranja
10. El total del contrato suma ambos programas

### Paso 6 &mdash; Probar el listado de programas

Sidebar -> Programas -> ves todos los programas. Los que esten bajo el minimo viable muestran un aviso amarillo en la tarjeta con texto "Faltan X ninos para el minimo viable".

## Archivos en el ZIP

### Esquema SQL
- `Schema/migration_v5_extracurriculares.sql` (ajustado con valor_por_nino y minimo_ninos)

### Modulo extracurriculares
- `modulos/extracurriculares/index.php` (landing con KPIs)
- `modulos/extracurriculares/clientes/index.php`
- `modulos/extracurriculares/clientes/form.php`
- `modulos/extracurriculares/clientes/eliminar.php`
- `modulos/extracurriculares/contratos/index.php`
- `modulos/extracurriculares/contratos/form.php`
- `modulos/extracurriculares/contratos/ver.php`
- `modulos/extracurriculares/contratos/eliminar.php`
- `modulos/extracurriculares/programas/index.php`
- `modulos/extracurriculares/programas/form.php`
- `modulos/extracurriculares/programas/eliminar.php`

### Sidebar actualizado
- `includes/sidebar.php`

## Cambios respecto a las versiones anteriores

1. Esquema `ec_contratos`: eliminado campo `valor_sesion`. El `valor_total` es referencia manual; el real se calcula sumando programas.
2. Esquema `ec_programas`: reemplazado `valor_sesion` por `valor_por_nino` (default 120000), agregado `minimo_ninos` (default 10), `total_sesiones` fijo en 4.
3. Formulario de contrato: mensaje explicativo del modelo por nino, solo valor_total de referencia.
4. Formulario de programa: grupo objetivo separa "Cantidad planeada" y "Minimo viable", horario sin total_sesiones (mensaje fijo 4), seccion Tarifa con valor_por_nino y preview que alerta si esta bajo minimo.
5. Vista detalle de contrato: tarjetas muestran `X / min Y` ninos, `$120.000/nino`, total calculado, y badge "Bajo minimo" cuando aplica.
6. Listado de contratos: columna "Programas" muestra total de ninos en lugar de sesiones.
7. Listado de programas: tarjeta muestra `ninos / minimo` y "Valor total" calculado en vivo.

## Rollback

```bash
cd /Applications/XAMPP/xamppfiles/htdocs
rm -rf robotschool_academy
mv robotschool_academy_backup_YYYYMMDD_HHMM robotschool_academy
```

Y en phpMyAdmin corre los DROP TABLE del Paso 1.

## Proxima entrega

**Entrega 3: Calendario visual con detector de conflictos.** Una vez con este modelo comercial correcto, podremos:
- Generar automaticamente las 4 sesiones por programa desde fecha_inicio hasta fecha_fin
- Verlas en calendario mensual con los colores del programa
- Detectar cuando un tallerista esta asignado a dos sesiones simultaneas
- Calcular distancias haversine entre clientes para ver si un tallerista alcanza a llegar de una sede a otra
