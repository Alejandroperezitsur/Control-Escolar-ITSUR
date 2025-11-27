<?php
$csrf = $_SESSION['csrf_token'] ?? '';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$role = $_SESSION['role'] ?? '';
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Registrar Calificaciones</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
  </div>

  <div class="card">
    <div class="card-body">
      <form id="grade-form" method="post" action="<?php echo $base; ?>/grades/create" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="redirect_to" id="redirect_to" value="/grades">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Grupo</label>
            <select name="grupo_id" id="grupo_id" class="form-select" required></select>
            <div class="form-text">Selecciona el grupo asignado.</div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Alumno</label>
            <select name="alumno_id" id="alumno_id" class="form-select" required></select>
            <div class="form-text">Selecciona el alumno activo.</div>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="onlyPending">
              <label class="form-check-label" for="onlyPending">Solo pendientes</label>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Parcial 1</label>
            <input type="number" min="0" max="100" step="1" name="parcial1" class="form-control" placeholder="0-100">
          </div>
        <div class="col-md-4">
          <label class="form-label">Parcial 2</label>
          <input type="number" min="0" max="100" step="1" name="parcial2" class="form-control" placeholder="0-100">
        </div>
        <div class="col-md-4">
          <label class="form-label">Final</label>
          <div class="input-group">
            <input type="number" min="0" max="100" step="1" name="final" class="form-control" placeholder="0-100">
            <button type="button" id="btnCalcFinal" class="btn btn-outline-secondary"><i class="fa-solid fa-calculator me-1"></i> Calcular</button>
          </div>
        </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <button type="button" id="openConfirm" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmModal"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
          <button type="button" id="openConfirmNext" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmModal"><i class="fa-solid fa-forward-step me-1"></i> Guardar y siguiente</button>
          <button type="button" id="btnReset" class="btn btn-outline-secondary"><i class="fa-solid fa-broom me-1"></i> Limpiar</button>
          <a href="<?php echo $base; ?>/grades/bulk" class="btn btn-outline-info"><i class="fa-solid fa-file-csv me-1"></i> Carga Masiva</a>
          <span class="badge bg-secondary" id="promedioBadge">Promedio actual: —</span>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal de confirmación -->
  <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Confirmar registro</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Se registrará la calificación seleccionada. ¿Deseas continuar?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" id="confirmSubmit" class="btn btn-primary">Confirmar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
