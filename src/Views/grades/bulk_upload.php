<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<h2 class="mb-4">Carga masiva de calificaciones (CSV)</h2>
<div class="alert alert-info">Sube un archivo CSV con columnas: <code>alumno_id,grupo_id,parcial1,parcial2,final</code>.</div>
<form method="post" action="<?php echo $base; ?>/grades/bulk" enctype="multipart/form-data" id="bulkForm" class="needs-validation" novalidate>
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
  <div class="mb-3">
    <input type="file" name="csv" accept="text/csv" class="form-control" required data-bs-toggle="tooltip" title="Selecciona el archivo CSV">
    <div class="invalid-feedback">Selecciona un archivo CSV válido.</div>
  </div>
  <button class="btn btn-primary" type="submit" data-bs-toggle="tooltip" title="Iniciar carga masiva"><i class="fa-solid fa-upload me-1"></i> Subir CSV</button>
  <a class="btn btn-outline-secondary ms-2" href="<?php echo $base; ?>/dashboard" data-bs-toggle="tooltip" title="Cancelar operación">Cancelar</a>
  <a class="btn btn-outline-success ms-2" href="<?php echo $base; ?>/reports/export/csv" data-bs-toggle="tooltip" title="Descargar plantilla de ejemplo"><i class="fa-solid fa-file-csv me-1"></i> Descargar CSV ejemplo</a>
  <div class="mt-3 small text-muted">Tip: Puedes exportar CSV y ajustarlo para la carga masiva.</div>
</form>

<div class="mt-4">
  <div class="progress" style="height: 24px; display:none;" id="bulkProgress">
    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="bulkBar">0%</div>
  </div>
  <div class="row mt-3" id="bulkSummary" style="display:none;">
    <div class="col-md-3">
      <div class="card text-bg-light"><div class="card-body"><div class="fw-bold">Procesadas</div><div id="sumProcessed">0</div></div></div>
    </div>
    <div class="col-md-3">
      <div class="card text-bg-success"><div class="card-body"><div class="fw-bold">Actualizadas</div><div id="sumUpdated">0</div></div></div>
    </div>
    <div class="col-md-3">
      <div class="card text-bg-warning"><div class="card-body"><div class="fw-bold">Omitidas</div><div id="sumSkipped">0</div></div></div>
    </div>
    <div class="col-md-3 d-flex align-items-center">
      <button class="btn btn-outline-primary w-100" id="downloadLogBtn" disabled>
        <i class="fa-solid fa-download me-1"></i> Descargar log
      </button>
    </div>
  </div>
</div>

<script>
// Validation
(() => {
  const form = document.getElementById('bulkForm');
  form.addEventListener('submit', (event) => {
    if (!form.checkValidity()) { event.preventDefault(); event.stopPropagation(); }
    form.classList.add('was-validated');
  }, false);
})();

// Toast helper
function showToast(message, type = 'primary') {
  const validTypes = ['primary','success','warning','danger','info'];
  if (!validTypes.includes(type)) type = 'primary';
  const wrapper = document.createElement('div');
  wrapper.innerHTML = `
    <div class="position-fixed bottom-0 end-0 p-3" style="z-index:1080">
      <div class="toast align-items-center text-bg-${type}" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">${message}</div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    </div>`;
  document.body.appendChild(wrapper);
  const toastEl = wrapper.querySelector('.toast');
  const t = new bootstrap.Toast(toastEl, { delay: 4000 });
  t.show();
}

// AJAX submit with progress
document.getElementById('bulkForm').addEventListener('submit', async (e) => {
  if (!e.target.checkValidity()) return; // validation handled above
  e.preventDefault();
  const prog = document.getElementById('bulkProgress');
  const bar = document.getElementById('bulkBar');
  const sum = document.getElementById('bulkSummary');
  prog.style.display = 'block';
  bar.classList.add('progress-bar-animated');
  bar.style.width = '25%'; bar.textContent = 'Procesando...';

  const fd = new FormData(e.target);
  try {
    const res = await fetch('<?php echo $base; ?>/grades/bulk', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
    if (!res.ok) throw new Error('Error en la carga');
    const data = await res.json();
    bar.classList.remove('progress-bar-animated');
    bar.style.width = '100%'; bar.textContent = 'Completado';
    sum.style.display = 'flex';
    document.getElementById('sumProcessed').textContent = data.processed ?? 0;
    document.getElementById('sumUpdated').textContent = data.updated ?? 0;
    document.getElementById('sumSkipped').textContent = data.skipped ?? 0;
    showToast('Carga masiva completada', 'success');
    const btn = document.getElementById('downloadLogBtn');
    btn.disabled = false;
    btn.onclick = () => { window.location.href = '<?php echo $base; ?>/grades/bulk-log'; };
  } catch (err) {
    bar.classList.remove('progress-bar-animated');
    bar.style.width = '100%'; bar.textContent = 'Error';
    showToast('No se pudo procesar el CSV', 'danger');
  }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
