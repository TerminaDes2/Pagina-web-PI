document.addEventListener('DOMContentLoaded', function () {
    const botones = document.querySelectorAll('.toggle-btn');
    
    // Agregar animación al cargar la página
    document.querySelector('.contacto-contenedor h1').classList.add('animate__animated', 'animate__fadeInDown');
    document.querySelector('.frase').classList.add('animate__animated', 'animate__fadeIn');
    document.querySelector('.info-contacto').classList.add('animate__animated', 'animate__fadeInUp');
    
    // Agregar efectos a los elementos del formulario
    const formElements = document.querySelectorAll('.formulario input, .formulario select, .formulario textarea');
    formElements.forEach((element, index) => {
        element.addEventListener('focus', function() {
            this.classList.add('input-active');
            if (this.parentElement.querySelector('label')) {
                this.parentElement.querySelector('label').classList.add('label-active');
            }
        });
        
        element.addEventListener('blur', function() {
            if (this.value === '') {
                this.classList.remove('input-active');
                if (this.parentElement.querySelector('label')) {
                    this.parentElement.querySelector('label').classList.remove('label-active');
                }
            }
        });
        
        // Agregar animación de entrada con retraso secuencial
        setTimeout(() => {
            element.classList.add('element-visible');
        }, 100 * index);
    });

    botones.forEach(btn => {
        btn.addEventListener('click', () => {
            // Obtener el elemento de respuesta
            const respuesta = btn.nextElementSibling;
            
            // Verificar si está visible (contemplando también cuando no tiene estilo definido)
            const visible = respuesta.style.display === 'block';
            
            if (!visible) {
                // Si no es visible, aplicar animación de entrada
                respuesta.style.display = 'block';
                respuesta.style.maxHeight = '0';
                respuesta.style.opacity = '0';
                
                // Forzar un reflow para que la animación funcione
                respuesta.offsetHeight;
                
                // Animar la apertura
                respuesta.style.maxHeight = respuesta.scrollHeight + 'px';
                respuesta.style.opacity = '1';
                
                // Cambiar el icono
                if (btn.textContent.includes('⮟')) {
                    btn.textContent = btn.textContent.replace('⮟', '⮝');
                }
                
                // Efecto visual en el botón
                btn.classList.add('active-btn');
            } else {
                // Si es visible, animar el cierre
                respuesta.style.maxHeight = '0';
                respuesta.style.opacity = '0';
                
                // Esperar a que termine la animación para ocultarlo
                setTimeout(() => {
                    respuesta.style.display = 'none';
                }, 300); // Tiempo igual a la duración de la transición
                
                // Cambiar el icono
                if (btn.textContent.includes('⮝')) {
                    btn.textContent = btn.textContent.replace('⮝', '⮟');
                }
                
                // Quitar efecto visual del botón
                btn.classList.remove('active-btn');
            }
        });
    });

    // Agregar funcionalidad para el editor del formulario
    document.querySelectorAll('.editor button').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Evitar envío del formulario
            
            // Agregar efecto visual al hacer clic
            this.classList.add('button-active');
            setTimeout(() => {
                this.classList.remove('button-active');
            }, 300);
        });
    });
    
    // Agregar animación al botón de enviar
    const btnEnviar = document.querySelector('.btn-enviar');
    if (btnEnviar) {
        btnEnviar.addEventListener('mouseenter', function() {
            this.classList.add('btn-pulse');
        });
        
        btnEnviar.addEventListener('mouseleave', function() {
            this.classList.remove('btn-pulse');
        });
    }
    
    // Animación al hacer scroll para elementos con data-animation
    const animatedElements = document.querySelectorAll('[data-animation]');
    
    function checkScroll() {
        const triggerBottom = window.innerHeight * 0.8;
        
        animatedElements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            
            if (elementTop < triggerBottom) {
                element.classList.add('element-animated');
            }
        });
    }
    
    // Iniciar comprobación al cargar
    checkScroll();
    
    // Comprobar en cada scroll
    window.addEventListener('scroll', checkScroll);
});

// Agregar estilos CSS dinámicos para las animaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .input-active {
        border-color: #719743 !important;
        box-shadow: 0 0 0 3px rgba(113, 151, 67, 0.2) !important;
        transform: translateY(-2px);
    }
    
    .label-active {
        color: #187424 !important;
        transform: translateY(-2px);
    }
    
    .element-visible {
        animation: slideIn 0.5s ease forwards;
    }
    
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .active-btn {
        background-color: #187424 !important;
        box-shadow: 0 6px 15px rgba(113, 151, 67, 0.5) !important;
        transform: translateY(-2px);
    }
    
    .respuesta {
        transition: max-height 0.3s ease, opacity 0.3s ease;
    }
    
    .button-active {
        transform: scale(0.95);
        background-color: #e0e0e0 !important;
    }
    
    .btn-pulse {
        animation: pulse 0.6s infinite;
    }
    
    [data-animation] {
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.6s ease;
    }
    
    .element-animated {
        opacity: 1;
        transform: translateY(0);
    }
`;

document.head.appendChild(style);
