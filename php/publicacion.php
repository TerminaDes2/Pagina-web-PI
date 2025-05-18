<?php
session_start();

//Uso de Cookie
require_once '../includes/auth.php';

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
    $sql = "SELECT e.id_entrada, e.titulo, e.contenido, e.cita, e.fecha, c.categoria as nombre_categoria, i.imagen 
            FROM entradas e 
            LEFT JOIN imagenes i ON i.id_entrada = e.id_entrada 
            LEFT JOIN categorias c ON e.categoria = c.id_categoria
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
$entrada['cita'] = $translator->traducirTexto($entrada['cita']);

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
    $id_entrada = isset($_GET['id']) ? $_GET['id'] : 0;
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
$sql = "SELECT c.descripcion, c.fecha, u.nombre, u.primer_apellido, u.segundo_apellido, u.imagen
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
    libxml_use_internal_errors(true); // Suprimir errores de HTML mal formado
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    
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
    // Eliminar tags XML y DOCTYPE añadidos
    $nuevoHtml = preg_replace('/<\?xml encoding="utf-8" \?>/i', '', $nuevoHtml);
    $nuevoHtml = preg_replace('/<\/?html>/i', '', $nuevoHtml);
    $nuevoHtml = preg_replace('/<\/?body>/i', '', $nuevoHtml);
    
    return ['indice' => $indice, 'contenido' => $nuevoHtml];
}

// Generar índice y traducir contenido con anchors
$htmlContenido = nl2br($entrada['contenido']);

$resultado = generarIndice($htmlContenido);
$indiceGenerado = $resultado['indice'];
$contenidoConAnchors = $translator->traducirHTML($resultado['contenido']);
?>

<!DOCTYPE html>
<html lang="<?= isset($_SESSION['idioma']) ? $_SESSION['idioma'] : 'es' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/> <!-- Asegura diseño responsivo -->
  <title><?php echo $entrada['titulo']; ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/publicacion-nuevo.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="icon" href="/Pagina-web-PI/assets/img/POALCE-logo.ico" type="image/x-icon">
