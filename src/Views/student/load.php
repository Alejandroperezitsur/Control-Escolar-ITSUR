<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Mi Carga Académica</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-sm btn-outline-secondary">Volver</a>
  </div>
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <input type="text" id="carga-filter" class="form-control form-control-sm" style="max-width: 240px" placeholder="Filtrar por materia/grupo" data-bs-toggle="tooltip" title="Filtrar por nombre de materia o grupo">
      </div>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th class="text-end">Estado</th><th class="text-end">Calificación</th></tr></thead>
          <tbody id="carga-tbody"><tr><td colspan="5" class="text-muted">Cargando...</td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
// Cargar carga académica
fetch('<?php echo $base; ?>/api/alumno/carga').then(r=>r.json()).then(resp=>{
  const rows = resp.data || [];
  const tbody = document.getElementById('carga-tbody');
  const noRec = document.createElement('div');
  noRec.className = 'alert alert-info mt-2 d-none';
  noRec.textContent = 'No hay registros disponibles.';
  tbody.parentElement.parentElement.appendChild(noRec);
  const render = () => {
    const q = (document.getElementById('carga-filter').value||'').toLowerCase();
    const cargaRows = rows.filter(x => (x.materia||'').toLowerCase().includes(q) || (x.grupo||'').toLowerCase().includes(q));
    tbody.innerHTML = cargaRows.map(x => {
      const cal = parseFloat(x.calificacion ?? '');
      const cls = isNaN(cal) ? 'text-muted' : (cal >= 70 ? 'text-success' : 'text-danger');
      return `<tr>
      <td>${x.ciclo ?? ''}</td>
      <td>${x.materia ?? ''}</td>
      <td>${x.grupo ?? ''}</td>
      <td class="text-end">${x.estado ?? ''}</td>
      <td class="text-end"><span class="${cls}">${x.calificacion ?? ''}</span></td>
    </tr>`;
    }).join('');
    if (cargaRows.length === 0) { noRec.classList.remove('d-none'); } else { noRec.classList.add('d-none'); }
  };
  render();
  document.getElementById('carga-filter').addEventListener('input', render);
});
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
