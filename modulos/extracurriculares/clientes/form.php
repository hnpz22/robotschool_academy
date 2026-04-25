<?php
// modulos/extracurriculares/clientes/form.php
require_once __DIR__ . '/../../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('coordinador_pedagogico');

$menu_activo = 'ec_clientes';
$U           = BASE_URL;

$id      = (int)($_GET['id'] ?? 0);
$cliente = null;

if ($id) {
    $s = $pdo->prepare("SELECT * FROM ec_clientes WHERE id = ?");
    $s->execute([$id]);
    $cliente = $s->fetch();
    if (!$cliente) { header('Location: ' . $U . 'modulos/extracurriculares/clientes/index.php'); exit; }
}

$titulo  = $cliente ? 'Editar cliente' : 'Nuevo cliente';
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo              = $_POST['tipo']              ?? 'colegio';
    $nombre            = trim($_POST['nombre']            ?? '');
    $nit               = trim($_POST['nit']               ?? '');
    $razon_social      = trim($_POST['razon_social']      ?? '');
    $ciudad            = trim($_POST['ciudad']            ?? '');
    $direccion         = trim($_POST['direccion']         ?? '');
    $barrio            = trim($_POST['barrio']            ?? '');
    $latitud           = $_POST['latitud']          !== '' ? (float)$_POST['latitud']  : null;
    $longitud          = $_POST['longitud']         !== '' ? (float)$_POST['longitud'] : null;
    $telefono          = trim($_POST['telefono']          ?? '');
    $email             = trim($_POST['email']             ?? '');
    $sitio_web         = trim($_POST['sitio_web']         ?? '');
    $contacto_nombre   = trim($_POST['contacto_nombre']   ?? '');
    $contacto_cargo    = trim($_POST['contacto_cargo']    ?? '');
    $contacto_telefono = trim($_POST['contacto_telefono'] ?? '');
    $contacto_email    = trim($_POST['contacto_email']    ?? '');
    $notas             = trim($_POST['notas']             ?? '');
    $activo            = isset($_POST['activo']) ? 1 : 0;

    $tipos_validos = ['colegio','empresa','institucion','otro'];
    if (!in_array($tipo, $tipos_validos)) $tipo = 'colegio';

    if (!$nombre) $errores[] = 'El nombre es obligatorio.';

    if (empty($errores)) {
        if ($id) {
            $pdo->prepare("UPDATE ec_clientes SET tipo=?, nombre=?, nit=?, razon_social=?, ciudad=?, direccion=?, barrio=?, latitud=?, longitud=?, telefono=?, email=?, sitio_web=?, contacto_nombre=?, contacto_cargo=?, contacto_telefono=?, contacto_email=?, notas=?, activo=? WHERE id=?")
                ->execute([$tipo, $nombre, $nit, $razon_social, $ciudad, $direccion, $barrio, $latitud, $longitud, $telefono, $email, $sitio_web, $contacto_nombre, $contacto_cargo, $contacto_telefono, $contacto_email, $notas, $activo, $id]);
        } else {
            $pdo->prepare("INSERT INTO ec_clientes (tipo, nombre, nit, razon_social, ciudad, direccion, barrio, latitud, longitud, telefono, email, sitio_web, contacto_nombre, contacto_cargo, contacto_telefono, contacto_email, notas, activo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$tipo, $nombre, $nit, $razon_social, $ciudad, $direccion, $barrio, $latitud, $longitud, $telefono, $email, $sitio_web, $contacto_nombre, $contacto_cargo, $contacto_telefono, $contacto_email, $notas, $activo]);
        }
        header('Location: ' . $U . 'modulos/extracurriculares/clientes/index.php?msg=' . ($cliente ? 'editado' : 'creado'));
        exit;
    }
}

// Default coord Bogota centro si no hay datos
$lat_default = $cliente['latitud']  ?? 4.6486;
$lng_default = $cliente['longitud'] ?? -74.0655;

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
#mapa { height: 340px; border-radius: 10px; border: 1.5px solid var(--border); }
.coord-badge {
  background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px;
  padding: .4rem .7rem; font-family: monospace; font-size: .78rem; color: #475569;
  display: inline-flex; align-items: center; gap: .4rem;
}
</style>

