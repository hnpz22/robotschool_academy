<?php
// modulos/extracurriculares/estudiantes/masivo.php
// Pegar una lista de estudiantes (uno por linea) para crear en bulk
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$menu_activo = 'ec_programas';
$U           = BASE_URL;

$programa_id = (int)($_GET['programa'] ?? 0);
if (!$programa_id) { header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php'); exit; }

$ph = $pdo->prepare("SELECT p.*, ct.nombre AS contrato_nombre
                     FROM ec_programas p
                     JOIN ec_contratos ct ON ct.id = p.contrato_id
                     WHERE p.id = ?");
$ph->execute([$programa_id]);
$programa = $ph->fetch();
if (!$programa) { header('Location: ' . $U . 'modulos/extracurriculares/programas/index.php'); exit; }

$titulo = 'Carga masiva de estudiantes';
$errores = [];
$preview = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lista        = trim($_POST['lista']        ?? '');
    $grado_def    = trim($_POST['grado_def']    ?? '');
    $fecha_ing    = $_POST['fecha_ingreso']     ?: date('Y-m-d');
    $confirmar    = isset($_POST['confirmar']);

    if (!$lista) $errores[] = 'Debes pegar al menos un nombre.';

    if (empty($errores)) {
        // Parsear lineas: detecta "Nombre Apellido,Grado,Edad" o solo "Nombre Apellido"
        $lineas = preg_split('/\r\n|\r|\n/', $lista);
        $parsed = [];
        foreach ($lineas as $L) {
            $L = trim($L);
            if (!$L) continue;
            $partes = array_map('trim', explode(',', $L));
            $nombre = $partes[0] ?? '';
            $grado  = $partes[1] ?? $grado_def;
            $edad   = isset($partes[2]) && is_numeric($partes[2]) ? (int)$partes[2] : null;
            if ($nombre) {
                $parsed[] = [
                    'nombre' => $nombre,
                    'grado'  => $grado,
                    'edad'   => $edad
                ];
            }
        }

        if (empty($parsed)) {
            $errores[] = 'No se pudo procesar ninguna linea.';
        } elseif (!$confirmar) {
            // Solo preview
            $preview = $parsed;
        } else {
            // Insertar todos
            $ins = $pdo->prepare("INSERT INTO ec_estudiantes
                (programa_id, nombre_completo, grado, edad, fecha_ingreso, activo)
                VALUES (?,?,?,?,?,1)");
            $n = 0;
            foreach ($parsed as $p) {
                $ins->execute([$programa_id, $p['nombre'], $p['grado'] ?: null, $p['edad'], $fecha_ing]);
                $n++;
            }
            header('Location: ' . $U . "modulos/extracurriculares/programas/ver.php?id=$programa_id&msg=est_masivo&n=$n");
            exit;
        }
    }
}

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">
    Carga masiva de estudiantes
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/extracurriculares/programas/ver.php?id=<?= $programa_id ?>"><?= h($programa['nombre']) ?></a>
      <i class="bi bi-chevron-right"></i>
      Pegar lista
    </span></small>
  </div>
</header>
<main class="main-content">

  <?php if (!empty($errores)): ?>
    <div class="alert-rsal alert-danger">
      <strong><i class="bi bi-exclamation-circle-fill"></i></strong> <?= h($errores[0]) ?>
    </div>
  <?php endif; ?>

  <div class="alert-rsal alert-info" style="margin-bottom:1rem;">
    <i class="bi bi-info-circle-fill"></i>
    Pega los nombres de los estudiantes, uno por l&iacute;nea. Puedes incluir grado y edad separados por coma. Formatos soportados:
    <ul style="margin:.5rem 0 0 1.3rem;font-size:.82rem;">
      <li><code>Juan P&eacute;rez</code> (solo nombre)</li>
      <li><code>Juan P&eacute;rez, 3o</code> (nombre y grado)</li>
      <li><code>Juan P&eacute;rez, 3o, 9</code> (nombre, grado y edad)</li>
    </ul>
  </div>

  <form method="POST">
    <div style="display:grid;grid-template-columns:1fr 280px;gap:1.4rem;align-items:start;">

      <div>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-clipboard-plus-fill"></i> Lista de estudiantes</div>

          <textarea name="lista" class="rsal-textarea" rows="14" style="font-family:monospace;font-size:.85rem;"
                    placeholder="Juan P&eacute;rez
Mar&iacute;a L&oacute;pez, 3o
Carlos Ruiz, 3o, 9
Laura Castro, 4o, 10"
                    autofocus><?= h($_POST['lista'] ?? '') ?></textarea>
        </div>

        <?php if ($preview !== null): ?>
          <div class="card-rsal" style="border:2px solid #1DA99A;">
            <div class="card-rsal-title" style="color:#0d6e5f;">
              <i class="bi bi-eye-fill"></i> Vista previa &mdash; <?= count($preview) ?> estudiantes listos
            </div>
            <div style="max-height:280px;overflow-y:auto;">
              <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
                <thead>
                  <tr style="background:var(--gray);">
                    <th style="padding:.4rem .6rem;text-align:left;font-size:.7rem;text-transform:uppercase;color:var(--muted);letter-spacing:.5px;">#</th>
                    <th style="padding:.4rem .6rem;text-align:left;font-size:.7rem;text-transform:uppercase;color:var(--muted);letter-spacing:.5px;">Nombre</th>
                    <th style="padding:.4rem .6rem;text-align:left;font-size:.7rem;text-transform:uppercase;color:var(--muted);letter-spacing:.5px;">Grado</th>
                    <th style="padding:.4rem .6rem;text-align:left;font-size:.7rem;text-transform:uppercase;color:var(--muted);letter-spacing:.5px;">Edad</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($preview as $i => $p): ?>
                    <tr style="border-bottom:1px solid var(--border);">
                      <td style="padding:.4rem .6rem;color:var(--muted);"><?= $i+1 ?></td>
                      <td style="padding:.4rem .6rem;font-weight:600;"><?= h($p['nombre']) ?></td>
                      <td style="padding:.4rem .6rem;color:var(--muted);"><?= h($p['grado'] ?: '&mdash;') ?></td>
                      <td style="padding:.4rem .6rem;color:var(--muted);"><?= $p['edad'] ? $p['edad'] . ' a' : '&mdash;' ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <input type="hidden" name="confirmar" value="1"/>
            <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.75rem;margin-top:.8rem;">
              <i class="bi bi-check-lg"></i> Confirmar e insertar <?= count($preview) ?> estudiantes
            </button>
          </div>
        <?php endif; ?>
      </div>

      <div>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-gear-fill"></i> Opciones por defecto</div>
          <label class="field-label">Grado por defecto</label>
          <input type="text" name="grado_def" class="rsal-input" maxlength="30"
                 placeholder="Ej 3o"
                 value="<?= h($_POST['grado_def'] ?? '') ?>"/>
          <div style="font-size:.7rem;color:var(--muted);margin-top:.2rem;">
            Se aplica si no lo especificas l&iacute;nea por l&iacute;nea.
          </div>

          <label class="field-label" style="margin-top:.7rem;">Fecha de ingreso</label>
          <input type="date" name="fecha_ingreso" class="rsal-input"
                 value="<?= h($_POST['fecha_ingreso'] ?? date('Y-m-d')) ?>"/>
        </div>

        <?php if ($preview === null): ?>
          <div class="card-rsal">
            <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.7rem;">
              <i class="bi bi-eye-fill"></i> Previsualizar lista
            </button>
            <a href="<?= $U ?>modulos/extracurriculares/programas/ver.php?id=<?= $programa_id ?>" class="btn-rsal-secondary"
               style="width:100%;justify-content:center;padding:.55rem;margin-top:.5rem;">Cancelar</a>
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
