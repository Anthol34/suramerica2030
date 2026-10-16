/**
 * Navegación Móvil Responsive - Suramerica 2030
 * Control del menú lateral tipo drawer y barra superior móvil
 */
(function() {
    'use strict';

    function initMobileNav() {
        const btnToggle = document.getElementById('btnToggleSidebar');
        const btnClose = document.getElementById('btnCloseSidebar');
        const sidebar = document.getElementById('sidebarNav');
        const backdrop = document.getElementById('sidebarBackdrop');

        if (!sidebar) return;

        // Evitar inicializar más de una vez
        if (sidebar.dataset.mobileNavInitialized === 'true') return;
        sidebar.dataset.mobileNavInitialized = 'true';

        function abrirMenu(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            sidebar.classList.add('mobile-open');
            if (backdrop) backdrop.classList.add('active');
            document.body.classList.add('sidebar-open');
        }

        function cerrarMenu(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            sidebar.classList.remove('mobile-open');
            if (backdrop) backdrop.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }

        if (btnToggle) {
            btnToggle.addEventListener('click', function (e) {
                if (sidebar.classList.contains('mobile-open')) {
                    cerrarMenu(e);
                } else {
                    abrirMenu(e);
                }
            });
        }

        if (btnClose) {
            btnClose.addEventListener('click', cerrarMenu);
        }

        if (backdrop) {
            backdrop.addEventListener('click', cerrarMenu);
        }

        // Cerrar al presionar la tecla Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
                cerrarMenu(e);
            }
        });

        // Cerrar si la pantalla se redimensiona a tamaño PC
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768 && sidebar.classList.contains('mobile-open')) {
                cerrarMenu();
            }
        });

        // Cerrar al hacer clic en enlaces normales dentro del menú
        const navLinks = sidebar.querySelectorAll('a.nav-link:not([data-bs-toggle="collapse"])');
        navLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth < 768) {
                    cerrarMenu();
                }
            });
        });

        // Gesto táctil Swipe hacia la izquierda para cerrar en móvil
        let touchStartX = 0;
        sidebar.addEventListener('touchstart', function (e) {
            if (e.changedTouches && e.changedTouches[0]) {
                touchStartX = e.changedTouches[0].screenX;
            }
        }, { passive: true });

        sidebar.addEventListener('touchend', function (e) {
            if (e.changedTouches && e.changedTouches[0]) {
                const touchEndX = e.changedTouches[0].screenX;
                if (touchStartX - touchEndX > 50) {
                    cerrarMenu(e);
                }
            }
        }, { passive: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileNav);
    } else {
        initMobileNav();
    }
})();
