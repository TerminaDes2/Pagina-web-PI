// Seleccionamos el botón de menú y el menú lateral
const menuButton = document.getElementById('menu-button');
const sidebar = document.getElementById('sidebar');

// Seleccionamos el botón para cerrar el menú
const closeButton = document.getElementById('close-button');

// Al hacer clic en el botón de menú, se despliega el sidebar
menuButton.addEventListener('click', function() {
  sidebar.classList.toggle('active');
});

// Al hacer clic en el botón de cerrar, se remueve la clase "active"
closeButton.addEventListener('click', function() {
  sidebar.classList.remove('active');
});

const loginButton = document.getElementById('login-button');
loginButton.addEventListener('click', function() {
  window.location.href = 'registro.html';
});
