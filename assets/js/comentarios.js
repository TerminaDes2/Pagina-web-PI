document.addEventListener('DOMContentLoaded', function() {
    const contenedor = document.getElementById('contenedor-comentarios');
    const boton = document.getElementById('btn_comen');

    boton.addEventListener('click', function(){
        const estiloActual = window.getComputedStyle(contenedor).display;

        if (estiloActual === 'none') {
        contenedor.style.display = 'block'; 
        boton.textContent = 'Ocultar comentarios'; 
        } else {
        contenedor.style.display = 'none';
        boton.textContent = 'Ver comentarios'; 
        }
    });
});