<?php
session_start();
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'es';
include "lang_{$lang}.php";

// Conexión a la base de datos
$host   = 'localhost';
$dbname = 'blog';
$dbuser = 'root';
$dbpass = 'administrador';


$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
// Configurar la conexión para usar UTF-8
$conn->set_charset("utf8");

// Verificar que se haya pasado el id de la publicación por GET
if (isset($_GET['id'])) {
    $id_entrada = intval($_GET['id']);
    $sql = "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, i.imagen 
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
$sqlAds = "SELECT e.id_entrada, e.titulo, e.fecha, i.imagen 
           FROM entradas e 
           LEFT JOIN imagenes i ON e.id_imagen = i.id_imagen 
           WHERE e.id_entrada <> $id_entrada 
           ORDER BY e.fecha DESC 
           LIMIT 2";
$resultAds = $conn->query($sqlAds);

/**
 * Función para generar un índice a partir de los encabezados <h2> en el contenido.
 * Se fuerza la codificación a UTF-8 para evitar warnings en setAttribute.
 */
function generarIndice($html) {
    $dom = new DOMDocument('1.0', 'UTF-8');
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    
    $headers = $dom->getElementsByTagName('h2');
    $indice = '<ul>';
    foreach ($headers as $header) {
        $rawText = $header->nodeValue;
        $encoding = mb_detect_encoding($rawText, mb_detect_order(), true);
        if ($encoding !== 'UTF-8') {
            $rawText = mb_convert_encoding($rawText, 'UTF-8', $encoding);
        }
        $id = strtolower(trim($rawText));
        $id = preg_replace('/\s+/', '-', $id);
        if (!mb_check_encoding($id, 'UTF-8')) {
            $id = mb_convert_encoding($id, 'UTF-8', 'auto');
        }
        $header->setAttribute('id', $id);
        $indice .= "<li><a href='#{$id}'>{$rawText}</a></li>";
    }
    $indice .= '</ul>';
    
    $nuevoHtml = $dom->saveHTML();
    return ['indice' => $indice, 'contenido' => $nuevoHtml];
}

$htmlContenido = nl2br($entrada['contenido']);
$resultado = generarIndice($htmlContenido);
$indiceGenerado = $resultado['indice'];
$contenidoConAnchors = $resultado['contenido'];
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
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
      <div class="social-icons">
        <a href="#"><img src="img/facebook.svg" alt="Facebook"></a>
        <a href="#"><img src="img/instagram.svg" alt="Instagram"></a>
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

  <div class="titulo">
    <h1><?php echo $entrada['titulo']; ?></h1>
  </div>

  <main>
    <!-- Sección Índice -->
    <div class="indice">
      <h2>Índice</h2>
      <?php echo $indiceGenerado; ?>
    </div>

    <!-- Sección Noticia -->
    <div class="noticia">
      <article>
        <?php
        if (!empty($entrada['imagen'])) {
            echo '<img src="' . $entrada['imagen'] . '" alt="' . $entrada['titulo'] . '">';
        }
        ?>
        <p><strong>Fecha:</strong> <?php echo $entrada['fecha']; ?></p>
        <div>
          <?php echo $contenidoConAnchors; ?>
        </div>
      </article>
    </div>

    <!-- Sección Publicidad: Otras publicaciones -->
    <aside class="publicidad">
      <?php
      if ($resultAds->num_rows > 0) {
          while ($rowAd = $resultAds->fetch_assoc()) {
              echo '<div class="imagenes">';
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
    </aside>
  </main>

  <footer>
    <p><?php echo $idioma['footer_text']; ?></p>
  </footer>
</body>
</html>
<?php include 'footer.php'; ?>
