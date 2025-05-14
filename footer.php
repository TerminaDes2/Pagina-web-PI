<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Footer</title>
  <style>
    .footer {
      background-color: #2e7d32;
      color: white;
      font-family: 'Segoe UI', sans-serif;
      padding: 30px 20px 10px;
      font-size: 14px;
    }

    .footer-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 20px;
      max-width: 1200px;
      margin: auto;
    }

    .footer-section {
      flex: 1 1 200px;
      min-width: 180px;
    }

    .footer-section h4 {
      margin-bottom: 10px;
      font-size: 16px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.3);
      padding-bottom: 5px;
    }

    .footer-section ul {
      list-style: none;
      padding: 0;
    }

    .footer-section ul li {
      margin-bottom: 6px;
    }

    .footer-section ul li a {
      color: white;
      text-decoration: none;
      transition: color 0.3s;
    }

    .footer-section ul li a:hover {
      color: #c8e6c9;
    }

    .footer-section input[type="email"] {
      width: 100%;
      padding: 6px;
      border: none;
      border-radius: 4px;
      margin-top: 5px;
    }

    .social-icons a img {
      width: 24px;
      margin-right: 10px;
      vertical-align: middle;
    }

    .footer-bottom {
      text-align: center;
      margin-top: 20px;
      font-size: 12px;
      border-top: 1px solid rgba(255, 255, 255, 0.2);
      padding-top: 10px;
    }

    .footer-bottom a {
      color: #c8e6c9;
      text-decoration: none;
      margin: 0 5px;
    }
  </style>
</head>
<body>
  <footer class="footer">
    <div class="footer-container">
      <div class="footer-section">
        <h4>Navegación</h4>
        <ul>
          <li><a href="#">Inicio</a></li>
          <li><a href="#">Acerca de</a></li>
          <li><a href="#">Contacto</a></li>
          <li><a href="#">FAQ</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h4>Categorías</h4>
        <ul>
          <li><a href="#">Derechos Laborales</a></li>
          <li><a href="#">Inclusión Laboral</a></li>
          <li><a href="#">Trabajo Global</a></li>
          <li><a href="#">Indicadores Económicos</a></li>
        </ul>
      </div>
      <div class="footer-section">
        <h4>Suscríbete</h4>
        <p>Recibe noticias y actualizaciones.</p>
        <input type="email" placeholder="Tu correo electrónico">
      </div>
      <div class="footer-section">
        <h4>Conéctate</h4>
        <div class="social-icons">
          <a href="#"><img src="icon-facebook.svg" alt="Facebook"></a>
          <a href="#"><img src="icon-twitter.svg" alt="Twitter"></a>
          <a href="#"><img src="icon-instagram.svg" alt="Instagram"></a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 POALCE. Todos los derechos reservados. | <a href="#">Términos</a> | <a href="#">Privacidad</a> | <a href="#">Cookies</a></p>
    </div>
  </footer>
</body>
</html>
