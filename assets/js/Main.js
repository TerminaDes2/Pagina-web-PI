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
    let cardsToShow = 3; // Número de tarjetas a mostrar a la vez
    let totalGroups;
    
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
    
    // Detectar si es dispositivo móvil
    const isMobile = window.innerWidth <= 768;
    cardsToShow = isMobile ? 1 : 3; // En móvil muestra 1, en desktop 3
    totalGroups = Math.ceil(carouselItems.length / cardsToShow);
    
    // Función para mover el carrusel
    function moveToIndex(index) {
        currentIndex = index;
        
        // Si es dispositivo móvil, usar un cálculo más preciso
        if (isMobile) {
            // Obtener el ancho real de la tarjeta incluyendo el margen
            const mobileItemWidth = carouselItems[0].offsetWidth;
            const mobileItemGap = parseInt(window.getComputedStyle(carouselTrack).columnGap || 15);
            const totalWidth = mobileItemWidth + mobileItemGap;
            
            let offset = currentIndex * totalWidth;
            
            // Aseguramos que la última tarjeta se vea completa
            if (index === carouselItems.length - 1) {
                const viewport = carouselTrack.parentElement;
                const viewportWidth = viewport.offsetWidth;
                const contentWidth = carouselItems.length * totalWidth;
                
                // Ajustar para que la última tarjeta esté completamente visible
                offset = contentWidth - viewportWidth;
                offset = Math.max(0, offset);
            }
            
            // Aplicar el desplazamiento calculado
            carouselTrack.style.transform = `translateX(-${offset}px)`;
            
            // Actualizar indicadores
            const indicatorsContainer = document.querySelector('.carousel-indicators');
            const currentIndicators = indicatorsContainer.querySelectorAll('.carousel-indicator');
            currentIndicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === currentIndex);
            });
            return;
        }
        
        // Si hay 5 o más tarjetas, avanzar como si hubiera grupos de 3
        if (carouselItems.length >= 5) {
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
                offset = contentWidth - viewportWidth;
                offset = Math.max(0, offset);
            }
            
            // Aplicar el desplazamiento
            carouselTrack.style.transform = `translateX(-${offset}px)`;
        } else {
            // Comportamiento original para menos de 5 tarjetas
            let offset = currentIndex * itemFullWidth;
            
            // Si es la última tarjeta o posterior, ajustar para eliminar espacio vacío
            if (index >= carouselItems.length - 1) {
                const viewport = carouselTrack.parentElement;
                const viewportWidth = viewport.offsetWidth;
                
                // Calcular la posición máxima para que la última tarjeta quede justo al borde derecho
                const maxOffset = ((carouselItems.length) * itemFullWidth) - viewportWidth;
                
                // Aplicar el desplazamiento máximo
                offset = Math.max(0, maxOffset);
            }
            
            // Aplicar el desplazamiento
            carouselTrack.style.transform = `translateX(-${offset}px)`;
        }
        
        // Actualizar indicadores - CORREGIDO
        const indicatorsContainer = document.querySelector('.carousel-indicators');
        const currentIndicators = indicatorsContainer.querySelectorAll('.carousel-indicator');
        
        if (carouselItems.length >= 5 && !isMobile) {
            // Para 5+ tarjetas en desktop, activar indicador según el grupo
            const currentGroup = Math.floor(index / cardsToShow);
            currentIndicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', parseInt(indicator.dataset.group) === currentGroup);
            });
        } else {
            // Comportamiento normal para menos de 5 tarjetas o en móvil
            currentIndicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === currentIndex);
            });
        }
    }
    
    // Navegación con botones
    if (buttonLeft) {
        buttonLeft.addEventListener('click', (e) => {
            e.stopPropagation();
            
            if (carouselItems.length >= 5) {
                // Para 5+ tarjetas, retroceder un grupo completo
                const currentGroup = Math.floor(currentIndex / cardsToShow);
                const newGroup = (currentGroup - 1 + totalGroups) % totalGroups;
                currentIndex = newGroup * cardsToShow;
            } else {
                // Comportamiento normal
                currentIndex = (currentIndex - 1 + carouselItems.length) % carouselItems.length;
            }
            
            moveToIndex(currentIndex);
        });
    }
    
    if (buttonRight) {
        buttonRight.addEventListener('click', (e) => {
            e.stopPropagation();
            
            if (carouselItems.length >= 5) {
                // Para 5+ tarjetas, avanzar un grupo completo
                const currentGroup = Math.floor(currentIndex / cardsToShow);
                const newGroup = (currentGroup + 1) % totalGroups;
                currentIndex = newGroup * cardsToShow;
            } else {
                // Comportamiento normal
                currentIndex = (currentIndex + 1) % carouselItems.length;
            }
            
            moveToIndex(currentIndex);
        });
    }
    
    // Navegación con indicadores - REHECHO COMPLETAMENTE
    const setupIndicators = () => {
        const indicatorsContainer = document.querySelector('.carousel-indicators');
        if (!indicatorsContainer) return;
        
        // Limpiar indicadores existentes
        indicatorsContainer.innerHTML = '';
        
        if (carouselItems.length >= 5 && !isMobile) {
            // Crear indicadores para grupos (desktop con 5+ tarjetas)
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
        } else {
            // Crear indicadores individuales (móvil o menos de 5 tarjetas)
            for (let i = 0; i < carouselItems.length; i++) {
                const indicator = document.createElement('span');
                indicator.classList.add('carousel-indicator');
                indicator.dataset.index = i;
                if (i === 0) indicator.classList.add('active');
                
                indicator.addEventListener('click', (e) => {
                    e.stopPropagation();
                    currentIndex = i;
                    moveToIndex(currentIndex);
                });
                
                indicatorsContainer.appendChild(indicator);
            }
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
    
    // Auto-deslizamiento (opcional)
    let autoSlide = setInterval(() => {
        if (carouselItems.length >= 5) {
            // Para 5+ tarjetas, avanzar un grupo completo
            const currentGroup = Math.floor(currentIndex / cardsToShow);
            const newGroup = (currentGroup + 1) % totalGroups;
            currentIndex = newGroup * cardsToShow;
        } else {
            // Comportamiento normal
            currentIndex = (currentIndex + 1) % carouselItems.length;
        }
        moveToIndex(currentIndex);
    }, 5000);
    
    // Pausar auto-deslizamiento al pasar el mouse
    carouselTrack.addEventListener('mouseenter', () => {
        clearInterval(autoSlide);
    });
    
    carouselTrack.addEventListener('mouseleave', () => {
        autoSlide = setInterval(() => {
            if (carouselItems.length >= 5) {
                // Para 5+ tarjetas, avanzar un grupo completo
                const currentGroup = Math.floor(currentIndex / cardsToShow);
                const newGroup = (currentGroup + 1) % totalGroups;
                currentIndex = newGroup * cardsToShow;
            } else {
                // Comportamiento normal
                currentIndex = (currentIndex + 1) % carouselItems.length;
            }
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
                    // Deslizamiento a la derecha (ir a la tarjeta anterior)
                    if (isMobile) {
                        currentIndex = Math.max(0, currentIndex - 1);
                    } else if (carouselItems.length >= 5) {
                        const currentGroup = Math.floor(currentIndex / cardsToShow);
                        const newGroup = Math.max(0, currentGroup - 1);
                        currentIndex = newGroup * cardsToShow;
                    } else {
                        currentIndex = Math.max(0, currentIndex - 1);
                    }
                } else {
                    // Deslizamiento a la izquierda (ir a la siguiente tarjeta)
                    if (isMobile) {
                        currentIndex = Math.min(carouselItems.length - 1, currentIndex + 1);
                    } else if (carouselItems.length >= 5) {
                        const currentGroup = Math.floor(currentIndex / cardsToShow);
                        const newGroup = Math.min(totalGroups - 1, currentGroup + 1);
                        currentIndex = newGroup * cardsToShow;
                    } else {
                        currentIndex = Math.min(carouselItems.length - 1, currentIndex + 1);
                    }
                }
            }
            
            // Mover al índice calculado
            moveToIndex(currentIndex);
            
            // Restablecer variables
            isDragging = false;
            
            // Reiniciar auto-deslizamiento
            autoSlide = setInterval(() => {
                if (carouselItems.length >= 5) {
                    // Para 5+ tarjetas, avanzar un grupo completo
                    const currentGroup = Math.floor(currentIndex / cardsToShow);
                    const newGroup = (currentGroup + 1) % totalGroups;
                    currentIndex = newGroup * cardsToShow;
                } else {
                    // Comportamiento normal
                    currentIndex = (currentIndex + 1) % carouselItems.length;
                }
                moveToIndex(currentIndex);
            }, 5000);
        }, { passive: true });
    }
    
    // Responder a cambios de tamaño - ACTUALIZADO
    window.addEventListener('resize', () => {
        // Actualizar detección de móvil
        const wasMobile = isMobile;
        const newIsMobile = window.innerWidth <= 768;
        
        // Si cambió el estado móvil/desktop, reconfigurar los indicadores
        if (wasMobile !== newIsMobile) {
            // Actualizar cardsToShow
            cardsToShow = newIsMobile ? 1 : 3;
            // Recalcular total de grupos
            totalGroups = Math.ceil(carouselItems.length / cardsToShow);
            // Recrear indicadores
            setupIndicators();
            // Reiniciar carrusel
            currentIndex = 0;
            moveToIndex(0);
            return;
        }
        
        const newItemWidth = carouselItems[0].offsetWidth;
        const newGap = parseInt(window.getComputedStyle(carouselTrack).columnGap || 30);
        
        // Actualizar las variables para cálculos correctos
        itemWidth = newItemWidth;
        itemGap = newGap;
        
        // Recalcular total de grupos
        totalGroups = Math.ceil(carouselItems.length / cardsToShow);
        
        // Volver a aplicar la posición
        moveToIndex(currentIndex);
    });
    
    // Iniciar en la primera tarjeta
    moveToIndex(0);
}