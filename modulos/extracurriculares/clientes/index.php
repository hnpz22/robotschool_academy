<?php
// modulos/extracurriculares/clientes/index.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Clientes &mdash; Extracurriculares';
$menu_activo = 'ec_clientes';
$U           = BASE_URL;
$msg         = $_GET['msg'] ?? '';

// Filtros
$q_buscar = trim($_GET['buscar'] ?? '');
$q_tipo   = $_GET['tipo']   ?? '';
$q_ciudad = trim($_GET['ciudad'] ?? '');
$q_activo = $_GET['activo'] ?? 'si';

$where  = ['1=1'];
$params = [];

if ($q_activo === 'si')  { $where[] = 'c.activo = 1'; }
if ($q_activo === 'no')  { $where[] = 'c.activo = 0'; }
if ($q_tipo)   { $where[] = 'c.tipo = ?';   $params[] = $q_tipo; }
if ($q_ciudad) { $where[] = 'c.ciudad LIKE ?'; $params[] = "%$q_ciudad%"; }
if ($q_buscar) {
    $where[] = '(c.nombre LIKE ? OR c.nit LIKE ? OR c.razon_social LIKE ?)';
    $params[] = "%$q_buscar%"; $params[] = "%$q_buscar%"; $params[] = "%$q_buscar%";
}

$sql = "SELECT c.*,
        (SELECT COUNT(*) FROM ec_contratos ct WHERE ct.cliente_id = c.id) AS total_contratos,
        (SELECT COUNT(*) FROM ec_contratos ct WHERE ct.cliente_id = c.id AND ct.estado = 'vigente') AS contratos_vigentes
        FROM ec_clientes c
        WHERE " . implode(' AND ', $where) . "
        ORDER BY c.activo DESC, c.nombre ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

$total = count($clientes);

// Ciudades unicas para el filtro
$ciudades = $pdo->query("SELECT DISTINCT ciudad FROM ec_clientes WHERE ciudad IS NOT NULL AND ciudad != '' ORDER BY ciudad")->fetchAll(PDO::FETCH_COLUMN);

$tipo_labels = [
    'colegio'     => ['Colegio',     'bi-mortarboard-fill', '#7c3aed'],
    'empresa'     => ['Empresa',     'bi-building-fill',    '#0d6e5f'],
    'institucion' => ['Instituci&oacute;n', 'bi-bank',         '#D85A30'],
    'otro'        => ['Otro',        'bi-three-dots',       '#6B7280'],
];

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">Clientes <small>Colegios empresas e instituciones con actividades extracurriculares</small></div>
  <a href="<?= $U ?>modulos/extracurriculares/clientes/form.php" class="btn-rsal-primary">
    <i class="bi bi-plus-lg"></i> Nuevo cliente
  </a>
