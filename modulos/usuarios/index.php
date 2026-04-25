<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$titulo      = 'Usuarios';
$menu_activo = 'usuarios';
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';
$sede_filtro = getSedeFiltro();

$where = $sede_filtro
    ? "WHERE u.rol != 'padre' AND (u.sede_id = ".(int)$sede_filtro." OR u.rol = 'admin_general')"
    : "WHERE u.rol != 'padre'";

$usuarios = $pdo->query("
    SELECT u.*, s.nombre AS sede_nombre
    FROM usuarios u
    LEFT JOIN sedes s ON s.id = u.sede_id
    $where
    ORDER BY FIELD(u.rol,'admin_general','admin_sede','coordinador_pedagogico','docente'), u.nombre
")->fetchAll();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <div class="header-title">Usuarios <small>Administradores del sistema</small></div>
  <a href="<?= $U ?>modulos/usuarios/form.php" class="btn-rsal-primary">
    <i class="bi bi-plus-lg"></i> Nuevo usuario
  </a>
</header>
<main class="main-content">
  <?php if ($msg==='creado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Usuario creado.</div>
  <?php elseif($msg==='editado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Usuario actualizado.</div>
  <?php elseif($msg==='eliminado'): ?><div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Usuario eliminado.</div><?php endif; ?>

  <div class="card-rsal" style="padding:0;overflow:hidden;margin:0;">
    <table class="table-rsal">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Email</th>
          <th>Rol</th>
          <th>Sede</th>
          <th>&Uacute;ltimo login</th>
          <th>Estado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $colores_rol = [
            'admin_general'          => 'linear-gradient(135deg,var(--orange),#ff8c42)',
            'admin_sede'             => 'linear-gradient(135deg,var(--teal),var(--teal-d))',
            'coordinador_pedagogico' => 'linear-gradient(135deg,#7c3aed,#a78bfa)',
            'docente'                => 'linear-gradient(135deg,#0ea5e9,#38bdf8)',
        ];
        $labels_rol = [
            'admin_general'          => 'Admin General',
            'admin_sede'             => 'Admin Sede',
            'coordinador_pedagogico' => 'Coordinador',
            'docente'                => 'Docente',
        ];
        $badge_rol = [
            'admin_general'          => 'be-pre',
            'admin_sede'             => 'be-activa',
            'coordinador_pedagogico' => 'be-suspendida',
            'docente'                => 'be-retirada',
        ];
        foreach ($usuarios as $u):
        $color_av = $colores_rol[$u['rol']] ?? 'linear-gradient(135deg,#64748b,#94a3b8)';
        $label_r  = $labels_rol[$u['rol']]  ?? $u['rol'];
        $badge_r  = $badge_rol[$u['rol']]   ?? 'be-pre';
        ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:.6rem;">
              <div style="width:34px;height:34px;border-radius:50%;background:<?= $color_av ?>;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;color:#fff;flex-shrink:0;">
                <?= strtoupper(substr($u['nombre'],0,2)) ?>
              </div>
              <span style="font-size:.85rem;font-weight:700;"><?= h($u['nombre']) ?></span>
            </div>
          </td>
          <td style="font-size:.82rem;"><?= h($u['email']) ?></td>
          <td>
            <span class="badge-estado <?= $badge_r ?>">
              <?= $label_r ?>
            </span>
          </td>
          <td style="font-size:.82rem;"><?= $u['sede_nombre'] ? h($u['sede_nombre']) : '<span style="color:var(--muted)">Todas</span>' ?></td>
          <td style="font-size:.78rem;color:var(--muted);"><?= $u['ultimo_login'] ? formatFecha($u['ultimo_login']) : '&mdash;' ?></td>
          <td><span class="badge-estado <?= $u['activo']?'be-activa':'be-inactiva' ?>"><?= $u['activo']?'Activo':'Inactivo' ?></span></td>
          <td>
            <div style="display:flex;gap:.4rem;">
              <a href="<?= $U ?>modulos/usuarios/form.php?id=<?= $u['id'] ?>"
                 class="btn-rsal-primary" style="padding:.35rem .7rem;font-size:.75rem;">
                <i class="bi bi-pencil-fill"></i>
              </a>
              <?php if ($u['id'] != $_SESSION['usuario_id']): ?>
              <button onclick="eliminar(<?= $u['id'] ?>, '<?= h(addslashes($u['nombre'])) ?>')"
                      class="btn-rsal-danger" style="padding:.35rem .7rem;font-size:.75rem;">
                <i class="bi bi-trash-fill"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<form id="fEl" method="POST" action="<?= $U ?>modulos/usuarios/eliminar.php">
  <input type="hidden" name="id" id="elId"/>
</form>
<script>
function eliminar(id, nombre) {
  if (confirm('&iquest;Eliminar al usuario "' + nombre + '"?')) {
    document.getElementById('elId').value = id;
    document.getElementById('fEl').submit();
  }
}
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
