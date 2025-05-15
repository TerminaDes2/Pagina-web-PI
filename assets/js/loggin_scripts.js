function mostrarFormulario() {
    document.getElementById("datos").style.display = "flex";
    document.getElementById("register").style.display = "none";
}

function mostrarFormulario2() {
    document.getElementById("datos").style.display = "none";
    document.getElementById("register").style.display = "flex";
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

  // Abrir modal
  if (openBtn && modal) {
    openBtn.addEventListener('click', function(e) {
      e.preventDefault();
      modal.style.display = 'block';
    });
  }

  // Cerrar al hacer clic en la X
  if (closeBtn && modal) {
    closeBtn.addEventListener('click', function() {
      modal.style.display = 'none';
    });
  }

  // Cerrar al hacer clic fuera del contenido
  if (modal) {
    window.addEventListener('click', function(e) {
      if (e.target === modal) {
        modal.style.display = 'none';
      }
    });
  }

  // Validación de coincidencia de contraseñas
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
    } else {
      mensajeCoincidencia.textContent = getTranslation('passwords_dont_match', 'Las contraseñas no coinciden');
      mensajeCoincidencia.className = 'password-match-message password-match-error';
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

$(document).ready(function() {
    var height = $(window).height();
    $('body').height(height);
    
    // Mostrar el nombre del archivo seleccionado con texto traducido
    $(".file-input").on("change", function() {
        var fileName = this.files[0] ? this.files[0].name : 
            (window.translations && window.translations.no_file_selected ? 
             window.translations.no_file_selected : getTranslation('no_file_selected', "Ningún archivo seleccionado"));
        $(this).siblings(".file-name").text(fileName);
    });
});