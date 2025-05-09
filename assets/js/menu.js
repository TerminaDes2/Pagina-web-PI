console.log('menu.js cargado correctamente');

document.addEventListener('DOMContentLoaded', () => {
    const menuButton = document.querySelector('.hf-menu-toggle');
    const mainMenu  = document.querySelector('.hf-menu-desplegable'); // Cambiado a .hf-menu-desplegable
  
    menuButton.addEventListener('click', (e) => {
        e.preventDefault();
        mainMenu.classList.toggle('active');
        console.log('Menú principal toggled');
    });

    // Si quieres mantener también los submenús:
    const submenuToggles = document.querySelectorAll('.hf-menu-desplegable > li > a');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', e => {
            e.preventDefault();
            toggle.nextElementSibling.classList.toggle('visible');
            console.log('Submenú toggled');
        });
    });
});
