// Funciones para el manejo de alumnos
const AlumnosAPI = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.content,
    
    async getAll(page = 1, limit = 10, search = '') {
        const params = new URLSearchParams({ page, limit, search });
        const response = await fetch(`/api/alumnos?${params}`);
        return await response.json();
    },

    async getOne(id) {
        const response = await fetch(`/api/alumnos/${id}`);
        return await response.json();
    },

    async create(formData) {
        formData.append('csrf_token', this.csrfToken);
        const response = await fetch('/api/alumnos', {
            method: 'POST',
            body: formData
        });
        return await response.json();
    },

    async update(id, formData) {
        formData.append('csrf_token', this.csrfToken);
        formData.append('id', id);
        const response = await fetch(`/api/alumnos/${id}`, {
            method: 'POST',
            body: formData
        });
        return await response.json();
    },

    async delete(id) {
        const formData = new FormData();
        formData.append('csrf_token', this.csrfToken);
        formData.append('id', id);
        const response = await fetch(`/api/alumnos/${id}`, {
            method: 'POST',
            body: formData
        });
        return await response.json();
    }
};

// Clase para manejar la UI de alumnos
class AlumnosUI {
    constructor() {
        this.tableBody = document.querySelector('#alumnos-table tbody');
        this.pagination = document.querySelector('#pagination');
        this.searchInput = document.querySelector('#search-input');
        this.currentPage = 1;
        this.setupEventListeners();
        this.loadAlumnos();
    }

