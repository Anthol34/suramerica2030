/**
 * Sanitización en tiempo real para Nombres, Apellidos y Nacionalidad.
 * - Convierte a MAYÚSCULAS en tiempo real mientras el colaborador tipea.
 * - Sustituye letras con tilde / acentos por su equivalente sin tilde (Á->A, É->E, Í->I, Ó->O, Ú->U, etc.).
 * - Mantiene la letra Ñ / ñ como único carácter especial permitido (preserva la 'Ñ').
 * - Preserva la posición del cursor para evitar saltos molestos mientras se edita texto.
 * - Compatible con entradas dinámicas (agregar pasajeros, etc.), modales, pegado de texto y autocompletado.
 */

(function () {
    'use strict';

    /**
     * Función pura para normalizar texto a mayúsculas sin tildes,
     * manteniendo exclusivamente la 'Ñ' como carácter especial.
     * @param {string} texto
     * @returns {string}
     */
    function sanitizarTexto(texto) {
        if (!texto || typeof texto !== 'string') return '';

        // 1. Convertir a mayúsculas
        let upper = texto.toUpperCase();

        // 2. Proteger la letra Ñ con un token seguro temporal
        const TOKEN_ENIE = '___ENIE_ESPECIAL___';
        upper = upper.replace(/Ñ/g, TOKEN_ENIE);

        // 3. Normalizar NFD y remover marcas diacríticas / tildes
        upper = upper.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

        // 4. Restaurar la Ñ
        upper = upper.replace(new RegExp(TOKEN_ENIE, 'g'), 'Ñ');

        // 5. Limpiar cualquier tilde o acento suelto (pulsaciones de teclas muertas: ´ ` ¨ ^ ~)
        upper = upper.replace(/[´`¨^~]/g, '');

        return upper;
    }

    // Exponer la función globalmente para su uso directo en cualquier parte del sistema
    window.sanitizarTextoMayusculasSinTilde = sanitizarTexto;

    /**
     * Determina si un elemento del formulario corresponde a Nombres, Apellidos o Nacionalidad.
     * @param {HTMLElement} el
     * @returns {boolean}
     */
    function esCampoObjetivo(el) {
        if (!el) return false;

        // Soporte directo para celdas editables en manifiestos (contenteditable)
        if (el.isContentEditable) {
            if (el.tagName === 'TD' || el.tagName === 'TH') {
                const table = el.closest('table');
                if (table && (table.id === 'tablaPasajeros' || el.closest('#bodyPasajeros'))) {
                    const cellIndex = el.cellIndex;
                    // Columna 2 = Apellidos, Columna 3 = Nombres, Columna 4 = Nacionalidad
                    if (cellIndex === 2 || cellIndex === 3 || cellIndex === 4) {
                        return true;
                    }
                }
            }
            return el.dataset && el.dataset.sanitize === 'true';
        }

        if (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA') return false;

        const type = (el.type || 'text').toLowerCase();
        // Ignorar tipos no pertinentes
        if (['password', 'email', 'file', 'checkbox', 'radio', 'hidden', 'button', 'submit', 'reset', 'number', 'date', 'time', 'color', 'range'].includes(type)) {
            return false;
        }

        // Marcas explícitas por atributo o clase
        if (el.dataset && el.dataset.noSanitize === 'true') return false;
        if ((el.dataset && el.dataset.sanitize === 'true') || (el.classList && el.classList.contains('auto-sanitize-upper'))) return true;

        const name = (el.name || '').toLowerCase();
        const id = (el.id || '').toLowerCase();
        const placeholder = (el.placeholder || '').toLowerCase();

        // Descartar campos específicos que contengan 'nombre' pero NO correspondan a nombres de personas o nacionalidad
        if (name.includes('user') || id.includes('user') || name.includes('login') || id.includes('login')) return false;
        if (name.includes('rol') || id.includes('rol')) return false;
        if (name.includes('sucursal') || id.includes('sucursal')) return false;
        if (name.includes('banco') || id.includes('banco') || name.includes('cuenta') || id.includes('cuenta')) return false;
        if (name.includes('corredor') || id.includes('corredor')) return false;
        if (name.includes('empresa') || id.includes('empresa')) return false;
        if (name.includes('asiento') || id.includes('asiento')) return false;
        if (name.includes('placa') || id.includes('placa')) return false;
        if (name.includes('password') || id.includes('password')) return false;
        if (name.includes('email') || id.includes('email') || name.includes('correo') || id.includes('correo')) return false;

        // Comprobación por 'name' o 'id'
        if (/nombre|apellido|nacionalidad/.test(name) || /nombre|apellido|nacionalidad/.test(id)) {
            return true;
        }

        // Comprobación por 'placeholder'
        if (/(nombres?|apellidos?|nacionalidad)/i.test(placeholder) && !/(usuario|login|correo|email|banco|cuenta|rol|empresa)/i.test(placeholder)) {
            return true;
        }

        // Comprobación contextual por la etiqueta <label> asociada
        const parentContainer = el.closest('.col-md-3, .col-md-4, .col-md-6, .col-6, .mb-3, .mb-2, .input-group, .form-group, div');
        if (parentContainer) {
            const label = parentContainer.querySelector('label');
            if (label) {
                const labelText = (label.textContent || '').trim().toLowerCase();
                if (/(nombres?|apellidos?|nacionalidad)/i.test(labelText)) {
                    if (!/(empresa|rol|sucursal|banco|cuenta|corredor|usuario|login)/i.test(labelText)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    window.esCampoNombreOApellidoONacionalidad = esCampoObjetivo;

    /**
     * Aplica la transformación en un input preservando con precisión la posición del cursor.
     * @param {HTMLInputElement|HTMLTextAreaElement} input
     */
    function sanitizarElementoInput(input) {
        if (!input || typeof input.value !== 'string') return;
        const original = input.value;
        const transformado = sanitizarTexto(original);

        if (original !== transformado) {
            const start = input.selectionStart;
            const end = input.selectionEnd;

            input.value = transformado;

            if (start !== null && end !== null) {
                const diff = original.length - transformado.length;
                const newPos = Math.max(0, start - (diff > 0 ? diff : 0));
                try {
                    input.setSelectionRange(newPos, newPos);
                } catch (e) {
                    // Ciertos inputs no soportan setSelectionRange
                }
            }
        }
    }

    /**
     * Aplica la transformación en elementos contenteditable.
     * @param {HTMLElement} el
     */
    function sanitizarElementoContentEditable(el) {
        const original = el.innerText || el.textContent || '';
        const transformado = sanitizarTexto(original);
        if (original !== transformado) {
            el.textContent = transformado;
            try {
                const sel = window.getSelection();
                const range = document.createRange();
                range.selectNodeContents(el);
                range.collapse(false);
                sel.removeAllRanges();
                sel.addRange(range);
            } catch (e) {}
        }
    }

    /**
     * Aplica la sanitización a cualquier elemento objetivo.
     * @param {HTMLElement} el
     */
    function aplicarSanitizacion(el) {
        if (el.isContentEditable) {
            sanitizarElementoContentEditable(el);
        } else {
            sanitizarElementoInput(el);
        }
    }

    window.aplicarSanitizacionAElemento = aplicarSanitizacion;

    // 1. Escuchar eventos en fase de captura (useCapture = true) para máxima reactividad
    document.addEventListener('input', function (e) {
        if (e.target && esCampoObjetivo(e.target)) {
            aplicarSanitizacion(e.target);
        }
    }, true);

    document.addEventListener('change', function (e) {
        if (e.target && esCampoObjetivo(e.target)) {
            aplicarSanitizacion(e.target);
        }
    }, true);

    document.addEventListener('paste', function (e) {
        if (e.target && esCampoObjetivo(e.target)) {
            setTimeout(() => aplicarSanitizacion(e.target), 0);
        }
    }, true);

    // 2. Interceptar asignaciones programáticas (.value = '...') de forma segura
    try {
        const originalValueDescriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
        if (originalValueDescriptor && originalValueDescriptor.set) {
            Object.defineProperty(HTMLInputElement.prototype, 'value', {
                get: function () {
                    return originalValueDescriptor.get.call(this);
                },
                set: function (val) {
                    if (typeof val === 'string' && esCampoObjetivo(this)) {
                        val = sanitizarTexto(val);
                    }
                    return originalValueDescriptor.set.call(this, val);
                },
                configurable: true
            });
        }
    } catch (e) {
        console.warn('[sanitizar-inputs] No se pudo interceptar setter de value:', e);
    }

    // 3. Barrido de campos existentes en el DOM
    function sanitizarTodosLosCampos() {
        document.querySelectorAll('input, textarea').forEach(input => {
            if (esCampoObjetivo(input) && input.value) {
                sanitizarElementoInput(input);
            }
        });
    }

    // Ejecutar al cargar el documento
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', sanitizarTodosLosCampos);
    } else {
        sanitizarTodosLosCampos();
    }

    // Ejecutar al mostrar modales de Bootstrap
    document.addEventListener('shown.bs.modal', function () {
        sanitizarTodosLosCampos();
    });

    // 4. MutationObserver para elementos insertados dinámicamente (+ Añadir Pasajero, etc.)
    const observer = new MutationObserver(function (mutations) {
        for (let mutation of mutations) {
            for (let node of mutation.addedNodes) {
                if (node.nodeType === 1) { // ELEMENT_NODE
                    if (esCampoObjetivo(node) && node.value) {
                        sanitizarElementoInput(node);
                    } else if (node.querySelectorAll) {
                        node.querySelectorAll('input, textarea').forEach(input => {
                            if (esCampoObjetivo(input) && input.value) {
                                sanitizarElementoInput(input);
                            }
                        });
                    }
                }
            }
        }
    });

    observer.observe(document.documentElement || document.body, {
        childList: true,
        subtree: true
    });

})();
