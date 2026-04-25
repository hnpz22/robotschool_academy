# Extracurriculares &mdash; PAQUETE COMPLETO (todo el modulo)

## Este paquete reemplaza cualquier version anterior

Contiene TODO el modulo extracurriculares hasta la fecha en un solo ZIP para evitar confusiones con entregas parciales:

- Tablas base (9 tablas ec_)
- CRUD de clientes con mapa Leaflet
- CRUD de contratos con modelo de cobro por nino
- CRUD de programas con valor por nino y minimo viable
- Vista detalle del programa
- Generador automatico de 4 sesiones
- CRUD de estudiantes individual + carga masiva
- Toma de asistencia con interfaz visual
- Panel tallerista "Mis sesiones EC"

## Instrucciones de instalacion

### Paso 1 &mdash; Backup

```bash
cd /Applications/XAMPP/xamppfiles/htdocs
cp -r robotschool_academy robotschool_academy_backup_$(date +%Y%m%d_%H%M)
```

### Paso 2 &mdash; SQL

En phpMyAdmin ejecuta los DOS archivos SQL que vienen en el ZIP **EN ORDEN**:

**Primero** `Schema/migration_v5_extracurriculares.sql` &mdash; crea las 9 tablas (usa `CREATE TABLE IF NOT EXISTS` asi que no sobreescribe nada existente).

**Despues** `Schema/migration_v6_ec_asistencia.sql` &mdash; agrega la columna `fecha_ingreso` a `ec_estudiantes`.

Si tus tablas ec_ ya existen pero con estructura VIEJA (valor_sesion en lugar de valor_por_nino), antes ejecuta los DROP:

```sql
SET FOREIGN_KEY_CHECKS = 0;
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
SET FOREIGN_KEY_CHECKS = 1;
```

Y despues corre los dos SQL en orden.

### Paso 3 &mdash; Copiar archivos

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/robotschool_academy
cp ~/Downloads/ec_modulo_completo.zip .
unzip -o ec_modulo_completo.zip
rm ec_modulo_completo.zip
```

### Paso 4 &mdash; Verificar estructura

```bash
ls modulos/extracurriculares/
ls modulos/extracurriculares/contratos/
ls modulos/extracurriculares/programas/
ls modulos/extracurriculares/estudiantes/
ls modulos/extracurriculares/asistencia/
ls modulos/extracurriculares/sesiones/
```

Deben existir:

```
modulos/extracurriculares/
├── index.php
├── clientes/
│   ├── index.php
│   ├── form.php
│   └── eliminar.php
├── contratos/
│   ├── index.php    <-- este es el que faltaba
│   ├── form.php
│   ├── ver.php
│   └── eliminar.php
├── programas/
│   ├── index.php
│   ├── form.php
│   ├── ver.php
│   └── eliminar.php
├── estudiantes/
│   ├── form.php
│   └── masivo.php
├── asistencia/
│   ├── tomar.php
│   └── mis_sesiones.php
└── sesiones/
    └── generar.php
```

## Flujo completo de prueba

1. Sidebar -> Extracurriculares -> **Clientes** -> crear Gimnasio Moderno con ubicacion en mapa
2. Sidebar -> Extracurriculares -> **Contratos** -> crear "Robotica 2026-1" vigente
3. En detalle del contrato -> **Agregar programa**: 20 ninos, 120.000 COP por nino, martes 14:00-15:30
4. En la tarjeta del programa -> **Ver** -> entra al detalle del programa
5. Clic en **Generar 4 sesiones automaticamente**
6. Clic en **Carga masiva** -> pega lista:
   ```
   Juan Perez, 3o, 9
   Maria Lopez, 3o
   Carlos Ruiz, 4o, 10
   ```
7. Clic en **Previsualizar** -> **Confirmar**
8. Vuelve al detalle del programa -> en la sesion #1 clic en **Asistencia**
9. Marca todos como Presentes (boton verde arriba) -> Guardar

## Lo que esta pendiente en el modulo

- Asignacion de talleristas a sesiones (para que "Mis sesiones EC" filtre correctamente)
- Calendario visual con colores por programa
- Fallas y recuperacion de sesiones (tabla ya tiene campos)
- Evaluaciones con rubricas reutilizando RSAL
- Certificados al terminar
- Prefactura conectada a Finanzas

## Rollback

```bash
cd /Applications/XAMPP/xamppfiles/htdocs
rm -rf robotschool_academy
mv robotschool_academy_backup_YYYYMMDD_HHMM robotschool_academy
```
