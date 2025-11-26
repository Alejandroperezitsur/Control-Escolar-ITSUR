<?php
$role = $_SESSION['role'] ?? 'guest';
$name = $_SESSION['name'] ?? '';
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$base = $scriptDir;
$p = strpos($scriptDir, '/public');
if ($p !== false) { $base = substr($scriptDir, 0, $p + 7); }
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Control Escolar</title>
  <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
  <base href="<?php echo $base; ?>/">
  <script>
    // Aplicar tema antes de cargar CSS para evitar flash
    (function(){
      try {
        var THEME_KEY = 'sicenet-theme';
        var t = localStorage.getItem(THEME_KEY);
        if (!t) {
          t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
        }
        document.documentElement.setAttribute('data-theme', t === 'light' ? 'light' : 'dark');
        if (document.body) document.body.setAttribute('data-theme', t === 'light' ? 'light' : 'dark');
      } catch(e) {}
    })();
  </script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://unpkg.com/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="<?php echo $base; ?>/assets/css/styles.css" rel="stylesheet">
  <link href="/Control-Escolar-ITSUR/public/assets/css/styles.css" rel="stylesheet">
  <style>
    .container{max-width:1200px;margin:0 auto;padding:0 1rem}
    .row{display:flex;flex-wrap:wrap;margin-left:-.5rem;margin-right:-.5rem}
    [class*="col-"]{padding:.5rem}
    .col-6{flex:0 0 auto;width:50%}
    .col-md-2{flex:0 0 auto;width:16.6667%}
    .col-md-3{flex:0 0 auto;width:25%}
    .col-md-4{flex:0 0 auto;width:33.3333%}
    .col-md-6{flex:0 0 auto;width:50%}
    @media (max-width: 768px){.col-md-2,.col-md-3,.col-md-4,.col-md-6{width:100%}}
    .table{width:100%;border-collapse:collapse}
    .table th,.table td{padding:.5rem;border:1px solid rgba(255,255,255,.08)}
    .table-striped tbody tr:nth-child(odd){background:rgba(255,255,255,.04)}
    .table-hover tbody tr:hover{background:rgba(255,255,255,.08)}
    .table-light th{background:rgba(255,255,255,.1)}
    .table-responsive{width:100%;overflow:auto}
    .btn{display:inline-block;padding:.375rem .75rem;border:1px solid transparent;border-radius:.375rem}
    .btn-outline-primary{border-color:#0d6efd;color:#0d6efd;background:transparent}
    .btn-outline-success{border-color:#198754;color:#198754;background:transparent}
    .btn-outline-secondary{border-color:#6c757d;color:#6c757d;background:transparent}
    .btn-primary{background:#0d6efd;color:#fff;border-color:#0d6efd}
    .badge{display:inline-block;padding:.35em .65em;font-size:.75em;border-radius:.5rem}
    .card{background:#ffffff0d;border:1px solid rgba(255,255,255,.12);border-radius:.5rem}
    .card .card-body{padding:1rem}
    .modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);align-items:center;justify-content:center;z-index:1050}
    .modal.show{display:flex}
    .modal-dialog{background:var(--surface);border-radius:12px;max-width:500px;width:100%}
    .modal-content{border-radius:12px}
    .modal-header,.modal-footer{padding:1rem;border-bottom:1px solid rgba(255,255,255,.08)}
    .modal-header{display:flex;align-items:center;justify-content:space-between}
  </style>
</head>
<body data-theme="dark">
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
      <a class="navbar-brand" href="<?php echo $base; ?>/dashboard"><i class="fa-solid fa-graduation-cap me-2"></i>ITSUR</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <?php if ($role === 'admin'): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/dashboard">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/reports">Reportes</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/alumnos">Alumnos <span class="badge bg-light text-dark ms-1" id="nav-count-alumnos" data-bs-toggle="tooltip" title="Alumnos activos">—</span></a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/subjects">Materias <span class="badge bg-light text-dark ms-1" id="nav-count-materias" data-bs-toggle="tooltip" title="Materias registradas">—</span><span class="badge bg-warning text-dark ms-1" id="nav-count-sinoferta" data-bs-toggle="tooltip" title="Materias sin oferta">—</span></a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/professors">Profesores <span class="badge bg-light text-dark ms-1" id="nav-count-profesores" data-bs-toggle="tooltip" title="Profesores activos">—</span></a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/groups">Grupos <span class="badge bg-light text-dark ms-1" id="nav-count-grupos" data-bs-toggle="tooltip" title="Grupos activos con calificaciones">—</span></a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/admin/settings">Ajustes</a></li>
          <?php elseif ($role === 'profesor'): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/dashboard">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/reports">Reportes</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/grades/bulk">Carga masiva</a></li>
          <?php elseif ($role === 'alumno'): ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo $base; ?>/dashboard">Mi tablero</a></li>
          <?php endif; ?>
        </ul>
        <div class="d-flex align-items-center">
          <?php if ($role === 'admin'): ?>
          <button id="nav-refresh-kpis" class="btn btn-outline-primary btn-sm me-2" type="button" data-bs-toggle="tooltip" title="Actualizar ahora">↻</button>
          <?php endif; ?>
          <button id="theme-toggle" class="btn btn-outline-secondary me-2" type="button" aria-label="Cambiar tema">🌙</button>
          <?php if ($role !== 'guest'): ?>
            <span class="navbar-text me-3"><i class="fa-regular fa-user me-1"></i><?php echo htmlspecialchars($name ?: $role); ?></span>
            <a href="<?php echo $base; ?>/logout" class="btn btn-exit">Salir</a>
          <?php else: ?>
            <a href="<?php echo $base; ?>/login" class="btn btn-login">Acceder</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </nav>
  <main class="container py-4">
    <?php echo $content ?? ''; ?>
  </main>
  <?php if (!empty($_SESSION['flash'])): ?>
  <?php $flashType = $_SESSION['flash_type'] ?? 'primary'; $validTypes = ['primary','success','warning','danger','info']; if (!in_array($flashType, $validTypes)) { $flashType = 'primary'; } ?>
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
    <div class="toast show align-items-center text-bg-<?php echo htmlspecialchars($flashType); ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?php echo htmlspecialchars($_SESSION['flash']); ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>
  <?php unset($_SESSION['flash'], $_SESSION['flash_type']); endif; ?>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
  <script src="https://unpkg.com/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="<?php echo $base; ?>/assets/js/main.js"></script>
  <script src="/Control-Escolar-ITSUR/public/assets/js/main.js"></script>
  <script>
    (function(){
      document.addEventListener('click', function(e){
        var trigger = e.target.closest('[data-bs-toggle="modal"]');
        if (!trigger) return;
        var targetSel = trigger.getAttribute('data-bs-target');
        var el = targetSel ? document.querySelector(targetSel) : null;
        if (!el) return;
        e.preventDefault();
        try {
          if (window.bootstrap && window.bootstrap.Modal) {
            var inst = window.bootstrap.Modal.getInstance(el) || new window.bootstrap.Modal(el);
            inst.show();
          } else {
            el.classList.add('show');
          }
        } catch { el.classList.add('show'); }
      });

      document.addEventListener('click', function(e){
        var isDismiss = !!e.target.closest('[data-bs-dismiss="modal"], .btn-close');
        if (!isDismiss) return;
        var el = e.target.closest('.modal');
        if (!el) return;
        e.preventDefault();
        try {
          if (window.bootstrap && window.bootstrap.Modal) {
            var inst = window.bootstrap.Modal.getInstance(el) || new window.bootstrap.Modal(el);
            inst.hide();
          } else {
            el.classList.remove('show');
          }
        } catch { el.classList.remove('show'); }
      });
    })();
  </script>
  <?php if (($role ?? '') === 'admin'): ?>
  <script>
  (function(){
    const base = '<?php echo $base; ?>';
    var tEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tEls.forEach(function(el){ new bootstrap.Tooltip(el); });
    function setBadge(id, val){ var el = document.getElementById(id); if (!el) return; var prev = el.textContent; var next = (typeof val === 'number' ? String(val) : '—'); el.textContent = next; if (prev !== next) { el.classList.add('kpi-flash'); setTimeout(function(){ el.classList.remove('kpi-flash'); }, 800); } }
    function setTip(id, text){ var el = document.getElementById(id); if (!el) return; el.setAttribute('title', text); var inst = bootstrap.Tooltip.getInstance(el); if (inst) inst.dispose(); new bootstrap.Tooltip(el); }
    function setErrorMode(on){ var ids=['nav-count-alumnos','nav-count-profesores','nav-count-materias','nav-count-grupos']; ids.forEach(function(id){ var el=document.getElementById(id); if(!el) return; el.classList.toggle('bg-warning', !!on); el.classList.toggle('text-dark', !!on); el.classList.toggle('bg-light', !on); }); }
    var loadingTimer = null;
    function showLoading(){ var ids=['nav-count-alumnos','nav-count-profesores','nav-count-materias','nav-count-grupos','nav-count-sinoferta']; ids.forEach(function(id){ var el=document.getElementById(id); if(!el) return; if (!el.nextElementSibling || el.nextElementSibling.getAttribute('data-role') !== 'kpi-loading') { var sp=document.createElement('span'); sp.setAttribute('data-role','kpi-loading'); sp.className='spinner-grow spinner-grow-sm text-light ms-1'; sp.style.verticalAlign='middle'; el.parentNode.insertBefore(sp, el.nextSibling); } setTip(id, 'Actualizando…'); }); }
    function showLoadingDelayed(){ if (loadingTimer) { clearTimeout(loadingTimer); } loadingTimer = setTimeout(showLoading, 200); }
    function hideLoading(){ if (loadingTimer) { clearTimeout(loadingTimer); loadingTimer = null; } document.querySelectorAll('[data-role="kpi-loading"]').forEach(function(sp){ sp.remove(); }); }
    function loadKpis(){
      showLoadingDelayed();
      fetch(base + '/api/kpis/admin').then(r=>r.json()).then(j=>{
        if (!j) return;
        setErrorMode(false);
        setBadge('nav-count-alumnos', Number(j.alumnos ?? 0));
        setBadge('nav-count-profesores', Number(j.profesores ?? 0));
        setBadge('nav-count-materias', Number(j.materias ?? 0));
        setBadge('nav-count-grupos', Number(j.grupos ?? 0));
        var sinO = Number(j.sin_oferta ?? 0);
        setBadge('nav-count-sinoferta', sinO);
        var soEl = document.getElementById('nav-count-sinoferta'); if (soEl) { soEl.classList.toggle('bg-warning', sinO > 0); soEl.classList.toggle('text-dark', sinO > 0); soEl.classList.toggle('bg-light', sinO === 0); }
        var stamp = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'});
        setTip('nav-count-alumnos', 'Alumnos activos: ' + (j.alumnos ?? '—') + ' — Actualizado: ' + stamp);
        setTip('nav-count-profesores', 'Profesores activos: ' + (j.profesores ?? '—') + ' — Actualizado: ' + stamp);
        setTip('nav-count-materias', 'Materias registradas: ' + (j.materias ?? '—') + ' — Actualizado: ' + stamp);
        setTip('nav-count-sinoferta', 'Materias sin oferta: ' + sinO + ' — Actualizado: ' + stamp + ' — Clic para filtrar');
        setTip('nav-count-grupos', 'Grupos activos con calificaciones: ' + (j.grupos ?? '—') + ' — Actualizado: ' + stamp);
        hideLoading();
      }).catch(()=>{
        setErrorMode(true);
        var stamp = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'});
        setTip('nav-count-alumnos', 'Error de red — Último intento: ' + stamp);
        setTip('nav-count-profesores', 'Error de red — Último intento: ' + stamp);
        setTip('nav-count-materias', 'Error de red — Último intento: ' + stamp);
        setTip('nav-count-sinoferta', 'Error de red — Último intento: ' + stamp);
        setTip('nav-count-grupos', 'Error de red — Último intento: ' + stamp);
        hideLoading();
      });
    }
    var btn = document.getElementById('nav-refresh-kpis'); if (btn) { btn.addEventListener('click', function(){ loadKpis(); }); }
    var so = document.getElementById('nav-count-sinoferta'); if (so) { so.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); var c=''; try { c = String(localStorage.getItem('subjects_last_ciclo')||''); } catch(e){} var url = base + '/subjects?estado=sin_grupos' + (c!==''?('&ciclo='+encodeURIComponent(c)):''); location.href = url; }); so.style.cursor = 'pointer'; }
    loadKpis();
    var isDash = (location.search.indexOf('r=/dashboard') !== -1) || (location.pathname.endsWith('/dashboard'));
    var REFRESH_MS = isDash ? 30000 : 60000;
    var kpiTimer = setInterval(loadKpis, REFRESH_MS);
    document.addEventListener('visibilitychange', function(){
      if (document.hidden) { clearInterval(kpiTimer); }
      else { clearInterval(kpiTimer); kpiTimer = setInterval(loadKpis, REFRESH_MS); loadKpis(); }
    });
  })();
  </script>
  <?php endif; ?>
</body>
</html>
