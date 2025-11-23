<?php ob_start(); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0"><i class="fa-solid fa-sliders me-2"></i>Ajustes de siembra</h3>
  <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
</div>

<form method="post" action="<?php echo $base; ?>/admin/settings/save" class="card p-3">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Grupos mínimos por ciclo</label>
      <input type="number" name="seed_min_groups_per_cycle" class="form-control" min="1" value="<?php echo (int)$minGroups; ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Calificaciones mínimas por grupo</label>
      <input type="number" name="seed_min_grades_per_group" class="form-control" min="1" value="<?php echo (int)$minGrades; ?>">
    </div>
    <div class="col-md-4">
      <label class="form-label">Tamaño del pool de alumnos</label>
      <input type="number" name="seed_students_pool" class="form-control" min="10" value="<?php echo (int)$pool; ?>">
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i> Guardar</button>
  </div>
</form>

<div class="mt-4">
  <h3 class="mb-3"><i class="fa-solid fa-broom me-2"></i>Deduplicación</h3>
  <div class="row g-3">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Materias duplicadas por clave</div>
        <div class="card-body">
          <div id="dup-materias"></div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Profesores duplicados por email</div>
        <div class="card-body">
          <div id="dup-profesores"></div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">Alumnos duplicados por matrícula</div>
        <div class="card-body">
          <div id="dup-alumnos"></div>
        </div>
      </div>
    </div>
  </div>
  <div class="mt-3">
    <button class="btn btn-outline-success" id="btn-refresh-indexes"><i class="fa-solid fa-database me-1"></i> Crear/Refrescar índices únicos</button>
    <span class="small text-muted ms-2" id="refresh-result"></span>
  </div>
  <script>
    (function(){
      const csrf = '<?php echo htmlspecialchars($csrf); ?>';
      const esc = s => (s ?? '').toString().replace(/[&<>\"]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));
      function renderGroup(title, items, key, type){
        const label = (i) => type==='materias' ? (esc(i.nombre)+' · '+esc(i.clave)) : (type==='profesores' ? (esc(i.matricula)+' · '+esc(i.email)) : (esc(i.matricula)+' · '+esc(i.nombre)+' '+esc(i.apellido)));
        const radios = items.map(i => `<div class="form-check"><input class="form-check-input" type="radio" name="primary-${type}-${esc(key)}" value="${i.id}" id="${type}-${key}-${i.id}"><label class="form-check-label" for="${type}-${key}-${i.id}">${label(i)} <span class="text-muted">ID ${i.id}</span></label></div>`).join('');
        const btn = `<button class="btn btn-sm btn-primary mt-2" data-action="merge" data-type="${type}" data-key="${esc(key)}">Unificar</button>`;
        return `<div class="mb-3 p-2 border rounded"><div class="mb-2 fw-bold">${title}</div>${radios}${btn}</div>`;
      }
      function load(type){
        fetch(`/api/admin/dedup?type=${type}`).then(r=>r.json()).then(json => {
          const cont = document.querySelector(type==='materias' ? '#dup-materias' : (type==='profesores' ? '#dup-profesores' : '#dup-alumnos'));
          if (!json.success || !(json.data||[]).length){ cont.innerHTML = '<div class="text-muted">Sin duplicados</div>'; return; }
          const html = json.data.map(group => {
            const title = type==='materias' ? ('Clave '+esc(group.key)) : ('Email '+esc(group.key));
            return renderGroup(title, group.items, group.key, type);
          }).join('');
          cont.innerHTML = html;
        });
      }
      function onMerge(e){
        const b = e.target.closest('button[data-action="merge"]'); if (!b) return;
        const type = b.getAttribute('data-type'); const key = b.getAttribute('data-key');
        const name = `primary-${type}-${key}`;
        const sel = document.querySelector(`input[name="${CSS.escape(name)}"]:checked`);
        if (!sel) return;
        const primary = sel.value;
        const body = new URLSearchParams();
        body.set('csrf_token', csrf);
        body.set('action', type==='materias' ? 'merge_materias' : 'merge_profesores');
        if (type==='materias') body.set('clave', key); else if (type==='profesores') body.set('email', key); else body.set('matricula', key);
        body.set('primary_id', primary);
        if (type==='alumnos') body.set('action', 'merge_alumnos');
        fetch('/api/admin/dedup', { method:'POST', body }).then(r=>r.json()).then(json => { if (json.success) { load(type); } });
      }
      document.addEventListener('click', onMerge);
      load('materias');
      load('profesores');
      load('alumnos');

      document.querySelector('#btn-refresh-indexes').addEventListener('click', function(){
        fetch('/api/kpis/admin').then(r=>r.json()).then(json => {
          document.querySelector('#refresh-result').textContent = 'OK · Índices verificados';
        }).catch(()=>{ document.querySelector('#refresh-result').textContent = 'Error'; });
      });
    })();
  </script>
</div>

<?php $content = ob_get_clean(); include __DIR__ . '/../../Views/layout.php'; ?>
