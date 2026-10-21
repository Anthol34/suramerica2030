/**
 * SURAMERICA 2030 - LIVE DATA & ASYNC ENGINE
 * Motor universal de interacción reactiva, peticiones AJAX, modales y notificaciones en vivo.
 */

(function(window, document) {
    'use strict';

    const LiveData = {
        toastContainerId: 'liveDataToastContainer',

        /**
         * Inicializa el contenedor global de notificaciones toast si no existe
         */
        ensureToastContainer: function() {
            let container = document.getElementById(this.toastContainerId);
            if (!container) {
                container = document.createElement('div');
                container.id = this.toastContainerId;
                document.body.appendChild(container);
            }
            return container;
        },

        /**
         * Muestra una notificación Toast moderna y flotante
         * @param {string} message Texto del mensaje
         * @param {string} type 'success' | 'danger' | 'error' | 'warning' | 'info'
         * @param {string|null} title Título opcional
         * @param {number} duration Duración en ms (defecto 4000)
         */
        toast: function(message, type = 'success', title = null, duration = 4000) {
            const container = this.ensureToastContainer();
            const normType = (type === 'error') ? 'danger' : type;

            const toastEl = document.createElement('div');
            toastEl.className = `live-toast live-toast-${normType}`;

            let icon = '✅';
            let defaultTitle = 'Operación Exitosa';
            if (normType === 'danger') {
                icon = '❌';
                defaultTitle = 'Error';
            } else if (normType === 'warning') {
                icon = '⚠️';
                defaultTitle = 'Advertencia';
            } else if (normType === 'info') {
                icon = 'ℹ️';
                defaultTitle = 'Información';
            }

            const displayTitle = title || defaultTitle;

            toastEl.innerHTML = `
                <div class="live-toast-icon-wrap">${icon}</div>
                <div class="live-toast-content">
                    <div class="live-toast-title">${this.escapeHtml(displayTitle)}</div>
                    <div class="live-toast-message">${this.escapeHtml(message)}</div>
                </div>
                <button type="button" class="live-toast-close" aria-label="Cerrar">&times;</button>
                <div class="live-toast-progress" style="animation-duration: ${duration}ms;"></div>
            `;

            container.appendChild(toastEl);

            const closeToast = () => {
                if (toastEl.classList.contains('live-toast-hiding')) return;
                toastEl.classList.add('live-toast-hiding');
                setTimeout(() => {
                    if (toastEl.parentNode) {
                        toastEl.parentNode.removeChild(toastEl);
                    }
                }, 280);
            };

            const closeBtn = toastEl.querySelector('.live-toast-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', closeToast);
            }

            const timer = setTimeout(closeToast, duration);
            toastEl.addEventListener('mouseenter', () => clearTimeout(timer));

            return toastEl;
        },

        /**
         * Aplica un efecto visual de pulso / highlight verde a un elemento
         */
        highlight: function(elementOrSelector) {
            const el = (typeof elementOrSelector === 'string') 
                ? document.querySelector(elementOrSelector) 
                : elementOrSelector;
            if (!el) return;

            el.classList.remove('live-data-highlight');
            void el.offsetWidth; // Forzar reflujo de render
            el.classList.add('live-data-highlight');
            setTimeout(() => {
                el.classList.remove('live-data-highlight');
            }, 3000);
        },

        /**
         * Cierra de forma segura cualquier modal de Bootstrap que contenga el elemento
         */
        closeModal: function(elementInsideModal) {
            if (!elementInsideModal) return;
            const modalEl = elementInsideModal.closest('.modal');
            if (modalEl && window.bootstrap && bootstrap.Modal) {
                const inst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                if (inst) {
                    inst.hide();
                    // Limpieza preventiva de backdrop en caso de delay
                    setTimeout(() => {
                        document.querySelectorAll('.modal-backdrop').forEach(b => {
                            if (!document.querySelector('.modal.show')) b.remove();
                        });
                        document.body.classList.remove('modal-open');
                        document.body.style.removeProperty('padding-right');
                    }, 350);
                }
            }
        },

        /**
         * Refresca un contenedor del DOM descargando únicamente su contenido actualizado vía AJAX
         * sin recargar la página completa y sin perder filtros ni scroll.
         * @param {string} containerSelector Selector CSS del elemento a refrescar
         * @param {string|null} customUrl URL opcional (por defecto la URL actual)
         * @param {function|null} callback Función opcional post-actualización
         */
        refreshContainer: function(containerSelector, customUrl = null, callback = null) {
            const container = document.querySelector(containerSelector);
            if (!container) return Promise.resolve();

            container.classList.add('live-container-loading');
            const targetUrl = customUrl || window.location.href;

            return fetch(targetUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                if (!res.ok) throw new Error('Error al recargar contenedor HTTP ' + res.status);
                return res.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContent = doc.querySelector(containerSelector);
                if (newContent) {
                    container.innerHTML = newContent.innerHTML;
                    // Transferir atributos si cambiaron
                    Array.from(newContent.attributes).forEach(attr => {
                        container.setAttribute(attr.name, attr.value);
                    });
                }
                container.classList.remove('live-container-loading');
                if (typeof callback === 'function') callback(container);
                return container;
            })
            .catch(err => {
                console.error('[LiveData] Error al refrescar contenedor:', err);
                container.classList.remove('live-container-loading');
            });
        },

        /**
         * Envía un formulario de forma asíncrona mediante AJAX
         */
        submitForm: function(form, options = {}) {
            if (!form) return;

            const btnSubmit = form.querySelector('button[type="submit"]') || form.querySelector('input[type="submit"]');
            let originalBtnHtml = '';
            if (btnSubmit) {
                originalBtnHtml = btnSubmit.innerHTML || btnSubmit.value;
                btnSubmit.disabled = true;
                if (btnSubmit.tagName === 'BUTTON') {
                    btnSubmit.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Guardando...`;
                }
            }

            const formData = new FormData(form);
            if (!formData.has('ajax')) {
                formData.append('ajax', '1');
            }

            const restoreButton = () => {
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                    if (btnSubmit.tagName === 'BUTTON') {
                        btnSubmit.innerHTML = originalBtnHtml;
                    } else {
                        btnSubmit.value = originalBtnHtml;
                    }
                }
            };

            const targetUrl = form.action || window.location.href;

            fetch(targetUrl, {
                method: (form.method || 'POST').toUpperCase(),
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => {
                const contentType = res.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    return res.json().then(data => ({ ok: res.ok, status: res.status, data }));
                } else {
                    return res.text().then(text => ({ ok: res.ok, status: res.status, text }));
                }
            })
            .then(result => {
                restoreButton();

                const isSuccess = result.ok && (
                    (result.data && (result.data.status === 'success' || result.data.success === true)) ||
                    (!result.data && result.status >= 200 && result.status < 400)
                );

                if (isSuccess) {
                    const mensaje = (result.data && (result.data.mensaje || result.data.message)) 
                        || form.getAttribute('data-live-success-msg') 
                        || 'Cambios guardados con éxito.';

                    // 1. Cerrar modal si existe
                    this.closeModal(form);

                    // 2. Notificación Toast elegante
                    this.toast(mensaje, 'success');

                    // 3. Resaltar fila o elemento objetivo si está especificado o inferido
                    let highlightSelector = form.getAttribute('data-live-highlight') || form.getAttribute('data-live-row') || options.highlight;
                    if (!highlightSelector && result.data) {
                        if (result.data.id_usuario) highlightSelector = `#fila_usuario_${result.data.id_usuario}`;
                        else if (result.data.id_bus) highlightSelector = `#fila_bus_${result.data.id_bus}`;
                        else if (result.data.id_operador) highlightSelector = `#fila_operador_${result.data.id_operador}`;
                        else if (result.data.id_sucursal) highlightSelector = `#fila_sucursal_${result.data.id_sucursal}`;
                        else if (result.data.id_rol) highlightSelector = `#fila_rol_${result.data.id_rol}`;
                        else if (result.data.id_cierre) highlightSelector = `#fila_cierre_${result.data.id_cierre}`;
                        else if (result.data.id_movimiento) highlightSelector = `#fila_mov_${result.data.id_movimiento}`;
                    }

                    if (highlightSelector) {
                        this.highlight(highlightSelector);
                    }

                    // 4. Refrescar contenedor objetivo sin recargar la página completa
                    const refreshSelector = form.getAttribute('data-live-refresh') || options.refresh;
                    if (refreshSelector) {
                        this.refreshContainer(refreshSelector, null, () => {
                            if (highlightSelector) this.highlight(highlightSelector);
                            // Despachar evento para re-aplicar filtros existentes si hay función registrada
                            document.dispatchEvent(new CustomEvent('livedata:refreshed', { detail: { selector: refreshSelector } }));
                        });
                    }

                    // 5. Limpiar formulario si está configurado
                    if (form.getAttribute('data-live-reset') === 'true' || options.reset) {
                        form.reset();
                    }

                    // 6. Despachar evento personalizado para la vista específica
                    const eventDetail = { form, data: result.data || {}, options };
                    form.dispatchEvent(new CustomEvent('livedata:success', { bubbles: true, detail: eventDetail }));
                    document.dispatchEvent(new CustomEvent('livedata:success', { detail: eventDetail }));

                    // Callback manual opcional
                    const callbackName = form.getAttribute('data-live-callback');
                    if (callbackName && typeof window[callbackName] === 'function') {
                        window[callbackName](result.data, form);
                    }
                } else {
                    const errorMsg = (result.data && (result.data.mensaje || result.data.message)) 
                        || 'Ocurrió un error al procesar los datos (HTTP ' + result.status + ').';

                    this.toast(errorMsg, 'danger');

                    const eventDetail = { form, data: result.data || {}, error: errorMsg };
                    form.dispatchEvent(new CustomEvent('livedata:error', { bubbles: true, detail: eventDetail }));
                    document.dispatchEvent(new CustomEvent('livedata:error', { detail: eventDetail }));
                }
            })
            .catch(err => {
                console.error('[LiveData] Error de red en submitForm:', err);
                restoreButton();
                this.toast('Error de conexión o fallo del servidor: ' + err.message, 'danger');
            });
        },

        /**
         * Ejecuta una acción rápida (ej. cambiar estado, activar/bloquear, eliminar) vía AJAX
         */
        executeAction: function(triggerEl, url, postData = {}, confirmMsg = null, refreshSelector = null, callback = null) {
            if (confirmMsg && !confirm(confirmMsg)) {
                return;
            }

            const formData = new FormData();
            formData.append('ajax', '1');
            Object.keys(postData).forEach(k => formData.append(k, postData[k]));

            if (triggerEl) {
                triggerEl.disabled = true;
                triggerEl.classList.add('opacity-50');
            }

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (triggerEl) {
                    triggerEl.disabled = false;
                    triggerEl.classList.remove('opacity-50');
                }

                if (data.status === 'success' || data.success) {
                    const msg = data.mensaje || data.message || 'Operación completada.';
                    this.toast(msg, 'success');

                    if (refreshSelector) {
                        this.refreshContainer(refreshSelector, null, callback);
                    } else if (typeof callback === 'function') {
                        callback(data);
                    }
                } else {
                    this.toast(data.mensaje || data.message || 'No se pudo completar la acción.', 'danger');
                }
            })
            .catch(err => {
                if (triggerEl) {
                    triggerEl.disabled = false;
                    triggerEl.classList.remove('opacity-50');
                }
                this.toast('Error al ejecutar la acción: ' + err.message, 'danger');
            });
        },

        escapeHtml: function(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    };

    // Auto-interceptar formularios configurados con data-live="true" o .live-form
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form || !form.tagName || form.tagName.toUpperCase() !== 'FORM') return;

        const isLive = form.getAttribute('data-live') === 'true' || 
                       form.classList.contains('live-form') ||
                       form.classList.contains('form-ajax');

        if (isLive) {
            e.preventDefault();
            LiveData.submitForm(form);
        }
    });

    // Delegación global para botones/enlaces de acción rápida con data-live-post
    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('[data-live-post]');
        if (!trigger) return;

        e.preventDefault();
        const url = trigger.getAttribute('data-live-post');
        const confirmMsg = trigger.getAttribute('data-live-confirm');
        const refreshSel = trigger.getAttribute('data-live-refresh');
        
        let postData = {};
        const dataAttrs = trigger.getAttribute('data-live-params');
        if (dataAttrs) {
            try { postData = JSON.parse(dataAttrs); } catch(ex){}
        }

        LiveData.executeAction(trigger, url, postData, confirmMsg, refreshSel);
    });

    // Exponer globalmente
    window.LiveData = LiveData;

    // Puente de retrocompatibilidad con flota.js
    window.mostrarToastFlota = function(mensaje, tipo = 'success') {
        LiveData.toast(mensaje, tipo);
    };

})(window, document);
