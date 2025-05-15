document.addEventListener('DOMContentLoaded', function () {
    const botones = document.querySelectorAll('.toggle-btn');
    
    // Agregar animación al cargar la página
    document.querySelector('.contacto-contenedor h1').classList.add('animate__animated', 'animate__fadeInDown');
    document.querySelector('.frase').classList.add('animate__animated', 'animate__fadeIn');
    document.querySelector('.info-contacto').classList.add('animate__animated', 'animate__fadeInUp');
    
    // Efectos visuales adicionales para la página
    const decorElements = `
        <div class="decoracion-circulo c1"></div>
        <div class="decoracion-circulo c2"></div>
        <div class="decoracion-circulo c3"></div>
        <div class="decoracion-linea l1"></div>
        <div class="decoracion-linea l2"></div>
    `;
    document.querySelector('.contacto-contenedor').insertAdjacentHTML('afterbegin', decorElements);
    
    // Mejorar los iconos en la información de contacto
    const infoContacto = document.querySelector('.info-contacto div:first-child');
    if (infoContacto) {
        const direccionP = infoContacto.querySelector('p:nth-child(2)');
        const emailP = infoContacto.querySelector('p:nth-child(3)');
        const horarioP = infoContacto.querySelector('p:nth-child(4)');
        
        if (direccionP) {
            const strongText = direccionP.querySelector('strong').outerHTML;
            direccionP.innerHTML = `<span class="info-icon"><i class="fas fa-map-marker-alt"></i></span> ${strongText} ${direccionP.innerHTML.split('</strong>')[1]}`;
        }
        
        if (emailP) {
            const strongText = emailP.querySelector('strong').outerHTML;
            emailP.innerHTML = `<span class="info-icon"><i class="fas fa-envelope"></i></span> ${strongText} ${emailP.innerHTML.split('</strong>')[1]}`;
        }
        
        if (horarioP) {
            const strongText = horarioP.querySelector('strong').outerHTML;
            horarioP.innerHTML = `<span class="info-icon"><i class="fas fa-clock"></i></span> ${strongText} ${horarioP.innerHTML.split('</strong>')[1]}`;
        }
    }
    
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

    // Mejorar la interactividad de los botones de preguntas frecuentes
    botones.forEach((btn, index) => {
        // Añadir icono visual al botón
        const btnText = btn.textContent;
        btn.innerHTML = `<span class="btn-text">${btnText.replace('⮟', '').replace('⮝', '')}</span><span class="btn-icon">⮟</span>`;
        
        // Añadir animación de entrada con retraso
        setTimeout(() => {
            btn.classList.add('animate__animated', 'animate__fadeInLeft');
            btn.style.visibility = 'visible';
        }, 200 + (index * 100));
        
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
                btn.querySelector('.btn-icon').textContent = '⮝';
                
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
                btn.querySelector('.btn-icon').textContent = '⮟';
                
                // Quitar efecto visual del botón
                btn.classList.remove('active-btn');
            }
        });
    });

    // Agregar funcionalidad para el editor del formulario
    document.querySelectorAll('.editor button').forEach(button => {
        // Añadir icono basado en la función del botón
        if (button.textContent === 'B') {
            button.innerHTML = '<i class="fas fa-bold"></i>';
        } else if (button.textContent === 'U') {
            button.innerHTML = '<i class="fas fa-underline"></i>';
        } else if (button.textContent.includes('zquierda')) {
            button.innerHTML = '<i class="fas fa-align-left"></i>';
        } else if (button.textContent.includes('entro')) {
            button.innerHTML = '<i class="fas fa-align-center"></i>';
        } else if (button.textContent.includes('erecha')) {
            button.innerHTML = '<i class="fas fa-align-right"></i>';
        } else if (button.textContent.includes('ustificar')) {
            button.innerHTML = '<i class="fas fa-align-justify"></i>';
        }
        
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
        btnEnviar.innerHTML = `<span>Enviar</span><i class="fas fa-paper-plane"></i>`;
        
        btnEnviar.addEventListener('mouseenter', function() {
            // Aplicamos la clase de una manera más segura
            // La animación de pulso ahora sólo afectará al icono, no al botón completo
            this.querySelector('i').classList.add('icon-pulse');
        });
        
        btnEnviar.addEventListener('mouseleave', function() {
            this.querySelector('i').classList.remove('icon-pulse');
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

    // Crear el efecto de mapa interactivo
    const mapa = document.querySelector('.mapa');
    if (mapa) {
        mapa.addEventListener('mouseenter', function() {
            this.classList.add('mapa-hover');
        });
        
        mapa.addEventListener('mouseleave', function() {
            this.classList.remove('mapa-hover');
        });
    }
    
    // Efectos visuales adicionales para el formulario
    const formulario = document.querySelector('.formulario');
    if (formulario) {
        const campos = formulario.querySelectorAll('input, select, #mensaje');
        
        // Crear efecto de campo activo más visual
        campos.forEach(campo => {
            // Crear un contenedor para el campo si no está dentro de un div específico
            if (!campo.parentElement.classList.contains('campo-wrapper') && campo.id !== 'mensaje') {
                const wrapper = document.createElement('div');
                wrapper.className = 'campo-wrapper';
                campo.parentNode.insertBefore(wrapper, campo);
                wrapper.appendChild(campo);
            }
            
            // Efecto de onda al hacer clic
            campo.addEventListener('click', function(e) {
                // Crear el elemento de efecto onda
                const ripple = document.createElement('span');
                ripple.className = 'input-ripple';
                
                // Posicionar en el punto de clic
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                
                // Añadir al campo
                if (this.parentElement.classList.contains('campo-wrapper')) {
                    this.parentElement.appendChild(ripple);
                } else {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'campo-wrapper';
                    this.parentNode.insertBefore(wrapper, this);
                    wrapper.appendChild(this);
                    wrapper.appendChild(ripple);
                }
                
                // Eliminar después de la animación
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });
        
        // Añadir comprobación visual de campos al enviar
        formulario.querySelector('form').addEventListener('submit', function(e) {
            let camposIncompletos = false;
            
            campos.forEach(campo => {
                if (campo.required && !campo.value.trim()) {
                    camposIncompletos = true;
                    campo.classList.add('campo-incompleto');
                    
                    // Sacudir el campo para indicar error
                    campo.classList.add('shake-animation');
                    setTimeout(() => {
                        campo.classList.remove('shake-animation');
                    }, 600);
                } else {
                    campo.classList.remove('campo-incompleto');
                }
            });
            
            if (camposIncompletos) {
                e.preventDefault();
                
                // Mostrar mensaje de error suavemente
                let mensajeError = document.querySelector('.mensaje-error');
                if (!mensajeError) {
                    mensajeError = document.createElement('div');
                    mensajeError.className = 'mensaje-error';
                    mensajeError.innerHTML = '<i class="fas fa-exclamation-circle"></i> Por favor, completa todos los campos requeridos.';
                    formulario.insertBefore(mensajeError, formulario.querySelector('.btn-enviar').parentNode);
                }
                
                mensajeError.style.opacity = '0';
                mensajeError.style.display = 'block';
                
                setTimeout(() => {
                    mensajeError.style.opacity = '1';
                }, 10);
            }
        });
    }
    
    // Mejorar interactividad del mapa
    const mapaElemento = document.querySelector('.mapa');
    if (mapaElemento) {
        // Añadir capa interactiva
        const overlay = document.createElement('div');
        overlay.className = 'mapa-overlay';
        mapaElemento.appendChild(overlay);
        
        // Añadir marcador pulsante
        const marker = document.createElement('div');
        marker.className = 'mapa-marker';
        marker.innerHTML = '<i class="fas fa-map-marker-alt"></i>';
        mapaElemento.appendChild(marker);
        
        // Añadir tooltip
        const tooltip = document.createElement('div');
        tooltip.className = 'mapa-tooltip';
        tooltip.textContent = 'Estamos aquí';
        marker.appendChild(tooltip);
        
        mapaElemento.addEventListener('mouseenter', function() {
            this.classList.add('mapa-hover');
            marker.classList.add('marker-active');
        });
        
        mapaElemento.addEventListener('mouseleave', function() {
            this.classList.remove('mapa-hover');
            marker.classList.remove('marker-active');
        });
    }
    
    // Mejorar las preguntas frecuentes
    document.querySelectorAll('.respuesta').forEach(respuesta => {
        respuesta.addEventListener('transitionstart', function() {
            if (this.style.opacity === '1') {
                this.classList.add('active');
            }
        });
        
        respuesta.addEventListener('transitionend', function() {
            if (this.style.opacity === '0') {
                this.classList.remove('active');
            }
        });
    });

    // Añadir estilos adicionales dinámicamente
    const styleAdicional = document.createElement('style');
    styleAdicional.textContent = `
        .campo-wrapper {
            position: relative;
            overflow: hidden;
            width: 100%;
        }
        
        .input-ripple {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: rgba(113, 151, 67, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes ripple {
            to {
                transform: scale(40);
                opacity: 0;
            }
        }
        
        .shake-animation {
            animation: shake 0.6s cubic-bezier(.36,.07,.19,.97) both;
        }
        
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
        
        .campo-incompleto {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25) !important;
        }
        
        .mensaje-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            border-radius: 8px;
            margin: 15px 0;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            text-align: center;
            font-weight: 500;
        }
        
        .mensaje-error i {
            margin-right: 8px;
            animation: pulse 2s infinite;
        }
        
        .mapa-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(113,151,67,0.1) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 2;
        }
        
        .mapa-hover .mapa-overlay {
            opacity: 1;
        }
        
        .mapa-marker {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 3;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mapa-marker i {
            color: #dc3545;
            font-size: 30px;
            filter: drop-shadow(0 2px 5px rgba(0,0,0,0.5));
            transition: all 0.3s ease;
            transform: translateY(0);
        }
        
        .marker-active i {
            animation: marker-bounce 1.5s infinite;
        }
        
        @keyframes marker-bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .mapa-tooltip {
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%) scale(0);
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            opacity: 0;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .mapa-tooltip::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid rgba(0,0,0,0.8);
        }
        
        .marker-active .mapa-tooltip {
            transform: translateX(-50%) scale(1);
            opacity: 1;
            top: -35px;
        }
    `;
    
    document.head.appendChild(styleAdicional);
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
    
    /* Nuevos estilos */
    .decoracion-circulo {
        position: absolute;
        border-radius: 50%;
        z-index: -1;
        filter: blur(20px);
        opacity: 0.4;
        animation: float 20s infinite ease-in-out;
    }
    
    .c1 {
        width: 150px;
        height: 150px;
        background: radial-gradient(circle, rgba(113,151,67,0.5) 0%, rgba(113,151,67,0) 70%);
        top: 10%;
        left: 5%;
    }
    
    .c2 {
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(24,116,36,0.4) 0%, rgba(24,116,36,0) 70%);
        bottom: 20%;
        right: 5%;
        animation-delay: 5s;
    }
    
    .c3 {
        width: 120px;
        height: 120px;
        background: radial-gradient(circle, rgba(255,255,255,0.7) 0%, rgba(255,255,255,0) 70%);
        bottom: 40%;
        left: 15%;
        animation-delay: 10s;
    }
    
    .decoracion-linea {
        position: absolute;
        z-index: -1;
        opacity: 0.15;
    }
    
    .l1 {
        width: 150px;
        height: 3px;
        background: linear-gradient(90deg, rgba(113,151,67,0) 0%, rgba(113,151,67,1) 50%, rgba(113,151,67,0) 100%);
        top: 30%;
        right: 10%;
        transform: rotate(45deg);
    }
    
    .l2 {
        width: 200px;
        height: 2px;
        background: linear-gradient(90deg, rgba(24,116,36,0) 0%, rgba(24,116,36,1) 50%, rgba(24,116,36,0) 100%);
        bottom: 15%;
        left: 20%;
        transform: rotate(-30deg);
    }
    
    @keyframes float {
        0% { transform: translate(0, 0) rotate(0deg); }
        33% { transform: translate(15px, 15px) rotate(5deg); }
        66% { transform: translate(-15px, 10px) rotate(-5deg); }
        100% { transform: translate(0, 0) rotate(0deg); }
    }
    
    .toggle-btn {
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: center;
        visibility: hidden;
    }
    
    .btn-icon {
        font-size: 1.2em;
        transition: transform 0.3s ease;
    }
    
    .active-btn .btn-icon {
        transform: rotate(180deg);
    }
    
    .info-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        background-color: rgba(113, 151, 67, 0.15);
        border-radius: 50%;
        margin-right: 10px;
        color: #187424;
        transition: all 0.3s ease;
    }
    
    p:hover .info-icon {
        background-color: #187424;
        color: white;
        transform: scale(1.1) rotate(10deg);
    }
    
    .btn-enviar {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .btn-enviar i {
        transition: all 0.3s ease;
    }
    
    .btn-enviar:hover i {
        transform: translateX(5px);
    }
    
    .mapa {
        position: relative;
        transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
    }
    
    .mapa-hover {
        transform: scale(1.03) perspective(1000px) rotateX(2deg) !important;
        box-shadow: 0 15px 30px rgba(0,0,0,0.2) !important;
    }
    
    .mapa::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        border-radius: 12px;
        box-shadow: inset 0 0 0 2px rgba(113, 151, 67, 0.3);
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }
    
    .mapa-hover::after {
        opacity: 1;
    }
    
    .editor button {
        min-width: 40px;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    
    .editor button:hover {
        background: #719743;
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(113, 151, 67, 0.3);
    }
    
    /* Reemplazamos btn-pulse con icon-pulse que solo afecta al icono */
    .icon-pulse {
        animation: iconPulse 0.6s infinite;
    }
    
    @keyframes iconPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
`;

document.head.appendChild(style);
