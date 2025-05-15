document.addEventListener('DOMContentLoaded', function () {
    const botones = document.querySelectorAll('.toggle-btn');

    botones.forEach(btn => {
        btn.addEventListener('click', () => {
            // Obtener el elemento de respuesta
            const respuesta = btn.nextElementSibling;
            
            // Verificar si está visible (contemplando también cuando no tiene estilo definido)
            const visible = respuesta.style.display === 'block';
            
            // Cambiar la visualización
            respuesta.style.display = visible ? 'none' : 'block';
            
            // Actualizar solo el ícono, preservando el resto del texto
            if (btn.textContent.includes('⮟')) {
                btn.textContent = btn.textContent.replace('⮟', '⮝');
            } else {
                btn.textContent = btn.textContent.replace('⮝', '⮟');
            }
        });
    });

    // Agregar funcionalidad para el editor del formulario
    document.querySelectorAll('.editor button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Evitar envío del formulario
        });
    });
});
