<?php
// dashboard.php &mdash; ROBOTSchool Academy Learning
// Est&aacute; en la RA&Iacute;Z del proyecto, mismo nivel que config/
require_once __DIR__ . '/config/config.php';
require_once ROOT   . '/config/auth.php';
requireLogin();

// Los padres y docentes tienen sus propios portales
if ($_SESSION['usuario_rol'] === 'padre') {
    header('Location: ' . BASE_URL . 'portal/index.php'); exit;
}
if ($_SESSION['usuario_rol'] === 'docente') {
    header('Location: ' . BASE_URL . 'docente/index.php'); exit;
}
// El coordinador pedag&oacute;gico tiene su propio dashboard acad&eacute;mico
if ($_SESSION['usuario_rol'] === 'coordinador_pedagogico') {
    header('Location: ' . BASE_URL . 'modulos/academico/dashboard.php'); exit;
}

$titulo      = 'Dashboard';
$menu_activo = 'dashboard';
$U           = BASE_URL;

// Stats globales
$sede_filtro = getSedeFiltro();

$where_m_sede = $sede_filtro ? 'AND m.sede_id = ' . (int)$sede_filtro : '';
$where_p_sede = $sede_filtro ? 'AND m2.sede_id = ' . (int)$sede_filtro : '';

$total_estudiantes = $pdo->query("SELECT COUNT(DISTINCT e.id) FROM estudiantes e JOIN matriculas m ON m.estudiante_id = e.id WHERE m.estado = 'activa' $where_m_sede")->fetchColumn();
$total_matriculas  = $pdo->query("SELECT COUNT(*) FROM matriculas m WHERE m.estado = 'activa' $where_m_sede")->fetchColumn();
$pagos_vencidos    = $pdo->query("SELECT COUNT(*) FROM pagos p JOIN matriculas m2 ON m2.id = p.matricula_id WHERE p.estado = 'vencido' $where_p_sede")->fetchColumn();
$recaudo_mes       = $pdo->query("SELECT COALESCE(SUM(pa.valor),0) FROM pagos_abonos pa JOIN pagos p2 ON p2.id = pa.pago_id JOIN matriculas m2 ON m2.id = p2.matricula_id WHERE MONTH(pa.fecha)=MONTH(NOW()) AND YEAR(pa.fecha)=YEAR(NOW()) $where_p_sede")->fetchColumn();

