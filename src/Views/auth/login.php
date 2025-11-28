<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

// Ruta de la imagen (ajusta según tu estructura)
$logoPath = __DIR__ . '/../../../public/assets/ITSUR-LOGO.webp';
$logoBase64 = '';

if (file_exists($logoPath)) {
    $type = pathinfo($logoPath, PATHINFO_EXTENSION);
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($logoData);
} else {
    // Fallback si no existe la imagen
    $logoBase64 = 'https://via.placeholder.com/150';
}

ob_start();
?>
<style>
  /* Reset & Base */
  body {
    margin: 0;
    padding: 0;
    font-family: 'Inter', sans-serif;
    overflow-x: hidden;
    background: #0f172a; /* Fallback dark */
    color: #fff;
  }

  /* Particle Background */
  #particles-js {
    position: fixed;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: -1;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  }

  /* Main Container */
  .login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    position: relative;
    z-index: 1;
  }

  /* Glassmorphism Card */
  .login-card {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 24px;
    padding: 3rem;
    width: 100%;
    max-width: 420px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    position: relative;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  .login-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.6);
    border-color: rgba(255, 255, 255, 0.2);
  }

  /* Inner Glow */
  .login-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -50%;
    width: 200%;
    height: 100%;
    background: radial-gradient(circle at 50% 0%, rgba(255, 255, 255, 0.1), transparent 70%);
    pointer-events: none;
  }

  /* Logo Wrapper */
  .logo-wrapper {
    width: 100px;
    height: 100px;
    margin: 0 auto 2rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
    z-index: 2;
  }

  .logo-img {
    width: 70px;
    height: auto;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
  }

  /* Typography */
  .login-title {
    text-align: center;
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    background: linear-gradient(to right, #fff, #94a3b8);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.025em;
  }

  .login-subtitle {
    text-align: center;
    color: #94a3b8;
    font-size: 0.875rem;
    margin-bottom: 2.5rem;
  }

  /* Form Elements */
  .form-group {
    margin-bottom: 1.5rem;
    position: relative;
  }

  .form-control {
    width: 100%;
    background: rgba(15, 23, 42, 0.6);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1rem 1rem 1rem 3rem;
    color: #fff;
    font-size: 0.95rem;
    transition: all 0.3s ease;
  }

  .form-control:focus {
    background: rgba(15, 23, 42, 0.8);
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    outline: none;
  }

  .input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    transition: color 0.3s ease;
  }

  .form-control:focus + .input-icon {
    color: #3b82f6;
  }

  /* Button */
  .btn-login {
    width: 100%;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border: none;
    padding: 1rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    margin-top: 1rem;
  }

  .btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4);
  }

  .btn-login::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: 0.5s;
  }

  .btn-login:hover::after {
    left: 100%;
  }

  /* Captcha */
  .captcha-group {
    background: rgba(255, 255, 255, 0.03);
    padding: 1rem;
    border-radius: 12px;
    border: 1px dashed rgba(255, 255, 255, 0.2);
  }

  .captcha-label {
    display: block;
    font-size: 0.75rem;
    color: #94a3b8;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  /* Animations */
  @keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
    100% { transform: translateY(0px); }
  }

  .logo-wrapper {
    animation: float 6s ease-in-out infinite;
  }

  /* Responsive */
  @media (max-width: 480px) {
    .login-card {
      padding: 2rem;
    }
  }
</style>

<!-- Particles Container -->
<div id="particles-js"></div>

<div class="login-container">
  <div class="login-card">
    <div class="logo-wrapper">
      <img src="<?php echo $logoBase64; ?>" alt="ITSUR Logo" class="logo-img">
    </div>
    
    <h1 class="login-title">Bienvenido</h1>
    <p class="login-subtitle">Sistema Integral de Control Escolar</p>

    <form method="post" action="<?php echo $base; ?>/login" novalidate>
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
      
      <div class="form-group">
        <i class="fa-regular fa-user input-icon"></i>
        <input type="text" name="identity" class="form-control" placeholder="Email o Matrícula" required autocomplete="username">
      </div>

      <div class="form-group">
        <i class="fa-solid fa-lock input-icon"></i>
        <input type="password" name="password" class="form-control" placeholder="Contraseña" required autocomplete="current-password">
      </div>

      <?php if (!empty($captchaQuestion)): ?>
      <div class="form-group captcha-group">
        <span class="captcha-label">Verificación de seguridad</span>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-3">
            <?php echo htmlspecialchars($captchaQuestion); ?>
          </span>
          <input type="text" name="captcha" class="form-control m-0" placeholder="Respuesta" required style="height: 42px;">
        </div>
      </div>
      <?php endif; ?>

      <button class="btn-login" type="submit">
        Acceder al Sistema
      </button>
    </form>
  </div>
</div>

<!-- Particles.js -->
<script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
<script>
  particlesJS('particles-js', {
    "particles": {
      "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
      "color": { "value": "#ffffff" },
      "shape": { "type": "circle" },
      "opacity": { "value": 0.1, "random": true },
      "size": { "value": 3, "random": true },
      "line_linked": { "enable": true, "distance": 150, "color": "#ffffff", "opacity": 0.05, "width": 1 },
      "move": { "enable": true, "speed": 2, "direction": "none", "random": false, "straight": false, "out_mode": "out", "bounce": false }
    },
    "interactivity": {
      "detect_on": "canvas",
      "events": { "onhover": { "enable": true, "mode": "grab" }, "onclick": { "enable": true, "mode": "push" }, "resize": true },
      "modes": { "grab": { "distance": 140, "line_linked": { "opacity": 0.3 } } }
    },
    "retina_detect": true
  });
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