<header class="main-header">
  <div class="header-title">
    <?= $titulo ?>
    <small><span class="breadcrumb-rsal">
      <a href="<?= $U ?>modulos/extracurriculares/clientes/index.php">Clientes</a>
      <i class="bi bi-chevron-right"></i>
      <?= $cliente ? h($cliente['nombre']) : 'Nuevo' ?>
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

      <!-- IZQUIERDA -->
      <div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-building-fill"></i> Informaci&oacute;n b&aacute;sica</div>

          <div style="display:grid;grid-template-columns:180px 1fr;gap:.8rem;">
            <div>
              <label class="field-label">Tipo <span class="req">*</span></label>
              <select name="tipo" class="rsal-select" required>
                <option value="colegio"     <?= ($cliente['tipo']??'colegio') === 'colegio'     ? 'selected' : '' ?>>Colegio</option>
                <option value="empresa"     <?= ($cliente['tipo']??'')      === 'empresa'     ? 'selected' : '' ?>>Empresa</option>
                <option value="institucion" <?= ($cliente['tipo']??'')      === 'institucion' ? 'selected' : '' ?>>Instituci&oacute;n</option>
                <option value="otro"        <?= ($cliente['tipo']??'')      === 'otro'        ? 'selected' : '' ?>>Otro</option>
              </select>
            </div>
            <div>
              <label class="field-label">Nombre <span class="req">*</span></label>
              <input type="text" name="nombre" class="rsal-input" required maxlength="200"
                     placeholder="Ej Gimnasio Moderno"
                     value="<?= h($cliente['nombre'] ?? '') ?>"/>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:180px 1fr;gap:.8rem;">
            <div>
              <label class="field-label">NIT</label>
              <input type="text" name="nit" class="rsal-input" maxlength="30"
                     placeholder="860.123.456-7"
                     value="<?= h($cliente['nit'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Raz&oacute;n social</label>
              <input type="text" name="razon_social" class="rsal-input" maxlength="200"
                     placeholder="Si difiere del nombre comercial"
                     value="<?= h($cliente['razon_social'] ?? '') ?>"/>
            </div>
          </div>
        </div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-geo-alt-fill"></i> Ubicaci&oacute;n</div>

          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.8rem;">
            <div>
              <label class="field-label">Ciudad</label>
              <input type="text" name="ciudad" class="rsal-input" maxlength="100"
                     placeholder="Bogot&aacute;"
                     value="<?= h($cliente['ciudad'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Barrio / Zona</label>
              <input type="text" name="barrio" class="rsal-input" maxlength="100"
                     placeholder="Chapinero"
                     value="<?= h($cliente['barrio'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Direcci&oacute;n</label>
              <input type="text" name="direccion" class="rsal-input" maxlength="250"
                     placeholder="Carrera 9 # 74-08"
                     value="<?= h($cliente['direccion'] ?? '') ?>"/>
            </div>
          </div>

          <label class="field-label" style="margin-top:.4rem;">Ubicaci&oacute;n exacta en el mapa</label>
          <p style="font-size:.78rem;color:var(--muted);margin-bottom:.5rem;line-height:1.5;">
            Haz clic en el mapa para marcar la ubicaci&oacute;n exacta del cliente. Esta coordenada se usar&aacute; para calcular tiempos de desplazamiento entre sedes.
          </p>
          <div id="mapa"></div>

          <div style="display:flex;gap:.6rem;margin-top:.6rem;align-items:center;flex-wrap:wrap;">
            <div class="coord-badge">
              <i class="bi bi-pin-map-fill" style="color:#D85A30;"></i>
              <span id="coord-display">
                <?php if ($cliente && $cliente['latitud']): ?>
                  Lat: <?= number_format($cliente['latitud'],  6) ?>, Lng: <?= number_format($cliente['longitud'], 6) ?>
                <?php else: ?>
                  Sin coordenadas seleccionadas
                <?php endif; ?>
              </span>
            </div>
            <button type="button" onclick="limpiarCoord()"
                    style="background:none;border:1px solid var(--border);color:var(--muted);border-radius:8px;padding:.35rem .7rem;font-size:.75rem;cursor:pointer;">
              <i class="bi bi-x-circle"></i> Limpiar
            </button>
          </div>

          <input type="hidden" name="latitud"  id="latitud"  value="<?= h($cliente['latitud']  ?? '') ?>"/>
          <input type="hidden" name="longitud" id="longitud" value="<?= h($cliente['longitud'] ?? '') ?>"/>
        </div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-telephone-fill"></i> Contacto institucional</div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
            <div>
              <label class="field-label">Tel&eacute;fono</label>
              <input type="text" name="telefono" class="rsal-input" maxlength="30"
                     value="<?= h($cliente['telefono'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Email</label>
              <input type="email" name="email" class="rsal-input" maxlength="150"
                     value="<?= h($cliente['email'] ?? '') ?>"/>
            </div>
          </div>
          <label class="field-label">Sitio web</label>
          <input type="text" name="sitio_web" class="rsal-input" maxlength="200"
                 placeholder="https://www.ejemplo.edu.co"
                 value="<?= h($cliente['sitio_web'] ?? '') ?>"/>
        </div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-person-rolodex"></i> Contacto principal</div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
            <div>
              <label class="field-label">Nombre</label>
              <input type="text" name="contacto_nombre" class="rsal-input" maxlength="150"
                     placeholder="Mar&iacute;a Fernanda Rojas"
                     value="<?= h($cliente['contacto_nombre'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Cargo</label>
              <input type="text" name="contacto_cargo" class="rsal-input" maxlength="100"
                     placeholder="Coordinadora acad&eacute;mica"
                     value="<?= h($cliente['contacto_cargo'] ?? '') ?>"/>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.8rem;">
            <div>
              <label class="field-label">Tel&eacute;fono</label>
              <input type="text" name="contacto_telefono" class="rsal-input" maxlength="30"
                     value="<?= h($cliente['contacto_telefono'] ?? '') ?>"/>
            </div>
            <div>
              <label class="field-label">Email</label>
              <input type="email" name="contacto_email" class="rsal-input" maxlength="150"
                     value="<?= h($cliente['contacto_email'] ?? '') ?>"/>
            </div>
          </div>
        </div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-journal-text"></i> Notas</div>
          <textarea name="notas" class="rsal-textarea" style="min-height:80px;"
                    placeholder="Observaciones generales sobre este cliente..."><?= h($cliente['notas'] ?? '') ?></textarea>
        </div>

      </div>

      <!-- DERECHA -->
      <div>

        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-toggle-on"></i> Estado</div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:.8rem 1rem;background:var(--gray);border-radius:10px;">
            <div>
              <div style="font-size:.85rem;font-weight:700;color:var(--dark);">Cliente activo</div>
              <div style="font-size:.72rem;color:var(--muted);">Visible en listados</div>
            </div>
            <label style="position:relative;width:44px;height:24px;cursor:pointer;">
              <input type="checkbox" name="activo" style="opacity:0;width:0;height:0;position:absolute;"
                     <?= ($cliente['activo']??1) ? 'checked' : '' ?>
                     onchange="this.nextElementSibling.style.background=this.checked?'var(--teal)':'var(--gray2)';this.nextElementSibling.children[0].style.transform=this.checked?'translateX(20px)':'';">
              <span style="position:absolute;inset:0;background:<?= ($cliente['activo']??1)?'var(--teal)':'var(--gray2)' ?>;border-radius:12px;transition:.3s;">
                <span style="position:absolute;height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 4px rgba(0,0,0,.2);transform:<?= ($cliente['activo']??1)?'translateX(20px)':'' ?>;"></span>
              </span>
            </label>
          </div>
        </div>

        <div class="card-rsal">
          <button type="submit" class="btn-rsal-primary" style="width:100%;justify-content:center;padding:.82rem;font-size:.95rem;">
            <i class="bi bi-check-lg"></i> <?= $cliente ? 'Guardar cambios' : 'Crear cliente' ?>
          </button>
          <a href="<?= $U ?>modulos/extracurriculares/clientes/index.php" class="btn-rsal-secondary"
             style="width:100%;justify-content:center;padding:.68rem;margin-top:.6rem;">Cancelar</a>
        </div>

        <?php if ($cliente): ?>
        <div class="card-rsal">
          <div class="card-rsal-title"><i class="bi bi-arrow-right-circle"></i> Siguiente paso</div>
          <p style="font-size:.8rem;color:var(--muted);line-height:1.6;margin-bottom:.8rem;">
            Una vez guardado podr&aacute;s crear contratos asociados a este cliente para definir programas y calendarios.
          </p>
        </div>
        <?php endif; ?>

      </div>

    </div>
  </form>
