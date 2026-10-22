/**
 * SearchableSelect
 * Componente modular de selector con buscador integrado y ordenamiento descendente por pasajes vendidos.
 */
class SearchableSelect {
    constructor(selectElement, options = {}) {
        this.select = typeof selectElement === 'string' ? document.querySelector(selectElement) : selectElement;
        if (!this.select) return;

        // Evitar doble inicialización
        if (this.select._searchableInstance) {
            this.select._searchableInstance.updateOptions(options);
            return this.select._searchableInstance;
        }

        this.options = Object.assign({
            sortBySales: true,
            salesData: {},          // Mapa { 'Caracas': 8, 'Cúcuta': 5, ... }
            showBadges: true,
            placeholder: 'Seleccionar',
            uppercase: true,
            onSelect: null
        }, options);

        this.isOpen = false;
        this.focusedIndex = -1;
        this.visibleItems = [];

        this.initDOM();
        this.rebuild();
        this.bindEvents();

        this.select._searchableInstance = this;
    }

    /**
     * Construye la estructura visual envolvente
     */
    initDOM() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'searchable-select-wrapper';

        // Disparador
        this.trigger = document.createElement('div');
        this.trigger.className = 'searchable-select-trigger';
        this.trigger.setAttribute('tabindex', '0');

        this.labelSpan = document.createElement('span');
        this.labelSpan.className = 'searchable-select-label';
        this.labelSpan.textContent = this.options.placeholder;

        this.arrowBox = document.createElement('div');
        this.arrowBox.className = 'searchable-select-arrow-box';
        this.arrowBox.innerHTML = '&#9660;';

        this.trigger.appendChild(this.labelSpan);
        this.trigger.appendChild(this.arrowBox);

        // Desplegable
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'searchable-select-dropdown';

        // Caja de búsqueda con lupa
        this.searchBox = document.createElement('div');
        this.searchBox.className = 'searchable-select-search-box';

        this.searchInput = document.createElement('input');
        this.searchInput.type = 'text';
        this.searchInput.className = 'searchable-select-search-input';
        this.searchInput.placeholder = '';
        this.searchInput.setAttribute('autocomplete', 'off');

        this.searchIcon = document.createElement('span');
        this.searchIcon.className = 'searchable-select-search-icon';
        this.searchIcon.innerHTML = '&#128269;'; // 🔍

        this.searchBox.appendChild(this.searchInput);
        this.searchBox.appendChild(this.searchIcon);

        // Lista de opciones
        this.optionsList = document.createElement('ul');
        this.optionsList.className = 'searchable-select-options';

        // Mensaje sin resultados
        this.noResults = document.createElement('div');
        this.noResults.className = 'searchable-select-no-results';
        this.noResults.textContent = 'No se encontraron resultados';
        this.noResults.style.display = 'none';

        this.dropdown.appendChild(this.searchBox);
        this.dropdown.appendChild(this.optionsList);
        this.dropdown.appendChild(this.noResults);

