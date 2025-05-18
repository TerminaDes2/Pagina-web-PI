document.addEventListener('DOMContentLoaded', function() {
    const contenedor = document.getElementById('contenedor-comentarios');
    const boton = document.getElementById('btn_comen');
    
    // Si alguno de los elementos no existe, salir
    if (!contenedor || !boton) return;
    
    // Elemento span dentro del botón (para cambiar solo el texto)
    const spanTexto = boton.querySelector('span');
    
    boton.addEventListener('click', function(){
        if (contenedor.classList.contains('ocultar')) {
            // Mostrar comentarios
            contenedor.classList.remove('ocultar');
            contenedor.style.display = 'block';
            
            // Solo cambiamos el texto dentro del span, manteniendo el icono
            if (spanTexto) {
                spanTexto.textContent = ' Ocultar comentarios';
            } else {
                // Si por algún motivo no hay span, cambiamos todo preservando el icono
                boton.innerHTML = '<i class="far fa-comments"></i> Ocultar comentarios';
            }
            
            // Efecto de desplazamiento suave hacia los comentarios
            setTimeout(() => {
                contenedor.scrollIntoView({behavior: 'smooth'});
            }, 300);
        } else {
            // Ocultar comentarios
            contenedor.classList.add('ocultar');
            contenedor.style.display = 'none';
            
            // Solo cambiamos el texto dentro del span, manteniendo el icono
            if (spanTexto) {
                spanTexto.textContent = ' Ver comentarios';
            } else {
                // Si por algún motivo no hay span, cambiamos todo preservando el icono
                boton.innerHTML = '<i class="far fa-comments"></i> Ver comentarios';
            }
        }
    });
});