</head>
<body>
  <?php include '../includes/header.php'; ?>

  <div class="titulo">
      <h1><?php echo $entrada['titulo']; ?></h1>
      <div class="post-meta">
        <span class="date"><i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($entrada['fecha'])); ?></span>
        <span class="categoria"><i class="fas fa-tag"></i> <?php echo $entrada['nombre_categoria']; ?></span>
      </div>
  </div>

  <main>
    
    <!-- Sección Índice -->
    <div class="indice">
      <h2><i class="fas fa-list-ul"></i> <?= $translator->__("Índice") ?></h2>
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
        <div>
          <?php echo $contenidoConAnchors; ?>
        </div>

        <?php if (!empty($entrada['cita'])): ?> 
        <!-- Si el campo de referencias no está vacío, entonces se muestra el bloque -->
          <div class="mt-4">
            <h5><i class="fas fa-book"></i> Referencias</h5>
            <ul>
              <?php 
                $lineas = explode("\n", $entrada['cita']); 
                // Separa las referencias por cada salto de línea, creando un array        
                foreach ($lineas as $ref): 
                  $ref = trim($ref); 
                  // Quita espacios en blanco al inicio y al final de cada línea
                  if (!empty($ref)): 
                  // Solo si la línea no está vacía
                    if (filter_var($ref, FILTER_VALIDATE_URL)) {
                    // Si la línea es una URL válida...
                    $host = parse_url($ref, PHP_URL_HOST);
                    // Obtiene el dominio del enlace (por ejemplo: www.ejemplo.com)
                    $nombreSitio = ucfirst(str_replace('www.', '', $host));
                    // Elimina el "www." si existe y pone la primera letra en mayúscula
                    echo "<li>$nombreSitio. (s.f.). Recuperado de <a href=\"$ref\" target=\"_blank\">$ref</a></li>";
                    // Muestra la referencia con formato APA básico para sitios web
                    } else {
                    echo "<li>" . htmlspecialchars($ref) . "</li>";
                    // Si no es un enlace, se imprime tal cual el texto de la referencia, protegido con htmlspecialchars
                    }
                  endif;
                endforeach;
              ?>
            </ul>
          </div>
        <?php endif; ?>
      </article>

      <div class="men_container">
        <h2 class="men_titulo"><i class="far fa-comments"></i> <?= $translator->__("Comentarios") ?></h2>
        <?php if(isset($_SESSION['usuario'])): ?>
          <!-- Comentarios -->
          <form action="publicacion.php?id=<?= $id_entrada ?>" method="POST" class="comen_container">
            <div class="comen">
              <label class="comen_title">
                <?= $datos['nombre'] . ' ' . $datos['primer_apellido'] . ' ' . $datos['segundo_apellido'] ?>
              </label>

              <div class="comen_group">
                <label class="comen_comen"><?= $translator->__("Comentario:") ?></label>
                <textarea class="comen_paragraph" id="descripcion" name="descripcion" rows="5" maxlength="255" placeholder="<?= $translator->__("Escribe tu comentario...") ?>"></textarea>
              </div>
                
              <div class="button_comen">
                <button type="submit" id="comentar" name="comentar" class="btn_comen">
                  <i class="far fa-paper-plane"></i> <?= $translator->__("Publicar") ?>
                </button>
              </div>
            </div>
          </form>
          <button type="button" class="btn_comen" id="btn_comen">
            <i class="far fa-comments"></i> <span><?= $translator->__("Ver comentarios") ?></span>
          </button>
          
          <!-- Contenedor de comentarios para usuarios autenticados -->
          <div id="contenedor-comentarios" class="ocultar">
            <?php
              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $nombre_completo = $row['nombre'] . ' ' . $row['primer_apellido'] . ' ' . $row['segundo_apellido'];
                    $fecha = $row['fecha'];
                    $descripcion = htmlspecialchars($row['descripcion']);
                    echo "
                    <div class='comentario-container'>
                      <div class='comentario'>
                        <div class='usuario-info'>";
                    // Mostrar la imagen de usuario o un ícono por defecto
                    if (isset($row['imagen']) && !empty($row['imagen'])) {
                        $imagen = '/Pagina-web-PI/' . ltrim($row['imagen'], '/');
                        echo "<img src='$imagen' alt='avatar' class='avatar'>";
                    } else {
                        echo '<i class="fas fa-user"></i>';
                    }
                    echo "      <div>
                            <strong>$nombre_completo</strong><br>
                            <span class='fecha'>$fecha</span>
                          </div>
                        </div>
                        <p class='comentario-texto'>$descripcion</p>
                      </div>
                    </div>
                    ";
                }
              } else {
                  echo "<p class = 'men_err'>" . $translator->__("No hay comentarios aún.") . "</p>";
              }
            ?>
          </div>
          
        <?php elseif(isset($_GET['error'])): ?>
          <p class="men_err"><?= htmlspecialchars($_GET['error']) ?></p>
          <button type="button" class="btn_comen" id="btn_comen">
            <i class="far fa-comments"></i> <span><?= $translator->__("Ver comentarios") ?></span>
          </button>
          <div id="contenedor-comentarios" class="ocultar">
            <?php
              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $nombre_completo = $row['nombre'] . ' ' . $row['primer_apellido'] . ' ' . $row['segundo_apellido'];
                    $fecha = $row['fecha'];
                    $descripcion = htmlspecialchars($row['descripcion']);
                    // Corregir la ruta de la imagen
                    $imagen = !empty($row['imagen']) ? '/Pagina-web-PI/' . $row['imagen'] : '/Pagina-web-PI/assets/img/default-avatar.png';
                    echo "
                    <div class='comentario-container'>
                      <div class='comentario'>
                        <div class='usuario-info'>
                          <img src='$imagen' alt='avatar' class='avatar'>
                          <div>
                            <strong>$nombre_completo</strong><br>
                            <span class='fecha'>$fecha</span>
                          </div>
                        </div>
                        <p class='comentario-texto'>$descripcion</p>
                      </div>
                    </div>
                    ";
                }
              } else {
                  echo "<p class = 'men_err'>" . $translator->__("No hay comentarios aún.") . "</p>";
              }
            ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Sección Publicidad: Otras publicaciones -->
    <aside class="publicidad">
      <h3><i class="fas fa-bookmark"></i> <?= $translator->__("Artículos relacionados") ?></h3>
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
          echo "<p>" . $translator->__("No hay otras publicaciones disponibles.") . "</p>";
      }
      ?>
    </aside>
  </main>

  <?php include '../includes/footer.php'; ?>
  <script src="../assets/js/comentarios.js"></script>
  <script src="../assets/js/scroll-index.js"></script>
</body>
</html>
