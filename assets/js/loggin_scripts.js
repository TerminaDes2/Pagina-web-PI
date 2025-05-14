function mostrarFormulario() {
    document.getElementById("datos").style.display = "flex";
    document.getElementById("register").style.display = "none";
}

function mostrarFormulario2() {
    document.getElementById("datos").style.display = "none";
    document.getElementById("register").style.display = "flex";
}

document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('verifyModal');
  const openBtn = document.getElementById('openVerifyModal');
  const closeBtn = modal.querySelector('.close');

  // Abrir modal
  openBtn.addEventListener('click', function(e) {
    e.preventDefault();
    modal.style.display = 'block';
  });

  // Cerrar al hacer clic en la X
  closeBtn.addEventListener('click', function() {
    modal.style.display = 'none';
  });

  // Cerrar al hacer clic fuera del contenido
  window.addEventListener('click', function(e) {
    if (e.target === modal) {
      modal.style.display = 'none';
    }
  });
});

$(document).ready(function() {
    var height = $(window).height();
    $('body').height(height);
});