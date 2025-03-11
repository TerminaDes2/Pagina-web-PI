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

// Verificar que se haya pasado el id de la publicación por GET
if (isset($_GET['id'])) {
    $id_entrada = intval($_GET['id']);
    // Consulta para obtener los datos de la publicación y su imagen asociada (si existe)
    $sql    = "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, i.imagen 
               FROM entradas e 
               LEFT JOIN imagenes i ON e.id_imagen = i.id_imagen 
               WHERE e.id_entrada = $id_entrada";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $entrada = $result->fetch_assoc();
    } else {
        echo "No se encontró la publicación.";
        exit;
    }
} else {
    echo "No se especificó la publicación.";
    exit;
}

// Consulta para obtener otras publicaciones (para la sección de publicidad) 
// que sean diferentes a la actual, ordenadas de la más reciente a la más antigua
$sqlAds = "SELECT e.id_entrada, e.titulo, e.fecha, i.imagen 
           FROM entradas e 
           LEFT JOIN imagenes i ON e.id_imagen = i.id_imagen 
           WHERE e.id_entrada <> $id_entrada 
           ORDER BY e.fecha DESC 
           LIMIT 2";
$resultAds = $conn->query($sqlAds);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?php echo $entrada['titulo']; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">
  <script src="Main.js" defer></script>
  <link rel="stylesheet" href="estilos/publicacion.css">
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

  <div class="titulo">
    <h1><?php echo $entrada['titulo']; ?></h1>
  </div>

  <main>
    <!-- Sección Índice -->
    <div class="indice">
      <ul>
        <h1>Indice</h1>
        <li>Plan de Transporte</li>
        <li>Renovación de Parques</li>
        <li>Modernización de Edificios Públicos</li>
      </ul>
    </div>

    <!-- Sección Noticia: Contenido completo de la publicación -->
    <div class="noticia">
      <article>
        <?php
        // Mostrar la imagen/banner si existe
        if (!empty($entrada['imagen'])) {
            echo '<img src="' . $entrada['imagen'] . '" alt="' . $entrada['titulo'] . '">';
        }
        ?>
        <p><strong>Fecha:</strong> <?php echo $entrada['fecha']; ?></p>
        <div>
          <?php echo $entrada['contenido']; ?>
        </div>
      </article>
    </div>

    <!-- Sección Publicidad: Otras publicaciones ordenadas de la más reciente a la más antigua -->
    <article class="publicidad">
      <?php
      if ($resultAds->num_rows > 0) {
          while ($rowAd = $resultAds->fetch_assoc()) {
              echo '<div class="imagenes">';
              // Se muestra la imagen (si existe) y se enlaza a la publicación correspondiente
              if (!empty($rowAd['imagen'])) {
                  echo '<a href="publicacion.php?id=' . $rowAd['id_entrada'] . '"><img src="' . $rowAd['imagen'] . '" alt="' . $rowAd['titulo'] . '"></a>';
              }
              echo '<h3><a href="publicacion.php?id=' . $rowAd['id_entrada'] . '">' . $rowAd['titulo'] . '</a></h3>';
              echo '</div>';
          }
      } else {
          echo "<p>No hay otras publicaciones disponibles.</p>";
      }
      ?>
    </article>
  </main>

  <footer>
    <p>&copy; 2025 Voces del Proceso. Todos los derechos reservados.</p>
  </footer>
</body>
</html>
