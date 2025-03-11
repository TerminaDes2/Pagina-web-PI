<?php
session_start();

// Configuración de la conexión a la base de datos (ajusta según tu entorno)
$host       = "localhost";
$usuario    = "root";
$contrasena = "administrador";
$bd         = "blog";

date_default_timezone_set('America/Mexico_City');

$conn = new mysqli($host, $usuario, $contrasena, $bd);
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
    $contenido = trim($_POST['contenido']); // Se guarda el contenido tal cual (incluyendo saltos de línea o HTML)
    // Convertir el contenido a UTF-8
    $contenido = mb_convert_encoding($contenido, 'UTF-8', 'auto');
    $fecha     = date("Y-m-d"); // Fecha actual
    // Para este ejemplo usamos un id_usuario fijo (en producción se obtiene de la sesión)
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
                header("Location: crear_publicacion.php?msg=" . urlencode("Error al subir la imagen.") . "&msgType=error");
                exit();
            }
        }
        header("Location: crear_publicacion.php?msg=" . urlencode("Publicación creada exitosamente.") . "&msgType=success");
        exit();
    } else {
        header("Location: crear_publicacion.php?msg=" . urlencode("Error al crear la publicación: " . $stmt->error) . "&msgType=error");
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
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Voces del Proceso</title>
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

  <main class="main">
    <h1>Crear Nueva Publicación</h1>
    <form action="crear_publicacion.php" method="POST" enctype="multipart/form-data">
      <label for="titulo">Título:</label>
      <input type="text" name="titulo" id="titulo" required>

      <label for="categoria">Categoría:</label>
      <input type="text" name="categoria" id="categoria" required>

      <label for="contenido">Contenido:</label>
      <textarea name="contenido" id="contenido" rows="10" required></textarea>

      <label for="imagen">Imagen / Banner:</label>
      <input type="file" name="imagen" id="imagen" accept="image/*">

      <button type="submit">Crear Publicación</button>
    </form>
  </main>

  <footer>
    <p>&copy; 2025 Voces del Proceso. Todos los derechos reservados.</p>
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
