<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: registro.php?error=Debe+iniciar+sesión");
    exit();
}
// Verificar si el idioma está configurado en la sesión, si no, establecer un idioma predeterminado
if (!isset($_SESSION['idioma'])) {
  $_SESSION['idioma'] = 'es'; // Idioma predeterminado
}
$idiomaActual = $_SESSION['idioma']; // Guardar el idioma actual
include "../includes/db_config.php";

date_default_timezone_set('America/Mexico_City');

$conn = new mysqli(host, dbuser, dbpass, dbname);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
// Configurar la conexión para usar UTF-8
$conn->set_charset("utf8");

// Incluir el traductor
require_once '../includes/traductor.php';
$translator = new Translator($conn);

// Manejar cambio de idioma
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['idioma'])) {
    $translator->cambiarIdioma($_POST['idioma']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

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
<html lang="<?= $_SESSION['idioma'] ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Voces del Proceso</title>
  <link rel="stylesheet" href="../assets/css/publicar.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/loggin_scripts.js" defer></script>
  <script src="../assets/js/Main.js" defer></script>
  
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

    // Mostrar el nombre del archivo seleccionado
    function mostrarNombreArchivo() {
      var input = document.getElementById('imagen');
      var nombre = input.files.length > 0 ? input.files[0].name : '';
      document.getElementById('nombre-archivo').textContent = nombre;
    }
  </script>
  
</head>
<body>
  <?php include '../includes/header.php'; ?>

  <main class="main">
    <h1><?= $translator->__("Crear Nueva Publicación") ?></h1>
    <form id="publicacionForm" action="crear_publicacion.php" method="POST" enctype="multipart/form-data">
      <label for="titulo"><?= $translator->__("Título:") ?></label>
      <input type="text" name="titulo" id="titulo" required>

      <label for="categoria"><?= $translator->__("Categoría:") ?></label>
      <input type="text" name="categoria" id="categoria" required>

      <label for="contenido"><?= $translator->__("Contenido:") ?></label>
      
      <!-- Barra de herramientas para formateo -->
      <div id="toolbar">
        <button type="button" onclick="format('h2')"><?= $translator->__("Subtítulo") ?></button>
        <button type="button" onclick="format('p')"><?= $translator->__("Texto Cuerpo") ?></button>
        <button type="button" onclick="format('bold')"><?= $translator->__("Negrita") ?></button>
        <button type="button" onclick="format('italic')"><?= $translator->__("Cursiva") ?></button>
        <button type="button" onclick="format('underline')"><?= $translator->__("Subrayado") ?></button>
        <button type="button" onclick="format('insertOrderedList')"><?= $translator->__("Lista Ordenada") ?></button>
        <button type="button" onclick="format('insertUnorderedList')"><?= $translator->__("Lista No Ordenada") ?></button>
      </div>
      
      <!-- Editor de texto editable -->
      <div id="editor" contenteditable="true"></div>
      
      <!-- Campo oculto para enviar el contenido formateado -->
      <textarea name="contenido" id="contenido" style="display:none;"></textarea>

      <label for="imagen"><?= $translator->__("Imagen") ?> / Banner:</label>
      <label for="imagen" class="custom-file-upload">
        <?= $translator->__("Seleccionar archivo") ?>
      </label>
      <input type="file" name="imagen" id="imagen" accept="image/*" onchange="mostrarNombreArchivo()">
      <span id="nombre-archivo" style="margin-left:10px;"></span>

      <button type="submit"><?= $translator->__("Crear Publicación") ?></button>
    </form>
  </main>

  <?php include '../includes/footer.php'; ?>

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
<?php $conn->close(); ?>