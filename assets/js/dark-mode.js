document.addEventListener('DOMContentLoaded', function() {
    // Seleccionar el botón de modo oscuro
    const darkModeToggle = document.getElementById('darkModeToggle');
    
    // Verificar si hay una preferencia guardada
    const darkMode = localStorage.getItem('darkMode') === 'enabled';
    
    // Función para activar el modo oscuro
    function enableDarkMode() {
        document.body.classList.add('dark-mode');
        localStorage.setItem('darkMode', 'enabled');
        // Notificar a la consola para propósitos de depuración
        console.log('Modo oscuro activado');
    }
    
    // Función para desactivar el modo oscuro
    function disableDarkMode() {
        document.body.classList.remove('dark-mode');
        localStorage.setItem('darkMode', 'disabled');
        // Notificar a la consola para propósitos de depuración
        console.log('Modo oscuro desactivado');
    }
    
    // Aplicar el modo guardado si existe
    if (darkMode) {
        enableDarkMode();
    }
    
    // Manejar el clic en el botón para cambiar entre modos
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            if (document.body.classList.contains('dark-mode')) {
                disableDarkMode();
            } else {
                enableDarkMode();
            }
            
            // Aplicar una pequeña animación al botón
            this.classList.add('clicked');
            setTimeout(() => {
                this.classList.remove('clicked');
            }, 300);
        });
    }
    
    // Opcional: seguir la preferencia del sistema operativo
    const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');
    
    function handleColorSchemeChange(e) {
        // Solo aplicar si el usuario no ha hecho una elección manual
        if (!localStorage.getItem('darkMode')) {
            if (e.matches) {
                enableDarkMode();
            } else {
                disableDarkMode();
            }
        }
    }
    
    // Verificar la preferencia inicial
    handleColorSchemeChange(prefersDarkScheme);
    
    // Escuchar cambios en la preferencia del sistema
    prefersDarkScheme.addEventListener('change', handleColorSchemeChange);
});
