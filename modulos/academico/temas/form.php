<?php
// modulos/academico/temas/form.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$menu_activo = 'temas';
$sede_filtro = getSedeFiltro();
$U           = BASE_URL;

// Cursos disponibles
$sqlC = "SELECT id, nombre FROM cursos";
$paramsC = [];
if ($sede_filtro) {
    $sqlC .= " WHERE EXISTS (SELECT 1 FROM grupos g WHERE g.curso_id = cursos.id AND g.sede_id = ?)";
    $paramsC[] = $sede_filtro;
}
$sqlC .= " ORDER BY orden, nombre";
$stC = $pdo->prepare($sqlC);
$stC->execute($paramsC);
$cursos = $stC->fetchAll();

$id   = (int)($_GET['id'] ?? 0);
$tema = null;

if ($id) {
    $s = $pdo->prepare("SELECT * FROM temas WHERE id = ?");
    $s->execute([$id]);
    $tema = $s->fetch();
    if (!$tema) { header('Location:'.$U.'modulos/academico/temas/index.php'); exit; }
}

$titulo  = $tema ? 'Editar tema' : 'Nuevo tema';
$errores = [];

// Curso preseleccionado desde query string (cuando vienes con ?curso=N)
$curso_preselect = (int)($_GET['curso'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curso_id    = (int)($_POST['curso_id']    ?? 0);
    $nombre      = trim($_POST['nombre']        ?? '');
    $descripcion = trim($_POST['descripcion']   ?? '');
    $objetivos   = trim($_POST['objetivos']     ?? '');
    $orden       = (int)($_POST['orden']        ?? 0);
    $activo      = isset($_POST['activo']) ? 1 : 0;

    if (!$curso_id) $errores[] = 'Selecciona el curso.';
    if (!$nombre)   $errores[] = 'El nombre del tema es obligatorio.';

    if (empty($errores)) {
        if ($id) {
            $pdo->prepare("UPDATE temas SET curso_id=?, nombre=?, descripcion=?, objetivos=?, orden=?, activo=? WHERE id=?")
                ->execute([$curso_id, $nombre, $descripcion, $objetivos, $orden, $activo, $id]);
        } else {
            $pdo->prepare("INSERT INTO temas (curso_id, nombre, descripcion, objetivos, orden, activo) VALUES (?,?,?,?,?,?)")
                ->execute([$curso_id, $nombre, $descripcion, $objetivos, $orden, $activo]);
        }
        header('Location:'.$U.'modulos/academico/temas/index.php?msg='.($tema?'editado':'creado').'&curso='.$curso_id);
        exit;
    }
}

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    <?= $titulo ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/academico/temas/index.php">Temas</a>
      <i class="bi bi-chevron-right"></i>
      <?= $tema ? h($tema['nombre']) : 'Nuevo' ?>
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
    <div style="display:grid;grid-template-columns:1fr 300px;gap:1.4rem;align-items:start;">

      <!-- IZQUIERDA: Datos del tema -->
      <div>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-bookmark-fill"></i> Datos del tema</div>

          <label class="field-label">Curso <span class="req">*</span></label>
          <select name="curso_id" class="rsal-select" required>
            <option value="">Selecciona el curso...</option>
            <?php foreach($cursos as $c):
              $selected = ($tema['curso_id'] ?? $curso_preselect) == $c['id'] ? 'selected' : '';
            ?>
              <option value="<?= $c['id'] ?>" <?= $selected ?>><?= h($c['nombre']) ?></option>
            <?php endforeach; ?>
          </select>

          <label class="field-label">Nombre del tema <span class="req">*</span></label>
          <input type="text" name="nombre" class="rsal-input" required maxlength="200"
                 placeholder="Ej: Introducci&oacute;n a sensores y actuadores"
                 value="<?= h($tema['nombre'] ?? '') ?>"/>

          <label class="field-label">Descripci&oacute;n</label>
          <textarea name="descripcion" class="rsal-textarea" style="min-height:80px;"
                    placeholder="Resumen del tema y qu&eacute; abarca..."><?= h($tema['descripcion'] ?? '') ?></textarea>

          <label class="field-label">Objetivos pedag&oacute;gicos</label>
          <textarea name="objetivos" class="rsal-textarea" style="min-height:100px;"
                    placeholder="Al finalizar este tema el estudiante ser&aacute; capaz de..."><?= h($tema['objetivos'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- DERECHA -->
      <div>
        <!-- Orden y estado -->
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-sliders"></i> Organizaci&oacute;n</div>

          <label class="field-label">Orden</label>
          <input type="number" name="orden" class="rsal-input" min="0" max="99"
                 value="<?= (int)($tema['orden'] ?? 0) ?>"/>
          <p style="font-size:.72rem;color:var(--muted);margin-top:-.3rem;">N&uacute;mero que define la secuencia del tema dentro del curso (0 = primero).</p>

          <div style="display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;background:var(--gray);border-radius:10px;margin-top:.8rem;">
            <div>
              <div style="font-size:.85rem;font-weight:700;color:var(--dark);">Tema activo</div>
              <div style="font-size:.72rem;color:var(--muted);">Visible para docentes</div>
            </div>
            <label style="position:relative;width:44px;height:24px;cursor:pointer;">
              <input type="checkbox" name="activo" style="opacity:0;width:0;height:0;position:absolute;"
                     <?= ($tema['activo']??1)?'checked':'' ?>
                     onchange="this.nextElementSibling.style.background=this.checked?'var(--teal)':'var(--gray2)';this.nextElementSibling.children[0].style.transform=this.checked?'translateX(20px)':'';">
              <span style="position:absolute;inset:0;background:<?= ($tema['activo']??1)?'var(--teal)':'var(--gray2)' ?>;border-radius:12px;transition:.3s;">
                <span style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2);transform:<?= ($tema['activo']??1)?'translateX(20px)':'' ?>;"></span>
              </span>
            </label>
          </div>
        </div>

        <!-- Guardar -->
        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.82rem;font-size:.95rem;">
            <i class="bi bi-check-lg"></i> <?= $tema?'Guardar cambios':'Crear tema' ?>
          </button>
          <a href="<?= $U ?>modulos/academico/temas/index.php" class="btn-rsal-secondary"
             style="width:100%;justify-content:center;padding:.68rem;margin-top:.6rem;">Cancelar</a>
        </div>

        <?php if ($tema): ?>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-puzzle-fill"></i> Siguiente paso</div>
          <p style="font-size:.8rem;color:var(--muted);line-height:1.6;margin-bottom:.8rem;">
            Agrega las actividades que los docentes desarrollar&aacute;n dentro de este tema.
          </p>
          <a href="<?= $U ?>modulos/academico/actividades/index.php?tema=<?= $tema['id'] ?>"
             class="btn-rsal-secondary" style="width:100%;justify-content:center;padding:.6rem;">
            <i class="bi bi-arrow-right"></i> Ver actividades
          </a>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </form>
</main>
<script>
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
