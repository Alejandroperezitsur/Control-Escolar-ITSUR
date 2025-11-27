document.addEventListener('DOMContentLoaded', () => {

  // Marcar activo en el sidebar según la URL
  const links = document.querySelectorAll('.app-sidebar .menu-section a');
  const path = location.pathname.replace(/\\/g, '/');
  links.forEach(a => {
    const href = a.getAttribute('href');
    if (href && path.endsWith(href)) {
      a.classList.add('active');
    }
    // Insertar icono si existe atributo data-icon (Bootstrap Icons)
    const icon = a.getAttribute('data-icon');
    if (icon) {
      const i = document.createElement('i');
      i.className = `bi ${icon} menu-icon`;
      a.prepend(i);
    }
  });

  // Exportación CSV/PDF
  document.querySelectorAll('[data-export="csv"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.getAttribute('data-target');
      const table = document.querySelector(target);
      if (!table) return;
      const csv = tableToCSV(table);
      const filename = btn.getAttribute('data-filename') || 'export.csv';
      const tsMode = (btn.getAttribute('data-timestamp') || '').toLowerCase();
      const finalName = tsMode ? withTimestamp(filename, tsMode) : filename;
      downloadCSV(csv, finalName);
    });
  });

  document.querySelectorAll('[data-export="pdf"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.getAttribute('data-target');
      if (target) {
        // Impresión específica de tabla
        printTable(target);
      } else {
        // Impresión general de la página
        window.print();
      }
    });
  });


  // --- TEMA: toggle global y robusto ---
  const THEME_KEY = 'sicenet-theme';
  const getSystemPref = () => {
    try { return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light'; } catch { return 'dark'; }
  };
  const getStoredTheme = () => {
    try { return localStorage.getItem(THEME_KEY); } catch { return null; }
  };
  function updateThemeToggles(theme) {
    document.querySelectorAll('#theme-toggle, #themeToggle').forEach(btn => {
      btn.textContent = (theme === 'light') ? '\u2600\ufe0f' : '\ud83c\udf19';
      btn.setAttribute('aria-label', (theme === 'light') ? 'Cambiar a modo oscuro' : 'Cambiar a modo claro');
    });
  }
  function applyTheme(theme) {
    const t = theme === 'light' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', t);
    if (document.body) document.body.setAttribute('data-theme', t);
    try {
      document.getElementsByTagName('html')[0].setAttribute('data-theme', t);
      document.getElementsByTagName('body')[0].setAttribute('data-theme', t);
    } catch(e){}
    updateThemeToggles(t);
    document.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: t } }));
  }
  // Inicializar tema según preferencia guardada o sistema
  const initialTheme = getStoredTheme() || getSystemPref() || 'dark';
  applyTheme(initialTheme);
  // Eliminar listeners previos para evitar duplicados
  document.querySelectorAll('#theme-toggle, #themeToggle').forEach(btn => {
    btn.removeEventListener('click', btn._themeHandler || (()=>{}));
    btn._themeHandler = function() {
      const current = document.documentElement.getAttribute('data-theme') || 'dark';
      const next = current === 'light' ? 'dark' : 'light';
      applyTheme(next);
      try { localStorage.setItem(THEME_KEY, next); } catch {}
    };
    btn.addEventListener('click', btn._themeHandler, { passive: true });
  });

  // Autoactivar ordenamiento en tablas con clase .table-sort
  document.querySelectorAll('table.table-sort').forEach(tbl => enableTableSort(tbl));

  // Filtro rápido: inputs con data-quick-filter-for="#selector"
  document.querySelectorAll('[data-quick-filter-for]').forEach(input => {
    const sel = input.getAttribute('data-quick-filter-for');
    const table = document.querySelector(sel);
    if (!table) return;
    input.addEventListener('input', () => {
      const term = input.value.toLowerCase();
      const rows = Array.from(table.querySelectorAll('tbody tr'));
      let visibleCount = 0;
      rows.forEach(row => {
        const txt = row.textContent.toLowerCase();
        const show = txt.includes(term);
        row.style.display = show ? '' : 'none';
        if (show) visibleCount++;
      });
      const emptyState = table.querySelector('tbody .empty-state-row');
      if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? '' : 'none';
      }
    });
  });

  // Evitar doble envío: deshabilita botón submit y muestra spinner ligero
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', (e) => {
      const btn = form.querySelector('button[type="submit"]');
      if (btn && !btn.disabled) {
        btn.disabled = true;
        const original = btn.innerHTML;
        btn.dataset.originalText = original;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + original;
        // Rehabilitar si no hay navegación (por ejemplo, validación)
        setTimeout(() => {
          btn.disabled = false;
          btn.innerHTML = btn.dataset.originalText || original;
        }, 3000);
      }
    }, { passive: true });
  });
});

