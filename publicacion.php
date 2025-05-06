<?php
session_start();
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'es';
include "lang_{$lang}.php";

// Conexión a la base de datos
$host       = 'localhost';
$bd         = 'blog';
$usuario    = 'root';
$contrasena = '';

$conn = new mysqli($host, $usuario, $contrasena, $bd);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
// Configurar la conexión para usar UTF-8
$conn->set_charset("utf8");

// Verificar que se haya pasado el id de la publicación por GET
if (isset($_GET['id'])) {
    $id_entrada = intval($_GET['id']);
    $sql = "SELECT e.id_entrada, e.titulo, e.contenido, e.cita, e.fecha, i.imagen 
            FROM entradas e 
            LEFT JOIN imagenes i ON e.id_entrada = i.id_entrada 
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
           LEFT JOIN imagenes i ON e.id_entrada = i.id_entrada 
           WHERE e.id_entrada <> $id_entrada 
           ORDER BY e.fecha DESC 
           LIMIT 2";
$resultAds = $conn->query($sqlAds);

/*Ingresar comentarios*/
if (isset($_SESSION['registro'])) {
  $datos = $_SESSION['registro'];
} else {
  echo "Hola";
}

if(isset($_POST['publicar']) && isset($datos['id_usuario']) && isset($_GET['id'])) {
  $descripcion = trim($_POST['descripcion']);
  $id_usuario = 1; //intval($datos['id_usuario']);
  $id_entrada = intval($_GET['id']);

  if(!empty($descripcion)) {
    $stmt = $conn->prepare("INSERT INTO comentarios (descripcion, id_entrada, id_usuario) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $descripcion, $id_entrada, $id_usuario);

    if($stmt->execute()) {
      echo "<p>Comentario publicado correctamente.</p>";
    } else {
      echo "<p>Error al publicar el comentario: " . $stmt->error . "</p>";
    }

    $stmt->close();
  } else {
    echo "<p>El comentario no puede estar vacío.</p>";
  }
}

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

function obtenerMetadatos($url) {
  $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $html = curl_exec($ch);
    curl_close($ch);

    if (!$html) {
        return false;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    $xpath = new DOMXPath($dom);

    $tituloNode = $xpath->query('//meta[@property="og:title"]/@content')->item(0);
    if ($tituloNode) {
        $titulo = $tituloNode->nodeValue;
    } else {
        $tituloNode = $xpath->query('//title')->item(0);
        $titulo = $tituloNode ? $tituloNode->nodeValue : "Título desconocido";
    }

    $autorNode = $xpath->query('//meta[@name="author"]/@content')->item(0);
    $autor = $autorNode ? $autorNode->nodeValue : 'Autor desconocido';

    $fechaNode = $xpath->query('//meta[@name="date"]/@content')->item(0);
    if (!$fechaNode) {
        $fechaNode = $xpath->query('//meta[@property="article:published_time"]/@content')->item(0);
    }
    $fecha = $fechaNode ? $fechaNode->nodeValue : 's.f.';

    return [
        'titulo' => $titulo,
        'autor' => $autor,
        'fecha' => $fecha,
    ];
}

function generarCitaAPA($url) {
  $metadatos = obtenerMetadatos($url);

  if (!$metadatos) {
      return "No se pudieron obtener los metadatos de la página.";
  }

  $fecha_acceso = date('Y');
  $cita_apa = "{$metadatos['autor']}. ({$metadatos['fecha']}). {$metadatos['titulo']}. Recuperado el {$fecha_acceso} de {$url}";

  return $cita_apa;
}

/*function obtenerMetadatos($url) {
  $html = @file_get_contents($url);
  if(!$html) {
    return "No se pudo acceder a la URL";
  }

  $doc = new DOMDocument();
  @$doc->loadHTML($html);

  $xpath = new DOMXPath($doc);

  $tags = [
    "title" => "//title",
    "author" => "//meta[@name='author']/@content",
    "date" => "//meta[@name='date']/@content | //meta[@property='article:published_time']/@content",
    "publisher" => "//meta[@name='publisher']/@content | //meta[@property='og:site_name']/@content"
  ];

  $metadatos = [];
  foreach ($tags as $key => $query) {
    $nodeList = $xpath->query($query);
    $metadatos[$key] = ($nodeList && $nodeList->length > 0) ? $nodeList->item(0)->nodeValue : "";
  }

    return $metadatos;
}

function generarCitaAPA($url) {
  $datos = obtenerMetadatos($url);
  if (is_string($datos)) {
    return $datos;
  }

  $autor = $datos['author'] ?: "Autor desconocido";
  $titulo = $datos['title'] ?: "Título desconocido";
  $fecha = $datos['date'] ?: date("Y");  
  $sitio = $datos['publisher'] ?: parse_url($url, PHP_URL_HOST);

  return "$autor. ($fecha). *$titulo*. $sitio. Disponible en: $url";
}*/
$url = $entrada['cita'];
$cita = generarCitaAPA($url);

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
        <p><strong>Fecha:</strong><?php echo $entrada['fecha']; ?></p>
        <div>
          <?php echo $contenidoConAnchors; ?>
          <?php echo $cita ?>
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

    <!-- Comentarios -->
    <div>
      <button class="btn_comen">Comentar</button>
      <form action="publicacion.php?id=<?= $id_entrada ?>" method="POST" class="modal">
        <div class="modal_container">
          <label class="modal_title">Mario Alberto Ballato Román</label><br>
          <label class="modal_comen">Comentario:</label><br>
          <input type="text" class="modal_paragraph" id="descripcion" name="descripcion"><br>
          <button type="submit" id="publicar" name="publicar" class="modal_publi">Publicar</button>
          <button type="button" class="modal_close">Cancelar</button>
        </div>
      </form>
    </div>
  </main>

  <footer>
    <p><?php echo $idioma['footer_text']; ?></p>
  </footer>
  
  <script src="comentarios.js"></script>
</body>
</html>
