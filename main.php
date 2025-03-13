<?php
session_start();
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'es';
include "lang_{$lang}.php";

// Conexión a la base de datos
$host       = "localhost";
$usuario    = "root";
$contrasena = "administrador";
$bd         = "blog";

$conn = new mysqli($host, $usuario, $contrasena, $bd);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}

$sqlBanner = "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, i.imagen 
              FROM entradas e 
              LEFT JOIN imagenes i ON e.id_imagen = i.id_imagen 
              ORDER BY e.id_entrada DESC 
              LIMIT 1";
$resultBanner = $conn->query($sqlBanner);
$banner = ($resultBanner->num_rows > 0) ? $resultBanner->fetch_assoc() : null;

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
<html lang="<?php echo $lang; ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $idioma['voces_proceso']; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="estilos/main.css">
  <script src="Main.js" defer></script>
</head>
<body>
  <header class="header-top">
    <div class="logo">
      <h1><a href="Main.php"><?php echo $idioma['voces_proceso']; ?></a></h1>
    </div>
    <nav class="main-nav">
      <div id="menu-button" class="menu-button">
        <img src="img/menu.svg">
        <span class="ocultar-texto"><?php echo $idioma['menu']; ?></span>
      </div>
      <div class="search-bar">
        <input type="text" placeholder=<?php echo $idioma['buscar']; ?> />
      </div>
      <!-- Selector de idioma -->
      <div class="lang-selector">
        <a href="set_lang.php?lang=es">Español</a> | 
        <a href="set_lang.php?lang=en">English</a>
      </div>
    </nav>
  </header>

  <div id="sidebar" class="sidebar">
    <button id="close-button" class="close-button"><?php echo $idioma['cerrar']; ?></button>
    <ul>
      <li><a href="Main.php"><?php echo $idioma['inicio']; ?></a></li>
      <li><a href="#"><?php echo $idioma['noticias']; ?></a></li>
      <li><a href="#"><?php echo $idioma['contacto']; ?></a></li>
      <li><a href="#"><?php echo $idioma['acerca_de']; ?></a></li>
    </ul>
    <button id="login-button" class="login-button"><?php echo $idioma['login']; ?></button>
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
    <p><?php echo $idioma['footer_text']; ?></p>
  </footer>
</body>
</html>