var GO_NEXT = false;
(async function init() {
  const grupoSel = document.getElementById('grupo_id');
  const alumnoSel = document.getElementById('alumno_id');
  const promBadge = document.getElementById('promedioBadge');
  const redirectInput = document.getElementById('redirect_to');
  const LS_G = 'grades_last_group';
  const LS_A = 'grades_last_alumno';
  const LS_P = 'grades_only_pending';
  
  console.log('Iniciando carga de catálogos...');
  console.log('Session info - Role:', '<?php echo $_SESSION['role'] ?? 'N/A'; ?>', 'User ID:', '<?php echo $_SESSION['user_id'] ?? 'N/A'; ?>');
  
  try {
    // Poblar grupos: si admin, usar todos los grupos; si profesor, solo los propios
    const gruposUrl = '<?php echo $base; ?>/app.php?r=<?php echo ($role === 'admin' ? '/api/catalogs/groups_all' : '/api/catalogs/groups'); ?>';
    const qs = new URLSearchParams(location.search);
    const prefGid = qs.get('grupo_id');
    const prefAid = qs.get('alumno_id');
    if (redirectInput) {
      const qsRedir = qs.get('redirect_to');
      if (qsRedir) { redirectInput.value = qsRedir; }
      else if (prefGid) { redirectInput.value = '/grades/group?grupo_id=' + encodeURIComponent(prefGid); }
      else { redirectInput.value = '/grades'; }
    }
    console.log('Fetching grupos from:', gruposUrl);
    const gRes = await fetch(gruposUrl);
    console.log('Grupos response status:', gRes.status, gRes.statusText);
    
    if (!gRes.ok) {
      console.error('Error fetching groups:', gRes.status, gRes.statusText);
      const errorText = await gRes.text();
      console.error('Error response:', errorText);
      grupoSel.innerHTML = '<option value="">Error al cargar grupos</option>';
    } else {
      const grupos = await gRes.json();
      console.log('Grupos cargados:', grupos);
      console.log('Número de grupos:', grupos ? grupos.length : 0);
      if (grupos && grupos.length > 0) {
        grupoSel.innerHTML = '<option value="">Selecciona el grupo asignado...</option>';
        const buckets = {};
        for (const g of grupos) { const k = String(g.ciclo || ''); (buckets[k] = buckets[k] || []).push(g); }
        const cycles = Object.keys(buckets).sort().reverse();
        for (const c of cycles) {
          const og = document.createElement('optgroup'); og.label = c;
          og.innerHTML = buckets[c].map(g => `<option value="${g.id}">${g.materia} · ${g.nombre}</option>`).join('');
          grupoSel.appendChild(og);
        }
        const savedG = (()=>{ try { return localStorage.getItem(LS_G) || ''; } catch(e){ return ''; } })();
        var pendCb = document.getElementById('onlyPending');
        if (pendCb) {
          try {
            var sv = localStorage.getItem(LS_P);
            pendCb.checked = (sv === '1');
          } catch(e) {}
        }
        const targetOpt = (prefGid && Array.from(grupoSel.options).some(o => o.value === String(prefGid)))
          ? { value: String(prefGid) }
          : (savedG && Array.from(grupoSel.options).some(o => o.value === String(savedG)))
            ? { value: String(savedG) }
            : Array.from(grupoSel.options).find(o => o.value);
        if (targetOpt) { grupoSel.value = targetOpt.value; await loadStudentsByGroup(targetOpt.value); }
        if (prefAid) { alumnoSel.value = String(prefAid); }
        else {
          const savedA = (()=>{ try { return localStorage.getItem(LS_A) || ''; } catch(e){ return ''; } })();
          if (savedA && Array.from(alumnoSel.options).some(o => o.value === String(savedA))) {
            alumnoSel.value = String(savedA);
          }
        }
        await prefillGrades();
      } else {
        grupoSel.innerHTML = '<option value="">No tienes grupos asignados</option>';
        console.warn('No se encontraron grupos para este profesor');
      }
    }
    
    // Cargar alumnos según el grupo seleccionado (propios del profesor)
    async function loadStudentsByGroup(gid) {
      if (!gid) { alumnoSel.innerHTML = '<option value="">Selecciona el alumno activo...</option>'; return; }
      var pend = document.getElementById('onlyPending');
      var qpend = (pend && pend.checked) ? '&pending=1' : '';
      const url = '<?php echo $base; ?>/app.php?r=/api/catalogs/group_students&gid=' + encodeURIComponent(gid) + qpend;
      console.log('Fetching alumnos por grupo desde:', url);
      try {
        const res = await fetch(url);
        console.log('Alumnos (grupo) response status:', res.status, res.statusText);
        if (!res.ok) {
          const t = await res.text();
          console.error('Error alumnos por grupo:', res.status, res.statusText, t);
          alumnoSel.innerHTML = '<option value="">Error al cargar alumnos</option>';
          return;
        }
        const list = await res.json();
        if (list.length > 0) {
          alumnoSel.innerHTML = '<option value="">Selecciona el alumno activo...</option>';
          const og = document.createElement('optgroup');
          og.label = 'Alumnos del grupo';
          og.innerHTML = list.map(a => `<option value="${a.id}">${a.matricula || ''} · ${a.nombre || ''} ${a.apellido || ''}</option>`).join('');
          alumnoSel.appendChild(og);
          const firstA = list[0]?.id;
          if (firstA) { alumnoSel.value = String(firstA); }
        } else {
          alumnoSel.innerHTML = '<option value="">No hay alumnos en el grupo</option>';
        }
      } catch (e) {
        console.error('Excepción cargando alumnos por grupo:', e);
        alumnoSel.innerHTML = '<option value="">Error al cargar alumnos</option>';
      }
    }

    async function prefillGrades() {
      const gid = grupoSel.value;
      const aid = alumnoSel.value;
      if (!gid || !aid) { return; }
      const url = '<?php echo $base; ?>/app.php?r=/api/grades/row&grupo_id=' + encodeURIComponent(gid) + '&alumno_id=' + encodeURIComponent(aid);
      try {
        const res = await fetch(url);
        if (!res.ok) { return; }
        const json = await res.json();
        const d = json && json.data ? json.data : null;
        const setVal = (name, val) => { const el = document.querySelector(`[name="${name}"]`); if (!el) return; el.value = (val === null || typeof val === 'undefined') ? '' : String(val); };
        if (d) { setVal('parcial1', d.parcial1); setVal('parcial2', d.parcial2); setVal('final', d.final); updatePromedioBadge(); focusNext(); }
      } catch {}
    }

    async function loadProfessorStudentsMerge() {
      const url = '<?php echo $base; ?>/app.php?r=/api/catalogs/professor_students';
      try {
        const res = await fetch(url);
        if (!res.ok) { return; }
        const profList = await res.json();
        const seen = new Set(Array.from(alumnoSel.options).map(o => o.value).filter(Boolean));
        const extras = profList.filter(a => !seen.has(String(a.id)));
        if (extras.length > 0) {
          let og = Array.from(alumnoSel.querySelectorAll('optgroup')).find(g => g.label === 'Todos mis alumnos');
          if (!og) { og = document.createElement('optgroup'); og.label = 'Todos mis alumnos'; alumnoSel.appendChild(og); }
          og.insertAdjacentHTML('beforeend', extras.map(a => `<option value="${a.id}">${a.matricula || ''} · ${a.nombre || ''} ${a.apellido || ''}</option>`).join(''));
        }
      } catch {}
    }

    grupoSel.addEventListener('change', (e) => {
      try { localStorage.setItem(LS_G, String(e.target.value||'')); } catch(e){}
      loadStudentsByGroup(e.target.value);
      setTimeout(prefillGrades, 100);
    });
    var pend = document.getElementById('onlyPending'); if (pend) { pend.addEventListener('change', function(){ try { localStorage.setItem(LS_P, pend.checked ? '1' : '0'); } catch(e) {} var gid = grupoSel.value; loadStudentsByGroup(gid); setTimeout(prefillGrades, 100); }); }
    grupoSel.addEventListener('focus', async () => {
      try {
        const res = await fetch(gruposUrl);
        if (!res.ok) { return; }
        const prev = grupoSel.value;
        const grupos = await res.json();
        grupoSel.innerHTML = '<option value="">Selecciona el grupo asignado...</option>';
        const buckets = {};
        for (const g of grupos) { const k = String(g.ciclo || ''); (buckets[k] = buckets[k] || []).push(g); }
        const cycles = Object.keys(buckets).sort().reverse();
        for (const c of cycles) {
          const og = document.createElement('optgroup'); og.label = c;
          og.innerHTML = buckets[c].map(g => `<option value="${g.id}">${g.materia} · ${g.nombre}</option>`).join('');
          grupoSel.appendChild(og);
        }
        if (prev && Array.from(grupoSel.options).some(o => o.value === prev)) { grupoSel.value = prev; }
      } catch {}
    });
    if ('<?php echo $role; ?>' === 'profesor') { alumnoSel.addEventListener('focus', loadProfessorStudentsMerge); }
    // Si ya hay un grupo seleccionado, cargar sus alumnos; si no, seleccionar el primero
    if (grupoSel.value) { loadStudentsByGroup(grupoSel.value); }
    else {
      const firstOpt = Array.from(grupoSel.options).find(o => o.value);
      if (firstOpt) { grupoSel.value = firstOpt.value; loadStudentsByGroup(firstOpt.value); }
    }
    alumnoSel.addEventListener('change', (e) => { try { localStorage.setItem(LS_A, String(e.target.value||'')); } catch(e){} prefillGrades(); });
    setTimeout(prefillGrades, 200);
    var btnOpen = document.getElementById('openConfirm'); if (btnOpen) { btnOpen.addEventListener('click', function(){ GO_NEXT = false; }); }
    var btnNext = document.getElementById('openConfirmNext'); if (btnNext) { btnNext.addEventListener('click', function(){ GO_NEXT = true; }); }
    var btnReset = document.getElementById('btnReset'); if (btnReset) { btnReset.addEventListener('click', function(){ var f1=document.querySelector('[name="parcial1"]'); var f2=document.querySelector('[name="parcial2"]'); var ff=document.querySelector('[name="final"]'); if (f1) f1.value=''; if (f2) f2.value=''; if (ff) ff.value=''; updatePromedioBadge(); focusNext(); }); }
    document.addEventListener('keydown', function(e){ var k = e.key || e.keyCode; if (e.ctrlKey && (k === 'Enter' || k === 13)) { GO_NEXT = true; var m = document.getElementById('confirmModal'); try { var inst = window.bootstrap && window.bootstrap.Modal ? (window.bootstrap.Modal.getInstance(m) || new window.bootstrap.Modal(m)) : null; if (inst) inst.show(); else { m.classList.add('show'); } } catch(x){ m.classList.add('show'); } e.preventDefault(); } });
    document.addEventListener('keydown', function(e){ var k = e.key || e.keyCode; if (!e.ctrlKey && (k === 'Enter' || k === 13)) { GO_NEXT = false; var m = document.getElementById('confirmModal'); try { var inst = window.bootstrap && window.bootstrap.Modal ? (window.bootstrap.Modal.getInstance(m) || new window.bootstrap.Modal(m)) : null; if (inst) inst.show(); else { m.classList.add('show'); } } catch(x){ m.classList.add('show'); } e.preventDefault(); } });
    var btnCalc = document.getElementById('btnCalcFinal'); if (btnCalc) { btnCalc.addEventListener('click', function(){ var p1=document.querySelector('[name="parcial1"]').value; var p2=document.querySelector('[name="parcial2"]').value; var fin=document.querySelector('[name="final"]'); var n1=Number(p1), n2=Number(p2); if (!isNaN(n1) && !isNaN(n2)) { var avg=Math.round(((n1+n2)/2)); avg=Math.max(0, Math.min(100, avg)); fin.value=String(avg); updatePromedioBadge(); fin.focus(); } }); }
  } catch (error) {
    console.error('Error en init():', error);
    console.error('Error stack:', error.stack);
    grupoSel.innerHTML = '<option value="">Error al cargar datos</option>';
    alumnoSel.innerHTML = '<option value="">Error al cargar datos</option>';
  }
  
  console.log('Carga de catálogos completada');
})();

