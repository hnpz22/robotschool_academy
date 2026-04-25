<?php
// modulos/academico/actividades/form.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$menu_activo = 'actividades';
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;

$id        = (int)($_GET['id']   ?? 0);
$actividad = null;

if ($id) {
    $s = $pdo->prepare("SELECT a.*, t.curso_id FROM actividades a JOIN temas t ON t.id = a.tema_id WHERE a.id = ?");
    $s->execute([$id]);
    $actividad = $s->fetch();
    if (!$actividad) { header('Location:'.$U.'modulos/academico/actividades/index.php'); exit; }
}

// Tema preseleccionado (desde querystring o desde la actividad actual)
$tema_preselect = (int)($_GET['tema'] ?? ($actividad['tema_id'] ?? 0));

// Obtener curso asociado al tema preseleccionado (para filtrar rubricas)
$curso_del_tema = $actividad['curso_id'] ?? 0;
if (!$curso_del_tema && $tema_preselect) {
    $s = $pdo->prepare("SELECT curso_id FROM temas WHERE id = ?");
    $s->execute([$tema_preselect]);
    $curso_del_tema = (int)$s->fetchColumn();
}

// Todos los temas disponibles (agrupados por curso para el selector)
$sqlT = "SELECT t.id, t.nombre, t.curso_id, c.nombre AS curso_nombre
         FROM temas t
         JOIN cursos c ON c.id = t.curso_id
         WHERE t.activo = 1";
$paramsT = [];
if ($sede_filtro) {
    $sqlT .= " AND EXISTS (SELECT 1 FROM grupos g WHERE g.curso_id = t.curso_id AND g.sede_id = ?)";
    $paramsT[] = $sede_filtro;
}
$sqlT .= " ORDER BY c.nombre, t.orden, t.nombre";
$stT = $pdo->prepare($sqlT);
$stT->execute($paramsT);
$temas = $stT->fetchAll();

// Rubricas del curso (solo si hay curso derivado)
$rubricas = [];
if ($curso_del_tema) {
    $sR = $pdo->prepare("SELECT id, nombre FROM rubricas WHERE curso_id = ? AND activa = 1 ORDER BY nombre");
    $sR->execute([$curso_del_tema]);
    $rubricas = $sR->fetchAll();
}

$titulo  = $actividad ? 'Editar actividad' : 'Nueva actividad';
$errores = [];

$tipos = [
    'armado'        => 'Armado',
    'programacion'  => 'Programaci&oacute;n',
    'investigacion' => 'Investigaci&oacute;n',
    'reto'          => 'Reto',
    'proyecto'      => 'Proyecto',
    'exposicion'    => 'Exposici&oacute;n',
    'taller'        => 'Taller',
    'otro'          => 'Otro',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tema_id     = (int)($_POST['tema_id']     ?? 0);
    $rubrica_id  = (int)($_POST['rubrica_id']  ?? 0) ?: null;
    $nombre      = trim($_POST['nombre']        ?? '');
    $descripcion = trim($_POST['descripcion']   ?? '');
    $tipo        = $_POST['tipo']                ?? 'taller';
    $duracion    = (int)($_POST['duracion_min'] ?? 0) ?: null;
    $materiales  = trim($_POST['materiales']    ?? '');
    $orden       = (int)($_POST['orden']        ?? 0);
    $activa      = isset($_POST['activa']) ? 1 : 0;

    if (!array_key_exists($tipo, $tipos)) $tipo = 'taller';

    if (!$tema_id) $errores[] = 'Selecciona el tema.';
    if (!$nombre)  $errores[] = 'El nombre de la actividad es obligatorio.';

    if (empty($errores)) {
        if ($id) {
            $pdo->prepare("UPDATE actividades SET tema_id=?, rubrica_id=?, nombre=?, descripcion=?, tipo=?, duracion_min=?, materiales=?, orden=?, activa=? WHERE id=?")
                ->execute([$tema_id, $rubrica_id, $nombre, $descripcion, $tipo, $duracion, $materiales, $orden, $activa, $id]);
        } else {
            $pdo->prepare("INSERT INTO actividades (tema_id, rubrica_id, nombre, descripcion, tipo, duracion_min, materiales, orden, activa) VALUES (?,?,?,?,?,?,?,?,?)")
                ->execute([$tema_id, $rubrica_id, $nombre, $descripcion, $tipo, $duracion, $materiales, $orden, $activa]);
        }
        header('Location:'.$U.'modulos/academico/actividades/index.php?msg='.($actividad?'editada':'creada').'&tema='.$tema_id);
        exit;
    }
}

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';

