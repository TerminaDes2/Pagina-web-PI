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
});

// Carrusel ajuste hola
const carouselTrack = document.querySelector('.carousel-track');
const carouselItems = document.querySelectorAll('.carousel-item');
const carouselPrev = document.querySelector('.carousel-prev');
const carouselNext = document.querySelector('.carousel-next');
const carouselContainer = document.querySelector('.carousel-container');

let currentIndex = 0;
const itemWidth = carouselItems[0].offsetWidth;
const trackWidth = itemWidth * carouselItems.length;

carouselTrack.style.width = `${trackWidth}px`;

function moveCarousel() {
    let offset = 0;
    if (currentIndex === carouselItems.length - 1) {
        offset = 50;
    }
    carouselTrack.style.transform = `translateX(-${currentIndex * itemWidth - offset}px)`;
}

function nextSlide() {
    currentIndex = (currentIndex + 1) % carouselItems.length;
    if (currentIndex === 0) {
        moveCarousel();
    } else if (currentIndex === carouselItems.length - 1) {
        moveCarousel(-100);
        setTimeout(() => {
            moveCarousel();
        }, 500);
        } else {
            moveCarousel();
        }
    }

function prevSlide() {
    currentIndex = (currentIndex - 1 + carouselItems.length) % carouselItems.length;
    moveCarousel();
}

carouselNext.addEventListener('click', nextSlide);
carouselPrev.addEventListener('click', prevSlide);

// Automatic slide change (optional)
let carouselInterval = setInterval(nextSlide, 5000);

carouselContainer.addEventListener('mouseenter', () => {
    clearInterval(carouselInterval);
});

carouselContainer.addEventListener('mouseleave', () => {
    carouselInterval = setInterval(nextSlide, 5000);
});