</header>
<main class="main-content">

  <?php if ($msg === 'creado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Cliente creado correctamente.</div>
  <?php elseif ($msg === 'editado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Cliente actualizado.</div>
  <?php elseif ($msg === 'eliminado'): ?>
    <div class="alert-rsal alert-success"><i class="bi bi-check-circle-fill"></i> Cliente eliminado.</div>
  <?php elseif ($msg === 'no_eliminable'): ?>
    <div class="alert-rsal alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> No se puede eliminar: tiene contratos asociados.</div>
  <?php endif; ?>

  <div class="alert-rsal alert-info" style="margin-bottom:1.2rem;">
    <i class="bi bi-info-circle-fill"></i>
    Los clientes son las instituciones donde vamos a dictar extracurriculares. Cada cliente puede tener uno o varios contratos y cada contrato uno o varios programas.
  </div>

  <div class="card-rsal" style="margin-bottom:1rem;">
    <form method="GET" style="display:grid;grid-template-columns:1fr 150px 140px 120px auto;gap:.8rem;align-items:end;">
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Buscar</label>
        <input type="text" name="buscar" value="<?= h($q_buscar) ?>"
               placeholder="Nombre NIT razon social..."
               style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;"/>
      </div>
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Tipo</label>
        <select name="tipo" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;">
          <option value="">Todos</option>
          <?php foreach ($tipo_labels as $k => $v): ?>
            <option value="<?= $k ?>" <?= $q_tipo === $k ? 'selected' : '' ?>><?= $v[0] ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Ciudad</label>
        <select name="ciudad" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;">
          <option value="">Todas</option>
          <?php foreach ($ciudades as $ci): ?>
            <option value="<?= h($ci) ?>" <?= $q_ciudad === $ci ? 'selected' : '' ?>><?= h($ci) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label style="font-size:.75rem;font-weight:700;color:var(--muted);display:block;margin-bottom:.3rem;">Estado</label>
        <select name="activo" style="width:100%;padding:.55rem .8rem;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;">
          <option value="si"    <?= $q_activo === 'si'    ? 'selected' : '' ?>>Activos</option>
          <option value="no"    <?= $q_activo === 'no'    ? 'selected' : '' ?>>Inactivos</option>
          <option value="todos" <?= $q_activo === 'todos' ? 'selected' : '' ?>>Todos</option>
        </select>
      </div>
      <button type="submit" class="btn-rsal-primary" style="padding:.6rem 1rem;">
        <i class="bi bi-funnel-fill"></i> Filtrar
      </button>
    </form>
  </div>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.8rem;">
    <div style="font-size:.85rem;color:var(--muted);">
      <strong style="color:var(--dark);"><?= $total ?></strong> cliente<?= $total != 1 ? 's' : '' ?> encontrado<?= $total != 1 ? 's' : '' ?>
    </div>
  </div>

  <?php if (empty($clientes)): ?>
    <div class="empty-state">
      <i class="bi bi-building"></i>
      <h3>No hay clientes registrados</h3>
      <p>Crea tu primer cliente para empezar a gestionar extracurriculares.</p>
      <a href="<?= $U ?>modulos/extracurriculares/clientes/form.php" class="btn-rsal-primary">
        <i class="bi bi-plus-lg"></i> Crear primer cliente
      </a>
    </div>
  <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem;">
      <?php foreach ($clientes as $c):
        $t = $tipo_labels[$c['tipo']] ?? $tipo_labels['otro'];
      ?>
      <a href="<?= $U ?>modulos/extracurriculares/clientes/form.php?id=<?= $c['id'] ?>"
         style="text-decoration:none;color:inherit;">
      <div class="card-rsal" style="margin:0;transition:all .2s;<?= !$c['activo'] ? 'opacity:.55;' : '' ?>"
           onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.08)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="display:flex;align-items:flex-start;gap:.8rem;margin-bottom:.7rem;">
          <div style="width:44px;height:44px;border-radius:10px;background:<?= $t[2] ?>20;color:<?= $t[2] ?>;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
            <i class="bi <?= $t[1] ?>"></i>
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-family:'Poppins',sans-serif;font-size:.95rem;font-weight:700;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?= h($c['nombre']) ?>
            </div>
            <?php if ($c['nit']): ?>
              <div style="font-size:.7rem;color:var(--muted);">NIT: <?= h($c['nit']) ?></div>
            <?php endif; ?>
          </div>
          <span style="background:<?= $t[2] ?>20;color:<?= $t[2] ?>;font-size:.65rem;font-weight:700;padding:3px 10px;border-radius:12px;white-space:nowrap;">
            <?= $t[0] ?>
          </span>
        </div>

        <?php if ($c['ciudad'] || $c['direccion']): ?>
        <div style="font-size:.75rem;color:var(--muted);margin-bottom:.4rem;display:flex;align-items:flex-start;gap:.3rem;">
          <i class="bi bi-geo-alt-fill" style="color:<?= $t[2] ?>;flex-shrink:0;margin-top:2px;"></i>
          <span><?php
            $ubic = [];
            if ($c['direccion']) $ubic[] = $c['direccion'];
            if ($c['barrio'])    $ubic[] = $c['barrio'];
            if ($c['ciudad'])    $ubic[] = $c['ciudad'];
            echo h(implode(', ', $ubic));
          ?></span>
        </div>
        <?php endif; ?>

        <?php if ($c['contacto_nombre']): ?>
        <div style="font-size:.75rem;color:var(--muted);margin-bottom:.7rem;display:flex;align-items:center;gap:.3rem;">
          <i class="bi bi-person-fill"></i>
          <?= h($c['contacto_nombre']) ?>
          <?php if ($c['contacto_cargo']): ?>
            &middot; <span style="color:var(--muted);"><?= h($c['contacto_cargo']) ?></span>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Stats contratos -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;padding-top:.6rem;border-top:1px solid var(--border);">
          <div style="text-align:center;">
            <div style="font-family:'Poppins',sans-serif;font-size:1.4rem;font-weight:900;color:<?= $t[2] ?>;"><?= (int)$c['total_contratos'] ?></div>
            <div style="font-size:.65rem;color:var(--muted);font-weight:600;">Contratos totales</div>
          </div>
          <div style="text-align:center;">
            <div style="font-family:'Poppins',sans-serif;font-size:1.4rem;font-weight:900;color:#10B981;"><?= (int)$c['contratos_vigentes'] ?></div>
            <div style="font-size:.65rem;color:var(--muted);font-weight:600;">Vigentes</div>
          </div>
        </div>
      </div>
      </a>
      <?php endforeach; ?>
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
