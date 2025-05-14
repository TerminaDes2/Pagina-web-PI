// Prevenir duplicación de hojas de estilo
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si ya existe un script que haya ejecutado esta función
    if (window.headerFooterStylesLoaded) return;
    
    // Marcar como ejecutado
    window.headerFooterStylesLoaded = true;
    
    // Buscar todas las hojas de estilo de header-footer y dejar solo una
    const headerFooterStyles = document.querySelectorAll('link[href*="header-footer.css"]');
    if (headerFooterStyles.length > 1) {
        for (let i = 1; i < headerFooterStyles.length; i++) {
            headerFooterStyles[i].remove();
        }
    }
    
    // Inicializar el carrusel
    initCarousel();
});

function initCarousel() {
    // Seleccionar elementos del DOM
    const carouselTrack = document.querySelector('.carousel-track');
    const carouselItems = document.querySelectorAll('.carousel-item');
    const buttonLeft = document.querySelector('.carousel-button-left');
    const buttonRight = document.querySelector('.carousel-button-right');
    const indicators = document.querySelectorAll('.carousel-indicator');
    const carouselViewport = document.querySelector('.carousel-viewport');
    const carouselContainer = document.querySelector('.carousel-container');
    
    // Salir si no hay elementos necesarios
    if (!carouselTrack || !carouselItems.length) return;
    
    // Configuración del carrusel
    const cardsToShow = 3; // Siempre mostrar 3 tarjetas a la vez
    const totalGroups = Math.ceil(carouselItems.length / cardsToShow);
    
    // Hacer que las tarjetas sean clickeables
    carouselItems.forEach(item => {
        const link = item.querySelector('a').getAttribute('href');
        item.addEventListener('click', (e) => {
            // No navegar si el click fue en el botón o en los controles
            if (e.target.closest('.btn') || 
                e.target.closest('.carousel-button-left') || 
                e.target.closest('.carousel-button-right') ||
                e.target.closest('.carousel-indicator')) {
                return;
            }
            window.location.href = link;
        });
        item.style.cursor = 'pointer';
    });
    
    // Variables de control
    let currentIndex = 0;
    let itemWidth = carouselItems[0].offsetWidth;
    let itemGap = parseInt(window.getComputedStyle(carouselTrack).columnGap || 30);
    const itemFullWidth = itemWidth + itemGap;
    
    // Detectar si es dispositivo móvil (solo para ajustes visuales, no de comportamiento)
    const isMobile = window.innerWidth <= 768;
    
    // Función para mover el carrusel (siempre en grupos de 3)
    function moveToIndex(index) {
        currentIndex = index;
        
        // Calcular el grupo actual
        const currentGroup = Math.floor(index / cardsToShow);
        
        // Calcular posición del primer elemento del grupo
        let offset = currentGroup * (itemFullWidth * cardsToShow);
        
        // Si es el último grupo, ajustar para eliminar espacio vacío
        if (currentGroup === totalGroups - 1) {
            const viewport = carouselTrack.parentElement;
            const viewportWidth = viewport.offsetWidth;
            const contentWidth = carouselItems.length * itemFullWidth;
            
            // Ajustar para mostrar hasta el final sin espacio extra
            if (carouselItems.length % cardsToShow !== 0) {
                offset = contentWidth - viewportWidth;
                offset = Math.max(0, offset);
            }
        }
        
        // Aplicar el desplazamiento
        carouselTrack.style.transform = `translateX(-${offset}px)`;
        
        // Actualizar indicadores
        const indicatorsContainer = document.querySelector('.carousel-indicators');
        const currentIndicators = indicatorsContainer.querySelectorAll('.carousel-indicator');
        
        // Activar indicador según el grupo
        currentIndicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', parseInt(indicator.dataset.group) === currentGroup);
        });
    }
    
    // Navegación con botones (siempre de 3 en 3)
    if (buttonLeft) {
        buttonLeft.addEventListener('click', (e) => {
            e.stopPropagation();
            
            // Retroceder un grupo completo
            const currentGroup = Math.floor(currentIndex / cardsToShow);
            const newGroup = (currentGroup - 1 + totalGroups) % totalGroups;
            currentIndex = newGroup * cardsToShow;
            
            moveToIndex(currentIndex);
        });
    }
    
    if (buttonRight) {
        buttonRight.addEventListener('click', (e) => {
            e.stopPropagation();
            
            // Avanzar un grupo completo
            const currentGroup = Math.floor(currentIndex / cardsToShow);
            const newGroup = (currentGroup + 1) % totalGroups;
            currentIndex = newGroup * cardsToShow;
            
            moveToIndex(currentIndex);
        });
    }
    
    // Navegación con indicadores (un indicador por grupo)
    const setupIndicators = () => {
        const indicatorsContainer = document.querySelector('.carousel-indicators');
        if (!indicatorsContainer) return;
        
        // Limpiar indicadores existentes
        indicatorsContainer.innerHTML = '';
        
        // Crear indicadores para grupos
        for (let i = 0; i < totalGroups; i++) {
            const indicator = document.createElement('span');
            indicator.classList.add('carousel-indicator');
            indicator.dataset.group = i;
            if (i === 0) indicator.classList.add('active');
            
            indicator.addEventListener('click', (e) => {
                e.stopPropagation();
                currentIndex = i * cardsToShow;
                moveToIndex(currentIndex);
            });
            
            indicatorsContainer.appendChild(indicator);
        }
    };
    
    // Inicializar los indicadores
    setupIndicators();
    
    // Re-sincronización después de que todas las imágenes carguen
    Promise.all(Array.from(document.querySelectorAll('.carousel-item img')).map(img => {
        if (img.complete) return Promise.resolve();
        return new Promise(resolve => {
            img.onload = resolve;
            img.onerror = resolve; // También manejar errores
        });
    })).then(() => {
        // Recalcular anchos y posiciones después de cargar todas las imágenes
        itemWidth = carouselItems[0].offsetWidth;
        itemGap = parseInt(window.getComputedStyle(carouselTrack).columnGap || 30);
        moveToIndex(currentIndex);
    });
    
    // Auto-deslizamiento (siempre de 3 en 3)
    let autoSlide = setInterval(() => {
        // Avanzar un grupo completo
        const currentGroup = Math.floor(currentIndex / cardsToShow);
        const newGroup = (currentGroup + 1) % totalGroups;
        currentIndex = newGroup * cardsToShow;
        
        moveToIndex(currentIndex);
    }, 5000);
    
    // Pausar auto-deslizamiento al pasar el mouse
    carouselTrack.addEventListener('mouseenter', () => {
        clearInterval(autoSlide);
    });
    
    carouselTrack.addEventListener('mouseleave', () => {
        autoSlide = setInterval(() => {
            const currentGroup = Math.floor(currentIndex / cardsToShow);
            const newGroup = (currentGroup + 1) % totalGroups;
            currentIndex = newGroup * cardsToShow;
            moveToIndex(currentIndex);
        }, 5000);
    });
    
    // Implementar funcionalidad de deslizamiento táctil (swipe)
    if (carouselViewport) {
        let touchStartX = 0;
        let touchEndX = 0;
        let isDragging = false;
        let startOffset = 0;
        
        // Añadir indicador de deslizamiento para móviles si no existe
        if (isMobile && !document.querySelector('.swipe-indicator')) {
            const swipeIndicator = document.createElement('div');
            swipeIndicator.className = 'swipe-indicator';
            swipeIndicator.innerHTML = '<i class="fas fa-chevron-left"></i> Desliza <i class="fas fa-chevron-right"></i>';
            carouselContainer.appendChild(swipeIndicator);
            
            // Ocultar después de 5 segundos
            setTimeout(() => {
                swipeIndicator.style.opacity = '0';
                setTimeout(() => swipeIndicator.remove(), 500);
            }, 5000);
        }
        
        carouselViewport.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].clientX;
            isDragging = true;
            startOffset = parseFloat(carouselTrack.style.transform.replace('translateX(', '').replace('px)', '')) || 0;
            
            // Detener animación durante el deslizamiento
            carouselTrack.style.transition = 'none';
            
            // Detener auto-deslizamiento
            clearInterval(autoSlide);
        }, { passive: true });
        
        carouselViewport.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            
            const currentX = e.touches[0].clientX;
            const diff = currentX - touchStartX;
            
            // Aplicar el desplazamiento durante el arrastre
            carouselTrack.style.transform = `translateX(${startOffset + diff}px)`;
        }, { passive: true });
        
        carouselViewport.addEventListener('touchend', (e) => {
            if (!isDragging) return;
            
            // Restaurar transición
            carouselTrack.style.transition = 'transform 0.3s ease-in-out';
            
            touchEndX = e.changedTouches[0].clientX;
            const diff = touchEndX - touchStartX;
            
            // Determinar dirección y distancia del deslizamiento
            if (Math.abs(diff) > 50) { // Umbral mínimo para considerar un deslizamiento
                if (diff > 0) {
                    // Deslizamiento a la derecha (grupo anterior)
                    const currentGroup = Math.floor(currentIndex / cardsToShow);
                    const newGroup = Math.max(0, currentGroup - 1);
                    currentIndex = newGroup * cardsToShow;
                } else {
                    // Deslizamiento a la izquierda (grupo siguiente)
                    const currentGroup = Math.floor(currentIndex / cardsToShow);
                    const newGroup = Math.min(totalGroups - 1, currentGroup + 1);
                    currentIndex = newGroup * cardsToShow;
                }
            }
            
            // Mover al índice calculado
            moveToIndex(currentIndex);
            
            // Restablecer variables
            isDragging = false;
            
            // Reiniciar auto-deslizamiento
            autoSlide = setInterval(() => {
                const currentGroup = Math.floor(currentIndex / cardsToShow);
                const newGroup = (currentGroup + 1) % totalGroups;
                currentIndex = newGroup * cardsToShow;
                moveToIndex(currentIndex);
            }, 5000);
        }, { passive: true });
    }
    
    // Responder a cambios de tamaño
    window.addEventListener('resize', () => {
        // Recalcular dimensiones
        itemWidth = carouselItems[0].offsetWidth;
        itemGap = parseInt(window.getComputedStyle(carouselTrack).columnGap || 30);
        
        // Volver a aplicar la posición
        moveToIndex(currentIndex);
    });
    
    // Iniciar en la primera tarjeta
    moveToIndex(0);
}