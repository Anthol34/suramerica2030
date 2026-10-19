/**
 * Validador Universal para Suramerica 2030 (Frontend)
 * Validaciones de negocio reutilizables:
 * - Cédula ecuatoriana (Algoritmo Módulo 10)
 * - RUC ecuatoriano (Personas naturales, jurídicas y públicas)
 * - Pagos Mixtos (Cálculo, auto-balanceo y validación de cuadre)
 * - Teléfonos, Correos y Placas vehiculares
 */
(function (window) {
    'use strict';

    const Validador = {

        /**
         * Valida cédula de identidad ecuatoriana de 10 dígitos.
         * @param {string|number} cedula
         * @returns {boolean}
         */
        validarCedula: function (cedula) {
            cedula = String(cedula || '').trim();
            if (!/^\d{10}$/.test(cedula)) return false;

            const prov = parseInt(cedula.substring(0, 2), 10);
            if ((prov < 1 || prov > 24) && prov !== 30) return false;

            const tercer = parseInt(cedula[2], 10);
            if (tercer < 0 || tercer > 5) return false;

            const coef = [2, 1, 2, 1, 2, 1, 2, 1, 2];
            let suma = 0;
            for (let i = 0; i < 9; i++) {
                let v = parseInt(cedula[i], 10) * coef[i];
                if (v >= 10) v -= 9;
                suma += v;
            }
            const digitoCalc = (10 - (suma % 10)) % 10;
            const digitoReal = parseInt(cedula[9], 10);

            return digitoCalc === digitoReal;
        },

        /**
         * Valida RUC ecuatoriano de 13 dígitos.
         * @param {string|number} ruc
         * @returns {boolean}
         */
        validarRuc: function (ruc) {
            ruc = String(ruc || '').trim();
            if (!/^\d{13}$/.test(ruc)) return false;

            const prov = parseInt(ruc.substring(0, 2), 10);
            if ((prov < 1 || prov > 24) && prov !== 30) return false;

            const tercer = parseInt(ruc[2], 10);

            // 1. Persona Natural (0 a 5)
            if (tercer >= 0 && tercer <= 5) {
                const cedula = ruc.substring(0, 10);
                const estab = parseInt(ruc.substring(10, 13), 10);
                return this.validarCedula(cedula) && estab > 0;
            }

            // 2. Sociedad Privada (9)
            if (tercer === 9) {
                const estab = parseInt(ruc.substring(10, 13), 10);
                if (estab < 1) return false;
                const coef = [4, 3, 2, 7, 6, 5, 4, 3, 2];
                let suma = 0;
                for (let i = 0; i < 9; i++) {
                    suma += parseInt(ruc[i], 10) * coef[i];
                }
                const res = suma % 11;
                const digito = (res === 0) ? 0 : 11 - res;
                return digito === parseInt(ruc[9], 10);
            }

            // 3. Entidad Pública (6)
            if (tercer === 6) {
                const estab = parseInt(ruc.substring(9, 13), 10);
                if (estab < 1) return false;
                const coef = [3, 2, 7, 6, 5, 4, 3, 2];
                let suma = 0;
                for (let i = 0; i < 8; i++) {
                    suma += parseInt(ruc[i], 10) * coef[i];
                }
                const res = suma % 11;
                const digito = (res === 0) ? 0 : 11 - res;
                return digito === parseInt(ruc[8], 10);
            }

            return false;
        },

        /**
         * Valida número telefónico ecuatoriano (convencional o celular).
         */
        validarTelefono: function (tel) {
            const num = String(tel || '').replace(/\D/g, '');
            if (num.length === 10 && num.startsWith('09')) return true;
            if (num.length >= 7 && num.length <= 9) return true;
            return false;
        },

        /**
         * Valida correo electrónico.
         */
        validarEmail: function (email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(String(email || '').trim());
        },

        /**
         * Valida placa vehicular de Ecuador.
         */
        validarPlaca: function (placa) {
            const p = String(placa || '').toUpperCase().trim();
            return /^[A-Z]{1,3}-?\d{3,4}$/.test(p);
        },

        /**
         * Valida si un pago mixto cuadra con el monto total.
         */
        validarPagoMixto: function (total, efectivo, transferencia, tolerancia = 0.02) {
            const tot = parseFloat(total) || 0;
            const ef = parseFloat(efectivo) || 0;
            const tr = parseFloat(transferencia) || 0;
            if (tot <= 0) return false;
            return Math.abs((ef + tr) - tot) <= tolerancia;
        },

        /**
         * Balancea automáticamente los montos de Efectivo y Transferencia en un pago mixto.
         * @param {HTMLElement|string} totalEl Elemento o ID del total
         * @param {HTMLElement|string} efEl Elemento o ID de efectivo
         * @param {HTMLElement|string} trEl Elemento o ID de transferencia
         * @param {HTMLElement|string} badgeEl Elemento o ID del badge de estado
         * @param {'init'|'efectivo'|'transferencia'|'total'} origen Origen del cambio
         */
        balancearPagoMixto: function (totalEl, efEl, trEl, badgeEl, origen = 'init') {
            const tInput = typeof totalEl === 'string' ? document.getElementById(totalEl) : totalEl;
            const eInput = typeof efEl === 'string' ? document.getElementById(efEl) : efEl;
            const rInput = typeof trEl === 'string' ? document.getElementById(trEl) : trEl;
            const bEl = typeof badgeEl === 'string' ? document.getElementById(badgeEl) : badgeEl;

            if (!tInput || !eInput || !rInput) return;

            let total = parseFloat(tInput.value) || 0;
            let ef = parseFloat(eInput.value) || 0;
            let tr = parseFloat(rInput.value) || 0;

            if (origen === 'init') {
                ef = total > 0 ? (total / 2) : 0;
                tr = total - ef;
                eInput.value = ef.toFixed(2);
                rInput.value = tr.toFixed(2);
            } else if (origen === 'efectivo') {
                tr = Math.max(0, total - ef);
                rInput.value = tr.toFixed(2);
            } else if (origen === 'transferencia') {
                ef = Math.max(0, total - tr);
                eInput.value = ef.toFixed(2);
            } else if (origen === 'total') {
                if (ef > total) ef = total;
                tr = Math.max(0, total - ef);
                eInput.value = ef.toFixed(2);
                rInput.value = tr.toFixed(2);
            }

            if (bEl) {
                const suma = (parseFloat(eInput.value) || 0) + (parseFloat(rInput.value) || 0);
                const diff = Math.abs(suma - total);
                if (diff < 0.02 && total > 0) {
                    bEl.className = 'badge bg-success';
                    bEl.textContent = '✔ Cuadrado';
                } else {
                    bEl.className = 'badge bg-danger';
                    bEl.textContent = '⚠️ Descuadre';
                }
            }
        },

        /**
         * Configura el comportamiento interactivo de un selector de método de pago mixto.
         */
        enlazarPagoMixto: function (cfg) {
            const sel = typeof cfg.select === 'string' ? document.getElementById(cfg.select) : cfg.select;
            const div = typeof cfg.div === 'string' ? document.getElementById(cfg.div) : cfg.div;
            if (!sel || !div) return;

            const toggle = () => {
                if (sel.value === 'MIXTO') {
                    div.classList.remove('d-none');
                    this.balancearPagoMixto(cfg.total, cfg.efectivo, cfg.transferencia, cfg.badge, 'init');
                } else {
                    div.classList.add('d-none');
                }
            };

            sel.addEventListener('change', toggle);

            const eInput = typeof cfg.efectivo === 'string' ? document.getElementById(cfg.efectivo) : cfg.efectivo;
            const rInput = typeof cfg.transferencia === 'string' ? document.getElementById(cfg.transferencia) : cfg.transferencia;
            const tInput = typeof cfg.total === 'string' ? document.getElementById(cfg.total) : cfg.total;

            if (eInput) eInput.addEventListener('input', () => this.balancearPagoMixto(cfg.total, cfg.efectivo, cfg.transferencia, cfg.badge, 'efectivo'));
            if (rInput) rInput.addEventListener('input', () => this.balancearPagoMixto(cfg.total, cfg.efectivo, cfg.transferencia, cfg.badge, 'transferencia'));
            if (tInput) tInput.addEventListener('input', () => this.balancearPagoMixto(cfg.total, cfg.efectivo, cfg.transferencia, cfg.badge, 'total'));

            // Estado inicial
            if (sel.value === 'MIXTO') {
                div.classList.remove('d-none');
            }
        }
    };

    // Exponer globalmente
    window.Validador = Validador;

})(window);
