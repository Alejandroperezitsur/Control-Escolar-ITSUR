<?php
$csrf = $_SESSION['csrf_token'] ?? '';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Grupos</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="post" action="<?php echo $base; ?>/groups/create" class="row g-2 needs-validation" novalidate id="groupForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="col-md-3">
          <select class="form-select" name="materia_id" id="materiaSelect" required disabled data-bs-toggle="tooltip" title="Selecciona la materia">
            <option value="">Cargando materias...</option>
          </select>
          <div class="invalid-feedback">Selecciona una materia.</div>
        </div>
        <div class="col-md-3">
          <select class="form-select" name="profesor_id" id="profesorSelect" required disabled data-bs-toggle="tooltip" title="Asigna un profesor">
            <option value="">Cargando profesores...</option>
          </select>
          <div class="invalid-feedback">Selecciona un profesor.</div>
        </div>
        <div class="col-md-2">
          <input class="form-control" name="nombre" placeholder="Grupo" required data-bs-toggle="tooltip" title="Nombre del grupo (ej. A, B, 101)">
          <div class="invalid-feedback">Ingresa el nombre del grupo.</div>
        </div>
        <div class="col-md-2">
          <input class="form-control" name="ciclo" placeholder="Ciclo (YYYY-1 o YYYY-2)" required pattern="\d{4}-(1|2)" title="Formato YYYY-1 o YYYY-2" data-bs-toggle="tooltip">
          <div class="invalid-feedback">Ingresa el ciclo con formato válido.</div>
        </div>
        <div class="col-md-2">
          <input class="form-control" type="number" min="1" max="100" name="cupo" placeholder="Cupo" value="30" required data-bs-toggle="tooltip" title="Capacidad máxima de alumnos">
          <div class="invalid-feedback">Ingresa cupo (1-100).</div>
        </div>
        <div class="col-12"><button class="btn btn-primary" type="submit" id="btnCreate" disabled><i class="fa-solid fa-plus me-1"></i> Crear grupo</button></div>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped">
      <thead>
        <tr>
          <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="selectAll"></th>
          <th data-sort="id" role="button">ID <span class="ms-1" id="sort-id"></span></th>
          <th data-sort="materia" role="button">Materia <span class="ms-1" id="sort-materia"></span></th>
          <th data-sort="profesor" role="button">Profesor <span class="ms-1" id="sort-profesor"></span></th>
          <th data-sort="grupo" role="button">Grupo <span class="ms-1" id="sort-grupo"></span></th>
          <th data-sort="ciclo" role="button">Ciclo <span class="ms-1" id="sort-ciclo"></span></th>
          <th data-sort="cupo" role="button">Cupo <span class="ms-1" id="sort-cupo"></span></th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($groups ?? []) as $g): ?>
        <tr>
          <td><input type="checkbox" class="form-check-input group-check" value="<?= $g['id'] ?>"></td>
          <td><?= htmlspecialchars($g['id']) ?></td>
          <td><a href="<?= $base; ?>/subjects/detail?id=<?= (int)($g['materia_id'] ?? 0) ?>" class="text-decoration-none"><?= htmlspecialchars($g['materia'] ?? '') ?></a></td>
          <td><a href="<?= $base; ?>/professors/detail?id=<?= (int)($g['profesor_id'] ?? 0) ?>" class="text-decoration-none"><?= htmlspecialchars($g['profesor'] ?? '') ?></a></td>
          <td><a href="<?= $base; ?>/grades/group?grupo_id=<?= (int)$g['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($g['nombre']) ?></a></td>
          <td><?= htmlspecialchars($g['ciclo']) ?></td>
          <td><?= htmlspecialchars($g['cupo']) ?></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delGroup<?= (int)$g['id'] ?>"><i class="fa-regular fa-trash-can"></i></button>
            <a class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/grades/group?grupo_id=<?= (int)$g['id'] ?>"><i class="fa-solid fa-table"></i></a>
            <div class="modal fade" id="delGroup<?= (int)$g['id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title">Eliminar grupo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">¿Confirmas eliminar el grupo "<?= htmlspecialchars($g['nombre']) ?>"?</div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="post" action="<?php echo $base; ?>/groups/delete">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                      <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                      <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="d-flex justify-content-end align-items-center mt-2">
    <input type="text" id="grp-search" class="form-control form-control-sm" placeholder="Buscar materia/grupo/profesor" style="max-width: 280px">
    <select id="subject-filter" class="form-select form-select-sm ms-2" style="max-width: 220px">
      <option value="">Todas las materias</option>
    </select>
    <select id="prof-filter" class="form-select form-select-sm ms-2" style="max-width: 220px">
      <option value="">Todos los profesores</option>
    </select>
    <select id="cycle-filter" class="form-select form-select-sm ms-2" style="max-width: 160px">
      <option value="">Todos los ciclos</option>
    </select>
    <button type="button" id="btn-export" class="btn btn-sm btn-outline-secondary ms-2"><i class="fa-solid fa-file-csv me-1"></i> Exportar CSV</button>
    <button type="button" id="btn-export-pdf" class="btn btn-sm btn-outline-secondary ms-2"><i class="fa-solid fa-file-pdf me-1"></i> Exportar PDF</button>
    <button id="bulkDeleteBtn" class="btn btn-sm btn-danger d-none ms-2" onclick="bulkDeleteGroups()" data-bs-toggle="tooltip" title="Eliminar seleccionados">
        <i class="fa-solid fa-trash me-1"></i> Eliminar (<span id="selectedCount">0</span>)
    </button>
  </div>
  <div class="d-flex justify-content-between align-items-center mt-2">
    <div>
      <label class="form-label me-2">Tamaño de página</label>
      <select id="page-size" class="form-select form-select-sm d-inline-block" style="width:120px">
        <option value="10">10</option>
        <option value="20">20</option>
        <option value="50">50</option>
      </select>
    </div>
    <div>
      <button type="button" id="page-prev" class="btn btn-sm btn-outline-secondary">Anterior</button>
      <span class="mx-2" id="page-info">—</span>
      <button type="button" id="page-next" class="btn btn-sm btn-outline-secondary">Siguiente</button>
    </div>
  </div>
  <div id="cycle-summary" class="mt-2"></div>
