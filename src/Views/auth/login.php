<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$logoPath = __DIR__ . '/../../Logo_ITSUR.jpg';
$logoData = null;
if (file_exists($logoPath)) { $logoData = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath)); }
ob_start();
?>
<div class="min-vh-100 d-flex align-items-center">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card shadow-sm">
          <div class="row g-0">
            <div class="col-md-5 d-flex align-items-center justify-content-center p-4">
              <div class="text-center w-100">
                <?php if ($logoData): ?>
                  <img src="<?php echo $logoData; ?>" alt="ITSUR" class="img-fluid" style="max-height:100px;object-fit:contain">
                <?php endif; ?>
                <div class="mt-3">
                  <div class="h5 mb-0">SICEnet · ITSUR</div>
                  <div class="text-muted">Acceso al Sistema</div>
                </div>
              </div>
            </div>
            <div class="col-md-7">
              <div class="card-body">
                <form method="post" action="<?php echo $base; ?>/login" novalidate>
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                  <div class="mb-3">
                    <label class="form-label">Email (admin/profesor) o Matrícula (alumno)</label>
                    <input type="text" name="identity" class="form-control" placeholder="Email o Matrícula" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                  </div>
                  <?php if (!empty($captchaQuestion)): ?>
                  <div class="mb-3">
                    <label class="form-label">Verificación (Captcha)</label>
                    <div class="input-group">
                      <span class="input-group-text"><?php echo htmlspecialchars($captchaQuestion); ?></span>
                      <input type="text" name="captcha" class="form-control" required>
                    </div>
                  </div>
                  <?php endif; ?>
                  <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-right-to-bracket me-1"></i> Acceder</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
