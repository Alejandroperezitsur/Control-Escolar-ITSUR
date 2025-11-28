<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="h4 mb-3">Acceso al Sistema</h1>
        <form method="post" action="<?php echo $base; ?>/login" novalidate>
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
          <div class="mb-3">
            <label class="form-label">Email (admin/profesor) o Matrícula (alumno)</label>
            <input type="text" name="identity" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <?php if (!empty($captchaQuestion)): ?>
          <div class="mb-3">
            <label class="form-label">Verificación (Captcha)</label>
            <div class="input-group">
              <span class="input-group-text"><?php echo htmlspecialchars($captchaQuestion); ?></span>
              <input type="text" name="captcha" class="form-control" required>
            </div>
            <div class="form-text">Se requiere tras múltiples intentos fallidos.</div>
          </div>
          <?php endif; ?>
          <button class="btn btn-login w-100" type="submit"><i class="fa-solid fa-right-to-bracket me-1"></i> Acceder</button>
        </form>
      </div>
    </div>
  </div>
  </div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