// -------- Tabla: ordenamiento sencillo por encabezado --------
function enableTableSort(table) {
  if (!table) return;
  const headers = Array.from(table.querySelectorAll('thead th'));
  headers.forEach((th, index) => {
    th.style.cursor = 'pointer';
    th.addEventListener('click', () => {
      const tbody = table.querySelector('tbody');
      const rows = Array.from(tbody.querySelectorAll('tr'));
      const asc = th.dataset.sort !== 'asc'; // toggle
      headers.forEach(h => delete h.dataset.sort);
      th.dataset.sort = asc ? 'asc' : 'desc';
      rows.sort((a, b) => {
        const av = (a.querySelectorAll('td')[index]?.textContent || '').trim();
        const bv = (b.querySelectorAll('td')[index]?.textContent || '').trim();
        const an = parseFloat(av); const bn = parseFloat(bv);
        const cmp = (!isNaN(an) && !isNaN(bn)) ? (an - bn) : av.localeCompare(bv);
        return asc ? cmp : -cmp;
      });
      rows.forEach(r => tbody.appendChild(r));
    });
  });
}

// -------- Filtros: helpers para guardar/cargar --------
function saveFilters(elements, storageKey, label) {
  const data = {};
  elements.forEach(el => data[el.id || el.name] = (el.type === 'checkbox' ? el.checked : el.value));
  const payload = { label: label || new Date().toLocaleString(), data };
  const list = JSON.parse(localStorage.getItem(storageKey) || '[]');
  list.push(payload);
  localStorage.setItem(storageKey, JSON.stringify(list));
}

