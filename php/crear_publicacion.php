<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: registro.php?error=Debe+iniciar+sesión");
    exit();
}

include "../includes/db_config.php";

$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'es';
include "../includes/lang_{$lang}.php";

date_default_timezone_set('America/Mexico_City');

$conn = new mysqli(host, dbuser, dbpass, dbname);
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
    $id_usuario = $_SESSION['usuario']['id_usuario'];
    
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
  <link rel="stylesheet" href="../assets/css/publicar.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">
  
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- SweetAlert2 para popups -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/loggin_scripts.js" defer></script>
  <script src="../assets/js/Main.js" defer></script>
  
  <style>
    /* Estilos simples para la barra de herramientas y el editor */
    #toolbar {
      margin-bottom: 10px;
    }
    #toolbar button {
      padding: 5px 10px;
      margin-right: 5px;
      cursor: pointer;
    }
    #editor {
      border: 1px solid #ccc;
      padding: 10px;
      min-height: 200px;
    }
  </style>
  
  <script>
    // Función de formateo con capacidad de toggle para bloques (h2, p, listas)
    function format(command, value = null) {
      // Solo aplicamos el toggle para bloques 'h2', 'p', 'insertOrderedList', 'insertUnorderedList'
      if (command === 'h2' || command === 'p') {
        let sel = window.getSelection();
        if (sel.rangeCount > 0) {
          let container = sel.anchorNode;
          if (container.nodeType !== 1) {
            container = container.parentElement;
          }
          if (container.tagName.toLowerCase() === command) {
            document.execCommand('formatBlock', false, 'div');
          } else {
            document.execCommand('formatBlock', false, command);
          }
        }
      } else {
        // Para comandos como listas ordenadas/no ordenadas y otros
        document.execCommand(command, false, value);
      }
    }
    
    // Al enviar el formulario, copiamos el HTML del editor al campo oculto
    document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('publicacionForm').addEventListener('submit', function() {
        document.getElementById('contenido').value = document.getElementById('editor').innerHTML;
      });
    });
  </script>
  
</head>
<body>
  <header class="header-top">
    <div class="logo">
      <h1><a href="../index.php"><?php echo $idioma['voces_proceso']; ?></a></h1>
    </div>
    <nav class="main-nav">
      <div id="menu-button" class="menu-button">
        <img src="../assets/img/menu.svg">
        <span class="ocultar-texto"><?php echo $idioma['menu']; ?></span>
      </div>
      <div class="search-bar">
        <input type="text" placeholder="<?php echo $idioma['buscar']; ?>" />
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
      <li><a href="index.php"><?php echo $idioma['inicio']; ?></a></li>
      <li><a href="#"><?php echo $idioma['noticias']; ?></a></li>
      <li><a href="#"><?php echo $idioma['contacto']; ?></a></li>
      <li><a href="#"><?php echo $idioma['acerca_de']; ?></a></li>
    </ul>
    <button id="login-button" class="login-button"><?php echo $idioma['login']; ?></button>
  </div>

  <main class="main">
    <h1><?php echo $idioma['crear_publicacion_title']; ?></h1>
    <form id="publicacionForm" action="crear_publicacion.php" method="POST" enctype="multipart/form-data">
      <label for="titulo"><?php echo $idioma['titulo_label']; ?></label>
      <input type="text" name="titulo" id="titulo" required>

      <label for="categoria"><?php echo $idioma['categoria_label']; ?></label>
      <input type="text" name="categoria" id="categoria" required>

      <label for="contenido"><?php echo $idioma['contenido_label']; ?></label>
      
      <!-- Barra de herramientas para formateo -->
      <div id="toolbar">
        <button type="button" onclick="format('h2')">Subtítulo</button>
        <button type="button" onclick="format('p')">Texto Cuerpo</button>
        <button type="button" onclick="format('bold')">Negrita</button>
        <button type="button" onclick="format('italic')">Cursiva</button>
        <button type="button" onclick="format('underline')">Subrayado</button>
        <button type="button" onclick="format('insertOrderedList')">Lista Ordenada</button>
        <button type="button" onclick="format('insertUnorderedList')">Lista No Ordenada</button>
      </div>
      
      <!-- Editor de texto editable -->
      <div id="editor" contenteditable="true"></div>
      
      <!-- Campo oculto para enviar el contenido formateado -->
      <textarea name="contenido" id="contenido" style="display:none;"></textarea>

      <label for="imagen"><?php echo $idioma['imagen_label']; ?></label>
      <input type="file" name="imagen" id="imagen" accept="image/*">

      <button type="submit"><?php echo $idioma['crear_button']; ?></button>
    </form>
  </main>

  <footer>
    <p><?php echo $idioma['footer_text']; ?></p>
    <div class="social-icons">
      <img src="../assets/img/facebook.svg" alt="Facebook">
      <img src="../assets/img/instagram.svg" alt="Instagram">
    </div>
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