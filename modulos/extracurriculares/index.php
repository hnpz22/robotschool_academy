<?php
// modulos/extracurriculares/index.php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$titulo      = 'Extracurriculares';
$menu_activo = 'extracurriculares';
$U           = BASE_URL;

$total_clientes  = (int)$pdo->query("SELECT COUNT(*) FROM ec_clientes WHERE activo = 1")->fetchColumn();
$total_contratos = (int)$pdo->query("SELECT COUNT(*) FROM ec_contratos WHERE estado = 'vigente'")->fetchColumn();
$total_programas = (int)$pdo->query("SELECT COUNT(*) FROM ec_programas WHERE estado IN ('planeado','en_curso')")->fetchColumn();
$total_sesiones_mes = (int)$pdo->query("
    SELECT COUNT(*) FROM ec_sesiones
    WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())
")->fetchColumn();

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">Extracurriculares <small>Actividades en colegios empresas e instituciones</small></div>
</header>
<main class="main-content">

  <div style="background:linear-gradient(135deg,#D85A30,#F2A623);color:#fff;border-radius:16px;padding:1.4rem 1.6rem;margin-bottom:1.4rem;">
    <div style="font-family:'Poppins',sans-serif;font-size:1.4rem;font-weight:700;margin-bottom:.25rem;">
      Extracurriculares RSAL
    </div>
    <div style="font-size:.9rem;opacity:.9;">
      Gesti&oacute;n de actividades extracurriculares: clientes contratos programas calendario asistencia y evaluaci&oacute;n.
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.4rem;">
    <a href="<?= $U ?>modulos/extracurriculares/clientes/index.php" style="text-decoration:none;color:inherit;">
      <div class="card-rsal" style="margin:0;transition:all .2s;"
           onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.08)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="display:flex;align-items:center;gap:.8rem;margin-bottom:.5rem;">
          <div style="width:44px;height:44px;border-radius:12px;background:#faece7;color:#D85A30;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
            <i class="bi bi-building-fill"></i>
          </div>
          <div style="font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:900;color:var(--dark);"><?= $total_clientes ?></div>
        </div>
        <div style="font-size:.75rem;color:var(--muted);font-weight:600;">Clientes activos</div>
      </div>
    </a>

    <a href="<?= $U ?>modulos/extracurriculares/contratos/index.php" style="text-decoration:none;color:inherit;">
      <div class="card-rsal" style="margin:0;transition:all .2s;"
           onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.08)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="display:flex;align-items:center;gap:.8rem;margin-bottom:.5rem;">
          <div style="width:44px;height:44px;border-radius:12px;background:#ede4fb;color:#7c3aed;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
            <i class="bi bi-file-earmark-text-fill"></i>
          </div>
          <div style="font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:900;color:var(--dark);"><?= $total_contratos ?></div>
        </div>
        <div style="font-size:.75rem;color:var(--muted);font-weight:600;">Contratos vigentes</div>
      </div>
    </a>

    <a href="<?= $U ?>modulos/extracurriculares/programas/index.php" style="text-decoration:none;color:inherit;">
      <div class="card-rsal" style="margin:0;transition:all .2s;"
           onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.08)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="display:flex;align-items:center;gap:.8rem;margin-bottom:.5rem;">
          <div style="width:44px;height:44px;border-radius:12px;background:#e1f5f2;color:#1DA99A;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
            <i class="bi bi-bookmark-check-fill"></i>
          </div>
          <div style="font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:900;color:var(--dark);"><?= $total_programas ?></div>
        </div>
        <div style="font-size:.75rem;color:var(--muted);font-weight:600;">Programas activos</div>
      </div>
    </a>

    <div class="card-rsal" style="margin:0;opacity:.6;">
      <div style="display:flex;align-items:center;gap:.8rem;margin-bottom:.5rem;">
        <div style="width:44px;height:44px;border-radius:12px;background:#dbeafe;color:#3B82F6;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;">
          <i class="bi bi-calendar3"></i>
        </div>
        <div style="font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:900;color:var(--dark);"><?= $total_sesiones_mes ?></div>
      </div>
      <div style="font-size:.75rem;color:var(--muted);font-weight:600;">Sesiones del mes</div>
      <div style="font-size:.65rem;color:var(--muted);margin-top:.3rem;font-style:italic;">Pr&oacute;xima entrega</div>
    </div>
  </div>

  <div class="card-rsal">
    <div class="card-rsal-title"><i class="bi bi-info-circle-fill"></i> Estado del m&oacute;dulo</div>
    <div style="font-size:.9rem;line-height:1.7;color:var(--muted);">
      <p style="margin-bottom:.8rem;">
        Este m&oacute;dulo se construye en 7 entregas. Actualmente est&aacute; disponible:
      </p>
      <ul style="margin-left:1.2rem;">
        <li><strong style="color:#0d6e5f;"><i class="bi bi-check-circle-fill"></i> Entrega 1:</strong> Cimientos + CRUD de clientes con mapa Leaflet</li>
        <li><strong style="color:#0d6e5f;"><i class="bi bi-check-circle-fill"></i> Entrega 2 (actual):</strong> Contratos y programas</li>
        <li style="opacity:.6;">Entrega 3: Calendario visual con colores y detecci&oacute;n de conflictos</li>
        <li style="opacity:.6;">Entrega 4: Fallas y recuperaci&oacute;n de sesiones</li>
        <li style="opacity:.6;">Entrega 5: Estudiantes y asistencia</li>
        <li style="opacity:.6;">Entrega 6: Evaluaciones e informes</li>
        <li style="opacity:.6;">Entrega 7: Certificados y prefactura</li>
      </ul>
    </div>
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