function loadFilterList(storageKey) {
  try { return JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch { return []; }
}

function applyFilters(elements, record) {
  elements.forEach(el => {
    const key = el.id || el.name;
    if (record.data.hasOwnProperty(key)) {
      if (el.type === 'checkbox') el.checked = !!record.data[key];
      else el.value = record.data[key];
      el.dispatchEvent(new Event('input'));
      el.dispatchEvent(new Event('change'));
    }
  });
}

// -------- Utilidad: valores únicos por columna --------
function collectUniqueColumnValues(table, colIndex) {
  const set = new Set();
  Array.from(table.querySelectorAll('tbody tr')).forEach(row => {
    const txt = (row.querySelectorAll('td')[colIndex]?.textContent || '').trim();
    if (txt) set.add(txt);
  });
  return Array.from(set.values()).sort();
}

window.TableUtils = { enableTableSort, saveFilters, loadFilterList, applyFilters, collectUniqueColumnValues };

function tableToCSV(table) {
  const rows = Array.from(table.querySelectorAll('tr'));
  const visibleRows = rows.filter(row => {
    const style = window.getComputedStyle(row);
    return style.display !== 'none' && !row.classList.contains('d-none');
  });
  return visibleRows.map(row => {
    const cells = Array.from(row.querySelectorAll('th,td'));
    return cells.map(cell => escapeCSV(cell.textContent.trim())).join(',');
  }).join('\n');
}

function escapeCSV(text) {
  const needsQuotes = /[",\n]/.test(text);
  let escaped = text.replace(/"/g, '""');
  return needsQuotes ? `"${escaped}"` : escaped;
}

function downloadCSV(csv, filename) {
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

function withTimestamp(filename, mode = 'date') {
  try {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    const datePart = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
    const timePart = `${pad(now.getHours())}-${pad(now.getMinutes())}`;
    const ts = mode === 'full' ? `${datePart}_${timePart}` : datePart;
    const dotIndex = filename.lastIndexOf('.');
    if (dotIndex !== -1) {
      const name = filename.substring(0, dotIndex);
      const ext = filename.substring(dotIndex);
      return `${name}_${ts}${ext}`;
    }
    return `${filename}_${ts}.csv`;
  } catch {
    return filename;
  }
}

function printTable(tableSelector) {
  const table = document.querySelector(tableSelector);
  if (!table) {
    window.print();
    return;
  }
  
  // Crear ventana de impresión con solo la tabla
  const printWindow = window.open('', '_blank');
  const title = document.title || 'Exportación PDF';
  
  printWindow.document.write(`
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>${title}</title>
      <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #000; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        h1 { color: #333; margin-bottom: 10px; }
        .empty-state-row { font-style: italic; color: #666; }
        @media (max-width: 576px) {
          th, td { padding: 6px; font-size: 12px; }
        }
        @media print {
          body { margin: 0; }
          table { page-break-inside: auto; }
          tr { page-break-inside: avoid; page-break-after: auto; }
        }
      </style>
    </head>
    <body>
      <h1>${title}</h1>
      ${table.outerHTML}
    </body>
    </html>
  `);
  
  printWindow.document.close();
  printWindow.focus();
  
  // Esperar a que cargue y luego imprimir
  setTimeout(() => {
    printWindow.print();
    printWindow.close();
  }, 250);
}

// -------- CSRF helpers: lectura y uso automático --------
(function setupCSRF() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  const token = meta?.content || '';
  // Helper: añade csrf_token a FormData o URLSearchParams
  function appendCSRF(body) {
    try {
      if (!token) return body;
      if (body instanceof FormData) {
        if (!body.has('csrf_token')) body.append('csrf_token', token);
        return body;
      }
      if (body instanceof URLSearchParams) {
        if (!body.has('csrf_token')) body.set('csrf_token', token);
        return body;
      }
      // Si es JSON, transformar a URLSearchParams para compatibilidad con PHP $_POST
      if (typeof body === 'string') {
        // No tocar
        return body;
      }
      if (body && typeof body === 'object') {
        const params = new URLSearchParams();
        Object.entries(body).forEach(([k,v]) => params.append(k, v));
        params.set('csrf_token', token);
        return params;
      }
      return body;
    } catch { return body; }
  }
  // Wrapper fetch: si método es POST/PUT/DELETE, asegurar csrf_token en cuerpo
  const _fetch = window.fetch.bind(window);
  window.csrfFetch = async function(url, options = {}) {
    const opts = { ...options };
    const method = (opts.method || 'GET').toUpperCase();
    if (['POST','PUT','DELETE','PATCH'].includes(method)) {
      opts.body = appendCSRF(opts.body);
    }
    return _fetch(url, opts);
  };
  // XMLHttpRequest: interceptar send para añadir en FormData/URLSearchParams
  const _send = XMLHttpRequest.prototype.send;
  XMLHttpRequest.prototype.send = function(body) {
    const method = (this._method || 'GET');
    try { this._method = method; } catch {}
    const b = appendCSRF(body);
    return _send.call(this, b);
  };
  const _open = XMLHttpRequest.prototype.open;
  XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
    this._method = method ? method.toUpperCase() : 'GET';
    return _open.call(this, method, url, async, user, password);
  };
})();

// -------- Helpers para gestión de Alumnos (compartidos) --------
window.BASE_URL = window.BASE_URL || (document.querySelector('base')?.getAttribute('href') || '');
const PUBLIC_BASE = (function(){
  try {
    const path = location.pathname.replace(/\\/g,'/');
    const i = path.indexOf('/public');
    return i >= 0 ? path.substring(0, i + 7) : '';
  } catch { return ''; }
})();
const ROUTER = PUBLIC_BASE + '/app.php?r=';
function openCreateModal(){
  try{
    const form = document.getElementById('studentForm'); if(form) form.reset();
    const idEl = document.getElementById('studentId'); if(idEl) idEl.value = '';
    const title = document.getElementById('modalTitle'); if(title) title.textContent = 'Nuevo Alumno';
    const pwd = document.getElementById('password'); if(pwd){ pwd.placeholder='Contraseña'; pwd.required = true; }
    const help = document.getElementById('passwordHelp'); if(help) help.style.display = 'none';
    const act = document.getElementById('activo'); if(act) act.checked = true;
    const m = (typeof bootstrap !== 'undefined' && document.getElementById('studentModal')) ? new bootstrap.Modal(document.getElementById('studentModal')) : null;
    if(m) m.show();
  }catch(e){ console.error(e); alert('Error al abrir el formulario'); }
}

async function openEditModal(id){
  try{
    const modalEl = document.getElementById('studentModal');
    const m = (typeof bootstrap !== 'undefined' && modalEl) ? new bootstrap.Modal(modalEl) : null;
    if(m) m.show();
    const title = document.getElementById('modalTitle'); if(title) title.textContent = 'Cargando alumno...';
    const btn = document.getElementById('saveBtn'); if(btn){ btn.disabled = true; btn.textContent = 'Cargando...'; }
    const res = await fetch(`${ROUTER}/alumnos/get&id=${id}`, { credentials: 'same-origin' });
    const data = await res.json();
    if(data.error){ alert(data.error); return; }
    document.getElementById('studentId').value = data.id;
    document.getElementById('matricula').value = data.matricula || '';
    document.getElementById('nombre').value = data.nombre || '';
    document.getElementById('apellido').value = data.apellido || '';
    document.getElementById('email').value = data.email || '';
    const act = document.getElementById('activo'); if(act) act.checked = data.activo == 1;
    const title = document.getElementById('modalTitle'); if(title) title.textContent = 'Editar Alumno';
    const pwd = document.getElementById('password'); if(pwd){ pwd.placeholder='Dejar en blanco para mantener actual'; pwd.required = false; }
    if(title) title.textContent = 'Editar Alumno';
    if(btn){ btn.disabled = false; btn.textContent = 'Guardar'; }
  }catch(e){ console.error(e); alert('Error de conexión al obtener datos'); }
}

function saveStudent(e){
  e.preventDefault();
  const form = e.target;
  const fd = new FormData(form);
  const id = fd.get('id');
  const url = id ? `${ROUTER}/alumnos/update` : `${ROUTER}/alumnos/store`;
  const btn = document.getElementById('saveBtn'); const orig = btn ? btn.textContent : '';
  if(btn){ btn.disabled = true; btn.textContent = 'Guardando...'; }
  csrfFetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(r=>r.json()).then(data=>{ if(data.success){ location.reload(); } else { alert(data.error || 'Error desconocido'); } }).catch(()=> alert('Error de red')).finally(()=>{ if(btn){ btn.disabled=false; btn.textContent=orig; } });
}

function deleteStudent(id){
  if(!confirm('¿Estás seguro de eliminar este alumno? Esta acción no se puede deshacer.')) return;
  const fd = new FormData(); fd.append('id', id); fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
  csrfFetch(`${ROUTER}/alumnos/delete`, { method: 'POST', body: fd, credentials: 'same-origin' }).then(r=>r.json()).then(data=>{ if(data.success){ location.reload(); } else { alert(data.error || 'Error al eliminar'); } });
}

window.openCreateModal = openCreateModal;
window.openEditModal = openEditModal;
window.saveStudent = saveStudent;
window.deleteStudent = deleteStudent;
