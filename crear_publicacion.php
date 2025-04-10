<?php
// Seguridad de sesión
session_start();
$varsesion = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
if ($varsesion == null || $varsesion == '') {
  echo '<!DOCTYPE html>
      <html lang="es">
      <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Acceso Denegado</title>
      <style>
        body {
        font-family: Arial, sans-serif;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
        background-color: rgb(157, 201, 149);
        color: rgb(1, 6, 2);
        text-align: center;
        }
        .message-container {
        border: 1px solid rgb(165, 211, 166);
        padding: 20px;
        background-color: rgb(130, 159, 136);
        border-radius: 5px;
        }
        .message-container h1 {
        font-size: 24px;
        margin-bottom: 10px;
        }
        .message-container p {
        font-size: 18px;
        }
        .message-container a {
        display: inline-block;
        margin-top: 15px;
        padding: 10px 20px;
        background-color: rgb(1, 6, 2);
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-size: 16px;
        }
        .message-container a:hover {
        background-color: rgb(0, 50, 0);
        }
      </style>
      </head>
      <body>
      <div class="message-container">
        <h1>Acceso Denegado</h1>
        <p>Usted no tiene autorización para ingresar a esta página web.</p>
        <a href="Main.php">Regresar al Inicio</a>
      </div>
      </body>
      </html>';
  exit();
}


$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'es';
include "lang_{$lang}.php";

// Configuración de la conexión a la base de datos (ajusta según tu entorno)
$host   = 'localhost';
$dbname = 'blog';
$dbuser = 'root';
$dbpass = 'administrador';

date_default_timezone_set('America/Mexico_City');

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
// Configurar la conexión para usar UTF-8
$conn->set_charset("utf8");

// Procesa el formulario al enviarlo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger y sanitizar datos
    $titulo    = trim($_POST['titulo']);
    $categoria = trim($_POST['categoria']);
    $contenido = trim($_POST['contenido']);
    // Convertir el contenido a UTF-8
    $contenido = mb_convert_encoding($contenido, 'UTF-8', 'auto');
    $fecha     = date("Y-m-d");
    $id_usuario = isset($_SESSION['id_usuario']) ? $_SESSION['id_usuario'] : 1;
    
    // Inserta la publicación en la tabla entradas (sin id_imagen aún)
    $stmt = $conn->prepare("INSERT INTO entradas (titulo, categoria, contenido, fecha, id_usuario) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $titulo, $categoria, $contenido, $fecha, $id_usuario);
    if ($stmt->execute()) {
        $id_entrada = $stmt->insert_id;
        
        // Procesa la imagen si se sube alguna
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $destino = "uploads/";
            if (!is_dir($destino)) {
                mkdir($destino, 0777, true);
            }
            
            $nombreArchivo = time() . "_" . basename($_FILES['imagen']['name']);
            $rutaDestino   = $destino . $nombreArchivo;
            
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                $stmtImg = $conn->prepare("INSERT INTO imagenes (imagen, id_entrada) VALUES (?, ?)");
                $stmtImg->bind_param("si", $rutaDestino, $id_entrada);
                if ($stmtImg->execute()) {
                    $id_imagen = $stmtImg->insert_id;
                    $stmtUpdate = $conn->prepare("UPDATE entradas SET id_imagen = ? WHERE id_entrada = ?");
                    $stmtUpdate->bind_param("ii", $id_imagen, $id_entrada);
                    $stmtUpdate->execute();
                    $stmtUpdate->close();
                }
                $stmtImg->close();
            } else {
                header("Location: crear_publicacion.php?msg=" . urlencode($idioma['error_subir_imagen']) . "&msgType=error");
                exit();
            }
        }
        header("Location: crear_publicacion.php?msg=" . urlencode($idioma['publicacion_success']) . "&msgType=success");
        exit();
    } else {
        header("Location: crear_publicacion.php?msg=" . urlencode($idioma['publicacion_error'] . " " . $stmt->error) . "&msgType=error");
        exit();
    }
    $stmt->close();
    $conn->close();
}

$message = "";
$messageType = "";
if(isset($_GET['msg'])){
    $message = $_GET['msg'];
    $messageType = isset($_GET['msgType']) ? $_GET['msgType'] : "success";
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $idioma['voces_proceso']; ?></title>
  <link rel="stylesheet" href="estilos/publicar.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- SweetAlert2 para popups -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="loggin_scripts.js" defer></script>
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
      <div class="social-icons">
        <a href="#"><img src="img/facebook.svg" alt="Facebook"></a>
        <a href="#"><img src="img/instagram.svg" alt="Instagram"></a>
      </div>
      <!-- Enlaces para cambiar idioma -->
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
    <?php if (isset($_SESSION['usuario'])): ?>
      <button id="cerrar-sesion" class="cerrarsesion">
        <a href="cerrarsesion.php"><?php echo $idioma['cerrar_sesion']; ?></a>
      </button>
    <?php else: ?>
      <button id="login-button" class="login-button">
        <a href="login.php"><?php echo $idioma['iniciar_sesion']; ?></a>
      </button>
    <?php endif; ?>
  </div>

  <main class="main">
    <h1><?php echo $idioma['crear_publicacion_title']; ?></h1>
    <form action="crear_publicacion.php" method="POST" enctype="multipart/form-data">
      <label for="titulo"><?php echo $idioma['titulo_label']; ?></label>
      <input type="text" name="titulo" id="titulo" required>

      <label for="categoria"><?php echo $idioma['categoria_label']; ?></label>
      <input type="text" name="categoria" id="categoria" required>

      <label for="contenido"><?php echo $idioma['contenido_label']; ?></label>
      <textarea name="contenido" id="contenido" rows="10" required></textarea>

      <label for="imagen"><?php echo $idioma['imagen_label']; ?></label>
      <input type="file" name="imagen" id="imagen" accept="image/*">

      <button type="submit"><?php echo $idioma['crear_button']; ?></button>
    </form>
  </main>

  <footer>
    <p><?php echo $idioma['footer_text']; ?></p>
  </footer>

  <!-- Popup de notificación con SweetAlert2 -->
  <?php if (!empty($message)) : ?>
  <script>
    $(document).ready(function(){
      Swal.fire({
        icon: '<?php echo $messageType; ?>',
        title: '<?php echo $message; ?>',
        showConfirmButton: false,
        timer: 2000
      });
    });
  </script>
  <?php endif; ?>
</body>
</html>
