<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$titulo      = 'Padres y Acudientes';
$menu_activo = 'padres';
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';
$buscar      = trim($_GET['buscar'] ?? '');
$sede_filtro = getSedeFiltro();

$where  = ['1=1'];
$params = [];

// Filtro por sede: padres que tienen al menos un hijo en esa sede
if ($sede_filtro) {
    $where[] = 'p.id IN (SELECT DISTINCT e.padre_id FROM estudiantes e WHERE e.sede_id = ?)';
    $params[] = $sede_filtro;
}
if ($buscar) {
    $where[] = '(p.nombre_completo LIKE ? OR p.numero_doc LIKE ? OR p.email LIKE ? OR p.telefono LIKE ?)';
    $params  = array_merge($params, ["%$buscar%","%$buscar%","%$buscar%","%$buscar%"]);
}

$padres = $pdo->prepare("
    SELECT p.*, u.email AS login_email, u.activo AS login_activo,
        (SELECT COUNT(*) FROM estudiantes e WHERE e.padre_id = p.id) AS total_hijos
    FROM padres p JOIN usuarios u ON u.id = p.usuario_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY p.nombre_completo ASC
");
$padres->execute($params);
$padres = $padres->fetchAll();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">
    Padres y Acudientes <small><?= count($padres) ?> registrados</small>
  </div>
  <a href="<?= $U ?>modulos/padres/form.php" class="btn-rsal-primary">
    <i class="bi bi-plus-lg"></i> Nuevo padre
  </a>
</header>
<main class="main-content">
  <?php if ($msg === 'creado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Padre registrado correctamente.</div>
  <?php elseif ($msg === 'editado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Datos actualizados.</div>
  <?php elseif ($msg === 'eliminado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Padre eliminado.</div>
  <?php endif; ?>

  <div class="toolbar">
    <div class="toolbar-left">
      <form method="GET" style="display:contents;">
        <div class="search-box">
          <i class="bi bi-search"></i>
          <input type="text" name="buscar" placeholder="Buscar por nombre, doc, email..."
                 value="<?= h($buscar) ?>" onchange="this.form.submit()"/>
        </div>
      </form>
    </div>
    <a href="<?= $U ?>modulos/padres/form.php" class="btn-rsal-primary">
      <i class="bi bi-plus-lg"></i> Nuevo padre
    </a>
  </div>

  <?php if (empty($padres)): ?>
    <div class="empty-state">
      <i class="bi bi-people"></i>
      <h3>No hay padres registrados</h3>
      <p>Los padres se registran desde la web p&uacute;blica o manualmente desde aqu&iacute;.</p>
      <a href="<?= $U ?>modulos/padres/form.php" class="btn-rsal-primary">
        <i class="bi bi-plus-lg"></i> Registrar padre
      </a>
    </div>
  <?php else: ?>
    <div class="card-rsal" style="padding:0;overflow:hidden;margin:0;">
      <table class="table-rsal">
        <thead>
          <tr>
            <th>Padre / Acudiente</th>
            <th>Documento</th>
            <th>Contacto</th>
            <th>Hijos</th>
            <th>Pol&iacute;ticas</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($padres as $p): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:.6rem;">
                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--teal-d));display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;color:#fff;flex-shrink:0;">
                  <?= strtoupper(substr($p['nombre_completo'],0,2)) ?>
                </div>
                <div>
                  <div style="font-weight:700;font-size:.85rem;color:var(--dark);"><?= h($p['nombre_completo']) ?></div>
                  <div style="font-size:.72rem;color:var(--muted);"><?= h($p['login_email']) ?></div>
                </div>
              </div>
            </td>
            <td style="font-size:.82rem;"><?= h($p['tipo_doc']) ?> <?= h($p['numero_doc']) ?></td>
            <td>
              <div style="font-size:.82rem;"><i class="bi bi-telephone" style="color:var(--teal);"></i> <?= h($p['telefono']) ?></div>
              <?php if ($p['telefono_alt']): ?>
                <div style="font-size:.75rem;color:var(--muted);"><?= h($p['telefono_alt']) ?></div>
              <?php endif; ?>
            </td>
            <td style="text-align:center;">
              <span style="font-family:'Poppins',sans-serif;font-size:1rem;font-weight:900;color:var(--teal);"><?= $p['total_hijos'] ?></span>
            </td>
            <td>
              <div style="display:flex;gap:.3rem;flex-wrap:wrap;">
                <span class="badge-estado <?= $p['acepta_datos']?'be-activa':'be-vencido' ?>" title="Tratamiento de datos">
                  <i class="bi bi-file-earmark-check"></i> Datos
                </span>
                <span class="badge-estado <?= $p['acepta_imagenes']?'be-activa':'be-vencido' ?>" title="Uso de im&aacute;genes">
                  <i class="bi bi-camera"></i> Im&aacute;genes
                </span>
              </div>
            </td>
            <td>
              <span class="badge-estado <?= $p['login_activo']?'be-activa':'be-inactiva' ?>">
                <?= $p['login_activo']?'Activo':'Inactivo' ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:.4rem;">
                <a href="<?= $U ?>modulos/padres/ver.php?id=<?= $p['id'] ?>"
                   class="btn-rsal-secondary" style="padding:.35rem .7rem;font-size:.75rem;" title="Ver detalle">
                  <i class="bi bi-eye-fill"></i>
                </a>
                <a href="<?= $U ?>modulos/padres/form.php?id=<?= $p['id'] ?>"
                   class="btn-rsal-primary" style="padding:.35rem .7rem;font-size:.75rem;">
                  <i class="bi bi-pencil-fill"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>
<script>
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
