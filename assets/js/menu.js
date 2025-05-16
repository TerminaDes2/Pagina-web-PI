/**
 * Script para manejar la funcionalidad del menú desplegable
 * Versión: 3.3
 */
document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const menuBtn = document.getElementById('menuToggleBtn');
    const header = document.querySelector('.hf-main-header');
    const mainNav = document.querySelector('.hf-main-nav');
    
    // Estado inicial
    let menuVisible = false;
    let mobileMenuCreated = false;
    let mobileMenu = null;
    
    // Inicialización
    init();
    
    /**
     * Inicializa la configuración del menú
     */
    function init() {
        // Preparar el menú móvil si estamos en una pantalla pequeña
        checkAndCreateMobileMenu();
        
        // Configurar event listeners
        setupEventListeners();
        
        // Verificar el tamaño de la ventana al inicio
        handleWindowResize();
    }
    
    /**
     * Configura todos los event listeners necesarios
     */
    function setupEventListeners() {
        // Evento clic para el botón de menú
        if (menuBtn) {
            menuBtn.addEventListener('click', toggleMenu);
        }
        
        // Evento para submenús desplegables (menú móvil)
        const submenuToggles = document.querySelectorAll('.hf-submenu-toggle');
        submenuToggles.forEach(toggle => {
            toggle.addEventListener('click', handleSubmenuToggle);
        });
        
        // Evento para categorías en versión desktop
        const categoriaToggles = document.querySelectorAll('.hf-main-nav .hf-categoria-toggle');
        categoriaToggles.forEach(toggle => {
            toggle.addEventListener('click', handleDesktopCategoriaToggle);
        });
        
        // Evento para el menú de usuario
        const userProfileButtons = document.querySelectorAll('.hf-profile-circle');
        userProfileButtons.forEach(btn => {
            btn.addEventListener('click', handleUserMenuToggle);
        });
        
        // Evento para cerrar submenús al hacer clic fuera
        document.addEventListener('click', function(e) {
            // Cerrar menús de categorías al hacer clic fuera
            if (!e.target.closest('.hf-menu-categorias')) {
                const visibleCategorias = document.querySelectorAll('.hf-contenido-categorias.visible');
                visibleCategorias.forEach(submenu => {
                    submenu.classList.remove('visible');
                });
                
                // Resetear todas las flechas y estados activos
                const activeToggles = document.querySelectorAll('.hf-categoria-toggle.active');
                activeToggles.forEach(toggle => {
                    toggle.classList.remove('active');
                    const arrow = toggle.querySelector('.hf-dropdown-arrow');
                    if (arrow) arrow.style.transform = '';
                });
            }
            
            // Cerrar submenús desplegables al hacer clic fuera
            if (!e.target.closest('.hf-menu-desplegable')) {
                const visibleSubmenus = document.querySelectorAll('.hf-contenido-desplegable.visible');
                visibleSubmenus.forEach(submenu => {
                    submenu.classList.remove('visible');
                });
            }
            
            // Cerrar menú de usuario al hacer clic fuera
            if (!e.target.closest('.hf-user-menu')) {
                const userDropdowns = document.querySelectorAll('.hf-user-dropdown');
                userDropdowns.forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
            }
        });
        
        // Evento resize para ajustar el menú al cambio de tamaño
        window.addEventListener('resize', handleWindowResize);
    }
    
    /**
     * Maneja la interacción con categorías en versión desktop
     * @param {Event} e - El evento click
     */
    function handleDesktopCategoriaToggle(e) {
        // Solo prevenir navegación si estamos en desktop y se hace clic en la flecha
        if (window.innerWidth > 768) {
            const isClickInsideArrow = e.target.classList.contains('hf-dropdown-arrow') || 
                                     e.target.closest('.hf-dropdown-arrow');
            if (isClickInsideArrow) {
                e.preventDefault();
            }
        }
    }
    
    /**
     * Maneja la interacción con categorías en móvil
     * @param {Event} e - El evento click
     */
    function handleMobileCategoriaToggle(e) {
        e.preventDefault();
        e.stopPropagation();

        const toggle = e.currentTarget;
        const parent = toggle.closest('.hf-menu-categorias');
        if (!parent) return;

        const submenu = parent.querySelector('.hf-contenido-categorias');
        if (!submenu) return;

        // Verificar si el menú está actualmente visible
        const isVisible = submenu.classList.contains('visible');

        if (isVisible) {
            // Deseleccionar: quitar todo el diseño de seleccionado
            submenu.classList.remove('visible');
            toggle.classList.remove('active');
            // Restaurar la flecha
            const arrow = toggle.querySelector('.hf-dropdown-arrow');
            if (arrow) {
                arrow.style.transform = '';
                arrow.classList.remove('rotated');
            }
            // Quitar fondo y borde si tuvieran clases extra (según CSS)
            toggle.style.backgroundColor = '';
            toggle.style.borderLeft = '';
        } else {
            // Cerrar todos los demás submenús y limpiar todos los toggles
            const allVisibleSubmenus = mobileMenu.querySelectorAll('.hf-contenido-categorias.visible');
            const allActiveToggles = mobileMenu.querySelectorAll('.hf-categoria-toggle.active');
            const allArrows = mobileMenu.querySelectorAll('.hf-dropdown-arrow');

            allVisibleSubmenus.forEach(menu => menu.classList.remove('visible'));
            allActiveToggles.forEach(activeToggle => {
                activeToggle.classList.remove('active');
                activeToggle.style.backgroundColor = '';
                activeToggle.style.borderLeft = '';
            });
            allArrows.forEach(arrow => {
                arrow.style.transform = '';
                arrow.classList.remove('rotated');
            });

            // Seleccionar el actual
            submenu.classList.add('visible');
            toggle.classList.add('active');
            // Rotar la flecha
            const arrow = toggle.querySelector('.hf-dropdown-arrow');
            if (arrow) {
                arrow.style.transform = 'rotate(180deg)';
                arrow.classList.add('rotated');
            }
            // Aplicar fondo y borde si tu CSS lo requiere (opcional)
            // toggle.style.backgroundColor = 'rgba(255,255,255,0.1)';
            // toggle.style.borderLeft = '3px solid var(--accent-color)';
        }
    }
    
    /**
     * Maneja el toggle de los submenús en el menú móvil
     * @param {Event} e - El evento click
     */
    function handleSubmenuToggle(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Obtener el submenú relacionado con este toggle
        const parent = this.parentElement;
        const submenu = parent.querySelector('.hf-contenido-desplegable');
        
        if (!submenu) return;
        
        // Cerrar otros submenús
        const allSubmenus = document.querySelectorAll('.hf-contenido-desplegable');
        allSubmenus.forEach(menu => {
            if (menu !== submenu && menu.classList.contains('visible')) {
                menu.classList.remove('visible');
            }
        });
        
        // Alternar el submenú actual
        submenu.classList.toggle('visible');
        
        // Rotar el icono
        const icon = this.querySelector('i');
        if (icon) {
            icon.style.transform = submenu.classList.contains('visible') ? 'rotate(180deg)' : '';
        }
    }
    
    /**
     * Maneja el toggle del menú de usuario
     * @param {Event} e - El evento click
     */
    function handleUserMenuToggle(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Obtener el dropdown relacionado con este botón de perfil
        const parent = this.closest('.hf-user-menu');
        if (!parent) return;
        
        const dropdown = parent.querySelector('.hf-user-dropdown');
        if (!dropdown) return;
        
        // Cerrar otros dropdowns de usuario
        const allDropdowns = document.querySelectorAll('.hf-user-dropdown');
        allDropdowns.forEach(menu => {
            if (menu !== dropdown && menu.classList.contains('show')) {
                menu.classList.remove('show');
            }
        });
        
        // Alternar el dropdown actual
        dropdown.classList.toggle('show');
    }
    
    /**
     * Crea el menú móvil si no existe
     */
    function checkAndCreateMobileMenu() {
        if (!mobileMenuCreated) {
            // Crear el contenedor del menú móvil
            mobileMenu = document.createElement('div');
            mobileMenu.className = 'hf-menu-desplegable';
            
            // Agregar botón de cierre en la parte superior
            const closeBtn = document.createElement('button');
            closeBtn.className = 'hf-menu-close-btn';
            closeBtn.innerHTML = '<i class="fas fa-times"></i>';
            closeBtn.addEventListener('click', function() {
                toggleMenu(null, false);
            });
            mobileMenu.appendChild(closeBtn);
            
            // Agregar el título del menú
            const menuTitle = document.createElement('div');
            menuTitle.className = 'hf-menu-title';
            menuTitle.textContent = 'Menú';
            mobileMenu.appendChild(menuTitle);
            
            // Clonar el menú principal para el móvil
            const mainMenu = document.querySelector('.hf-main-menu');
            if (mainMenu) {
                const clonedMenu = mainMenu.cloneNode(true);
                mobileMenu.appendChild(clonedMenu);
                
                // NUEVA IMPLEMENTACIÓN: Configurar el menú de noticias móvil
                configurarMenuNoticiasMobile(mobileMenu);
            }
            
            // Agregar el buscador y selector de idioma
            const mobileMenuExtras = document.createElement('div');
            mobileMenuExtras.className = 'hf-mobile-menu-extras';
            
            // Clonar el buscador
            const searchBox = document.querySelector('.hf-search-box');
            if (searchBox) {
                const clonedSearch = searchBox.cloneNode(true);
                clonedSearch.className = 'hf-search-box hf-mobile-search';
                mobileMenuExtras.appendChild(clonedSearch);
            }
            
            // Clonar el selector de idioma
            const langForm = document.querySelector('.language-form');
            if (langForm) {
                const clonedLangForm = langForm.cloneNode(true);
                clonedLangForm.className = 'language-form hf-mobile-language';
                mobileMenuExtras.appendChild(clonedLangForm);
            }
            
            // Añadir extras al menú móvil
            mobileMenu.appendChild(mobileMenuExtras);
            
            // Añadir al DOM
            header.appendChild(mobileMenu);
            mobileMenuCreated = true;
            
            // Asegurarse de que el menú esté inicialmente oculto
            setTimeout(() => {
                mobileMenu.style.display = 'block';
            }, 100);
        }
    }
    
    /**
     * Configura específicamente el menú de noticias para móviles
     * @param {HTMLElement} menuContainer - El contenedor del menú móvil
     */
    function configurarMenuNoticiasMobile(menuContainer) {
        // Buscar el toggle de noticias en el menú clonado
        const noticiasCategorias = menuContainer.querySelectorAll('.hf-menu-categorias');
        
        noticiasCategorias.forEach(categoria => {
            // Obtener elementos relevantes
            const toggle = categoria.querySelector('.hf-categoria-toggle');
            const submenu = categoria.querySelector('.hf-contenido-categorias');
            
            if (!toggle || !submenu) return;
            
            // Eliminar cualquier event listener previo
            const nuevoToggle = toggle.cloneNode(true);
            toggle.parentNode.replaceChild(nuevoToggle, toggle);
            
            // Añadir nuevo event listener optimizado para móvil
            nuevoToggle.addEventListener('click', handleMobileCategoriaToggle);
        });
    }
    
    /**
     * Alterna la visibilidad del menú móvil
     * @param {Event|null} e - El evento click (opcional)
     * @param {boolean} [force] - Forzar estado específico (opcional)
     */
    function toggleMenu(e, force) {
        // Determinar el nuevo estado
        menuVisible = force !== undefined ? force : !menuVisible;

        // Aplicar estado al menú
        if (mobileMenu) {
            // Añadir o quitar clase activa
            mobileMenu.classList.toggle('active', menuVisible);

            // Prevenir scroll cuando el menú está abierto
            document.body.style.overflow = menuVisible ? 'hidden' : '';

            // Forzar repintado del DOM para asegurar que la transición funcione
            if (menuVisible) {
                mobileMenu.style.visibility = 'visible';
            } else {
                // Dar tiempo para la transición antes de ocultar
                setTimeout(() => {
                    if (!menuVisible) {
                        mobileMenu.style.visibility = 'hidden';
                    }
                }, 400); // Tiempo igual a la duración de la transición
            }

            // Actualizar ícono del botón
            if (menuBtn) {
                const icon = menuBtn.querySelector('i');
                if (icon) {
                    if (menuVisible) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                        menuBtn.setAttribute('aria-expanded', 'true');
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                        menuBtn.setAttribute('aria-expanded', 'false');
                    }
                }
            }
        } else {
            console.error("El menú móvil no está disponible");
            // Crear el menú si no existe
            checkAndCreateMobileMenu();
            // Intentar de nuevo después de crearlo
            setTimeout(() => toggleMenu(null, menuVisible), 100);
        }
    }
    function handleWindowResize() {
        const isMobile = window.innerWidth <= 768;
        
        // Si la ventana es grande y el menú está visible, cerrarlo
        if (!isMobile && menuVisible) {
            toggleMenu(null, false);
        }
        
        // Asegurar que el botón del menú solo aparece en móvil
        if (menuBtn) {
            menuBtn.style.display = isMobile ? 'flex' : 'none';
        }
        
        // Si estamos en vista móvil, asegurar que el menú móvil está creado
        if (isMobile && !mobileMenuCreated) {
            checkAndCreateMobileMenu();
        }
        
        // En cambio de tamaño, restablecer todos los menús desplegables a su estado inicial
        if (mobileMenu) {
            // Ocultar submenús y restaurar estados
            const visibleSubmenus = mobileMenu.querySelectorAll('.hf-contenido-categorias.visible');
            const activeToggles = mobileMenu.querySelectorAll('.hf-categoria-toggle.active');
            const rotatedArrows = mobileMenu.querySelectorAll('.hf-dropdown-arrow');

            visibleSubmenus.forEach(menu => menu.classList.remove('visible'));
            activeToggles.forEach(toggle => toggle.classList.remove('active'));
            rotatedArrows.forEach(arrow => {
                arrow.style.transform = '';
                arrow.classList.remove('rotated');
            });
        }
    }
});
