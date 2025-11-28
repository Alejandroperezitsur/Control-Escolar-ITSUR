<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$containerFluid = true;
ob_start();
?>
<link href="<?php echo $base; ?>/assets/css/desktop-fixes.css" rel="stylesheet">
<style>
    .sidebar-fixed { position: sticky; top: 1rem; }
    .card-module { min-height: 420px; }
    .list-scroll { max-height: 360px; overflow: auto; }
    .btn-wide { min-width: 180px; }
    .input-lg { padding: .6rem .75rem; font-size: 1rem; }
    .saving { opacity:.7; pointer-events:none; }
</style>

<div class="row g-3">
    <aside class="col-12 col-lg-3">
      <div class="card sidebar-fixed">
        <div class="card-header"><strong>Menú</strong></div>
        <div class="list-group list-group-flush" id="menu-modulos">
          <a class="list-group-item list-group-item-action active" data-target="#mod-usuarios" href="#">Usuarios</a>
          <a class="list-group-item list-group-item-action" data-target="#mod-materias" href="#">Materias</a>
          <a class="list-group-item list-group-item-action" data-target="#mod-grupos" href="#">Grupos</a>
          <a class="list-group-item list-group-item-action" data-target="#mod-calificaciones" href="#">Calificaciones</a>
        </div>
      </div>
      <div class="card mt-3 sidebar-fixed">
        <div class="card-header"><strong>Accesos directos</strong></div>
        <div class="card-body d-grid gap-2">
          <a href="<?php echo $base; ?>/profesor_grupos.php" class="btn btn-outline-primary btn-lg">
            <i class="bi bi-collection me-2"></i> Mis Grupos (vista profesor)
          </a>
        </div>
      </div>
    </aside>
    <main class="col-12 col-lg-9">
      <div class="row g-3" id="modulos-grid">
        <!-- 2/3/4 columnas a 0/1280/1600 px con Bootstrap -->
        <div class="col-12 col-xl-6 col-xxl-3" id="mod-usuarios">
          <div class="card card-module">
            <div class="card-header d-flex justify-content-between align-items-center">
              <strong>Usuarios</strong>
              <input class="form-control form-control-sm" id="search-usuarios" placeholder="Buscar…" style="max-width:220px;">
            </div>
            <div class="card-body">
              <form id="form-usuario" class="row g-2 mb-3">
                <div class="col-12 col-md-6">
                  <label class="form-label">Matrícula</label>
                  <input class="form-control input-lg" name="matricula" required>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Email</label>
                  <input type="email" class="form-control input-lg" name="email" required>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Password</label>
                  <input type="password" class="form-control input-lg" name="password" required>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Rol</label>
                  <select class="form-select input-lg" name="rol" required>
                    <option value="alumno">Alumno</option>
                    <option value="profesor">Profesor</option>
                    <option value="admin">Admin</option>
                  </select>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Activo</label>
                  <select class="form-select input-lg" name="activo" required>
                    <option value="1">Sí</option>
                    <option value="0">No</option>
                  </select>
                </div>
                <div class="col-12">
                  <button class="btn btn-primary btn-wide" type="submit"><i class="bi bi-plus-circle"></i> Crear</button>
                </div>
              </form>
              <div class="list-scroll" id="list-usuarios"></div>
              <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="small text-muted" id="pag-usuarios"></div>
                <div>
                  <button class="btn btn-outline-secondary btn-sm" id="prev-usuarios">Prev</button>
                  <button class="btn btn-outline-secondary btn-sm" id="next-usuarios">Next</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-6 col-xxl-3" id="mod-materias">
          <div class="card card-module">
            <div class="card-header d-flex justify-content-between align-items-center">
              <strong>Materias</strong>
              <input class="form-control form-control-sm" id="search-materias" placeholder="Buscar…" style="max-width:220px;">
            </div>
            <div class="card-body">
              <form id="form-materia" class="row g-2 mb-3">
                <div class="col-12">
                  <label class="form-label">Nombre</label>
                  <input class="form-control input-lg" name="nombre" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Clave</label>
                  <input class="form-control input-lg" name="clave" required>
                </div>
                <div class="col-12">
                  <button class="btn btn-primary btn-wide" type="submit"><i class="bi bi-plus-circle"></i> Crear</button>
                </div>
              </form>
              <div class="list-scroll" id="list-materias"></div>
              <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="small text-muted" id="pag-materias"></div>
                <div>
                  <button class="btn btn-outline-secondary btn-sm" id="prev-materias">Prev</button>
                  <button class="btn btn-outline-secondary btn-sm" id="next-materias">Next</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-6 col-xxl-3" id="mod-grupos">
          <div class="card card-module">
            <div class="card-header d-flex justify-content-between align-items-center">
              <strong>Grupos</strong>
              <input class="form-control form-control-sm" id="search-grupos" placeholder="Buscar…" style="max-width:220px;">
            </div>
            <div class="card-body">
              <form id="form-grupo" class="row g-2 mb-3">
                <div class="col-12 col-md-6">
                  <label class="form-label">Nombre</label>
                  <input class="form-control input-lg" name="nombre" required>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Ciclo</label>
                  <input class="form-control input-lg" name="ciclo" required>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Materia</label>
                  <select class="form-select input-lg" name="materia_id" id="sel-grupo-materia" required></select>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Profesor</label>
                  <select class="form-select input-lg" name="profesor_id" id="sel-grupo-profesor" required></select>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Cupo</label>
                  <input type="number" min="1" class="form-control input-lg" name="cupo" required>
                </div>
                <div class="col-12">
                  <button class="btn btn-primary btn-wide" type="submit"><i class="bi bi-plus-circle"></i> Crear</button>
                </div>
              </form>
              <div class="list-scroll" id="list-grupos"></div>
              <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="small text-muted" id="pag-grupos"></div>
                <div>
                  <button class="btn btn-outline-secondary btn-sm" id="prev-grupos">Prev</button>
                  <button class="btn btn-outline-secondary btn-sm" id="next-grupos">Next</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-6 col-xxl-3" id="mod-calificaciones">
          <div class="card card-module">
            <div class="card-header d-flex justify-content-between align-items-center">
              <strong>Calificaciones</strong>
              <input class="form-control form-control-sm" id="search-calificaciones" placeholder="Buscar…" style="max-width:220px;">
            </div>
            <div class="card-body">
              <div class="row g-3 mb-3 align-items-end">
                <div class="col-12 col-md-4">
                  <label class="form-label">Materia</label>
                  <select class="form-select input-lg" id="filter-cal-materia"><option value="">Todas</option></select>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label">Grupo</label>
                  <select class="form-select input-lg" id="filter-cal-grupo" disabled><option value="">Todos</option></select>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label">Alumno</label>
                  <select class="form-select input-lg" id="filter-cal-alumno" disabled><option value="">Todos</option></select>
                </div>
              </div>
              <form id="form-calificacion" class="row g-2 mb-3">
                <div class="col-12 col-md-6">
                  <label class="form-label">Alumno</label>
                  <select class="form-select input-lg" name="alumno_id" id="sel-cal-alumno" required></select>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label">Grupo</label>
                  <select class="form-select input-lg" name="grupo_id" id="sel-cal-grupo" required></select>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label">Parcial 1</label>
                  <input type="number" min="0" max="100" step="0.01" class="form-control input-lg" name="parcial1" required>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label">Parcial 2</label>
                  <input type="number" min="0" max="100" step="0.01" class="form-control input-lg" name="parcial2" required>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label">Final</label>
                  <input type="number" min="0" max="100" step="0.01" class="form-control input-lg" name="final" required>
                </div>
                <div class="col-12">
                  <button class="btn btn-primary btn-wide" type="submit"><i class="bi bi-plus-circle"></i> Guardar</button>
                </div>
              </form>
              <div class="list-scroll" id="list-calificaciones"></div>
              <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="small text-muted" id="pag-calificaciones"></div>
                <div>
                  <button class="btn btn-outline-secondary btn-sm" id="prev-calificaciones">Prev</button>
                  <button class="btn btn-outline-secondary btn-sm" id="next-calificaciones">Next</button>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </main>
