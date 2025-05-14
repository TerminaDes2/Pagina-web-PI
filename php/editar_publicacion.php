<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] !== 'admin') {
    header("Location: registro.php?error=Acceso+denegado");
    exit();
}

if (!isset($_SESSION['idioma'])) {
    $_SESSION['idioma'] = 'es'; // Idioma predeterminado
}

if (!isset($_GET['id'])) {
    header("Location: lista_publicaciones.php?msg=" . urlencode("ID de publicación no proporcionado.") . "&msgType=error");
    exit();
}

$id_publicacion = intval($_GET['id']);
include "../includes/db_config.php";

$conn = new mysqli(host, dbuser, dbpass, dbname);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

require_once '../includes/traductor.php';
$translator = new Translator($conn);

// Manejar cambio de idioma
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['idioma'])) {
    $translator->cambiarIdioma($_POST['idioma']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Obtener categorías disponibles
$categorias = [];
$resultCats = $conn->query("SELECT id_categoria, categoria FROM categorias ORDER BY categoria");
if ($resultCats) {
    while ($cat = $resultCats->fetch_assoc()) {
        $categorias[] = $cat;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo    = trim($_POST['titulo']);
    $categoria = intval($_POST['categoria']); // Ahora es un ID de categoría
    $contenido = trim($_POST['contenido']);
    $cita = isset($_POST['cita']) ? trim($_POST['cita']) : '';
    $contenido = mb_convert_encoding($contenido, 'UTF-8', 'auto');

    $stmt = $conn->prepare("UPDATE entradas SET titulo = ?, categoria = ?, contenido = ?, cita = ? WHERE id_entrada = ?");
    if ($stmt) {
        $stmt->bind_param("sissi", $titulo, $categoria, $contenido, $cita, $id_publicacion);
        if ($stmt->execute()) {
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $destino = "uploads/";
                if (!is_dir($destino)) {
                    mkdir($destino, 0777, true);
                }

                $nombreArchivo = time() . "_" . basename($_FILES['imagen']['name']);
                $rutaDestino   = $destino . $nombreArchivo;

                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                    $stmtImg = $conn->prepare("UPDATE imagenes SET imagen = ? WHERE id_entrada = ?");
                    if ($stmtImg) {
                        $stmtImg->bind_param("si", $rutaDestino, $id_publicacion);
                        $stmtImg->execute();
                        $stmtImg->close();
                    }
                }
            }
            header("Location: editar_publicacion.php?id=$id_publicacion&msg=" . urlencode("Publicación actualizada exitosamente.") . "&msgType=success");
            exit();
        } else {
            header("Location: editar_publicacion.php?id=$id_publicacion&msg=" . urlencode("Error al actualizar la publicación.") . "&msgType=error");
            exit();
        }
        $stmt->close();
    }
}

$stmt = $conn->prepare("SELECT e.titulo, e.categoria, e.contenido, e.cita, i.imagen FROM entradas e LEFT JOIN imagenes i ON e.id_entrada = i.id_entrada WHERE e.id_entrada = ?");
$stmt->bind_param("i", $id_publicacion);
$stmt->execute();
$result = $stmt->get_result();
$publicacion = $result->fetch_assoc();
$stmt->close();

if (!$publicacion) {
    header("Location: lista_publicaciones.php?msg=" . urlencode("Publicación no encontrada.") . "&msgType=error");
    exit();
}

$message = "";
$messageType = "";
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = isset($_GET['msgType']) ? $_GET['msgType'] : "success";
}
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $translator->__("Editar Publicación") ?></title>
  <link rel="stylesheet" href="../assets/css/publicar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    function format(command, value = null) {
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
        document.execCommand(command, false, value);
      }
    }

    // Nueva función para insertar imagen en el editor
    function insertarImagenEnEditor() {
      const fileInput = document.createElement('input');
      fileInput.type = 'file';
      fileInput.accept = 'image/*';
      fileInput.style.display = 'none';
      document.body.appendChild(fileInput);
      
      fileInput.addEventListener('change', function() {
        if (fileInput.files && fileInput.files[0]) {
          const formData = new FormData();
          formData.append('imagen', fileInput.files[0]);
          
          // Mostrar indicador de carga
          Swal.fire({
            title: '<?= $translator->__("Subiendo imagen...") ?>',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });
          
          // Subir la imagen al servidor
          fetch('upload_image_inline.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            Swal.close();
            
            if (data.success) {
              // Insertar la imagen en el contenido
              const imgHtml = `<img src="${data.url}" alt="${data.filename}" style="max-width:100%; margin:10px 0;">`;
              document.execCommand('insertHTML', false, imgHtml);
            } else {
              Swal.fire({
                icon: 'error',
                title: '<?= $translator->__("Error") ?>',
                text: data.error || '<?= $translator->__("Error al subir la imagen") ?>'
              });
            }
          })
          .catch(error => {
            Swal.close();
            Swal.fire({
              icon: 'error',
              title: '<?= $translator->__("Error") ?>',
              text: '<?= $translator->__("Hubo un problema al subir la imagen") ?>'
            });
            console.error('Error:', error);
          });
        }
        
        // Eliminar el input una vez usado
        document.body.removeChild(fileInput);
      });
      
      fileInput.click();
    }

    document.addEventListener('DOMContentLoaded', function() {
      const editor = document.getElementById('editor');
      const contenido = document.getElementById('contenido');

      // Al enviar el formulario, copiar el contenido con etiquetas al campo oculto
      document.getElementById('publicacionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        contenido.value = editor.innerHTML;
        
        const titulo = document.getElementById('titulo').value;
        
        Swal.fire({
          title: '<?= $translator->__("Confirmar actualización") ?>',
          html: `<?= $translator->__("¿Estás seguro de actualizar la publicación") ?> <strong>${titulo}</strong>?`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#719743',
          cancelButtonColor: '#d33',
          confirmButtonText: '<?= $translator->__("Sí, actualizar") ?>',
          cancelButtonText: '<?= $translator->__("Cancelar") ?>'
        }).then((result) => {
          if (result.isConfirmed) {
            this.submit();
          }
        });
      });

      // Crear contenedor de previsualización si hay imagen actual
      var nombreArchivo = document.getElementById('nombre-archivo');
      if (nombreArchivo && nombreArchivo.textContent.trim() !== '') {
        var imagenActual = '<?= !empty($publicacion['imagen']) ? $publicacion['imagen'] : '' ?>';
        if (imagenActual) {
          var imgPreview = document.createElement('div');
          imgPreview.id = 'image-preview';
          imgPreview.style.marginTop = '10px';
          imgPreview.style.marginBottom = '20px';
          imgPreview.style.textAlign = 'center';
          
          var img = document.createElement('img');
          img.src = imagenActual;
          img.style.maxWidth = '100%';
          img.style.maxHeight = '300px';
          img.style.borderRadius = '8px';
          img.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
          
          imgPreview.appendChild(img);
          nombreArchivo.parentNode.insertBefore(imgPreview, nombreArchivo.nextSibling);
        }
      }
    });

    function mostrarNombreArchivo() {
      var input = document.getElementById('imagen');
      var nombre = input.files.length > 0 ? input.files[0].name : '';
      document.getElementById('nombre-archivo').textContent = nombre;
      
      // Previsualizar la imagen
      if (input.files && input.files[0]) {
        var reader = new FileReader();
        
        reader.onload = function(e) {
          // Actualizar la previsualización
          var imgPreview = document.getElementById('image-preview');
          imgPreview.querySelector('img').src = e.target.result;
          imgPreview.style.display = 'block';
        };
        
        reader.readAsDataURL(input.files[0]);
      }
    }
  </script>
  <style>
    /* Estilos adicionales para la previsualización */
    #image-preview {
      transition: all 0.3s ease;
    }
    #image-preview img {
      transition: transform 0.3s ease;
    }
    #image-preview img:hover {
      transform: scale(1.03);
    }
  </style>
