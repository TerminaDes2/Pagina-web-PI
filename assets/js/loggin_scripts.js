function mostrarFormulario() {
    document.getElementById("register").style.display = "none";
    
    // Ocultar primero el formulario actual con animación
    $("#datos").fadeIn(500);
    $("#register").fadeOut(500);
}

function mostrarFormulario2() {
    document.getElementById("datos").style.display = "none";
    
    // Mostrar el formulario de registro con animación
    $("#register").fadeIn(500);
    $("#datos").fadeOut(500);
}

// Función para obtener texto traducido o mostrar texto original si no hay traducción
function getTranslation(key, defaultText) {
  return window.translations && window.translations[key] ? window.translations[key] : defaultText;
}

// Función para alternar la visibilidad de la contraseña
function togglePassword(inputId) {
  const input = document.getElementById(inputId);
  const icon = event.currentTarget.querySelector('i');
  
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
}

document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('verifyModal');
  const openBtn = document.getElementById('openVerifyModal');
  const closeBtn = modal ? modal.querySelector('.close') : null;

  // Añadir efectos sutiles a los inputs al enfocarlos
  const inputs = document.querySelectorAll('input:not([type="checkbox"])');
  inputs.forEach(input => {
    input.addEventListener('focus', function() {
      this.parentNode.querySelector('i')?.classList.add('pulse');
    });
    
    input.addEventListener('blur', function() {
      this.parentNode.querySelector('i')?.classList.remove('pulse');
    });
  });

  // Validar los checkbox de términos y condiciones
  const formLeft = document.getElementById('form-left');
  if (formLeft) {
    formLeft.addEventListener('submit', function(e) {
      const privacyCheckbox = document.getElementById('accept-privacy');
      const termsCheckbox = document.getElementById('accept-terms');
      
      if (privacyCheckbox && termsCheckbox) {
        if (!privacyCheckbox.checked || !termsCheckbox.checked) {
          e.preventDefault();
          Swal.fire({
            title: getTranslation('attention', 'Atención'),
            text: getTranslation('privacy_terms_required', 'Debes aceptar la Política de Privacidad y los Términos de Uso para continuar.'),
            icon: 'warning',
            confirmButtonText: getTranslation('understood', 'Entendido')
          });
          return false;
        }
      }

      // Validar coincidencia de contraseñas
      const contraInput = document.getElementById('contra');
      const confirmaInput = document.getElementById('confirma_contra');
      if (contraInput && confirmaInput) {
        if (contraInput.value !== confirmaInput.value) {
          e.preventDefault();
          Swal.fire({
            title: getTranslation('error', 'Error'),
            text: getTranslation('passwords_dont_match', 'Las contraseñas no coinciden'),
            icon: 'error',
            confirmButtonText: getTranslation('understood', 'Entendido')
          });
          return false;
        }
      }
    });
  }

  // Abrir modal con animación
  if (openBtn && modal) {
    openBtn.addEventListener('click', function(e) {
      e.preventDefault();
      modal.style.display = 'block';
      setTimeout(() => {
        modal.classList.add('active');
      }, 10);
    });
  }

  // Cerrar al hacer clic en la X
  if (closeBtn && modal) {
    closeBtn.addEventListener('click', function() {
      modal.classList.remove('active');
      setTimeout(() => {
        modal.style.display = 'none';
      }, 400);
    });
  }

  // Cerrar al hacer clic fuera del contenido
  if (modal) {
    window.addEventListener('click', function(e) {
      if (e.target === modal) {
        modal.classList.remove('active');
        setTimeout(() => {
          modal.style.display = 'none';
        }, 400);
      }
    });
  }

  // Validación de coincidencia de contraseñas con efectos visuales
  const contraInput = document.getElementById('contra');
  const confirmaInput = document.getElementById('confirma_contra');
  const mensajeCoincidencia = document.getElementById('mensaje-coincidencia');
  
  function validarCoincidencia() {
    if (!confirmaInput || !mensajeCoincidencia) return;
    
    if (!confirmaInput.value) {
      mensajeCoincidencia.textContent = '';
      mensajeCoincidencia.className = 'password-match-message';
      return;
    }
    
    if (contraInput.value === confirmaInput.value) {
      mensajeCoincidencia.textContent = getTranslation('passwords_match', '¡Las contraseñas coinciden!');
      mensajeCoincidencia.className = 'password-match-message password-match-success';
      // Añadir animación sutil
      mensajeCoincidencia.style.opacity = '0';
      setTimeout(() => {
        mensajeCoincidencia.style.opacity = '1';
      }, 10);
    } else {
      mensajeCoincidencia.textContent = getTranslation('passwords_dont_match', 'Las contraseñas no coinciden');
      mensajeCoincidencia.className = 'password-match-message password-match-error';
      // Añadir animación sutil
      mensajeCoincidencia.style.opacity = '0';
      setTimeout(() => {
        mensajeCoincidencia.style.opacity = '1';
      }, 10);
    }
  }
  
  if (contraInput && confirmaInput) {
    contraInput.addEventListener('input', validarCoincidencia);
    confirmaInput.addEventListener('input', validarCoincidencia);
    
    // Ejecutar validación inicial si hay valores
    if (contraInput.value || confirmaInput.value) {
      validarCoincidencia();
    }
  }
});

