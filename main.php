<?php
// Conexión a la base de datos
$host       = "localhost";
$usuario    = "root";
$contrasena = "administrador";
$bd         = "blog";

$conn = new mysqli($host, $usuario, $contrasena, $bd);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}

// Consulta para obtener la publicación más reciente (banner) usando id_entrada DESC
$sqlBanner = "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, i.imagen 
              FROM entradas e 
              LEFT JOIN imagenes i ON e.id_imagen = i.id_imagen 
              ORDER BY e.id_entrada DESC 
              LIMIT 1";
$resultBanner = $conn->query($sqlBanner);
$banner = ($resultBanner->num_rows > 0) ? $resultBanner->fetch_assoc() : null;

// Consulta para obtener las demás publicaciones, excluyendo la del banner, ordenadas de la más reciente a la más antigua
if ($banner) {
    $sqlPosts = "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, i.imagen 
                 FROM entradas e 
                 LEFT JOIN imagenes i ON e.id_imagen = i.id_imagen 
                 WHERE e.id_entrada <> " . $banner['id_entrada'] . " 
                 ORDER BY e.id_entrada DESC";
} else {
    $sqlPosts = "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, i.imagen 
                 FROM entradas e 
                 LEFT JOIN imagenes i ON e.id_imagen = i.id_imagen 
                 ORDER BY e.id_imagen DESC";
}
$resultPosts = $conn->query($sqlPosts);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Voces del Proceso</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="estilos/main.css">
  <script src="Main.js" defer></script>
</head>
<body>
  <header class="header-top">
    <div class="logo">
      <h1><a href="Main.php">Voces del Proceso</a></h1>
    </div>
    <nav class="main-nav">
      <div id="menu-button" class="menu-button">
        <img src="img/menu.svg">
        <span class="ocultar-texto">MENU</span>
      </div>
      <div class="search-bar">
        <input type="text" placeholder="Buscar..." />
      </div>
      <div class="social-icons">
        <a href="#"><img src="img/facebook.svg" alt="Facebook"></a>
        <a href="#"><img src="img/instagram.svg" alt="Instagram"></a>
      </div>
    </nav>
  </header>

  <div id="sidebar" class="sidebar">
    <button id="close-button" class="close-button">Cerrar</button>
    <ul>
      <li><a href="Main.php">Inicio</a></li>
      <li><a href="#">Noticias</a></li>
      <li><a href="#">Contacto</a></li>
      <li><a href="#">Acerca de</a></li>
    </ul>
    <button id="login-button" class="login-button">Login</button>
  </div>

  <?php if ($banner): ?>
  <section class="banner" onclick="window.location.href='publicacion.php?id=<?php echo $banner['id_entrada']; ?>';" style="cursor: pointer;">
    <?php if (!empty($banner['imagen'])): ?>
      <img src="<?php echo $banner['imagen']; ?>" alt="<?php echo $banner['titulo']; ?>">
    <?php endif; ?>
    <h1><?php echo $banner['titulo']; ?></h1>
  </section>
  <?php endif; ?>

  <main>
    <section class="articles">
      <?php
      if ($resultPosts->num_rows > 0) {
          while ($row = $resultPosts->fetch_assoc()) {
              echo '<article class="card" onclick="window.location.href=\'publicacion.php?id=' . $row['id_entrada'] . '\'" style="cursor: pointer;">';
              
              echo '<div class="texto">';
              echo '<h3>' . $row['titulo'] . '</h3>';
              echo '<div class="semicuerpo">';
              echo '<p><strong>Fecha:</strong> ' . $row['fecha'] . '</p>';
              echo '<p>' . substr($row['contenido'], 0, 100) . '...</p>';
              echo '</div>';
              echo '</div>';
              
              if (!empty($row['imagen'])) {
                  echo '<div class="fotos"><img src="' . $row['imagen'] . '" alt="' . $row['titulo'] . '"></div>';
              }
              
              echo '</article>';
          }
      } else {
          echo "<p>No hay publicaciones disponibles.</p>";
      }
      $conn->close();
      ?>
    </section>
  </main>

  <footer>
    <p>&copy; 2025 Voces del Proceso. Todos los derechos reservados.</p>
  </footer>
</body>
</html>