</div>

<script>
// Bootstrap validation
(() => {
  const form = document.getElementById('groupForm');
  form.addEventListener('submit', (event) => {
    if (!form.checkValidity()) {
      event.preventDefault();
      event.stopPropagation();
    }
    form.classList.add('was-validated');
  }, false);
})();

// Dynamic catalogs for selects
async function loadCatalogs() {
  const subjSel = document.getElementById('materiaSelect');
  const profSel = document.getElementById('profesorSelect');
  const btn = document.getElementById('btnCreate');
  try {
    const [subjRes, profRes] = await Promise.all([
      fetch('<?php echo $base; ?>/api/catalogs/subjects'),
      fetch('<?php echo $base; ?>/api/catalogs/professors')
    ]);
    if (!subjRes.ok || !profRes.ok) throw new Error('Error cargando catálogos');
    const subjects = await subjRes.json();
    const professors = await profRes.json();
    subjSel.options[0].textContent = 'Seleccione materia...';
    profSel.options[0].textContent = 'Seleccione profesor...';
    subjects.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.id;
      opt.textContent = s.nombre + (s.clave ? ' ('+s.clave+')' : '');
      subjSel.appendChild(opt);
    });
    professors.forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = p.nombre + (p.email ? ' ('+p.email+')' : '');
      profSel.appendChild(opt);
    });
    subjSel.disabled = false;
    profSel.disabled = false;
    if (btn) btn.disabled = false;
  } catch (e) {
    const warn = document.createElement('div');
    warn.className = 'alert alert-warning mt-2';
    warn.textContent = 'No se pudieron cargar los catálogos. Intenta más tarde.';
    document.querySelector('.card .card-body').appendChild(warn);
    document.querySelector('#groupForm button[type="submit"]').disabled = true;
  }
}
document.addEventListener('DOMContentLoaded', loadCatalogs);

