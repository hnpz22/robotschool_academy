<?php
// modulos/extracurriculares/rutas/index.php
// Trazabilidad de la ruta diaria: lista de paradas + mapa Leaflet + Google Maps
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
require_once ROOT . '/includes/ec_helpers.php';
requireRol('coordinador_pedagogico');

$menu_activo = 'ec_rutas';
$U           = BASE_URL;

$fecha              = $_GET['fecha']      ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) $fecha = date('Y-m-d');
$tallerista_id      = (int)($_GET['tallerista'] ?? 0);

// Listado de talleristas con sesiones ese dia
$con_sesiones = $pdo->prepare("
    SELECT DISTINCT u.id, u.nombre
    FROM usuarios u
    JOIN ec_asignaciones a ON a.tallerista_id = u.id
    JOIN ec_sesiones s ON s.id = a.sesion_id
    WHERE s.fecha = ? AND u.rol = 'docente' AND u.activo = 1
    ORDER BY u.nombre
");
$con_sesiones->execute([$fecha]);
$talleristas_dia = $con_sesiones->fetchAll();

$todos_talleristas = $pdo->query("SELECT id, nombre FROM usuarios WHERE rol = 'docente' AND activo = 1 ORDER BY nombre")->fetchAll();

// Paradas del dia: si hay tallerista_id filtrar por asignacion; si no mostrar TODAS las sesiones del dia (modo demo)
if ($tallerista_id) {
    $sql = "SELECT s.id AS sesion_id, s.fecha, s.hora_inicio, s.hora_fin, s.numero_sesion, s.estado,
                   p.nombre AS programa_nombre, p.color AS programa_color,
                   cl.id AS cliente_id, cl.nombre AS cliente_nombre, cl.direccion, cl.ciudad, cl.barrio,
                   cl.latitud, cl.longitud
            FROM ec_sesiones s
            JOIN ec_programas p ON p.id = s.programa_id
            JOIN ec_contratos ct ON ct.id = p.contrato_id
            JOIN ec_clientes cl ON cl.id = ct.cliente_id
            JOIN ec_asignaciones a ON a.sesion_id = s.id
            WHERE s.fecha = ? AND a.tallerista_id = ?
            ORDER BY s.hora_inicio";
    $st = $pdo->prepare($sql);
    $st->execute([$fecha, $tallerista_id]);
    $modo_demo = false;
} else {
    $sql = "SELECT s.id AS sesion_id, s.fecha, s.hora_inicio, s.hora_fin, s.numero_sesion, s.estado,
                   p.nombre AS programa_nombre, p.color AS programa_color,
                   cl.id AS cliente_id, cl.nombre AS cliente_nombre, cl.direccion, cl.ciudad, cl.barrio,
                   cl.latitud, cl.longitud
            FROM ec_sesiones s
            JOIN ec_programas p ON p.id = s.programa_id
            JOIN ec_contratos ct ON ct.id = p.contrato_id
            JOIN ec_clientes cl ON cl.id = ct.cliente_id
            WHERE s.fecha = ?
            ORDER BY s.hora_inicio";
    $st = $pdo->prepare($sql);
    $st->execute([$fecha]);
    $modo_demo = true;
}
$paradas = $st->fetchAll();

// Calcular distancias entre paradas consecutivas
$total_km = 0;
$stops_with_km = [];
for ($i = 0; $i < count($paradas); $i++) {
    $p = $paradas[$i];
    $km_prev = null;
    if ($i > 0) {
        $prev = $paradas[$i-1];
        $km = ec_haversine_km((float)$prev['latitud'], (float)$prev['longitud'], (float)$p['latitud'], (float)$p['longitud']);
        if ($km !== null) {
            $km_prev = round($km * 1.4, 1);
            $total_km += $km_prev;
        }
    }
    $p['km_desde_anterior'] = $km_prev;
    $stops_with_km[] = $p;
}

// URL Google Maps con origen + destinos intermedios
$gmaps_url = '';
if (count($paradas) >= 2) {
    $coords = [];
    foreach ($paradas as $p) {
        if ($p['latitud'] && $p['longitud']) {
            $coords[] = $p['latitud'] . ',' . $p['longitud'];
        }
    }
    if (count($coords) >= 2) {
        $origin = array_shift($coords);
        $dest   = array_pop($coords);
        $gmaps_url = 'https://www.google.com/maps/dir/?api=1'
                   . '&origin='      . urlencode($origin)
                   . '&destination=' . urlencode($dest)
                   . '&travelmode=driving';
        if (count($coords) > 0) {
            $gmaps_url .= '&waypoints=' . urlencode(implode('|', $coords));
        }
    }
} elseif (count($paradas) === 1) {
    $p = $paradas[0];
    if ($p['latitud'] && $p['longitud']) {
        $gmaps_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($p['latitud'] . ',' . $p['longitud']);
    }
}

$tall_nombre = '';
if ($tallerista_id) {
    foreach ($todos_talleristas as $t) {
        if ($t['id'] == $tallerista_id) { $tall_nombre = $t['nombre']; break; }
    }
}

$titulo = 'Ruta del d&iacute;a';

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">
    Trazabilidad de ruta
    <small><?= $tall_nombre ? h($tall_nombre) : 'Todas las sesiones' ?> &middot; <?= date('D d M Y', strtotime($fecha)) ?></small>
  </div>
</header>
<main class="main-content">

  <form method="GET" style="display:flex;gap:.6rem;flex-wrap:wrap;margin-bottom:1rem;align-items:end;background:var(--gray);padding:.75rem 1rem;border-radius:10px;">
    <div>
      <label class="field-label" style="margin:0 0 .2rem;">Fecha</label>
      <input type="date" name="fecha" class="rsal-input" value="<?= h($fecha) ?>"
             style="padding:.4rem .6rem;font-size:.82rem;max-width:170px;"/>
    </div>
    <div>
      <label class="field-label" style="margin:0 0 .2rem;">Tallerista</label>
      <select name="tallerista" class="rsal-select" style="min-width:220px;padding:.4rem .6rem;font-size:.82rem;">
        <option value="0">Todos (modo demo)</option>
        <?php foreach ($todos_talleristas as $t):
          $tiene = false;
          foreach ($talleristas_dia as $td) { if ($td['id'] == $t['id']) { $tiene = true; break; } }
        ?>
          <option value="<?= $t['id'] ?>" <?= $tallerista_id == $t['id'] ? 'selected' : '' ?>>
            <?= h($t['nombre']) ?><?= $tiene ? ' (con sesiones ese dia)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn-rsal-primary" style="padding:.4rem .9rem;font-size:.82rem;">
      <i class="bi bi-funnel-fill"></i> Aplicar
    </button>

    <?php $ayer = date('Y-m-d', strtotime($fecha . ' -1 day')); $manana = date('Y-m-d', strtotime($fecha . ' +1 day')); ?>
    <div style="display:flex;gap:.3rem;margin-left:auto;">
      <a href="?fecha=<?= $ayer ?>&tallerista=<?= $tallerista_id ?>" class="btn-rsal-secondary" style="padding:.4rem .7rem;font-size:.82rem;">
        <i class="bi bi-chevron-left"></i> Ayer
      </a>
      <a href="?fecha=<?= date('Y-m-d') ?>&tallerista=<?= $tallerista_id ?>" class="btn-rsal-secondary" style="padding:.4rem .7rem;font-size:.82rem;">Hoy</a>
      <a href="?fecha=<?= $manana ?>&tallerista=<?= $tallerista_id ?>" class="btn-rsal-secondary" style="padding:.4rem .7rem;font-size:.82rem;">
        Ma&ntilde;ana <i class="bi bi-chevron-right"></i>
      </a>
    </div>
  </form>

  <?php if ($modo_demo): ?>
    <div class="alert-rsal alert-info" style="margin-bottom:1rem;">
      <i class="bi bi-info-circle-fill"></i>
      <strong>Modo demo:</strong> estas viendo TODAS las sesiones del d&iacute;a como si fueran de un solo tallerista.
      Cuando tengas asignaciones de talleristas cargadas en la entrega siguiente podr&aacute;s filtrar por persona.
    </div>
  <?php endif; ?>

  <?php if (empty($paradas)): ?>

    <div class="empty-state">
      <i class="bi bi-geo-alt"></i>
      <h3>No hay sesiones este d&iacute;a</h3>
      <p>Selecciona otra fecha o genera sesiones en los programas de extracurriculares.</p>
    </div>

  <?php else: ?>

    <div style="display:grid;grid-template-columns:380px 1fr;gap:1.2rem;align-items:start;">

      <div>

        <div class="card-rsal" style="padding:.9rem 1rem;">
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.5rem;">
            <div style="text-align:center;">
              <div style="font-size:.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Paradas</div>
              <div style="font-family:'Poppins',sans-serif;font-size:1.6rem;font-weight:900;color:#7c3aed;line-height:1.1;"><?= count($paradas) ?></div>
            </div>
            <div style="text-align:center;">
              <div style="font-size:.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Km totales</div>
              <div style="font-family:'Poppins',sans-serif;font-size:1.6rem;font-weight:900;color:#1E4DA1;line-height:1.1;"><?= number_format($total_km, 1, ',', '.') ?></div>
            </div>
            <div style="text-align:center;">
              <div style="font-size:.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;">Horas</div>
              <div style="font-family:'Poppins',sans-serif;font-size:1.6rem;font-weight:900;color:#0d6e5f;line-height:1.1;">
                <?php
                  if (count($paradas) > 0) {
                    $ini = substr($paradas[0]['hora_inicio'], 0, 5);
                    $fin = substr(end($paradas)['hora_fin'], 0, 5);
                    echo h($ini) . '&ndash;' . h($fin);
                  } else { echo '&mdash;'; }
                ?>
              </div>
            </div>
          </div>
        </div>

        <?php if ($gmaps_url): ?>
        <a href="<?= h($gmaps_url) ?>" target="_blank"
           class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.75rem;margin-bottom:1rem;background:#4285F4;border-color:#4285F4;">
          <i class="bi bi-google"></i> Abrir ruta en Google Maps
        </a>
        <?php endif; ?>

        <div style="display:grid;gap:.5rem;position:relative;">
          <div style="position:absolute;left:18px;top:20px;bottom:20px;width:2px;background:var(--border);z-index:0;"></div>

          <?php foreach ($stops_with_km as $i => $p):
            $color_prog = $p['programa_color'] ?: '#7c3aed';
            $num = $i + 1;
          ?>

            <?php if ($p['km_desde_anterior'] !== null): ?>
              <div style="margin-left:46px;font-size:.7rem;color:var(--muted);padding:.2rem 0;">
                <i class="bi bi-arrow-down"></i> <?= number_format($p['km_desde_anterior'], 1, ',', '.') ?> km
              </div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:38px 1fr;gap:.6rem;align-items:flex-start;position:relative;z-index:1;">
              <div style="width:38px;height:38px;border-radius:50%;background:<?= h($color_prog) ?>;color:#fff;font-family:'Poppins',sans-serif;font-weight:900;font-size:1rem;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.15);">
                <?= $num ?>
              </div>

              <div style="background:#fff;border:1px solid var(--border);border-left:4px solid <?= h($color_prog) ?>;border-radius:10px;padding:.6rem .8rem;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.4rem;margin-bottom:.3rem;">
                  <div style="font-weight:700;color:var(--dark);font-size:.9rem;"><?= h($p['cliente_nombre']) ?></div>
                  <span style="background:<?= h($color_prog) ?>22;color:<?= h($color_prog) ?>;font-size:.68rem;font-weight:700;padding:2px 7px;border-radius:8px;white-space:nowrap;"><?= substr($p['hora_inicio'],0,5) ?>&ndash;<?= substr($p['hora_fin'],0,5) ?></span>
                </div>
                <div style="font-size:.75rem;color:var(--muted);">
                  <?= h($p['programa_nombre']) ?> &middot; Sesi&oacute;n #<?= (int)$p['numero_sesion'] ?>
                </div>
                <?php if ($p['direccion']): ?>
                  <div style="font-size:.72rem;color:var(--muted);margin-top:.3rem;">
                    <i class="bi bi-geo-alt-fill"></i> <?= h(trim($p['direccion'] . ($p['barrio'] ? ', ' . $p['barrio'] : '') . ($p['ciudad'] ? ' ' . $p['ciudad'] : ''))) ?>
                  </div>
                <?php endif; ?>
                <?php if ($p['latitud'] && $p['longitud']): ?>
                  <a href="https://www.google.com/maps/search/?api=1&query=<?= h($p['latitud']) ?>,<?= h($p['longitud']) ?>" target="_blank"
                     style="font-size:.7rem;color:#1E4DA1;text-decoration:none;margin-top:.2rem;display:inline-block;">
                    <i class="bi bi-box-arrow-up-right"></i> Ver en Google Maps
                  </a>
                <?php endif; ?>
              </div>
            </div>

          <?php endforeach; ?>
        </div>

      </div>

      <div>
        <div id="map" style="height:620px;border-radius:12px;border:1px solid var(--border);overflow:hidden;"></div>
      </div>

    </div>

  <?php endif; ?>

</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
<?php if (!empty($paradas)): ?>
const paradas = <?= json_encode(array_map(function($p, $i){
    return [
        'num' => $i + 1,
        'nombre' => $p['cliente_nombre'],
        'hora' => substr($p['hora_inicio'],0,5),
        'direccion' => trim($p['direccion'] . ($p['barrio'] ? ', ' . $p['barrio'] : '')),
        'lat' => $p['latitud'] ? (float)$p['latitud'] : null,
        'lng' => $p['longitud'] ? (float)$p['longitud'] : null,
        'color' => $p['programa_color'] ?: '#7c3aed'
    ];
}, $stops_with_km, array_keys($stops_with_km))) ?>;

const validas = paradas.filter(p => p.lat && p.lng);

if (validas.length > 0) {
  const primera = validas[0];
  const map = L.map('map').setView([primera.lat, primera.lng], 12);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap',
    maxZoom: 18
  }).addTo(map);

  const bounds = [];
  const latlngs = [];

  validas.forEach((p, idx) => {
    const icon = L.divIcon({
      html: '<div style="width:32px;height:32px;border-radius:50%;background:' + p.color + ';color:#fff;font-family:Poppins,sans-serif;font-weight:900;font-size:14px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 6px rgba(0,0,0,.3);border:2px solid #fff;">' + p.num + '</div>',
      className: '',
      iconSize: [32, 32],
      iconAnchor: [16, 16]
    });
    L.marker([p.lat, p.lng], { icon: icon })
      .addTo(map)
      .bindPopup('<strong>' + p.nombre + '</strong><br>' + p.hora + '<br>' + (p.direccion || ''));
    bounds.push([p.lat, p.lng]);
    latlngs.push([p.lat, p.lng]);
  });

  if (latlngs.length >= 2) {
    L.polyline(latlngs, { color: '#F26522', weight: 4, opacity: 0.7, dashArray: '8, 6' }).addTo(map);
  }

  if (bounds.length > 1) map.fitBounds(bounds, { padding: [40, 40] });
} else {
  document.getElementById('map').innerHTML = '<div style="padding:2rem;text-align:center;color:var(--muted);">Ninguna parada tiene coordenadas registradas.<br>Edita los clientes y agrega la ubicaci&oacute;n en el mapa.</div>';
}
<?php endif; ?>

document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});
</script>
</body>
</html>