// Validación cliente ligera
document.getElementById('confirmSubmit').addEventListener('click', function() {
  const form = document.getElementById('grade-form');
  const btn = document.getElementById('confirmSubmit');
  const grupoSel = document.getElementById('grupo_id');
  const alumnoSel = document.getElementById('alumno_id');
  const redirectInput = document.getElementById('redirect_to');
  const required = ['grupo_id','alumno_id'];
  for (const name of required) {
    const el = form.querySelector(`[name="${name}"]`);
    if (!el.value) { el.classList.add('is-invalid'); return; }
    else { el.classList.remove('is-invalid'); }
  }
  const numeric = ['parcial1','parcial2','final'];
  for (const name of numeric) {
    const el = form.querySelector(`[name="${name}"]`);
    if (el.value !== '' && (isNaN(el.value) || el.value < 0 || el.value > 100)) {
      el.classList.add('is-invalid'); return;
    } else { el.classList.remove('is-invalid'); }
  }
  updatePromedioBadge();
  if (redirectInput) {
    if (GO_NEXT) {
      var gid = grupoSel.value || '';
      var opts = Array.from(alumnoSel.options).filter(function(o){ return !!o.value; });
      var idx = opts.findIndex(function(o){ return o.value === String(alumnoSel.value || ''); });
      var next = (idx >= 0 && (idx + 1) < opts.length) ? opts[idx + 1].value : '';
      if (gid && next) { redirectInput.value = '/grades?grupo_id=' + encodeURIComponent(gid) + '&alumno_id=' + encodeURIComponent(next); }
      else if (gid) { redirectInput.value = '/grades/group?grupo_id=' + encodeURIComponent(gid); }
      else { redirectInput.value = '/grades'; }
    }
  }
  if (btn) { btn.disabled = true; }
  form.submit();
});