const base = '<?php echo $base; ?>';
async function loadCycles(){
  try{
    const res = await fetch(base + '/api/catalogs/cycles');
    if (!res.ok) return;
    const list = await res.json();
    const sel = document.getElementById('cycle-filter');
    (list||[]).forEach(c => { const o = document.createElement('option'); o.value = c; o.textContent = c; sel.appendChild(o); });
  } catch(e){}
}
async function loadSubjects(){
  try{
    const res = await fetch(base + '/api/catalogs/subjects');
    if (!res.ok) return;
    const list = await res.json();
    const sel = document.getElementById('subject-filter');
    (list||[]).forEach(s => { const o = document.createElement('option'); o.value = s.nombre; o.textContent = s.nombre + (s.clave?(' ('+s.clave+')'):''); sel.appendChild(o); });
  } catch(e){}
}
async function loadProfessors(){
  try{
    const res = await fetch(base + '/api/catalogs/professors');
    if (!res.ok) return;
    const list = await res.json();
    const sel = document.getElementById('prof-filter');
    (list||[]).forEach(p => { const name = p.nombre || (p.email||''); const o = document.createElement('option'); o.value = name; o.textContent = name; sel.appendChild(o); });
  } catch(e){}
}
function applyGroupFilter(){
  const q = document.getElementById('grp-search').value.toLowerCase();
  const cyc = document.getElementById('cycle-filter').value;
  const subj = document.getElementById('subject-filter').value;
  const prof = document.getElementById('prof-filter').value;
  const tbody = document.querySelector('table tbody');
  Array.from(tbody.rows).forEach(tr => {
    const materia = (tr.cells[1]?.textContent || '').toLowerCase();
    const profesor = (tr.cells[2]?.textContent || '').toLowerCase();
    const grupo = (tr.cells[3]?.textContent || '').toLowerCase();
    const ciclo = (tr.cells[4]?.textContent || '');
    const matchQ = !q || materia.includes(q) || profesor.includes(q) || grupo.includes(q);
    const matchC = !cyc || ciclo === cyc;
    const matchS = !subj || materia === subj.toLowerCase();
    const matchP = !prof || profesor === prof.toLowerCase();
    const ok = (matchQ && matchC && matchS && matchP);
    tr.dataset.filtered = ok ? '1' : '0';
    tr.style.display = ok ? '' : 'none';
  });
  updateCycleSummary();
  paginate(0);
}
document.addEventListener('DOMContentLoaded', () => {
  Promise.all([loadCycles(), loadSubjects(), loadProfessors()]).then(initFiltersFromUrl);
  document.getElementById('grp-search').addEventListener('input', function(){ applyGroupFilter(); updateUrlParams(); });
  document.getElementById('cycle-filter').addEventListener('change', function(){ applyGroupFilter(); updateUrlParams(); });
  document.getElementById('subject-filter').addEventListener('change', function(){ applyGroupFilter(); updateUrlParams(); });
  document.getElementById('prof-filter').addEventListener('change', function(){ applyGroupFilter(); updateUrlParams(); });
  document.getElementById('btn-export').addEventListener('click', function(){
    const tbody = document.querySelector('table tbody');
    const rows = Array.from(tbody.rows).filter(r => r.style.display !== 'none');
    const csvRows = [['ID','Materia','Profesor','Grupo','Ciclo','Cupo']].concat(rows.map(r => [r.cells[0].textContent.trim(), r.cells[1].textContent.trim(), r.cells[2].textContent.trim(), r.cells[3].textContent.trim(), r.cells[4].textContent.trim(), r.cells[5].textContent.trim()]));
    const csv = csvRows.map(arr => arr.map(v => '"'+String(v).replace(/"/g,'""')+'"').join(',')).join('\n');
    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = 'grupos_filtrados.csv'; document.body.appendChild(a); a.click(); setTimeout(function(){ URL.revokeObjectURL(url); a.remove(); }, 100);
  });
  document.getElementById('btn-export-pdf').addEventListener('click', function(){
    const table = document.querySelector('table');
    const thead = table.querySelector('thead');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.rows).filter(r => r.style.display !== 'none');
    const headCells = Array.from(thead.rows[0].cells).slice(0,6).map(c => c.textContent.trim());
    const htmlRows = rows.map(r => '<tr>' + Array.from(r.cells).slice(0,6).map(c => '<td>' + c.textContent.trim() + '</td>').join('') + '</tr>').join('');
    const html = '<!doctype html><html><head><meta charset="utf-8"><title>Grupos Filtrados</title><style>body{font-family:Arial, sans-serif;margin:20px;} h2{margin:0 0 12px;} table{width:100%;border-collapse:collapse;font-size:12px;} th,td{border:1px solid #333;padding:6px;text-align:left;} .meta{margin-bottom:10px;color:#555;font-size:12px;} @page{size:auto;margin:12mm;} </style></head><body>' + '<h2>Grupos filtrados</h2>' + '<div class="meta">Fecha: ' + new Date().toLocaleString() + '</div>' + '<table><thead><tr>' + headCells.map(h => '<th>' + h + '</th>').join('') + '</tr></thead><tbody>' + htmlRows + '</tbody></table>' + '</body></html>';
    const w = window.open('', '_blank');
    if (!w) return;
    w.document.open();
    w.document.write(html);
    w.document.close();
    w.focus();
    setTimeout(function(){ w.print(); }, 300);
  });
  document.getElementById('page-size').addEventListener('change', function(){ paginate(0); updateUrlParams(); });
  document.getElementById('page-prev').addEventListener('click', function(){ paginate(-1); updateUrlParams(); });
  document.getElementById('page-next').addEventListener('click', function(){ paginate(1); updateUrlParams(); });
  paginate(0);
  initSortHandlers();
});

var CURRENT_PAGE = 1;
var SORT_KEY = '';
var SORT_DIR = 'asc';
function paginate(step){
  const tbody = document.querySelector('table tbody');
  const size = parseInt(document.getElementById('page-size').value || '10', 10);
  const visible = Array.from(tbody.rows).filter(r => r.style.display !== 'none');
  const total = visible.length;
  const maxPage = Math.max(1, Math.ceil(total / size));
  if (typeof step === 'number'){
    if (step === 1) CURRENT_PAGE = Math.min(maxPage, CURRENT_PAGE + 1);
    else if (step === -1) CURRENT_PAGE = Math.max(1, CURRENT_PAGE - 1);
    else CURRENT_PAGE = 1;
  }
  if (CURRENT_PAGE > maxPage) CURRENT_PAGE = maxPage;
  const start = (CURRENT_PAGE - 1) * size;
  const end = start + size;
  let idx = 0;
  visible.forEach((r) => { r.style.display = (idx >= start && idx < end) ? '' : 'none'; idx++; });
  const info = document.getElementById('page-info');
  const prev = document.getElementById('page-prev');
  const next = document.getElementById('page-next');
  if (info) info.textContent = 'Página ' + CURRENT_PAGE + ' de ' + maxPage + ' · ' + total + ' registros';
  if (prev) prev.disabled = CURRENT_PAGE <= 1;
  if (next) next.disabled = CURRENT_PAGE >= maxPage;
}

function updateCycleSummary(){
  const tbody = document.querySelector('table tbody');
  const rows = Array.from(tbody.rows).filter(r => r.dataset.filtered === '1');
  const counts = {};
  rows.forEach(r => { const c = (r.cells[4]?.textContent || '').trim(); counts[c] = (counts[c]||0) + 1; });
  const cont = document.getElementById('cycle-summary');
  if (!cont) return;
  const keys = Object.keys(counts).filter(k => k).sort();
  cont.innerHTML = keys.length ? ('Resumen por ciclo: ' + keys.map(k => '<span class="badge bg-light text-dark me-1">' + k + ': ' + counts[k] + '</span>').join('')) : '';
}

function compareByKey(a, b){
  const map = { id: 0, materia: 1, profesor: 2, grupo: 3, ciclo: 4, cupo: 5 };
  const idx = map[SORT_KEY];
  if (typeof idx === 'undefined') return 0;
  const va = (a.cells[idx]?.textContent || '').trim();
  const vb = (b.cells[idx]?.textContent || '').trim();
  if (SORT_KEY === 'id' || SORT_KEY === 'cupo') {
    const na = parseInt(va, 10); const nb = parseInt(vb, 10);
    if (isNaN(na) && isNaN(nb)) return 0;
    if (isNaN(na)) return 1;
    if (isNaN(nb)) return -1;
    return na - nb;
  }
  return va.toLowerCase().localeCompare(vb.toLowerCase());
}

function applySort(){
  if (!SORT_KEY) return;
  const tbody = document.querySelector('table tbody');
  const rows = Array.from(tbody.rows);
  rows.sort(compareByKey);
  if (SORT_DIR === 'desc') rows.reverse();
  rows.forEach(r => tbody.appendChild(r));
  updateSortIndicator();
  paginate(0);
}

function updateSortIndicator(){
  const keys = ['id','materia','profesor','grupo','ciclo','cupo'];
  keys.forEach(k => { var el = document.getElementById('sort-' + k); if (el) { el.textContent = (SORT_KEY === k) ? (SORT_DIR === 'asc' ? '▲' : '▼') : ''; } });
}

function initSortHandlers(){
  const ths = Array.from(document.querySelectorAll('thead th[data-sort]'));
  ths.forEach(th => th.addEventListener('click', function(){
    const k = th.getAttribute('data-sort');
    if (SORT_KEY === k) { SORT_DIR = (SORT_DIR === 'asc' ? 'desc' : 'asc'); }
    else { SORT_KEY = k; SORT_DIR = 'asc'; }
    applySort(); updateUrlParams();
  }));
}

function initFiltersFromUrl(){
  const params = new URLSearchParams(location.search);
  const q = params.get('q') || '';
  const cyc = params.get('ciclo') || '';
  const subj = params.get('materia') || '';
  const prof = params.get('profesor') || '';
  const size = params.get('pagesize') || '';
  const page = parseInt(params.get('page') || '1', 10);
  const sort = params.get('sort') || '';
  const dir = params.get('dir') || '';
  var elQ = document.getElementById('grp-search'); if (elQ) elQ.value = q;
  var elC = document.getElementById('cycle-filter'); if (elC && cyc) { var optC = Array.from(elC.options).find(o => o.value === cyc); if (optC) elC.value = cyc; }
  var elS = document.getElementById('subject-filter'); if (elS && subj) { var optS = Array.from(elS.options).find(o => o.value === subj); if (optS) elS.value = subj; }
  var elP = document.getElementById('prof-filter'); if (elP && prof) { var optP = Array.from(elP.options).find(o => o.value === prof); if (optP) elP.value = prof; }
  var elPS = document.getElementById('page-size'); if (elPS && size) { var optSz = Array.from(elPS.options).find(o => o.value === size); if (optSz) elPS.value = size; }
  applyGroupFilter();
  CURRENT_PAGE = isNaN(page) ? 1 : Math.max(1, page);
  if (sort) { SORT_KEY = sort; }
  if (dir === 'asc' || dir === 'desc') { SORT_DIR = dir; }
  applySort();
  paginate(0);
}

function updateUrlParams(){
  const params = new URLSearchParams();
  var q = document.getElementById('grp-search').value.trim(); if (q) params.set('q', q);
  var cyc = document.getElementById('cycle-filter').value.trim(); if (cyc) params.set('ciclo', cyc);
  var subj = document.getElementById('subject-filter').value.trim(); if (subj) params.set('materia', subj);
  var prof = document.getElementById('prof-filter').value.trim(); if (prof) params.set('profesor', prof);
  var size = document.getElementById('page-size').value.trim(); if (size) params.set('pagesize', size);
  params.set('page', String(CURRENT_PAGE));
  if (SORT_KEY) params.set('sort', SORT_KEY);
  if (SORT_DIR) params.set('dir', SORT_DIR);
  const qs = params.toString();
  const url = location.pathname + (qs ? ('?' + qs) : '');
  history.replaceState(null, '', url);
}
  if (SORT_DIR) params.set('dir', SORT_DIR);
  const qs = params.toString();
  const url = location.pathname + (qs ? ('?' + qs) : '');
  history.replaceState(null, '', url);
}

window.updateBulkDeleteBtn = function() {
    const checks = document.querySelectorAll('.group-check:checked');
    const btn = document.getElementById('bulkDeleteBtn');
    const countSpan = document.getElementById('selectedCount');
    
    if (checks.length > 0) {
        btn.classList.remove('d-none');
        countSpan.textContent = checks.length;
    } else {
        btn.classList.add('d-none');
    }
};

document.getElementById('selectAll')?.addEventListener('change', function(e) {
    const checks = document.querySelectorAll('.group-check');
    // Only select visible rows
    const visibleChecks = Array.from(checks).filter(c => c.closest('tr').style.display !== 'none');
    visibleChecks.forEach(c => c.checked = e.target.checked);
    updateBulkDeleteBtn();
});

// Use delegation for dynamic rows
document.querySelector('table tbody').addEventListener('change', function(e) {
    if (e.target.classList.contains('group-check')) {
        updateBulkDeleteBtn();
    }
});

window.bulkDeleteGroups = function() {
    const checks = document.querySelectorAll('.group-check:checked');
    if (checks.length === 0) return;
    
    if (!confirm(`¿Estás seguro de eliminar ${checks.length} grupos seleccionados? Esta acción no se puede deshacer.`)) return;
    
    const ids = Array.from(checks).map(c => c.value);
    const formData = new FormData();
    formData.append('ids', JSON.stringify(ids));
    formData.append('csrf_token', '<?= $csrf ?>');
    
    const btn = document.getElementById('bulkDeleteBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Eliminando...';
    
    fetch('<?php echo $base; ?>/groups/bulk-delete', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.text().then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Raw response:', text);
            throw new Error('Respuesta inválida del servidor');
        }
    }))
    .then(data => {
        if(data.success) {
            alert(`${data.deleted} grupos eliminados`);
            location.reload();
        } else {
            alert(data.error || 'Error al eliminar');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(e => {
        console.error(e);
        alert('Error de red');
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
};
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