// Añadir efectos al cargar la página
$(document).ready(function() {
    var height = $(window).height();
    $('body').height(height);
    
    // Animar la aparición de los formularios
    $('.card').css({opacity: 0, transform: 'translateY(20px)'}).animate({
      opacity: 1,
      transform: 'translateY(0)'
    }, 600);
    
    // Mostrar el nombre del archivo seleccionado con texto traducido
    $(".file-input").on("change", function() {
        var fileName = this.files[0] ? this.files[0].name : 
            (window.translations && window.translations.no_file_selected ? 
             window.translations.no_file_selected : getTranslation('no_file_selected', "Ningún archivo seleccionado"));
        $(this).siblings(".file-name").text(fileName);
        
        // Añadir un efecto visual cuando se selecciona un archivo
        if (this.files[0]) {
            $(this).siblings(".file-name").addClass("file-selected");
            setTimeout(() => {
                $(this).siblings(".file-name").removeClass("file-selected");
            }, 1500);
        }
    });
    
    // Animar los iconos de los inputs
    $(".input-container i").addClass("animated-icon");
});

// Añadir estilos dinámicos
const style = document.createElement('style');
style.textContent = `
  @keyframes pulse {
    0% { transform: translateY(-90%) scale(1); }
    50% { transform: translateY(-90%) scale(1.15); }
    100% { transform: translateY(-90%) scale(1); }
  }
  
  .pulse {
    animation: pulse 0.6s ease-in-out;
  }
  
  .animated-icon {
    transition: all 0.3s ease;
  }
  
  .file-selected {
    color: #187424 !important;
    font-weight: 600;
  }
  
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .input-container, .custom-file-upload, .terms-checkbox, .btn, .btin {
    animation: fadeIn 0.5s ease-out forwards;
  }
  
  .input-container:nth-child(1) { animation-delay: 0.1s; }
  .input-container:nth-child(2) { animation-delay: 0.2s; }
  .input-container:nth-child(3) { animation-delay: 0.3s; }
  .input-container:nth-child(4) { animation-delay: 0.4s; }
  .input-container:nth-child(5) { animation-delay: 0.5s; }
  .input-container:nth-child(6) { animation-delay: 0.6s; }
  .custom-file-upload { animation-delay: 0.7s; }
  .terms-checkbox:nth-of-type(1) { animation-delay: 0.8s; }
  .terms-checkbox:nth-of-type(2) { animation-delay: 0.9s; }
  .btn { animation-delay: 1s; }
  .btin { animation-delay: 1.1s; }
`;
document.head.appendChild(style);