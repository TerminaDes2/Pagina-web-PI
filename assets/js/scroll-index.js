/**
 * Script para mejorar la navegación por enlaces del índice
 * - Permite un desplazamiento suave
 * - Ajusta la posición para compensar la barra de navegación fija
 */

// Polyfill para CSS.escape si no está disponible en el navegador
if (!window.CSS || !window.CSS.escape) {
  window.CSS = window.CSS || {};
  window.CSS.escape = function(ident) {
    return ident.replace(/[\s+~%^=`|{}[\]:;,.<>/?!@#$&'()*\\]/g, '\\$&');
  };
}

document.addEventListener('DOMContentLoaded', function() {
  // Seleccionar todos los enlaces dentro del índice
  const indiceLinks = document.querySelectorAll('.indice a[href^="#"]');
  
  // Agregar event listener a cada enlace
  indiceLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      e.preventDefault();
      
      // Obtener el ID del destino desde el href
      const targetId = this.getAttribute('href');
      
      // Comprobar si el elemento existe
      if (targetId === '#') return;
      
      // Usar CSS.escape para manejar caracteres especiales en el selector
      const escapedId = CSS.escape(targetId.substring(1));
      const targetElement = document.querySelector('#' + escapedId);
      
      if (!targetElement) return;
      
      // Calcular la posición de desplazamiento con offset para el navbar
      const navbarHeight = 100; // Altura aproximada del navbar en píxeles
      const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset;
      const offsetPosition = targetPosition - navbarHeight;
      
      // Realizar el desplazamiento suave
      window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth'
      });
      
      // Si queremos actualizar la URL
      history.pushState(null, null, targetId);
    });
  });
});
