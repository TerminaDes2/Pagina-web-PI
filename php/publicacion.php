<?php
session_start();
// Verificar si el idioma está configurado en la sesión, si no, establecer un idioma predeterminado
if (!isset($_SESSION['idioma'])) {
  $_SESSION['idioma'] = 'es'; // Idioma predeterminado
}

// Conexión a la base de datos
include "../includes/db_config.php";

$conn = new mysqli(host, dbuser, dbpass, dbname);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
// Configurar la conexión para usar UTF-8
$conn->set_charset("utf8");

require_once '../includes/traductor.php';
$translator = new Translator($conn);

// Manejar cambio de idioma
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['idioma'])) {
    $translator->cambiarIdioma($_POST['idioma']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Verificar que se haya pasado el id de la publicación por GET
if (isset($_GET['id'])) {
    $id_entrada = intval($_GET['id']);
    $sql = "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, i.imagen 
            FROM entradas e 
            LEFT JOIN imagenes i ON i.id_entrada = e.id_entrada 
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

// Traducir los datos de la publicación principal
$entrada['titulo'] = $translator->traducirTexto($entrada['titulo']);
$entrada['contenido'] = $translator->traducirHTML($entrada['contenido']);

// Consulta para obtener otras publicaciones (para la sección de publicidad)
$sqlAds = "SELECT e.id_entrada, e.titulo, e.fecha, i.imagen 
           FROM entradas e 
           LEFT JOIN imagenes i ON i.id_entrada = e.id_entrada 
           WHERE e.id_entrada <> $id_entrada 
           ORDER BY e.fecha DESC 
           LIMIT 2";
$resultAds = $conn->query($sqlAds);

// Traducir los datos de las publicaciones relacionadas (publicidad)
$ads = [];
if ($resultAds->num_rows > 0) {
    while ($rowAd = $resultAds->fetch_assoc()) {
        $rowAd['titulo'] = $translator->traducirTexto($rowAd['titulo']);
        $ads[] = $rowAd;
    }
}

/* Ingresa comentarios */
if(!isset($_SESSION['usuario'])) {
  if(!isset($_GET['error'])) {
    $id_entrada = $_GET['id'] ?? 0;
    $error = urlencode("Es necesario iniciar sesión para poder realizar comentarios.");
    header("Location: publicacion.php?id=$id_entrada&error=$error");
    exit();
  }
} else {
  $datos = $_SESSION['usuario'];
}

if(isset($_POST['comentar']) && isset($_SESSION['usuario']) && isset($_GET['id'])) {
  $descripcion = trim($_POST['descripcion']);
  $id_usuario = intval($_SESSION['usuario']['id_usuario']);
  $id_entrada = intval($_GET['id']);

  if(!empty($descripcion)) {
    $stmt = $conn->prepare("INSERT INTO comentarios(descripcion, id_entrada, id_usuario) VALUES (?, ?, ?)");
    $stmt->bind_param("sii", $descripcion, $id_entrada, $id_usuario);
   
    if($stmt->execute()) {
      echo "<p>Comentario publicado correctamente.</p>";
    } else {
      echo "<p>Error al publicar el comentario: " . htmlspecialchars($stmt->error) . "</p>";
    }

    $stmt->close();
  } else {
    echo "<p>El comentario no puede estar vacío.</p>";
  }
}

/* Mostrar comentarios comenzando desde el mas reciente */
$sql = "SELECT c.descripcion, c.fecha, u.nombre, u.primer_apellido, u.segundo_apellido
        FROM comentarios c
        INNER JOIN usuarios u ON c.id_usuario = u.id_usuario
        WHERE c.id_entrada = ?
        ORDER BY c.id_comentario DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_entrada); // $id_entrada es la publicación
$stmt->execute();
$result = $stmt->get_result();

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

// Generar índice y traducir contenido con anchors
$htmlContenido = nl2br($entrada['contenido']);
$resultado = generarIndice($htmlContenido);
$indiceGenerado = $resultado['indice'];
$contenidoConAnchors = $translator->traducirHTML($resultado['contenido']);
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/> <!-- Asegura diseño responsivo -->
  <title><?php echo $entrada['titulo']; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/publicacion.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <?php include '../includes/header.php'; ?>

  <div class="titulo">
      <h1><?php echo $entrada['titulo']; ?></h1>
  </div>

  <main>
    
    <!-- Sección Índice -->
    <div class="indice">
      <h2><?= $translator->__("Índice") ?></h2>
      <?php echo $indiceGenerado; ?>
    </div>

    <!-- Sección Noticia -->
    <div class="noticia">
      <article>
        <?php
        if (!empty($entrada['imagen'])) {
            echo '<img src="' . $entrada['imagen'] . '" alt="' . $translator->traducirTexto($entrada['titulo']) . '">';
        }
        ?>
        <p><strong><?= $translator->__("Fecha:") ?></strong> <?php echo $entrada['fecha']; ?></p>
        <div>
          <?php echo $contenidoConAnchors; ?>
        </div>
      </article>

      <div class="men_container">
        <h2 class="men_titulo">Comentarios</h2>
        <?php if(isset($_SESSION['usuario'])): ?>
          <button class="btn_comen">Comentar</button>
          <?php
            if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                  $nombre_completo = $row['nombre'] . ' ' . $row['primer_apellido'] . ' ' . $row['segundo_apellido'];
                  $fecha = $row['fecha'];
                  $descripcion = htmlspecialchars($row['descripcion']);
                  echo "
                  <div class='comentario-container'>
                    <div class='comentario'>
                      <div>
                        <strong>$nombre_completo</strong><br>
                        <span class='fecha'>$fecha</span>
                      </div>
                      <p class='comentario-texto'>$descripcion</p>
                    </div>
                  </div>
                  ";
              }
            } else {
                echo "<p class = 'men_err'>No hay comentarios aún.</p>";
            }
          ?>
        <?php elseif(isset($_GET['error'])): ?>
          <p class="men_err"><?= htmlspecialchars($_GET['error']) ?></p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Sección Publicidad: Otras publicaciones -->
    <aside class="publicidad">
      <?php
      if (!empty($ads)) {
          foreach ($ads as $ad) {
              echo '<div class="imagenes">';
              if (!empty($ad['imagen'])) {
                  echo '<a href="publicacion.php?id=' . $ad['id_entrada'] . '"><img src="' . $ad['imagen'] . '" alt="' . $ad['titulo'] . '"></a>';
              }
              echo '<h3><a href="publicacion.php?id=' . $ad['id_entrada'] . '">' . $ad['titulo'] . '</a></h3>';
              echo '</div>';
          }
      } else {
          echo "<p>No hay otras publicaciones disponibles.</p>";
      }
      ?>
    </aside>

    <!-- Comentarios -->
    <div>
      <form action="publicacion.php?id=<?= $id_entrada ?>" method="POST" class="modal">
        <div class="modal_container">
          <label class="modal_title">
            <?= $datos['nombre'] . ' ' . $datos['primer_apellido'] . ' ' . $datos['segundo_apellido'] ?>
          </label>

          <div class="modal_group">
            <label class="modal_comen">Comentario:</label>
            <textarea class="modal_paragraph" id="descripcion" name="descripcion" rows="5" maxlength="255" placeholder="Escribe tu comentario..."></textarea>
          </div>
          
          <div class="modal_button">
            <button type="button" class="modal_close">Cancelar</button>
            <button type="submit" id="comentar" name="comentar" class="modal_publi">Publicar</button>
          </div>
        </div>
      </form>
    </div>
  </main>

  <?php include '../includes/footer.php'; ?>
  <script src="../assets/js/comentarios.js"></script>
</body>
</html>
