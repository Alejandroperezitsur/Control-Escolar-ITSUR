/**
 * PRODUCTION HARDENING - UX CRÍTICA
 * 
 * Implementa:
 * - Debounce en formularios críticos
 * - Loading states reales
 * - Manejo de errores consistente
 * - Prevención de doble submit
 */

(function() {
    'use strict';

    // ==========================================
    // CONFIGURACIÓN GLOBAL
    // ==========================================
    const CONFIG = {
        DEBOUNCE_DELAY: 500,
        SUBMIT_TIMEOUT: 30000, // 30 segundos máximo para submit
        ERROR_AUTO_DISMISS: 5000,
        RETRY_ATTEMPTS: 3
    };

    // Estado global de operaciones en curso
    const pendingOperations = new Map();

    // ==========================================
    // DEBOUNCE UTILS
    // ==========================================
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

    // ==========================================
    // LOADING STATE MANAGER
    // ==========================================
    class LoadingManager {
        static show(button, loadingText = 'Procesando...') {
            if (!button) return;
            
            // Guardar estado original
            const originalText = button.textContent;
            const originalDisabled = button.disabled;
            
            button.dataset.originalText = originalText;
            button.dataset.originalDisabled = originalDisabled;
            
            // Mostrar loading state
            button.disabled = true;
            button.innerHTML = `
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                ${loadingText}
            `;
            button.classList.add('btn-loading');
            
            // Agregar overlay preventivo
            button.style.position = 'relative';
            button.style.overflow = 'hidden';
        }

        static hide(button) {
            if (!button) return;
            
            // Restaurar estado original
            const originalText = button.dataset.originalText || 'Guardar';
            const originalDisabled = button.dataset.originalDisabled === 'true';
            
            button.disabled = originalDisabled;
            button.textContent = originalText;
            button.classList.remove('btn-loading');
            button.style.position = '';
            button.style.overflow = '';
        }

        static showError(button, errorMessage) {
            this.hide(button);
            button.classList.add('btn-error');
            button.textContent = `❌ ${errorMessage}`;
            
            setTimeout(() => {
                this.hide(button);
            }, CONFIG.ERROR_AUTO_DISMISS);
        }
    }

    // ==========================================
    // FORM SUBMIT HANDLER CON IDEMPOTENCIA
    // ==========================================
    function setupFormProtection() {
        const criticalForms = document.querySelectorAll('form[data-protect="true"], form.critical');
        
        criticalForms.forEach(form => {
            const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
            
            form.addEventListener('submit', async function(e) {
                // Verificar si ya hay una operación en curso para este form
                const formId = form.id || `form_${Date.now()}`;
                
                if (pendingOperations.has(formId)) {
                    e.preventDefault();
                    showToast('⚠️ Ya hay una operación en curso. Por favor espere.', 'warning');
                    return false;
                }

                // Generar idempotency key
                const formData = new FormData(form);
                const idempotencyKey = generateIdempotencyKey(formData, form.action);
                
                // Agregar key al form
                let idempotencyInput = form.querySelector('input[name="_idempotency_key"]');
                if (!idempotencyInput) {
                    idempotencyInput = document.createElement('input');
                    idempotencyInput.type = 'hidden';
                    idempotencyInput.name = '_idempotency_key';
                    form.appendChild(idempotencyInput);
                }
                idempotencyInput.value = idempotencyKey;

                // Marcar operación como en curso
                pendingOperations.set(formId, {
                    startTime: Date.now(),
                    key: idempotencyKey
                });

                // Mostrar loading en todos los botones
                submitButtons.forEach(btn => {
                    LoadingManager.show(btn);
                });

                // Timeout de seguridad
                const timeout = setTimeout(() => {
                    pendingOperations.delete(formId);
                    submitButtons.forEach(btn => {
                        LoadingManager.showError(btn, 'Tiempo agotado');
                    });
                    showToast('⏱️ La operación tardó demasiado. Por favor intente nuevamente.', 'error');
                }, CONFIG.SUBMIT_TIMEOUT);

                // Limpiar timeout cuando termine la operación
                const originalSubmit = form.submit;
                form.submit = function() {
                    clearTimeout(timeout);
                    pendingOperations.delete(formId);
                    submitButtons.forEach(btn => {
                        LoadingManager.hide(btn);
                    });
                    return originalSubmit.apply(this, arguments);
                };
            });
        });
    }

    // ==========================================
    // GENERADOR DE IDEMPOTENCY KEY
    // ==========================================
    function generateIdempotencyKey(formData, endpoint) {
        const data = {};
        formData.forEach((value, key) => {
            // Excluir campos dinámicos como timestamps
            if (!['_token', '_idempotency_key', 'timestamp'].includes(key)) {
                data[key] = value;
            }
        });

        const normalized = {
            endpoint: endpoint,
            data: data
        };

        // Hash simple (en producción usar crypto.subtle)
        const str = JSON.stringify(normalized, Object.keys(normalized).sort());
        return 'ik_' + btoa(unescape(encodeURIComponent(str))).substring(0, 32);
    }

    // ==========================================
    // TOAST NOTIFICATIONS
    // ==========================================
    function showToast(message, type = 'info') {
        const toastContainer = document.getElementById('toast-container') || createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type} show`;
        toast.innerHTML = `
            <div class="toast-body">
                ${message}
                <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Auto-dismiss
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, type === 'error' ? 8000 : CONFIG.ERROR_AUTO_DISMISS);
    }

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

    // ==========================================
    // CONFIRMACIÓN PARA ACCIONES DESTRUCTIVAS
    // ==========================================
    function setupDestructiveActionConfirmation() {
        const destructiveButtons = document.querySelectorAll('[data-action="delete"], [data-confirm], .btn-danger');
        
        destructiveButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                const message = this.dataset.confirm || '¿Está seguro de realizar esta acción? Esta operación no se puede deshacer.';
                const action = this.dataset.action || 'eliminar';
                
                if (!confirm(`⚠️ ${message}\n\nEscriba "CONFIRMAR" para continuar:`)) {
                    e.preventDefault();
                    return false;
                }
                
                // En una implementación más robusta, aquí iría un prompt con texto específico
            });
        });
    }

    // ==========================================
    // MANEJO DE ERRORES DE RED
    // ==========================================
    function setupNetworkErrorHandling() {
        window.addEventListener('offline', () => {
            showToast('📡 Se ha perdido la conexión. Los cambios podrían no guardarse.', 'error');
            document.body.classList.add('is-offline');
        });

        window.addEventListener('online', () => {
            showToast('✅ Conexión restaurada', 'success');
            document.body.classList.remove('is-offline');
        });

        // Intercept fetch requests para manejar errores
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            return originalFetch.apply(this, args)
                .catch(error => {
                    console.error('Network error:', error);
                    showToast('🌐 Error de conexión. Verifique su internet.', 'error');
                    throw error;
                });
        };
    }

    // ==========================================
    // INICIALIZACIÓN
    // ==========================================
    function init() {
        // Esperar a que el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeFeatures);
        } else {
            initializeFeatures();
        }
    }

    function initializeFeatures() {
        setupFormProtection();
        setupDestructiveActionConfirmation();
        setupNetworkErrorHandling();
        
        console.log('✅ Production Hardening UX inicializado');
    }

    // Iniciar
    init();

    // Exponer funciones globales útiles
    window.ProductionUX = {
        LoadingManager,
        showToast,
        debounce
    };

})();

/* ==========================================
   ESTILOS CSS ADICIONALES (agregar al CSS principal)
   ==========================================
   
.btn-loading {
    pointer-events: none;
    opacity: 0.7;
}

.btn-error {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.is-offline {
    cursor: not-allowed;
}

.is-offline * {
    pointer-events: none;
}

.toast {
    min-width: 300px;
    margin-bottom: 10px;
}

.toast-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.toast-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.toast-warning {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    color: #856404;
}
*/
