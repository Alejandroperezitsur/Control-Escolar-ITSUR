<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

// Encode images to base64 for InfinityFree compatibility
// Ruta de la imagen (ajusta según tu estructura)
$logoPath = __DIR__ . '/../../../public/assets/ITSUR-LOGO.webp';
$footerPath = __DIR__ . '/../../uploads/fotos/footer-image.png';

$logoBase64 = '';
$footerBase64 = '';

if (file_exists($logoPath)) {
    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($logoData);
} else {
    // Fallback si no existe la imagen
    $logoBase64 = 'https://via.placeholder.com/150';
}

if (file_exists($footerPath)) {
    $footerData = file_get_contents($footerPath);
    $footerBase64 = 'data:image/png;base64,' . base64_encode($footerData);
}

ob_start();
?>
<style>
/* Ultra-Premium Login Design */

/* ============================================
   PARTICLE SYSTEM BACKGROUND
   ============================================ */
.login-wrapper {
  min-height: calc(100vh - 120px);
  height: calc(100vh - 120px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  margin: -1.5rem -1rem;
  position: relative;
  overflow: hidden;
  background: var(--bg);
}

/* Animated gradient background */
.login-wrapper::before {
  content: '';
  position: absolute;
  inset: 0;
  background: 
    radial-gradient(ellipse 800px 600px at 10% 20%, rgba(20, 83, 45, 0.15), transparent),
    radial-gradient(ellipse 600px 800px at 90% 80%, rgba(96, 165, 250, 0.12), transparent),
    radial-gradient(ellipse 1000px 400px at 50% 50%, rgba(22, 101, 52, 0.08), transparent);
  animation: gradientShift 15s ease-in-out infinite;
  z-index: 0;
}

@keyframes gradientShift {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.8; transform: scale(1.1); }
}

/* Floating particles */
.login-wrapper::after {
  content: '';
  position: absolute;
  inset: 0;
  background-image: 
    radial-gradient(2px 2px at 20% 30%, rgba(255, 255, 255, 0.15), transparent),
    radial-gradient(2px 2px at 60% 70%, rgba(255, 255, 255, 0.1), transparent),
    radial-gradient(3px 3px at 50% 50%, rgba(255, 255, 255, 0.08), transparent),
    radial-gradient(2px 2px at 80% 10%, rgba(255, 255, 255, 0.12), transparent),
    radial-gradient(2px 2px at 90% 90%, rgba(255, 255, 255, 0.1), transparent),
    radial-gradient(3px 3px at 15% 80%, rgba(255, 255, 255, 0.09), transparent);
  background-size: 200% 200%;
  animation: particleFloat 20s linear infinite;
  z-index: 0;
}

@keyframes particleFloat {
  0% { background-position: 0% 0%; }
  100% { background-position: 100% 100%; }
}

/* ============================================
   MAIN CONTAINER
   ============================================ */