// Matr&iacute;culas recientes
$matriculas_recientes = $pdo->query("
    SELECT m.*, e.nombre_completo AS estudiante,
           c.nombre AS curso, s.nombre AS sede,
           p.estado AS pago_estado
    FROM matriculas m
    JOIN estudiantes e ON e.id = m.estudiante_id
    JOIN grupos g ON g.id = m.grupo_id
    JOIN cursos c ON c.id = g.curso_id
    JOIN sedes s ON s.id = m.sede_id
    LEFT JOIN pagos p ON p.matricula_id = m.id
    ORDER BY m.created_at DESC LIMIT 8
")->fetchAll();

// Datos por sede
$sedes_data = $pdo->query("
    SELECT s.*,
        (SELECT COUNT(*) FROM estudiantes e2
         JOIN matriculas m2 ON m2.estudiante_id = e2.id
         WHERE e2.sede_id = s.id AND m2.estado = 'activa') AS estudiantes_activos,
        (SELECT COUNT(DISTINCT g2.curso_id) FROM grupos g2 JOIN cursos c2 ON c2.id = g2.curso_id WHERE g2.sede_id = s.id AND c2.publicado = 1) AS cursos_activos,
        (SELECT COUNT(*) FROM pagos p2 JOIN matriculas m3 ON m3.id = p2.matricula_id WHERE m3.sede_id = s.id AND p2.estado='pagado') AS pagos_ok,
        (SELECT COUNT(*) FROM pagos p2 JOIN matriculas m3 ON m3.id = p2.matricula_id WHERE m3.sede_id = s.id AND p2.estado='parcial') AS pagos_parcial,
        (SELECT COUNT(*) FROM pagos p2 JOIN matriculas m3 ON m3.id = p2.matricula_id WHERE m3.sede_id = s.id AND p2.estado='vencido') AS pagos_vencido
    FROM sedes s WHERE s.activa = 1 ORDER BY s.id
")->fetchAll();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>

<!-- HEADER -->
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">
    Dashboard
    <small>Resumen general del sistema</small>
  </div>
  <div class="header-actions">
    <span class="sede-tag">
      <i class="bi bi-geo-alt-fill"></i>
      <?= $sede_filtro ? 'Mi sede' : 'Todas las sedes' ?>
    </span>
  </div>
</header>

<!-- CONTENIDO -->
<main class="main-content">

  <!-- Bienvenida -->
  <div style="background:linear-gradient(135deg,var(--dark) 0%,#1e3a5f 100%);border-radius:20px;padding:1.6rem 2rem;margin-bottom:1.6rem;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;gap:1rem;">
    <div style="position:absolute;right:-60px;top:-60px;width:220px;height:220px;background:radial-gradient(circle,rgba(29,169,154,.2) 0%,transparent 70%);border-radius:50%;"></div>
    <div style="position:relative;z-index:1;">
      <h2 style="font-size:1.3rem;font-weight:800;color:#fff;margin-bottom:.25rem;">
        Hola, <span style="color:var(--teal);"><?= h($_SESSION['usuario_nombre'] ?? 'Admin') ?></span> &#128075;
      </h2>
      <p style="font-size:.85rem;color:rgba(255,255,255,.6);margin:0;">
        <?= date('l, d \d\e F \d\e Y', strtotime('now')) ?>
      </p>
    </div>
    <div style="text-align:right;position:relative;z-index:1;">
      <strong style="display:block;font-size:2rem;font-weight:900;font-family:'Poppins',sans-serif;color:#fff;line-height:1;"><?= date('d') ?></strong>
      <small style="font-size:.75rem;color:rgba(255,255,255,.5);"><?= date('M Y') ?></small>
    </div>
  </div>

  <!-- Stats -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;margin-bottom:1.6rem;">
    <?php
      $stats = [
        ['icon'=>'bi-person-badge-fill','bg'=>'var(--teal-l)','color'=>'var(--teal)','num'=>$total_estudiantes,'lbl'=>'Estudiantes activos','link'=>$U.'modulos/estudiantes/index.php'],
        ['icon'=>'bi-clipboard2-check-fill','bg'=>'#e8f0fe','color'=>'#1E4DA1','num'=>$total_matriculas,'lbl'=>'Matr&iacute;culas vigentes','link'=>$U.'modulos/matriculas/index.php'],
        ['icon'=>'bi-cash-stack','bg'=>'#fff8e1','color'=>'#F59E0B','num'=>formatCOP($recaudo_mes),'lbl'=>'Recaudo del mes','link'=>$U.'modulos/pagos/index.php'],
        ['icon'=>'bi-exclamation-triangle-fill','bg'=>'var(--red-l)','color'=>'var(--red)','num'=>$pagos_vencidos,'lbl'=>'Pagos vencidos','link'=>$U.'modulos/pagos/index.php'],
      ];
      foreach ($stats as $st):
    ?>
    <a href="<?= $st['link'] ?>" style="text-decoration:none;">
      <div class="card-rsal" style="padding:1.2rem 1.4rem;margin:0;transition:transform .2s,box-shadow .2s;cursor:pointer;"
           onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 32px rgba(0,0,0,.08)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="width:44px;height:44px;border-radius:12px;background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-bottom:.9rem;">
          <i class="bi <?= $st['icon'] ?>"></i>
        </div>
        <div style="font-family:'Poppins',sans-serif;font-size:1.9rem;font-weight:900;color:var(--dark);line-height:1;margin-bottom:.2rem;"><?= $st['num'] ?></div>
        <div style="font-size:.78rem;color:var(--muted);font-weight:600;"><?= $st['lbl'] ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Sedes -->
  <div style="font-size:.8rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:.8rem;display:flex;align-items:center;justify-content:space-between;">
    Resumen por sede
    <a href="<?= $U ?>modulos/sedes/index.php" style="font-size:.75rem;color:var(--teal);text-decoration:none;font-weight:700;text-transform:none;">Ver detalle <i class="bi bi-arrow-right"></i></a>
  </div>
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.6rem;">
    <?php foreach ($sedes_data as $sede): ?>
    <div class="card-rsal" style="margin:0;transition:all .2s;"
         onmouseover="this.style.borderColor='var(--teal)';this.style.boxShadow='0 4px 24px rgba(29,169,154,.1)'"
         onmouseout="this.style.borderColor='';this.style.boxShadow=''">
      <div style="display:flex;align-items:center;gap:.7rem;margin-bottom:1rem;">
        <div style="width:40px;height:40px;border-radius:12px;background:var(--teal-l);color:var(--teal);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">
          <i class="bi bi-building-fill"></i>
        </div>
        <div>
          <div style="font-size:.88rem;font-weight:800;color:var(--dark);"><?= h($sede['nombre']) ?></div>
          <div style="font-size:.72rem;color:var(--muted);"><i class="bi bi-geo-alt"></i> <?= h($sede['ciudad']) ?></div>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.8rem;">
        <div style="text-align:center;background:var(--gray);border-radius:10px;padding:.5rem;">
          <span style="display:block;font-size:1.2rem;font-weight:900;color:var(--dark);font-family:'Poppins',sans-serif;"><?= $sede['estudiantes_activos'] ?></span>
          <small style="font-size:.65rem;color:var(--muted);font-weight:600;">Estudiantes</small>
        </div>
        <div style="text-align:center;background:var(--gray);border-radius:10px;padding:.5rem;">
          <span style="display:block;font-size:1.2rem;font-weight:900;color:var(--dark);font-family:'Poppins',sans-serif;"><?= $sede['cursos_activos'] ?></span>
          <small style="font-size:.65rem;color:var(--muted);font-weight:600;">Cursos activos</small>
        </div>
      </div>
      <!-- Sem&aacute;foros pago -->
      <div style="display:flex;gap:.4rem;">
        <div style="flex:1;background:#dcfce7;color:#16a34a;border-radius:8px;padding:.35rem .4rem;text-align:center;font-size:.68rem;font-weight:800;">&#10003; <?= $sede['pagos_ok'] ?></div>
        <div style="flex:1;background:#fef9c3;color:#ca8a04;border-radius:8px;padding:.35rem .4rem;text-align:center;font-size:.68rem;font-weight:800;">~ <?= $sede['pagos_parcial'] ?></div>
        <div style="flex:1;background:var(--red-l);color:var(--red);border-radius:8px;padding:.35rem .4rem;text-align:center;font-size:.68rem;font-weight:800;">&#10007; <?= $sede['pagos_vencido'] ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Matr&iacute;culas recientes -->
  <div style="font-size:.8rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin-bottom:.8rem;display:flex;align-items:center;justify-content:space-between;">
    Matr&iacute;culas recientes
    <a href="<?= $U ?>modulos/matriculas/index.php" style="font-size:.75rem;color:var(--teal);text-decoration:none;font-weight:700;text-transform:none;">Ver todas <i class="bi bi-arrow-right"></i></a>
  </div>
  <div class="card-rsal" style="padding:0;overflow:hidden;margin:0;">
    <table class="table-rsal">
      <thead>
        <tr>
          <th>Estudiante</th>
          <th>Curso</th>
          <th>Sede</th>
          <th>Estado matr&iacute;cula</th>
          <th>Estado pago</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($matriculas_recientes)): ?>
          <tr>
            <td colspan="5" style="text-align:center;padding:2.5rem;color:var(--muted);">
              <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3;"></i>
              No hay matr&iacute;culas a&uacute;n.
              <a href="<?= $U ?>modulos/matriculas/index.php" style="color:var(--teal);font-weight:700;">Crear primera matr&iacute;cula</a>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($matriculas_recientes as $m):
            $estado_class = [
              'activa'=>'be-activa','pre_inscrito'=>'be-pre',
              'retirada'=>'be-inactiva','finalizada'=>'be-inactiva','suspendida'=>'be-inactiva'
            ][$m['estado']] ?? 'be-inactiva';
            $pago_class = [
              'pagado'=>'sem-verde','parcial'=>'sem-amarillo',
              'vencido'=>'sem-rojo','pendiente'=>'sem-amarillo'
            ][$m['pago_estado'] ?? ''] ?? 'be-inactiva';
          ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:.6rem;">
                <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--teal),var(--teal-d));display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#fff;flex-shrink:0;">
                  <?= strtoupper(substr($m['estudiante'],0,2)) ?>
                </div>
                <?= h($m['estudiante']) ?>
              </div>
            </td>
            <td><?= h($m['curso']) ?></td>
            <td><?= h($m['sede']) ?></td>
            <td><span class="badge-estado <?= $estado_class ?>"><?= h($m['estado']) ?></span></td>
            <td>
              <?php if ($m['pago_estado']): ?>
                <span class="<?= $pago_class ?>"><?= h($m['pago_estado']) ?></span>
              <?php else: ?>
                <span style="color:var(--muted);font-size:.75rem;">&mdash;</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Accesos r&aacute;pidos -->
  <div style="font-size:.8rem;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;margin:1.6rem 0 .8rem;">
    Accesos r&aacute;pidos
  </div>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:.8rem;margin-bottom:1.6rem;">
    <?php
      $accesos = [
        ['icon'=>'bi-journal-richtext','color'=>'var(--teal)','lbl'=>'Nuevo curso','url'=>$U.'modulos/academico/cursos/form.php'],
        ['icon'=>'bi-person-plus-fill','color'=>'#1E4DA1','lbl'=>'Nuevo estudiante','url'=>$U.'modulos/estudiantes/form.php'],
        ['icon'=>'bi-clipboard2-plus-fill','color'=>'#F59E0B','lbl'=>'Nueva matr&iacute;cula','url'=>$U.'modulos/matriculas/form.php'],
        ['icon'=>'bi-cash-coin','color'=>'#16a34a','lbl'=>'Registrar pago','url'=>$U.'modulos/pagos/index.php'],
        ['icon'=>'bi-calendar3','color'=>'#8B5CF6','lbl'=>'Grupos/Horarios','url'=>$U.'modulos/academico/grupos/index.php'],
        ['icon'=>'bi-cpu-fill','color'=>'#EC4899','lbl'=>'Equipos','url'=>$U.'modulos/equipos/index.php'],
      ];
      foreach ($accesos as $a):
    ?>
    <a href="<?= $a['url'] ?>" style="text-decoration:none;">
      <div style="background:#fff;border:1px solid var(--border);border-radius:14px;padding:1rem;text-align:center;transition:all .2s;"
           onmouseover="this.style.borderColor='<?= $a['color'] ?>';this.style.transform='translateY(-2px)'"
           onmouseout="this.style.borderColor='';this.style.transform=''">
        <i class="bi <?= $a['icon'] ?>" style="font-size:1.4rem;color:<?= $a['color'] ?>;display:block;margin-bottom:.4rem;"></i>
        <span style="font-size:.75rem;font-weight:700;color:var(--dark);"><?= $a['lbl'] ?></span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>

</main>

<script>
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
