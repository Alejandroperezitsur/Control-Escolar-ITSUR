<?php
$csrf = $_SESSION['csrf_token'] ?? '';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
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
        <div class="col-md-4">
          <label class="form-label">Parcial 1</label>
          <input type="number" min="0" max="100" name="parcial1" class="form-control" placeholder="0-100">
        </div>
        <div class="col-md-4">
          <label class="form-label">Parcial 2</label>
          <input type="number" min="0" max="100" name="parcial2" class="form-control" placeholder="0-100">
        </div>
        <div class="col-md-4">
          <label class="form-label">Final</label>
          <input type="number" min="0" max="100" name="final" class="form-control" placeholder="0-100">
        </div>
        </div>

        <div class="mt-4 d-flex gap-2">
          <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#confirmModal"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
          <a href="<?php echo $base; ?>/grades/bulk" class="btn btn-outline-info"><i class="fa-solid fa-file-csv me-1"></i> Carga Masiva</a>
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
(async function init() {
  const grupoSel = document.getElementById('grupo_id');
  const alumnoSel = document.getElementById('alumno_id');
  
  console.log('Iniciando carga de catálogos...');
  console.log('Session info - Role:', '<?php echo $_SESSION['role'] ?? 'N/A'; ?>', 'User ID:', '<?php echo $_SESSION['user_id'] ?? 'N/A'; ?>');
  
  try {
    // Poblar grupos del profesor actual
    const gruposUrl = '<?php echo $base; ?>/app.php?r=/api/catalogs/groups';
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
        const firstOpt = Array.from(grupoSel.options).find(o => o.value);
        if (firstOpt) { grupoSel.value = firstOpt.value; await loadStudentsByGroup(firstOpt.value); }
      } else {
        grupoSel.innerHTML = '<option value="">No tienes grupos asignados</option>';
        console.warn('No se encontraron grupos para este profesor');
      }
    }
    
    // Cargar alumnos según el grupo seleccionado (propios del profesor)
    async function loadStudentsByGroup(gid) {
      if (!gid) { alumnoSel.innerHTML = '<option value="">Selecciona el alumno activo...</option>'; return; }
      const url = '<?php echo $base; ?>/app.php?r=/api/catalogs/group_students&gid=' + encodeURIComponent(gid);
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
      loadStudentsByGroup(e.target.value);
    });
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
    alumnoSel.addEventListener('focus', loadProfessorStudentsMerge);
    // Si ya hay un grupo seleccionado, cargar sus alumnos; si no, seleccionar el primero
    if (grupoSel.value) { loadStudentsByGroup(grupoSel.value); }
    else {
      const firstOpt = Array.from(grupoSel.options).find(o => o.value);
      if (firstOpt) { grupoSel.value = firstOpt.value; loadStudentsByGroup(firstOpt.value); }
    }
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
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