// Agrupar temas por curso para el select
$temas_por_curso = [];
foreach ($temas as $t) { $temas_por_curso[$t['curso_nombre']][] = $t; }
?>
<header class="main-header">
  <div class="header-title">
    <?= $titulo ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/academico/actividades/index.php">Actividades</a>
      <i class="bi bi-chevron-right"></i>
      <?= $actividad ? h($actividad['nombre']) : 'Nueva' ?>
    </span></small>
  </div>
</header>
<main class="main-content">
  <?php if (!empty($errores)): ?>
    <div class="alert-rsal alert-danger" style="flex-direction:column;align-items:flex-start;">
      <strong><i class="bi bi-exclamation-circle-fill"></i> Errores:</strong>
      <ul style="margin:.4rem 0 0 1.2rem;"><?php foreach($errores as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="POST">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:1.4rem;align-items:start;">

      <!-- IZQUIERDA: datos principales -->
      <div>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-puzzle-fill"></i> Datos de la actividad</div>

          <label class="field-label">Tema <span class="req">*</span></label>
          <select name="tema_id" id="selTema" class="rsal-select" required onchange="cambioTema()">
            <option value="">Selecciona el tema...</option>
            <?php foreach ($temas_por_curso as $nombre_curso => $lista): ?>
              <optgroup label="<?= h($nombre_curso) ?>">
                <?php foreach ($lista as $t):
                  $sel = $tema_preselect == $t['id'] ? 'selected' : '';
                ?>
                  <option value="<?= $t['id'] ?>" data-curso="<?= $t['curso_id'] ?>" <?= $sel ?>><?= h($t['nombre']) ?></option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>

          <label class="field-label">Nombre de la actividad <span class="req">*</span></label>
          <input type="text" name="nombre" class="rsal-input" required maxlength="200"
                 placeholder="Ej: Arma tu primer robot seguidor de l&iacute;nea"
                 value="<?= h($actividad['nombre'] ?? '') ?>"/>

          <label class="field-label">Descripci&oacute;n</label>
          <textarea name="descripcion" class="rsal-textarea" style="min-height:100px;"
                    placeholder="Explicaci&oacute;n detallada de qu&eacute; har&aacute; el estudiante..."><?= h($actividad['descripcion'] ?? '') ?></textarea>

          <label class="field-label">Materiales / kit</label>
          <textarea name="materiales" class="rsal-textarea" style="min-height:60px;"
                    placeholder="Ej: Kit LEGO SPIKE Prime, cable USB, computador"><?= h($actividad['materiales'] ?? '') ?></textarea>
        </div>

        <!-- Configuracion tecnica -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-sliders"></i> Tipo y duraci&oacute;n</div>

          <div style="display:grid;grid-template-columns:1fr 150px;gap:.8rem;">
            <div>
              <label class="field-label">Tipo de actividad</label>
              <select name="tipo" class="rsal-select">
                <?php foreach ($tipos as $k => $lbl): ?>
                  <option value="<?= $k ?>" <?= ($actividad['tipo']??'taller') == $k ? 'selected' : '' ?>>
                    <?= $lbl ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="field-label">Duraci&oacute;n (min)</label>
              <input type="number" name="duracion_min" class="rsal-input" min="0" max="999"
                     placeholder="Ej: 45"
                     value="<?= $actividad['duracion_min'] ?? '' ?>"/>
            </div>
          </div>
        </div>
      </div>

      <!-- DERECHA: rubrica, orden, estado, guardar -->
      <div>
        <!-- Rubrica asociada -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-list-check"></i> R&uacute;brica de evaluaci&oacute;n</div>
          <p style="font-size:.78rem;color:var(--muted);margin-bottom:.7rem;line-height:1.5;">
            Asocia una r&uacute;brica para que los docentes puedan evaluar esta actividad. Las r&uacute;bricas disponibles dependen del curso del tema seleccionado.
          </p>
          <select name="rubrica_id" id="selRubrica" class="rsal-select">
            <option value="0">Sin r&uacute;brica (opcional)</option>
            <?php foreach ($rubricas as $r): ?>
              <option value="<?= $r['id'] ?>" <?= ($actividad['rubrica_id']??0) == $r['id'] ? 'selected' : '' ?>>
                <?= h($r['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if ($curso_del_tema && empty($rubricas)): ?>
            <div style="margin-top:.6rem;padding:.5rem .7rem;background:#FEF3C7;border-radius:8px;font-size:.75rem;color:#92400E;">
              <i class="bi bi-exclamation-triangle-fill"></i>
              Este curso a&uacute;n no tiene r&uacute;bricas activas.
              <a href="<?= $U ?>modulos/academico/rubricas/form.php" style="color:#92400E;text-decoration:underline;font-weight:600;">Crear una</a>.
            </div>
          <?php endif; ?>
        </div>

        <!-- Orden y estado -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-arrow-down-up"></i> Organizaci&oacute;n</div>

          <label class="field-label">Orden</label>
          <input type="number" name="orden" class="rsal-input" min="0" max="99"
                 value="<?= (int)($actividad['orden'] ?? 0) ?>"/>
          <p style="font-size:.72rem;color:var(--muted);margin-top:-.3rem;">Secuencia dentro del tema (0 = primera).</p>

          <div style="display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;background:var(--gray);border-radius:10px;margin-top:.8rem;">
            <div>
              <div style="font-size:.85rem;font-weight:700;color:var(--dark);">Actividad activa</div>
              <div style="font-size:.72rem;color:var(--muted);">Visible para docentes</div>
            </div>
            <label style="position:relative;width:44px;height:24px;cursor:pointer;">
              <input type="checkbox" name="activa" style="opacity:0;width:0;height:0;position:absolute;"
                     <?= ($actividad['activa']??1)?'checked':'' ?>
                     onchange="this.nextElementSibling.style.background=this.checked?'var(--teal)':'var(--gray2)';this.nextElementSibling.children[0].style.transform=this.checked?'translateX(20px)':'';">
              <span style="position:absolute;inset:0;background:<?= ($actividad['activa']??1)?'var(--teal)':'var(--gray2)' ?>;border-radius:12px;transition:.3s;">
                <span style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2);transform:<?= ($actividad['activa']??1)?'translateX(20px)':'' ?>;"></span>
              </span>
            </label>
          </div>
        </div>

        <!-- Guardar -->
        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.82rem;font-size:.95rem;">
            <i class="bi bi-check-lg"></i> <?= $actividad?'Guardar cambios':'Crear actividad' ?>
          </button>
          <a href="<?= $U ?>modulos/academico/actividades/index.php" class="btn-rsal-secondary"
             style="width:100%;justify-content:center;padding:.68rem;margin-top:.6rem;">Cancelar</a>
        </div>
      </div>

    </div>
  </form>
</main>
<script>
// Al cambiar el tema, recargar la pagina con el nuevo tema
// para traer las rubricas del curso correspondiente
function cambioTema() {
  const sel = document.getElementById('selTema');
  const temaId = sel.value;
  if (!temaId) return;
  const id = <?= $id ?: 0 ?>;
  // Si estamos editando, conservar id; si no, usar ?tema= para preseleccion
  const url = new URL(window.location.href);
  url.searchParams.set('tema', temaId);
  if (id) url.searchParams.set('id', id);
  window.location.href = url.toString();
}
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