</main>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Inicializar mapa Leaflet con OpenStreetMap
var mapa = L.map('mapa').setView([<?= $lat_default ?>, <?= $lng_default ?>], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  maxZoom: 19,
  attribution: '&copy; OpenStreetMap'
}).addTo(mapa);

var marker = null;
var latInput = document.getElementById('latitud');
var lngInput = document.getElementById('longitud');
var coordDisplay = document.getElementById('coord-display');

<?php if ($cliente && $cliente['latitud'] && $cliente['longitud']): ?>
marker = L.marker([<?= $cliente['latitud'] ?>, <?= $cliente['longitud'] ?>]).addTo(mapa);
<?php endif; ?>

mapa.on('click', function(e) {
  if (marker) { mapa.removeLayer(marker); }
  marker = L.marker(e.latlng).addTo(mapa);
  latInput.value = e.latlng.lat.toFixed(7);
  lngInput.value = e.latlng.lng.toFixed(7);
  coordDisplay.textContent = 'Lat: ' + e.latlng.lat.toFixed(6) + ', Lng: ' + e.latlng.lng.toFixed(6);
});

function limpiarCoord() {
  if (marker) { mapa.removeLayer(marker); marker = null; }
  latInput.value = '';
  lngInput.value = '';
  coordDisplay.textContent = 'Sin coordenadas seleccionadas';
}

document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target)) sb.classList.remove('open');
});

setTimeout(function() { mapa.invalidateSize(); }, 100);
</script>
</body>
</html>
