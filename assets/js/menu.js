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
        
        // Evento para categorías
        const categoriaToggles = document.querySelectorAll('.hf-categoria-toggle');
        categoriaToggles.forEach(toggle => {
            toggle.addEventListener('click', handleCategoriaToggle);
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
     * Maneja el toggle de los menús de categorías
     * @param {Event} e - El evento click
     */
    function handleCategoriaToggle(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Solo manejar el toggle en móvil (en desktop usamos hover)
        const isMobile = window.innerWidth <= 768;
        
        if (!isMobile) {
            return;
        }
        
        // Obtener el submenú de categorías relacionado con este toggle
        const parent = this.parentElement;
        const submenu = parent.querySelector('.hf-contenido-categorias');
        
        if (!submenu) return;
        
        // Cerrar otros submenús de categorías
        const allCategorias = document.querySelectorAll('.hf-contenido-categorias');
        allCategorias.forEach(menu => {
            if (menu !== submenu && menu.classList.contains('visible')) {
                menu.classList.remove('visible');
            }
        });
        
        // Alternar el submenú actual de categorías
        submenu.classList.toggle('visible');
        
        // Rotar el icono
        const icon = this.querySelector('i');
        if (icon) {
            icon.style.transform = submenu.classList.contains('visible') ? 'rotate(180deg)' : '';
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
                
                // Actualizar clases para diferenciar entre menú de categorías y menú móvil
                const categoriasToggle = mobileMenu.querySelectorAll('.hf-categoria-toggle');
                categoriasToggle.forEach(toggle => {
                    toggle.addEventListener('click', handleCategoriaToggle);
                });
                
                // Agregar event listeners a los submenús clonados
                const clonedSubmenuToggles = mobileMenu.querySelectorAll('.hf-submenu-toggle');
                clonedSubmenuToggles.forEach(toggle => {
                    toggle.addEventListener('click', handleSubmenuToggle);
                });
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
        }
    }
    
    /**
     * Alterna la visibilidad del menú móvil
     * @param {Event|null} e - El evento click (opcional)
     * @param {boolean|undefined} force - Forzar estado específico (opcional)
     */
    function toggleMenu(e, force) {
        if (e) e.preventDefault();
        
        // Determinar el nuevo estado
        menuVisible = force !== undefined ? force : !menuVisible;
        
        // Aplicar estado al menú
        mobileMenu.classList.toggle('active', menuVisible);
        
        // Actualizar ícono del botón
        const icon = menuBtn.querySelector('i');
        if (icon) {
            if (menuVisible) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
        
        // Prevenir scroll cuando el menú está abierto
        document.body.style.overflow = menuVisible ? 'hidden' : '';
    }
    
    /**
     * Maneja el cambio de tamaño de la ventana
     */
    function handleWindowResize() {
        // Si la ventana es grande y el menú está visible, cerrarlo
        if (window.innerWidth > 768 && menuVisible) {
            toggleMenu(null, false);
        }
    }
});
