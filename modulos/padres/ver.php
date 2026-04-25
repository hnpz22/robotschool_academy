<?php
require_once __DIR__ . '/../../config/config.php';
require_once ROOT . '/config/auth.php';
requireRol('admin_sede');

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_URL . 'modulos/padres/index.php'); exit; }

// Cargar padre con datos de usuario
$stmt = $pdo->prepare("
    SELECT p.*, u.email AS login_email, u.activo AS login_activo, u.ultimo_login
    FROM padres p
    JOIN usuarios u ON u.id = p.usuario_id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$padre = $stmt->fetch();
if (!$padre) { header('Location: ' . BASE_URL . 'modulos/padres/index.php'); exit; }

// Cargar hijos (estudiantes) con sus matriculas
$hijos = $pdo->prepare("
    SELECT e.*,
        (SELECT COUNT(*) FROM matriculas m WHERE m.estudiante_id = e.id AND m.estado = 'activa') AS matriculas_activas,
        (SELECT GROUP_CONCAT(c.nombre ORDER BY c.nombre SEPARATOR ', ')
         FROM matriculas m
         JOIN grupos g ON g.id = m.grupo_id
         JOIN cursos c ON c.id = g.curso_id
         WHERE m.estudiante_id = e.id AND m.estado = 'activa') AS cursos_activos
    FROM estudiantes e
    WHERE e.padre_id = ?
    ORDER BY e.nombre_completo
");
$hijos->execute([$id]);
$hijos = $hijos->fetchAll();

$titulo      = 'Detalle del Padre';
$menu_activo = 'padres';
$U           = BASE_URL;

require_once ROOT . '/includes/head.php';
require_once ROOT . '/includes/sidebar.php';
?>

<header class="main-header">
  <button class="btn-logout d-lg-none" style="color:var(--dark);font-size:1.3rem;"
          onclick="document.getElementById('sidebar').classList.toggle('open')">
    <i class="bi bi-list"></i>
  </button>
  <div class="header-title">
    Padre / Acudiente <small><?= h($padre['nombre_completo']) ?></small>
  </div>
  <div style="display:flex;gap:.5rem;">
    <a href="<?= $U ?>modulos/padres/form.php?id=<?= $id ?>" class="btn-rsal-primary">
      <i class="bi bi-pencil-fill"></i> Editar
    </a>
    <a href="<?= $U ?>modulos/padres/index.php" class="btn-rsal-secondary">
      <i class="bi bi-arrow-left"></i> Volver
    </a>
  </div>
</header>

<main class="main-content">
  <div class="row g-4">

    <!-- &#9472;&#9472; DATOS PERSONALES &#9472;&#9472; -->
    <div class="col-lg-6">
      <div class="card-rsal">
        <div class="card-rsal-header">
          <i class="bi bi-person-fill"></i> Datos personales
        </div>
        <div class="card-rsal-body">
          <table class="table table-sm mb-0">
            <tbody>
              <tr>
                <th style="width:40%;color:var(--muted);font-weight:600;font-size:.82rem;">Nombre completo</th>
                <td><?= h($padre['nombre_completo']) ?></td>
              </tr>
              <tr>
                <th style="color:var(--muted);font-weight:600;font-size:.82rem;">Documento</th>
                <td><?= h($padre['tipo_doc']) ?> <?= h($padre['numero_doc']) ?></td>
              </tr>
              <tr>
                <th style="color:var(--muted);font-weight:600;font-size:.82rem;">Tel&eacute;fono</th>
                <td>
                  <?= h($padre['telefono']) ?>
                  <?php if ($padre['telefono_alt']): ?>
                    <br><span style="color:var(--muted);font-size:.82rem;"><?= h($padre['telefono_alt']) ?></span>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <th style="color:var(--muted);font-weight:600;font-size:.82rem;">Email</th>
                <td><?= h($padre['email']) ?></td>
              </tr>
              <?php if ($padre['direccion']): ?>
              <tr>
                <th style="color:var(--muted);font-weight:600;font-size:.82rem;">Direcci&oacute;n</th>
                <td><?= h($padre['direccion']) ?></td>
              </tr>
              <?php endif; ?>
              <?php if ($padre['ocupacion']): ?>
              <tr>
                <th style="color:var(--muted);font-weight:600;font-size:.82rem;">Ocupaci&oacute;n</th>
                <td><?= h($padre['ocupacion']) ?></td>
              </tr>
              <?php endif; ?>
              <tr>
                <th style="color:var(--muted);font-weight:600;font-size:.82rem;">Registro</th>
                <td><?= formatFecha($padre['created_at']) ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- &#9472;&#9472; DATOS DE ACCESO &#9472;&#9472; -->
    <div class="col-lg-6">
      <div class="card-rsal">
        <div class="card-rsal-header">
          <i class="bi bi-shield-lock-fill"></i> Acceso al portal
        </div>
        <div class="card-rsal-body">
          <table class="table table-sm mb-0">
            <tbody>
              <tr>
                <th style="width:40%;color:var(--muted);font-weight:600;font-size:.82rem;">Email de acceso</th>
                <td><?= h($padre['login_email']) ?></td>
              </tr>
              <tr>
                <th style="color:var(--muted);font-weight:600;font-size:.82rem;">Estado</th>
                <td>
                  <span class="badge-estado <?= $padre['login_activo'] ? 'be-activa' : 'be-inactiva' ?>">
                    <?= $padre['login_activo'] ? 'Activo' : 'Inactivo' ?>
                  </span>
                </td>
              </tr>
              <tr>
                <th style="color:var(--muted);font-weight:600;font-size:.82rem;">&Uacute;ltimo ingreso</th>
                <td><?= $padre['ultimo_login'] ? formatFecha($padre['ultimo_login']) : '&mdash;' ?></td>
              </tr>
              <tr>
                <th style="color:var(--muted);font-weight:600;font-size:.82rem;">Trat. de datos</th>
                <td>
                  <span class="badge-estado <?= $padre['acepta_datos'] ? 'be-activa' : 'be-vencido' ?>">
                    <i class="bi bi-<?= $padre['acepta_datos'] ? 'check' : 'x' ?>-circle-fill"></i>
                    <?= $padre['acepta_datos'] ? 'Aceptado' : 'No aceptado' ?>
                  </span>
                </td>
              </tr>
              <tr>
                <th style="color:var(--muted);font-weight:600;font-size:.82rem;">Uso de im&aacute;genes</th>
                <td>
                  <span class="badge-estado <?= $padre['acepta_imagenes'] ? 'be-activa' : 'be-vencido' ?>">
                    <i class="bi bi-<?= $padre['acepta_imagenes'] ? 'check' : 'x' ?>-circle-fill"></i>
                    <?= $padre['acepta_imagenes'] ? 'Autorizado' : 'No autorizado' ?>
                  </span>
                </td>
              </tr>
              <?php if ($padre['fecha_aceptacion']): ?>
              <tr>
                <th style="color:var(--muted);font-weight:600;font-size:.82rem;">Fecha aceptaci&oacute;n</th>
                <td><?= formatFecha($padre['fecha_aceptacion']) ?></td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- &#9472;&#9472; HIJOS / ESTUDIANTES &#9472;&#9472; -->
    <div class="col-12">
      <div class="card-rsal">
        <div class="card-rsal-header">
          <i class="bi bi-people-fill"></i> Estudiantes a cargo
          <span class="badge bg-secondary ms-2"><?= count($hijos) ?></span>
        </div>
        <div class="card-rsal-body p-0">
          <?php if (!$hijos): ?>
            <div style="padding:2rem;text-align:center;color:var(--muted);">
              <i class="bi bi-people" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
              Este padre no tiene estudiantes registrados.
            </div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="tabla-rsal">
              <thead>
                <tr>
                  <th>Estudiante</th>
                  <th>Documento</th>
                  <th>Edad</th>
                  <th>Sede</th>
                  <th>Cursos activos</th>
                  <th>M&aacute;triculas</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($hijos as $h): ?>
                <tr>
                  <td>
                    <div style="display:flex;align-items:center;gap:.6rem;">
                      <?php if ($h['foto']): ?>
                        <img src="<?= $U ?>uploads/estudiantes/<?= h($h['foto']) ?>"
                             style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                      <?php else: ?>
                        <div style="width:32px;height:32px;border-radius:50%;background:var(--teal-l);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;color:var(--teal);">
                          <?= strtoupper(substr($h['nombre_completo'], 0, 1)) ?>
                        </div>
                      <?php endif; ?>
                      <strong><?= h($h['nombre_completo']) ?></strong>
                    </div>
                  </td>
                  <td><?= h($h['tipo_doc'] ?? 'TI') ?> <?= h($h['numero_doc'] ?? '&mdash;') ?></td>
                  <td>
                    <?php
                      $edad = $h['fecha_nacimiento']
                        ? (int)((time() - strtotime($h['fecha_nacimiento'])) / 31557600)
                        : null;
                      echo $edad ? $edad . ' a&ntilde;os' : '&mdash;';
                    ?>
                  </td>
                  <td>
                    <?php
                      $sede_stmt = $pdo->prepare("SELECT nombre FROM sedes WHERE id = ?");
                      $sede_stmt->execute([$h['sede_id']]);
                      $sede_row = $sede_stmt->fetch();
                      echo h($sede_row['nombre'] ?? '&mdash;');
                    ?>
                  </td>
                  <td>
                    <?php if ($h['cursos_activos']): ?>
                      <span style="font-size:.82rem;"><?= h($h['cursos_activos']) ?></span>
                    <?php else: ?>
                      <span style="color:var(--muted);">&mdash;</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge-estado <?= $h['matriculas_activas'] ? 'be-activa' : 'be-inactiva' ?>">
                      <?= $h['matriculas_activas'] ?> activa<?= $h['matriculas_activas'] != 1 ? 's' : '' ?>
                    </span>
                  </td>
                  <td>
                    <a href="<?= $U ?>modulos/estudiantes/hoja_vida.php?id=<?= $h['id'] ?>"
                       class="btn-rsal-secondary" style="padding:.3rem .6rem;font-size:.75rem;">
                      <i class="bi bi-eye-fill"></i> Ver
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div><!-- /row -->
</main>

<?php require_once ROOT . '/assets/js/rsal.js' ; ?>
</body>
</html>