        // Insertar en el DOM reemplazando la visibilidad del select nativo
        this.select.classList.add('searchable-select-hidden-native');
        this.select.parentNode.insertBefore(this.wrapper, this.select);
        this.wrapper.appendChild(this.trigger);
        this.wrapper.appendChild(this.dropdown);
        this.wrapper.appendChild(this.select); // Mover dentro del wrapper para mantener flujo
    }

    /**
     * Normaliza cadenas para comparaciones sin acentos ni mayúsculas
     */
    static normalize(text) {
        if (!text) return '';
        return text.toString()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .trim()
            .toLowerCase();
    }

    /**
     * Reconstruye la lista de opciones a partir del <select> nativo
     */
    rebuild() {
        this.syncDisabled();
        this.optionsList.innerHTML = '';
        this.items = [];

        const nativeOptions = Array.from(this.select.options);
        if (nativeOptions.length === 0) {
            this.labelSpan.textContent = this.options.placeholder;
            this.trigger.classList.add('placeholder-selected');
            return;
        }

        // Separar placeholder / primera opción vacía de los ítems con valor real
        let placeholderOpt = null;
        let dataOptions = [];

        nativeOptions.forEach(opt => {
            const val = opt.value.trim();
            const text = opt.textContent.trim();
            // Determinar si es placeholder
            if (val === '' || text.startsWith('-') || text.toLowerCase() === 'seleccionar') {
                if (!placeholderOpt) placeholderOpt = opt;
                else dataOptions.push(opt);
            } else {
                dataOptions.push(opt);
            }
        });

        // Ordenamiento: En función de cuántos pasajes están vendidos (DESCENDENTE)
        if (this.options.sortBySales) {
            const salesMap = this.options.salesData || {};
            dataOptions.sort((a, b) => {
                const normA = a.value.trim();
                const normB = b.value.trim();
                const cleanA = normA.replace(/^[📍🏁🚌\s]+/, '');
                const cleanB = normB.replace(/^[📍🏁🚌\s]+/, '');

                const salesA = salesMap[cleanA] || salesMap[normA] || parseInt(a.getAttribute('data-ventas') || '0', 10) || 0;
                const salesB = salesMap[cleanB] || salesMap[normB] || parseInt(b.getAttribute('data-ventas') || '0', 10) || 0;

                if (salesB !== salesA) {
                    return salesB - salesA; // Más vendidos arriba
                }
                return cleanA.localeCompare(cleanB);
            });
        }

        const sortedOptions = [];
        if (placeholderOpt) sortedOptions.push(placeholderOpt);
        sortedOptions.push(...dataOptions);

        let selectedText = '';
        const currentSelectedVal = this.select.value;

        sortedOptions.forEach((opt, idx) => {
            const val = opt.value;
            const rawText = opt.textContent.trim().replace(/^[📍🏁🚌\s]+/, '');
            const isPlaceholder = (val === '' || opt.textContent.includes('Seleccione') || opt.textContent.includes('Elija'));
            const displayText = isPlaceholder ? (this.options.placeholder || 'Seleccionar') : (this.options.uppercase ? rawText.toUpperCase() : rawText);

            const li = document.createElement('li');
            li.className = 'searchable-select-option';
            li.setAttribute('data-value', val);
            li.setAttribute('data-index', idx);

            const isSelected = (val === currentSelectedVal) || (currentSelectedVal === '' && isPlaceholder);
            if (isSelected) {
                li.classList.add('is-selected');
                selectedText = displayText;
            }

            const textSpan = document.createElement('span');
            textSpan.className = 'searchable-select-text';
            textSpan.textContent = displayText;
            li.appendChild(textSpan);

            // Si tiene pasajes vendidos y no es placeholder, mostrar badge informativo
            const salesMap = this.options.salesData || {};
            const cleanKey = val.replace(/^[📍🏁🚌\s]+/, '');
            const ventas = salesMap[cleanKey] || salesMap[val] || parseInt(opt.getAttribute('data-ventas') || '0', 10) || 0;

            if (ventas > 0 && !isPlaceholder && this.options.showBadges) {
                const badge = document.createElement('span');
                badge.className = 'searchable-select-sales-badge' + (ventas >= 5 ? ' searchable-select-badge-top' : '');
                badge.innerHTML = `&#128293; ${ventas} pasajes`;
                badge.title = `${ventas} pasajes vendidos a este destino`;
                li.appendChild(badge);
            }

            li.addEventListener('click', (e) => {
                e.stopPropagation();
                this.selectValue(val);
            });

            this.optionsList.appendChild(li);
            this.items.push({
                element: li,
                value: val,
                text: displayText,
                normalized: SearchableSelect.normalize(displayText),
                sales: ventas,
                isPlaceholder: isPlaceholder
            });
        });

        this.visibleItems = [...this.items];

        // Actualizar etiqueta del disparador
        if (selectedText) {
            this.labelSpan.textContent = selectedText;
            if (this.select.value === '') {
                this.trigger.classList.add('placeholder-selected');
            } else {
                this.trigger.classList.remove('placeholder-selected');
            }
        } else {
            this.labelSpan.textContent = this.options.placeholder;
            this.trigger.classList.add('placeholder-selected');
        }
    }

    /**
     * Sincroniza estado habilitado / deshabilitado
     */
    syncDisabled() {
        if (this.select.disabled) {
            this.trigger.classList.add('is-disabled');
            this.close();
        } else {
            this.trigger.classList.remove('is-disabled');
        }
    }

    /**
     * Abre el desplegable y enfoca el buscador
     */
    open() {
        if (this.select.disabled || this.isOpen) return;

        // Cerrar cualquier otro selector abierto en la página
        document.querySelectorAll('.searchable-select-dropdown.is-open').forEach(el => {
            el.classList.remove('is-open');
        });
        document.querySelectorAll('.searchable-select-trigger.is-open').forEach(el => {
            el.classList.remove('is-open');
        });

        this.isOpen = true;
        this.trigger.classList.add('is-open');
        this.dropdown.classList.add('is-open');

        this.searchInput.value = '';
        this.filter('');

        setTimeout(() => {
            this.searchInput.focus();
            // Scroll al item seleccionado
            const selEl = this.optionsList.querySelector('.is-selected');
            if (selEl) {
                selEl.scrollIntoView({ block: 'nearest' });
            }
        }, 50);
    }

    /**
     * Cierra el desplegable
     */
    close() {
        if (!this.isOpen) return;
        this.isOpen = false;
        this.trigger.classList.remove('is-open');
        this.dropdown.classList.remove('is-open');
        this.focusedIndex = -1;
        this.items.forEach(it => it.element.classList.remove('is-focused'));
    }

    /**
     * Alterna apertura / cierre
     */
    toggle() {
        if (this.isOpen) this.close();
        else this.open();
    }

    /**
     * Selecciona un valor y dispara eventos
     */
    selectValue(val) {
        if (this.select.value !== val) {
            this.select.value = val;
            this.select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Actualizar visualmente la opción seleccionada
        this.items.forEach(it => {
            if (it.value === val) {
                it.element.classList.add('is-selected');
                this.labelSpan.textContent = it.text;
                if (it.isPlaceholder) {
                    this.trigger.classList.add('placeholder-selected');
                } else {
                    this.trigger.classList.remove('placeholder-selected');
                }
            } else {
                it.element.classList.remove('is-selected');
            }
        });

        if (typeof this.options.onSelect === 'function') {
            this.options.onSelect(val);
        }

        this.close();
        this.trigger.focus();
    }

    /**
     * Sincroniza la visualización cuando el select nativo cambia externamente
     */
    syncValueFromSelect() {
        const val = this.select.value;
        let found = false;
        this.items.forEach(it => {
            if (it.value === val) {
                it.element.classList.add('is-selected');
                this.labelSpan.textContent = it.text;
                if (it.isPlaceholder) {
                    this.trigger.classList.add('placeholder-selected');
                } else {
                    this.trigger.classList.remove('placeholder-selected');
                }
                found = true;
            } else {
                it.element.classList.remove('is-selected');
            }
        });
        if (!found) {
            this.labelSpan.textContent = this.options.placeholder;
            this.trigger.classList.add('placeholder-selected');
        }
    }

    /**
     * Filtra las opciones según el texto ingresado en el buscador
     */
    filter(query) {
        const qNorm = SearchableSelect.normalize(query);
        let matches = 0;
        this.visibleItems = [];

        this.items.forEach(item => {
            if (!qNorm || item.normalized.includes(qNorm)) {
                item.element.style.display = 'flex';
                this.visibleItems.push(item);
                matches++;
            } else {
                item.element.style.display = 'none';
            }
        });

        this.noResults.style.display = matches === 0 ? 'block' : 'none';
        this.focusedIndex = -1;
    }

    /**
     * Configuración de oyentes de eventos y teclado
     */
    bindEvents() {
        // Clic en el disparador
        this.trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });

        // Búsqueda en tiempo real
        this.searchInput.addEventListener('input', (e) => {
            this.filter(e.target.value);
        });

        // Clic dentro del buscador no debe cerrar
        this.searchBox.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Navegación por teclado en el buscador
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.moveFocus(1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.moveFocus(-1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (this.focusedIndex >= 0 && this.visibleItems[this.focusedIndex]) {
                    this.selectValue(this.visibleItems[this.focusedIndex].value);
                } else if (this.visibleItems.length > 0) {
                    this.selectValue(this.visibleItems[0].value);
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this.close();
                this.trigger.focus();
            }
        });

        // Navegación por teclado en el trigger
        this.trigger.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                e.preventDefault();
                this.open();
            }
        });

        // Clic fuera cierra el dropdown
        document.addEventListener('click', (e) => {
            if (!this.wrapper.contains(e.target)) {
                this.close();
            }
        });

        // Sincronizar si el select nativo cambia externamente
        this.select.addEventListener('change', () => {
            this.syncValueFromSelect();
        });

        // Observar cambios dinámicos en el <select> nativo (ej: alCambiarOrigenPasaje)
        this.mutationObserver = new MutationObserver(() => {
            this.rebuild();
        });
        this.mutationObserver.observe(this.select, {
            childList: true,
            attributes: true,
            attributeFilter: ['disabled']
        });
    }

    /**
     * Mueve el foco del teclado arriba/abajo
     */
    moveFocus(direction) {
        if (this.visibleItems.length === 0) return;

        this.items.forEach(it => it.element.classList.remove('is-focused'));

        this.focusedIndex += direction;
        if (this.focusedIndex < 0) this.focusedIndex = 0;
        if (this.focusedIndex >= this.visibleItems.length) this.focusedIndex = this.visibleItems.length - 1;

        const targetItem = this.visibleItems[this.focusedIndex];
        if (targetItem) {
            targetItem.element.classList.add('is-focused');
            targetItem.element.scrollIntoView({ block: 'nearest' });
        }
    }

    /**
     * Actualiza las opciones y los datos de ventas externamente
     */
    updateOptions(newOptions) {
        this.options = Object.assign(this.options, newOptions);
        this.rebuild();
    }
}

// Exponer globalmente
window.SearchableSelect = SearchableSelect;