</head>
<body>
  <?php include '../includes/header.php'; ?>

  <main class="main">
    <h1><?= $translator->__("Editar Publicación") ?></h1>
    <form id="publicacionForm" action="editar_publicacion.php?id=<?= $id_publicacion ?>" method="POST" enctype="multipart/form-data">
      <label for="titulo"><?= $translator->__("Título:") ?></label>
      <input type="text" name="titulo" id="titulo" value="<?= htmlspecialchars($publicacion['titulo']) ?>" required>

      <label for="categoria"><?= $translator->__("Categoría:") ?></label>
      <select name="categoria" id="categoria" required>
        <?php foreach ($categorias as $cat): ?>
        <option value="<?= $cat['id_categoria'] ?>" <?= $cat['id_categoria'] == $publicacion['categoria'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($cat['categoria']) ?>
        </option>
        <?php endforeach; ?>
      </select>

      <label for="cita"><?= $translator->__("Cita:") ?></label>
      <input type="text" name="cita" id="cita" value="<?= htmlspecialchars($publicacion['cita']) ?>">

      <label for="contenido"><?= $translator->__("Contenido:") ?></label>
      
      <!-- Barra de herramientas para formateo -->
      <div id="toolbar">
        <button type="button" onclick="format('h2')" title="<?= $translator->__("Subtítulo") ?>">
          <i class="fas fa-heading"></i>
        </button>
        <button type="button" onclick="format('p')" title="<?= $translator->__("Texto Cuerpo") ?>">
          <i class="fas fa-paragraph"></i>
        </button>
        <button type="button" onclick="format('bold')" title="<?= $translator->__("Negrita") ?>">
          <i class="fas fa-bold"></i>
        </button>
        <button type="button" onclick="format('italic')" title="<?= $translator->__("Cursiva") ?>">
          <i class="fas fa-italic"></i>
        </button>
        <button type="button" onclick="format('underline')" title="<?= $translator->__("Subrayado") ?>">
          <i class="fas fa-underline"></i>
        </button>
        <button type="button" onclick="format('insertOrderedList')" title="<?= $translator->__("Lista Ordenada") ?>">
          <i class="fas fa-list-ol"></i>
        </button>
        <button type="button" onclick="format('insertUnorderedList')" title="<?= $translator->__("Lista No Ordenada") ?>">
          <i class="fas fa-list-ul"></i>
        </button>
        <button type="button" onclick="insertarImagenEnEditor()" title="<?= $translator->__("Insertar Imagen") ?>">
          <i class="fas fa-image"></i> <span><?= $translator->__("Insertar Imagen") ?></span>
        </button>
      </div>
      
      <!-- Editor de texto editable -->
      <div id="editor" contenteditable="true"><?= $publicacion['contenido'] ?></div>
      
      <!-- Campo oculto para enviar el contenido formateado -->
      <textarea name="contenido" id="contenido" style="display:none;"><?= $publicacion['contenido'] ?></textarea>

      <label for="imagen"><?= $translator->__("Imagen") ?>:</label>
      <label for="imagen" class="custom-file-upload">
        <?= $translator->__("Seleccionar archivo") ?>
      </label>
      <input type="file" name="imagen" id="imagen" accept="image/*" onchange="mostrarNombreArchivo()">
      <span id="nombre-archivo" style="margin-left:10px;"><?= basename($publicacion['imagen']) ?></span>

      <button type="submit"><?= $translator->__("Actualizar Publicación") ?></button>
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