// Umbral visual (>=70 verde, <70 rojo)
(() => {
  const inputs = ['parcial1','parcial2','final'].map(n => document.querySelector(`[name="${n}"]`));
  const paint = (el) => {
    const v = el.value === '' ? null : Number(el.value);
    el.classList.remove('is-valid','is-invalid','text-success','text-danger');
    if (v === null || isNaN(v)) return;
    if (v >= 70) { el.classList.add('is-valid','text-success'); }
    else { el.classList.add('is-invalid','text-danger'); }
  };
  inputs.forEach(el => el && el.addEventListener('input', () => paint(el)));
  function clampInt(el){ var v = el.value; if (v === '') return; var n = Math.round(Number(v)); if (isNaN(n)) { el.value = ''; } else { if (n < 0) n = 0; if (n > 100) n = 100; el.value = String(n); } updatePromedioBadge(); paint(el); }
  inputs.forEach(el => el && el.addEventListener('blur', () => clampInt(el)));
  function updatePromedioBadge() {
    const p1 = document.querySelector('[name="parcial1"]').value;
    const p2 = document.querySelector('[name="parcial2"]').value;
    const fin = document.querySelector('[name="final"]').value;
    let prom = null;
    if (fin !== '') { prom = Number(fin); }
    else if (p1 !== '' && p2 !== '') { prom = Math.round(((Number(p1) + Number(p2)) / 2) * 100) / 100; }
    promBadge.textContent = 'Promedio actual: ' + (prom === null || isNaN(prom) ? '—' : String(prom));
    promBadge.classList.remove('bg-secondary','bg-success','bg-danger');
    if (prom === null || isNaN(prom)) { promBadge.classList.add('bg-secondary'); }
    else if (prom >= 70) { promBadge.classList.add('bg-success'); }
    else { promBadge.classList.add('bg-danger'); }
  }
  function focusNext(){
    const p1El = document.querySelector('[name="parcial1"]');
    const p2El = document.querySelector('[name="parcial2"]');
    const finEl = document.querySelector('[name="final"]');
    if (finEl && finEl.value === '') { finEl.focus(); return; }
    if (p1El && p1El.value === '') { p1El.focus(); return; }
    if (p2El && p2El.value === '') { p2El.focus(); return; }
    const btn = document.getElementById('confirmSubmit'); if (btn) { btn.focus(); }
  }
  inputs.forEach(el => el && el.addEventListener('input', updatePromedioBadge));
  updatePromedioBadge();
  focusNext();
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