.login-container {
  max-width: 460px;
  width: 100%;
  margin: 0;
  padding: 0 1rem;
  position: relative;
  z-index: 1;
  animation: slideUpFade 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideUpFade {
  from {
    opacity: 0;
    transform: translateY(40px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ============================================
   LOGO SECTION
   ============================================ */
.login-logo-section {
  text-align: center;
  margin-bottom: 1.75rem;
  animation: fadeInScale 1s cubic-bezier(0.16, 1, 0.3, 1) 0.2s both;
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.login-logo-wrapper {
  display: inline-block;
  margin-bottom: 1rem;
}

.login-logo {
  max-width: 110px;
  height: auto;
  display: block;
  border-radius: 50%;
  filter: drop-shadow(0 8px 24px rgba(20, 83, 45, 0.3));
  transition: transform 0.3s ease;
}

.login-logo:hover {
  transform: scale(1.05);
}

.login-header {
  margin-top: 1rem;
}

.login-title {
  font-size: 1.65rem;
  font-weight: 800;
  background: linear-gradient(135deg, var(--text) 0%, var(--accent) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 0.5rem;
  letter-spacing: -0.03em;
  text-shadow: 0 0 40px rgba(96, 165, 250, 0.3);
}

.login-subtitle {
  color: var(--muted);
  font-size: 0.9rem;
  font-weight: 500;
  opacity: 0.9;
}

/* ============================================
   PREMIUM GLASSMORPHISM CARD
   ============================================ */
.login-card {
  position: relative;
  background: rgba(255, 255, 255, 0.03);
  backdrop-filter: blur(30px) saturate(150%);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 28px;
  padding: 2rem 1.85rem;
  box-shadow: 
    0 20px 60px rgba(0, 0, 0, 0.4),
    0 0 0 1px rgba(255, 255, 255, 0.1) inset,
    0 2px 4px rgba(255, 255, 255, 0.05) inset;
  overflow: hidden;
}

[data-theme="light"] .login-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(30px) saturate(150%);
  border: 1px solid rgba(0, 0, 0, 0.1);
  box-shadow: 
    0 20px 60px rgba(0, 0, 0, 0.12),
    0 0 0 1px rgba(0, 0, 0, 0.05) inset;
}

/* Shimmer effect */
.login-card::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.08),
    transparent
  );
  animation: shimmerMove 4s infinite;
  pointer-events: none;
}

@keyframes shimmerMove {
  0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
  100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
}

/* Inner glow */
.login-card::after {
  content: '';
  position: absolute;
  inset: 0;
  border-radius: 28px;
  background: radial-gradient(
    circle at 50% 0%,
    rgba(34, 197, 94, 0.1),
    transparent 60%
  );
  pointer-events: none;
}

/* ============================================
   FORM STYLING
   ============================================ */
.login-form {
  position: relative;
  z-index: 1;
}

.login-form .form-label {
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: var(--text);
  font-size: 0.88rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.login-form .form-label i {
  color: var(--accent);
  font-size: 0.95rem;
}

.login-form .form-control {
  background: rgba(255, 255, 255, 0.05);
  border: 2px solid rgba(255, 255, 255, 0.1);
  color: var(--text);
  border-radius: 14px;
  padding: 0.95rem 1.1rem;
  font-size: 0.95rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
}

[data-theme="light"] .login-form .form-control {
  background: rgba(255, 255, 255, 0.7);
  border: 2px solid rgba(0, 0, 0, 0.1);
  color: #0b1220;
}

.login-form .form-control::placeholder {
  color: var(--muted);
  opacity: 0.5;
}

.login-form .form-control:focus {
  background: rgba(255, 255, 255, 0.1);
  border-color: var(--accent);
  box-shadow: 
    0 0 0 4px rgba(96, 165, 250, 0.15),
    0 0 20px rgba(96, 165, 250, 0.2);
  outline: none;
  transform: translateY(-2px);
}

[data-theme="light"] .login-form .form-control:focus {
  background: #ffffff;
  box-shadow: 
    0 0 0 4px rgba(96, 165, 250, 0.1),
    0 0 20px rgba(96, 165, 250, 0.15);
}

.login-form .form-control:hover {
  border-color: rgba(96, 165, 250, 0.3);
}

/* ============================================
   3D PREMIUM BUTTON
   ============================================ */
.btn-login-primary {
  position: relative;
  background: linear-gradient(135deg, #166534 0%, #14532d 50%, #0f3f22 100%);
  border: none;
  color: white;
  padding: 1rem 1.5rem;
  font-size: 1.05rem;
  font-weight: 700;
  border-radius: 14px;
  width: 100%;
  margin-top: 1.8rem;
  box-shadow: 
    0 10px 30px rgba(20, 83, 45, 0.4),
    0 0 0 1px rgba(255, 255, 255, 0.1) inset,
    0 2px 4px rgba(255, 255, 255, 0.1) inset;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  cursor: pointer;
  letter-spacing: 0.3px;
}

/* Shine effect */
.btn-login-primary::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.3),
    transparent
  );
  transition: left 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.btn-login-primary:hover::before {
  left: 100%;
}

.btn-login-primary:hover {
  transform: translateY(-3px);
  box-shadow: 
    0 15px 40px rgba(20, 83, 45, 0.5),
    0 0 0 1px rgba(255, 255, 255, 0.15) inset,
    0 0 30px rgba(34, 197, 94, 0.3);
  background: linear-gradient(135deg, #14532d 0%, #166534 50%, #14532d 100%);
}

.btn-login-primary:active {
  transform: translateY(-1px);
  box-shadow: 
    0 8px 20px rgba(20, 83, 45, 0.4),
    0 0 0 1px rgba(255, 255, 255, 0.1) inset;
}

.btn-login-primary i {
  margin-right: 0.6rem;
  transition: transform 0.3s ease;
}

.btn-login-primary:hover i {
  transform: translateX(4px);
}

/* ============================================
   CAPTCHA SECTION
   ============================================ */
.captcha-section {
  background: rgba(245, 158, 11, 0.08);
  border: 2px solid rgba(245, 158, 11, 0.25);
  border-radius: 14px;
  padding: 1.2rem;
  margin-bottom: 1.2rem;
  position: relative;
  overflow: hidden;
}

.captcha-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, 
    transparent,
    rgba(245, 158, 11, 0.5),
    transparent
  );
  animation: captchaPulse 2s ease-in-out infinite;
}

@keyframes captchaPulse {
  0%, 100% { opacity: 0.3; }
  50% { opacity: 1; }
}

.captcha-section .form-label {
  color: rgba(245, 158, 11, 1);
}

.captcha-section .input-group-text {
  background: rgba(245, 158, 11, 0.15);
  border: 2px solid rgba(245, 158, 11, 0.25);
  color: var(--text);
  border-radius: 14px 0 0 14px;
  font-weight: 600;
}

.captcha-section .form-control {
  border-radius: 0 14px 14px 0;
  border-left: none;
}

.captcha-section .form-text {
  color: var(--muted);
  font-size: 0.85rem;
  margin-top: 0.6rem;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}



/* ============================================
   RESPONSIVE DESIGN
   ============================================ */
@media (max-width: 576px) {
  .login-wrapper {
    min-height: calc(100vh - 100px);
    height: auto;
    margin: -1rem;
    padding: 1.5rem 0;
  }
  
  .login-container {
    max-width: 100%;
    padding: 0 1rem;
  }
  
  .login-card {
    padding: 2rem 1.75rem;
    border-radius: 24px;
  }
  
  .login-title {
    font-size: 1.5rem;
  }
  
  .login-subtitle {
    font-size: 0.9rem;
  }
  
  .login-logo {
    max-width: 100px;
  }
  
  .login-logo-section {
    margin-bottom: 1.5rem;
  }
  
  .btn-login-primary {
    padding: 0.9rem 1.2rem;
    font-size: 1rem;
  }
}

@media (max-width: 400px) {
  .login-wrapper {
    margin: -0.75rem;
    padding: 1.25rem 0;
  }
  
  .login-container {
    padding: 0 0.75rem;
  }
  
  .login-card {
    padding: 1.75rem 1.5rem;
  }
  
  .login-logo {
    max-width: 95px;
  }
  
  .login-logo-section {
    margin-bottom: 1.25rem;
  }
}

/* ============================================
   ACCESSIBILITY
   ============================================ */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Focus visible for keyboard navigation */
.login-form .form-control:focus-visible,
.btn-login-primary:focus-visible {
  outline: 3px solid var(--accent);
  outline-offset: 2px;
}

/* High contrast mode improvements */
@media (prefers-contrast: high) {
  .login-card {
    border: 2px solid var(--text);
  }
  
  .login-form .form-control {
    border: 2px solid var(--text);
  }
  
  .btn-login-primary {
    border: 2px solid var(--text);
  }
}
</style>

<div class="login-wrapper">
  <div class="login-container">
    <!-- Logo Section -->
    <div class="login-logo-section">
      <div class="login-logo-wrapper">
        <?php if ($logoBase64): ?>
          <img src="<?php echo $logoBase64; ?>" alt="ITSUR Logo" class="login-logo">
        <?php else: ?>
          <img src="<?php echo $base; ?>/assets/images/Logo_ITSUR.jpg" alt="ITSUR Logo" class="login-logo">
        <?php endif; ?>
      </div>
      <div class="login-header">
        <h1 class="login-title">Bienvenido</h1>
        <p class="login-subtitle">Control Escolar - ITSUR</p>
      </div>
    </div>
    
    <!-- Login Card -->
    <div class="login-card">
      <form method="post" action="<?php echo $base; ?>/login" class="login-form" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
        
        <!-- Identity Field -->
        <div class="mb-3">
          <label class="form-label">
            <i class="fa-solid fa-user"></i>
            Email (admin/profesor) o Matrícula (alumno)
          </label>
          <input 
            type="text" 
            name="identity" 
            class="form-control" 
            placeholder="Ingresa tu identificación"
            required 
            autocomplete="username"
            autofocus
          >
        </div>
        
        <!-- Password Field -->
        <div class="mb-3">
          <label class="form-label">
            <i class="fa-solid fa-lock"></i>
            Contraseña
          </label>
          <input 
            type="password" 
            name="password" 
            class="form-control" 
            placeholder="Ingresa tu contraseña"
            required
            autocomplete="current-password"
          >
        </div>
        
        <!-- Captcha Section (if needed) -->
        <?php if (!empty($captchaQuestion)): ?>
        <div class="captcha-section">
          <label class="form-label">
            <i class="fa-solid fa-shield-halved"></i>
            Verificación de Seguridad
          </label>
          <div class="input-group">
            <span class="input-group-text"><?php echo htmlspecialchars($captchaQuestion); ?></span>
            <input 
              type="text" 
              name="captcha" 
              class="form-control" 
              placeholder="Respuesta"
              required
            >
          </div>
          <div class="form-text">
            <i class="fa-solid fa-info-circle"></i>
            Se requiere tras múltiples intentos fallidos por seguridad.
          </div>
        </div>
        <?php endif; ?>
        
        <!-- Submit Button -->
        <button class="btn btn-login-primary" type="submit">
          <i class="fa-solid fa-right-to-bracket"></i>
          Acceder al Sistema
        </button>
      </form>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
