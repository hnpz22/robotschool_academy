# Extracurriculares &mdash; Entrega 2: Contratos y Programas

## Que trae esta entrega

Segunda entrega del modulo de extracurriculares. Esta entrega es 100% codigo PHP **sin SQL nuevo** &mdash; las tablas ya existen desde la Entrega 1.

- **CRUD de contratos** con listado filtrable (buscar, cliente, estado, vigencia), formulario con calculo automatico de codigo sugerido (EC-2026-001), validacion de fechas y codigo unico
- **Vista detalle de contrato** que muestra datos del contrato + listado de programas + sidebar con info del cliente y resumen en numeros
- **CRUD de programas** anidado dentro del contrato: selector de curso RSAL, equipos/kit, grado desde-hasta, rango de edades, cantidad de ninos, horario, total de sesiones, valor por sesion, color para el calendario
- **Preview en vivo del valor total** del programa al digitar (valor_sesion x total_sesiones)
- **Listado general de programas** con filtros por cliente, dia y estado
- **Sidebar actualizado**: ahora aparecen "Contratos" y "Programas" en la seccion Extracurriculares
- **Panel de extracurriculares actualizado**: los KPIs de Contratos y Programas ahora son clickeables y llevan a sus listados

## Pasos en XAMPP

### Paso 1 &mdash; Backup

```bash
cd /Applications/XAMPP/xamppfiles/htdocs
cp -r robotschool_academy robotschool_academy_backup_$(date +%Y%m%d_%H%M)
```

### Paso 2 &mdash; Copiar y descomprimir

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/robotschool_academy
cp ~/Downloads/ec_entrega2_contratos_programas.zip .
unzip -o ec_entrega2_contratos_programas.zip
rm ec_entrega2_contratos_programas.zip
```

### Paso 3 &mdash; Verificar estructura

```bash
ls modulos/extracurriculares/
```

Debe mostrar: `clientes`, `contratos`, `index.php`, `programas`.

```bash
ls modulos/extracurriculares/contratos/
ls modulos/extracurriculares/programas/
```

En cada uno deben aparecer `index.php`, `form.php`, `eliminar.php`. Contratos ademas tiene `ver.php`.

### Paso 4 &mdash; Probar en el navegador

Como coordinador_pedagogico o admin:

1. Sidebar -> seccion **Extracurriculares** -> ahora hay 4 links: Panel, Clientes, **Contratos**, **Programas**
2. Panel extracurriculares -> los KPIs de Contratos y Programas ahora hacen hover y llevan a sus listados
3. Click en **Contratos** -> listado vacio con boton "Crear primer contrato"
4. **Crear un contrato**:
   - Selecciona un cliente existente (los de Entrega 1)
   - Nombre: "Robotica 2026-1 Gimnasio Moderno"
   - Fechas: 2026-02-01 a 2026-06-15
   - Valor sesion: 250000
   - Estado: Vigente
   - Guardar
5. Al guardar redirige a la **vista detalle** del contrato con mensaje verde "Contrato creado"
6. En la vista detalle -> **Agregar programa**:
   - Nombre: "LEGO SPIKE Prime 3o y 4o"
   - Curso RSAL: selecciona uno existente
   - Equipos: "LEGO SPIKE Prime"
   - Grados: 3o a 5o, edades 8 a 11
   - Cantidad: 20 ninos
   - Dia: Martes, 14:00 a 15:30
   - Total sesiones: 16
   - Valor sesion: dejalo vacio (usa el del contrato)
   - Color: escoge un morado
   - Guardar
7. Al guardar redirige al detalle del contrato con el programa visible como tarjeta con borde de color
8. Crea otro programa en el mismo contrato:
   - Nombre: "Arduino bachillerato"
   - Dia: Viernes, 15:00 a 16:30
   - Total: 16 sesiones
   - Color: naranja
9. La vista detalle ahora muestra 2 programas con sus valores y un **total calculado**
10. Panel extracurriculares -> KPIs actualizados: 1 contrato vigente, 2 programas activos

### Paso 5 &mdash; Probar filtros

1. En listado de contratos filtra por cliente -> debe mostrar solo los de ese cliente
2. En listado de programas filtra por dia "martes" -> debe mostrar solo el programa del martes

### Paso 6 &mdash; Probar validaciones

1. Intenta crear contrato con fecha fin antes que inicio -> error
2. Intenta eliminar contrato con programas asociados -> error "no eliminable"
3. Intenta crear programa con hora fin antes que inicio -> error

## Archivos nuevos

- `modulos/extracurriculares/contratos/index.php`
- `modulos/extracurriculares/contratos/form.php`
- `modulos/extracurriculares/contratos/ver.php`
- `modulos/extracurriculares/contratos/eliminar.php`
- `modulos/extracurriculares/programas/index.php`
- `modulos/extracurriculares/programas/form.php`
- `modulos/extracurriculares/programas/eliminar.php`

## Archivos modificados

- `includes/sidebar.php` (links nuevos)
- `modulos/extracurriculares/index.php` (KPIs clickeables)

## Rollback

```bash
cd /Applications/XAMPP/xamppfiles/htdocs
rm -rf robotschool_academy
mv robotschool_academy_backup_YYYYMMDD_HHMM robotschool_academy
```

No hay cambios en BD que revertir.

## Proxima entrega

**Entrega 3: Calendario visual con detector de conflictos.** Una vez tengas contratos y programas cargados, podremos generar las sesiones automaticamente y verlas en un calendario con colores por programa. Detectaremos conflictos de horario entre talleristas y calcularemos distancias haversine entre clientes para ver si los desplazamientos son factibles.