    setupEventListeners() {
        // Búsqueda con debounce
        this.searchInput?.addEventListener('input', debounce(() => {
            this.currentPage = 1;
            this.loadAlumnos();
        }, 300));

        // Formulario de creación/edición
        document.querySelector('#alumno-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const id = formData.get('id');

            try {
                const response = id ? 
                    await AlumnosAPI.update(id, formData) :
                    await AlumnosAPI.create(formData);

                if (response.success) {
                    this.showAlert('success', response.message);
                    this.loadAlumnos();
                    const el = document.getElementById('alumnoModal');
                    if (el) {
                        const inst = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
                        inst.hide();
                    }
                } else {
                    this.showAlert('danger', response.error);
                    this.showFormErrors(response.errors);
                }
            } catch (error) {
                this.showAlert('danger', 'Error al procesar la solicitud');
            }
        });
    }

    async loadAlumnos() {
        try {
            const searchTerm = this.searchInput?.value || '';
            const response = await AlumnosAPI.getAll(this.currentPage, 10, searchTerm);
            
            if (response.success) {
                this.renderTable(response.data);
                this.renderPagination(response.pagination);
            } else {
                this.showAlert('danger', response.error);
            }
        } catch (error) {
            this.showAlert('danger', 'Error al cargar los alumnos');
        }
    }

    renderTable(alumnos) {
        if (!this.tableBody) return;
        
        this.tableBody.innerHTML = alumnos.map(alumno => `
            <tr>
                <td>${this.escapeHtml(alumno.matricula)}</td>
                <td>${this.escapeHtml(alumno.nombre)} ${this.escapeHtml(alumno.apellido)}</td>
                <td>${this.escapeHtml(alumno.email)}</td>
                <td>
                    ${alumno.foto ? `<img src="/uploads/fotos/${alumno.foto}" 
                                    alt="Foto" class="img-thumbnail" width="50">` : ''}
                </td>
                <td>
                    <button class="btn btn-sm btn-info" 
                            onclick="alumnosUI.showDetails(${alumno.id})">
                        Ver
                    </button>
                    <button class="btn btn-sm btn-primary" 
                            onclick="alumnosUI.editAlumno(${alumno.id})">
                        Editar
                    </button>
                    <button class="btn btn-sm btn-danger" 
                            onclick="alumnosUI.deleteAlumno(${alumno.id})">
                        Eliminar
                    </button>
                </td>
            </tr>
        `).join('');
    }

    renderPagination(pagination) {
        if (!this.pagination) return;

        this.pagination.innerHTML = `
            <nav>
                <ul class="pagination">
                    <li class="page-item ${!pagination.hasPrevPage ? 'disabled' : ''}">
                        <a class="page-link" href="#" 
                           onclick="alumnosUI.goToPage(${pagination.currentPage - 1})">
                            Anterior
                        </a>
                    </li>
                    ${this.generatePageNumbers(pagination)}
                    <li class="page-item ${!pagination.hasNextPage ? 'disabled' : ''}">
                        <a class="page-link" href="#" 
                           onclick="alumnosUI.goToPage(${pagination.currentPage + 1})">
                            Siguiente
                        </a>
                    </li>
                </ul>
            </nav>
        `;
    }

    generatePageNumbers(pagination) {
        const pages = [];
        for (let i = 1; i <= pagination.totalPages; i++) {
            if (i === 1 || i === pagination.totalPages || 
                (i >= pagination.currentPage - 2 && i <= pagination.currentPage + 2)) {
                pages.push(`
                    <li class="page-item ${i === pagination.currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="alumnosUI.goToPage(${i})">
                            ${i}
                        </a>
                    </li>
                `);
            } else if (pages[pages.length - 1] !== '<li class="page-item disabled"><span class="page-link">...</span></li>') {
                pages.push('<li class="page-item disabled"><span class="page-link">...</span></li>');
            }
        }
        return pages.join('');
    }

    async showDetails(id) {
        try {
            const response = await AlumnosAPI.getOne(id);
            if (response.success) {
                const alumno = response.data;
                const detalleEl = document.getElementById('detalleAlumnoModal');
                const modal = detalleEl ? new bootstrap.Modal(detalleEl) : null;
                document.querySelector('#detalle-alumno-content').innerHTML = `
                    <div class="row">
                        <div class="col-md-4">
                            ${alumno.foto ? 
                                `<img src="/uploads/fotos/${alumno.foto}" 
                                 alt="Foto" class="img-fluid rounded">` : 
                                '<p>Sin foto</p>'}
                        </div>
                        <div class="col-md-8">
                            <h4>${this.escapeHtml(alumno.nombre)} ${this.escapeHtml(alumno.apellido)}</h4>
                            <p><strong>Matrícula:</strong> ${this.escapeHtml(alumno.matricula)}</p>
                            <p><strong>Email:</strong> ${this.escapeHtml(alumno.email)}</p>
                            <p><strong>Fecha de nacimiento:</strong> ${alumno.fecha_nac}</p>
                            
                            <h5 class="mt-4">Calificaciones</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Materia</th>
                                            <th>Parcial 1</th>
                                            <th>Parcial 2</th>
                                            <th>Final</th>
                                            <th>Promedio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${this.renderCalificaciones(alumno)}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `;
                modal.show();
            } else {
                this.showAlert('danger', response.error);
            }
        } catch (error) {
            this.showAlert('danger', 'Error al cargar los detalles');
        }
    }

    renderCalificaciones(alumno) {
        if (!alumno.calificaciones?.length) {
            return '<tr><td colspan="5">No hay calificaciones registradas</td></tr>';
        }

        return alumno.calificaciones.map(cal => `
            <tr>
                <td>${this.escapeHtml(cal.materia)}</td>
                <td>${cal.parcial1 || '-'}</td>
                <td>${cal.parcial2 || '-'}</td>
                <td>${cal.final || '-'}</td>
                <td>${cal.promedio || '-'}</td>
            </tr>
        `).join('');
    }

    async editAlumno(id) {
        try {
            const response = await AlumnosAPI.getOne(id);
            if (response.success) {
                const alumno = response.data;
                document.querySelector('#alumno-form-id').value = alumno.id;
                document.querySelector('#alumno-form-matricula').value = alumno.matricula;
                document.querySelector('#alumno-form-nombre').value = alumno.nombre;
                document.querySelector('#alumno-form-apellido').value = alumno.apellido;
                document.querySelector('#alumno-form-email').value = alumno.email;
                document.querySelector('#alumno-form-fecha_nac').value = alumno.fecha_nac;
                
                const editEl = document.getElementById('alumnoModal');
                const modal = editEl ? new bootstrap.Modal(editEl) : null;
                if (modal) modal.show();
            } else {
                this.showAlert('danger', response.error);
            }
        } catch (error) {
            this.showAlert('danger', 'Error al cargar el alumno');
        }
    }

    async deleteAlumno(id) {
        if (!confirm('¿Está seguro de eliminar este alumno?')) return;

        try {
            const response = await AlumnosAPI.delete(id);
            if (response.success) {
                this.showAlert('success', response.message);
                this.loadAlumnos();
            } else {
                this.showAlert('danger', response.error);
            }
        } catch (error) {
            this.showAlert('danger', 'Error al eliminar el alumno');
        }
    }

    goToPage(page) {
        this.currentPage = page;
        this.loadAlumnos();
    }

    showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${this.escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('#alerts-container').appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }

    showFormErrors(errors) {
        const errorsList = document.querySelector('#form-errors');
        if (!errorsList || !errors?.length) return;

        errorsList.innerHTML = errors.map(error => 
            `<li>${this.escapeHtml(error)}</li>`
        ).join('');
        errorsList.style.display = 'block';
    }

    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

// Función helper para debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Inicializar la UI cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.alumnosUI = new AlumnosUI();
});