</div>

<script>
  (function(){
    // Helpers
    const qs = sel => document.querySelector(sel);
    const qsa = sel => Array.from(document.querySelectorAll(sel));
    const html = (el, s) => { el.innerHTML = s; };
    const esc = s => (s ?? '').toString().replace(/[&<>\"]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]));
    function paginateInfo(p){ return `Página ${p.page} de ${p.total_pages} · ${p.total_items} registros`; }

    // Menu switching
    qsa('#menu-modulos a').forEach(a => {
      a.addEventListener('click', e => {
        e.preventDefault();
        qsa('#menu-modulos a').forEach(x => x.classList.remove('active'));
        a.classList.add('active');
        const target = a.getAttribute('data-target');
        qsa('#modulos-grid > div').forEach(card => card.style.display = (card.id === target ? '' : 'none'));
      });
    });
    // Show only Usuarios by default
    qsa('#modulos-grid > div').forEach(card => card.style.display = (card.id === 'mod-usuarios' ? '' : 'none'));

    // State
    let pag = { usuarios:{page:1,limit:10}, materias:{page:1,limit:10}, grupos:{page:1,limit:10}, calificaciones:{page:1,limit:10} };

    // Loaders
    function loadUsuarios(){
      const s = qs('#search-usuarios').value.trim();
      fetch(`<?php echo $base; ?>/api/admin/usuarios?page=${pag.usuarios.page}&limit=${pag.usuarios.limit}${s?`&q=${encodeURIComponent(s)}`:''}`)
        .then(r=>r.json()).then(json => {
          if (!json.success) return html(qs('#list-usuarios'), '<div class="alert alert-danger">Error al cargar</div>');
          html(qs('#list-usuarios'), renderList(json.data, renderUsuarioItem));
          qs('#pag-usuarios').textContent = paginateInfo(json.pagination);
        });
    }
    function renderUsuarioItem(u){
      return `<div class="d-flex justify-content-between align-items-center py-2 border-bottom">
        <div>
          <strong>${esc(u.matricula)}</strong> · ${esc(u.email)}
          <div class="small text-muted">Rol: ${esc(u.rol)} · Activo: ${u.activo ? 'Sí' : 'No'}</div>
        </div>
        <div>
          <button class="btn btn-sm btn-outline-primary me-2" data-action="edit" data-id="${u.id}">Editar</button>
          <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${u.id}">Eliminar</button>
        </div>
      </div>`;
    }

    function loadMaterias(){
      const s = qs('#search-materias').value.trim();
      fetch(`<?php echo $base; ?>/api/admin/materias?page=${pag.materias.page}&limit=${pag.materias.limit}${s?`&q=${encodeURIComponent(s)}`:''}`)
        .then(r=>r.json()).then(json => {
          if (!json.success) return html(qs('#list-materias'), '<div class="alert alert-danger">Error al cargar</div>');
          html(qs('#list-materias'), renderList(json.data, renderMateriaItem));
          qs('#pag-materias').textContent = paginateInfo(json.pagination);
          // Fill selects for grupos
          fillSelect('#sel-grupo-materia', json.data, 'id', 'nombre');
        });
    }
    function renderMateriaItem(m){
      return `<div class="d-flex justify-content-between align-items-center py-2 border-bottom">
        <div>
          <strong>${esc(m.nombre)}</strong>
          <div class="small text-muted">Clave: ${esc(m.clave)} · ID: ${m.id}</div>
        </div>
        <div>
          <button class="btn btn-sm btn-outline-primary me-2" data-action="edit" data-id="${m.id}">Editar</button>
          <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${m.id}">Eliminar</button>
        </div>
      </div>`;
    }

    function loadGrupos(){
      const s = qs('#search-grupos').value.trim();
      fetch(`<?php echo $base; ?>/api/admin/grupos?page=${pag.grupos.page}&limit=${pag.grupos.limit}${s?`&q=${encodeURIComponent(s)}`:''}`)
        .then(r=>r.json()).then(json => {
          if (!json.success) return html(qs('#list-grupos'), '<div class="alert alert-danger">Error al cargar</div>');
          html(qs('#list-grupos'), renderList(json.data, renderGrupoItem));
          qs('#pag-grupos').textContent = paginateInfo(json.pagination);
          // Fill selects for calificaciones
          fillSelect('#sel-cal-grupo', json.data, 'id', 'nombre');
        });
      // Also load profesores for grupo select
      fetch(`<?php echo $base; ?>/api/admin/usuarios?page=1&limit=1000&rol=profesor`).then(r=>r.json()).then(json => {
        if (json.success) fillSelect('#sel-grupo-profesor', json.data, 'id', 'matricula');
      });
    }
    function renderGrupoItem(g){
      return `<div class="d-flex justify-content-between align-items-center py-2 border-bottom">
        <div>
          <strong>${esc(g.nombre)}</strong> · ${esc(g.ciclo)}
          <div class="small text-muted">Materia: ${esc(g.materia_nombre || g.materia_id)} · Profesor: ${esc(g.profesor_matricula || g.profesor_id)} · ID: ${g.id}</div>
        </div>
        <div>
          <button class="btn btn-sm btn-outline-primary me-2" data-action="edit" data-id="${g.id}">Editar</button>
          <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${g.id}">Eliminar</button>
        </div>
      </div>`;
    }

    function loadCalificaciones(){
      const s = qs('#search-calificaciones').value.trim();
      const materiaId = qs('#filter-cal-materia').value || '';
      const grupoId = qs('#filter-cal-grupo').value || '';
      const alumnoId = qs('#filter-cal-alumno').value || '';
      const params = new URLSearchParams({ page: pag.calificaciones.page, limit: pag.calificaciones.limit });
      if (s) params.set('q', s);
      if (materiaId) params.set('materia_id', materiaId);
      if (grupoId) params.set('grupo_id', grupoId);
      if (alumnoId) params.set('alumno_id', alumnoId);
      fetch(`<?php echo $base; ?>/api/admin/calificaciones?${params.toString()}`)
        .then(r=>r.json()).then(json => {
          if (!json.success) return html(qs('#list-calificaciones'), '<div class="alert alert-danger">Error al cargar</div>');
          html(qs('#list-calificaciones'), renderList(json.data, renderCalItem));
          if (json.pagination) qs('#pag-calificaciones').textContent = paginateInfo(json.pagination);
        });
      // Fill alumnos select principal
      fetch(`<?php echo $base; ?>/api/admin/usuarios?page=1&limit=1000&rol=alumno`).then(r=>r.json()).then(json => {
        if (json.success) fillSelect('#sel-cal-alumno', json.data, 'id', 'matricula');
      });
    }
    function renderCalItem(c){
      const alumno = `${esc(c.alumno_nombre || '')} ${esc(c.alumno_apellido || '')}`.trim();
      const matricula = esc(c.alumno_matricula || c.alumno_id || '');
      const grupo = esc(c.grupo_nombre || c.grupo_id || '');
      const materia = esc(c.materia_nombre || '');
      const clave = esc(c.materia_clave || '');
      return `<div class="d-flex justify-content-between align-items-center py-3 border-bottom">
        <div>
          <div><strong>Alumno:</strong> ${alumno || '—'} <span class="text-muted">· ${matricula}</span></div>
          <div class="small text-muted mt-1"><strong>Grupo:</strong> ${grupo} · <strong>Clave:</strong> ${clave} · <strong>Materia:</strong> ${materia}</div>
          <div class="small text-muted mt-1">P1: ${c.parcial1 ?? '-'} · P2: ${c.parcial2 ?? '-'} · Final: ${c.final ?? '-'}</div>
        </div>
        <div>
          <button class="btn btn-sm btn-outline-primary me-2" data-action="edit" data-id="${c.id}">Editar</button>
          <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${c.id}">Eliminar</button>
        </div>
      </div>`;
    }

    function renderList(items, renderer){
      if (!items || !items.length) return '<div class="text-muted">Sin resultados disponibles</div>';
      return items.map(renderer).join('');
    }
    function fillSelect(sel, items, valueKey, labelKey, includeBlank = false, blankLabel = 'Todos'){
      const el = qs(sel); if (!el) return;
      const opts = (items||[]).map(i => `<option value="${i[valueKey]}">${esc(i[labelKey])}</option>`).join('');
      el.innerHTML = (includeBlank ? `<option value="">${blankLabel}</option>` : '') + opts;
    }

    // Forms
    qs('#form-usuario').addEventListener('submit', e => {
      e.preventDefault(); const f = e.target; f.classList.add('saving');
      const body = new URLSearchParams(new FormData(f));
      fetch('<?php echo $base; ?>/api/admin/usuarios', { method:'POST', body })
        .then(r=>r.json()).then(json => { f.classList.remove('saving'); if (json.success) { f.reset(); loadUsuarios(); } else alert('Error al crear usuario'); });
    });
    qs('#list-usuarios').addEventListener('click', e => {
      const b = e.target.closest('button'); if (!b) return;
      const id = b.getAttribute('data-id'); const action = b.getAttribute('data-action');
      if (action === 'delete') {
        const body = new URLSearchParams(); body.set('id', id);
        fetch(`<?php echo $base; ?>/api/admin/usuarios/${id}`, { method:'POST', body })
          .then(r=>r.json()).then(json => { if (json.success) loadUsuarios(); else alert('No se pudo eliminar'); });
      }
      if (action === 'edit') {
        // Minimal inline edit: re-use create form values and send update
        const matricula = prompt('Nueva matrícula:'); if (!matricula) return;
        const body = new URLSearchParams(); body.set('matricula', matricula);
        fetch(`<?php echo $base; ?>/api/admin/usuarios/${id}`, { method:'POST', body })
          .then(r=>r.json()).then(json => { if (json.success) loadUsuarios(); else alert('No se pudo actualizar'); });
      }
    });

    qs('#form-materia').addEventListener('submit', e => {
      e.preventDefault(); const f = e.target; f.classList.add('saving');
      const body = new URLSearchParams(new FormData(f));
      fetch('<?php echo $base; ?>/api/admin/materias', { method:'POST', body })
        .then(r=>r.json()).then(json => { f.classList.remove('saving'); if (json.success) { f.reset(); loadMaterias(); } else alert('Error al crear materia'); });
    });
    qs('#list-materias').addEventListener('click', e => {
      const b = e.target.closest('button'); if (!b) return;
      const id = b.getAttribute('data-id'); const action = b.getAttribute('data-action');
      if (action === 'delete') {
        const body = new URLSearchParams(); body.set('id', id);
        fetch(`<?php echo $base; ?>/api/admin/materias/${id}`, { method:'POST', body })
          .then(r=>r.json()).then(json => { if (json.success) loadMaterias(); else alert('No se pudo eliminar'); });
      }
      if (action === 'edit') {
        const nombre = prompt('Nuevo nombre:'); if (!nombre) return;
        const body = new URLSearchParams(); body.set('nombre', nombre);
        fetch(`<?php echo $base; ?>/api/admin/materias/${id}`, { method:'POST', body })
          .then(r=>r.json()).then(json => { if (json.success) loadMaterias(); else alert('No se pudo actualizar'); });
      }
    });

    qs('#form-grupo').addEventListener('submit', e => {
      e.preventDefault(); const f = e.target; f.classList.add('saving');
      const body = new URLSearchParams(new FormData(f));
      fetch('<?php echo $base; ?>/api/admin/grupos', { method:'POST', body })
        .then(r=>r.json()).then(json => { f.classList.remove('saving'); if (json.success) { f.reset(); loadGrupos(); } else alert('Error al crear grupo'); });
    });
    qs('#list-grupos').addEventListener('click', e => {
      const b = e.target.closest('button'); if (!b) return;
      const id = b.getAttribute('data-id'); const action = b.getAttribute('data-action');
      if (action === 'delete') {
        const body = new URLSearchParams(); body.set('id', id);
        fetch(`<?php echo $base; ?>/api/admin/grupos/${id}`, { method:'POST', body })
          .then(r=>r.json()).then(json => { if (json.success) loadGrupos(); else alert('No se pudo eliminar'); });
      }
      if (action === 'edit') {
        const nombre = prompt('Nuevo nombre:'); if (!nombre) return;
        const body = new URLSearchParams(); body.set('nombre', nombre);
        fetch(`<?php echo $base; ?>/api/admin/grupos/${id}`, { method:'POST', body })
          .then(r=>r.json()).then(json => { if (json.success) loadGrupos(); else alert('No se pudo actualizar'); });
      }
    });

    qs('#form-calificacion').addEventListener('submit', e => {
      e.preventDefault(); const f = e.target; f.classList.add('saving');
      const fd = new FormData(f);
      // Frontend validation 0–100
      for (const k of ['parcial1','parcial2','final']) {
        const v = parseFloat(fd.get(k)); if (isNaN(v) || v < 0 || v > 100) { alert('Valores deben estar entre 0 y 100'); f.classList.remove('saving'); return; }
      }
      const body = new URLSearchParams(fd);
      fetch('<?php echo $base; ?>/api/admin/calificaciones', { method:'POST', body })
        .then(r=>r.json()).then(json => { f.classList.remove('saving'); if (json.success) { f.reset(); loadCalificaciones(); } else alert('Error al guardar calificación'); });
    });
    qs('#list-calificaciones').addEventListener('click', e => {
      const b = e.target.closest('button'); if (!b) return;
      const id = b.getAttribute('data-id'); const action = b.getAttribute('data-action');
      if (action === 'delete') {
        const body = new URLSearchParams(); body.set('id', id);
        fetch(`<?php echo $base; ?>/api/admin/calificaciones/${id}`, { method:'POST', body })
          .then(r=>r.json()).then(json => { if (json.success) loadCalificaciones(); else alert('No se pudo eliminar'); });
      }
      if (action === 'edit') {
        const final = prompt('Nuevo final (0–100):'); const v = parseFloat(final);
        if (isNaN(v) || v < 0 || v > 100) return alert('Valor inválido');
        const body = new URLSearchParams(); body.set('final', String(v));
        fetch(`<?php echo $base; ?>/api/admin/calificaciones/${id}`, { method:'POST', body })
          .then(r=>r.json()).then(json => { if (json.success) loadCalificaciones(); else alert('No se pudo actualizar'); });
      }
    });

    // Pagination buttons
    qs('#prev-usuarios').addEventListener('click', ()=>{ pag.usuarios.page = Math.max(1, pag.usuarios.page-1); loadUsuarios(); });
    qs('#next-usuarios').addEventListener('click', ()=>{ pag.usuarios.page += 1; loadUsuarios(); });
    qs('#prev-materias').addEventListener('click', ()=>{ pag.materias.page = Math.max(1, pag.materias.page-1); loadMaterias(); });
    qs('#next-materias').addEventListener('click', ()=>{ pag.materias.page += 1; loadMaterias(); });
    qs('#prev-grupos').addEventListener('click', ()=>{ pag.grupos.page = Math.max(1, pag.grupos.page-1); loadGrupos(); });
    qs('#next-grupos').addEventListener('click', ()=>{ pag.grupos.page += 1; loadGrupos(); });
    qs('#prev-calificaciones').addEventListener('click', ()=>{ pag.calificaciones.page = Math.max(1, pag.calificaciones.page-1); loadCalificaciones(); });
    qs('#next-calificaciones').addEventListener('click', ()=>{ pag.calificaciones.page += 1; loadCalificaciones(); });

    // Search inputs
    qs('#search-usuarios').addEventListener('input', debounce(loadUsuarios, 300));
    qs('#search-materias').addEventListener('input', debounce(loadMaterias, 300));
    qs('#search-grupos').addEventListener('input', debounce(loadGrupos, 300));
    qs('#search-calificaciones').addEventListener('input', debounce(loadCalificaciones, 300));

    // Dependent selects for Calificaciones filters
    function loadMateriasFilter(){
      fetch('<?php echo $base; ?>/api/admin/materias?page=1&limit=1000')
        .then(r=>r.json()).then(json => {
          if (!json.success) return;
          fillSelect('#filter-cal-materia', json.data, 'id', 'nombre', true);
        });
    }
    function onMateriaChange(){
      const mid = qs('#filter-cal-materia').value;
      const grupoSel = qs('#filter-cal-grupo');
      const alumnoSel = qs('#filter-cal-alumno');
      grupoSel.disabled = !mid; alumnoSel.disabled = true;
      // Reset dependent options
      fillSelect('#filter-cal-grupo', [], 'id', 'nombre', true);
      fillSelect('#filter-cal-alumno', [], 'id', 'matricula', true);
      if (!mid) { loadCalificaciones(); return; }
      fetch(`<?php echo $base; ?>/api/admin/grupos?page=1&limit=1000&materia_id=${encodeURIComponent(mid)}`)
        .then(r=>r.json()).then(json => {
          if (json.success) { fillSelect('#filter-cal-grupo', json.data, 'id', 'nombre', true); grupoSel.disabled = false; }
          loadCalificaciones();
        });
    }
    function onGrupoChange(){
      const gid = qs('#filter-cal-grupo').value;
      const alumnoSel = qs('#filter-cal-alumno');
      alumnoSel.disabled = !gid;
      fillSelect('#filter-cal-alumno', [], 'id', 'matricula', true);
      if (!gid) { loadCalificaciones(); return; }
      fetch(`<?php echo $base; ?>/api/admin/alumnos?page=1&limit=1000&grupo_id=${encodeURIComponent(gid)}`)
        .then(r=>r.json()).then(json => {
          if (json.success) { fillSelect('#filter-cal-alumno', json.data, 'id', 'matricula', true); alumnoSel.disabled = false; }
          loadCalificaciones();
        });
    }
    function onAlumnoChange(){ loadCalificaciones(); }

    qs('#filter-cal-materia').addEventListener('change', onMateriaChange);
    qs('#filter-cal-grupo').addEventListener('change', onGrupoChange);
    qs('#filter-cal-alumno').addEventListener('change', onAlumnoChange);

    function debounce(fn, wait){ let t; return (...args)=>{ clearTimeout(t); t=setTimeout(()=>fn(...args), wait); }; }

    // Initial load
    loadUsuarios();
    loadMaterias();
    loadGrupos();
    loadMateriasFilter();
    loadCalificaciones();
  })();
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
