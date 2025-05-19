<?php
session_start();

//Uso de Cookie
require_once '../includes/auth.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] !== 'admin') {
    header("Location: registro.php?error=Acceso+denegado");
    exit();
}

// Verificar si el idioma está configurado en la sesión, si no, establecer un idioma predeterminado
if (!isset($_SESSION['idioma'])) {
    $_SESSION['idioma'] = 'es'; // Idioma predeterminado
}
$idiomaActual = $_SESSION['idioma']; // Guardar el idioma actual
include "../includes/db_config.php";

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host;
    $folder = dirname($_SERVER['PHP_SELF']);
    return $baseUrl . substr($folder, 0, strrpos($folder, '/'));
}

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

// Obtener categorías disponibles
$categorias = [];
$resultCats = $conn->query("SELECT id_categoria, categoria FROM categorias ORDER BY categoria");
if ($resultCats) {
    while ($cat = $resultCats->fetch_assoc()) {
        $categorias[] = $cat;
    }
}

// Verificar que se haya proporcionado un ID de entrada para editar
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_panel.php?msg=" . urlencode("ID de publicación no válido.") . "&msgType=error");
    exit();
}

$id_entrada = intval($_GET['id']);

// Obtener los datos actuales de la publicación
$publicacion = null;

$stmt = $conn->prepare("SELECT e.*, i.imagen, i.id_imagen FROM entradas e 
                        LEFT JOIN imagenes i ON e.id_entrada = i.id_entrada 
                        WHERE e.id_entrada = ?");
if ($stmt) {
    $stmt->bind_param("i", $id_entrada);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $publicacion = $result->fetch_assoc();
        } else {
            header("Location: admin_panel.php?msg=" . urlencode("Publicación no encontrada.") . "&msgType=error");
            exit();
        }
    } else {
        header("Location: admin_panel.php?msg=" . urlencode("Error al obtener datos de la publicación.") . "&msgType=error");
        exit();
    }
    $stmt->close();
} else {
    header("Location: admin_panel.php?msg=" . urlencode("Error en la consulta.") . "&msgType=error");
    exit();
}

// Procesa el formulario al enviarlo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo'])) {
    // Recoger y sanitizar datos
    $titulo    = trim($_POST['titulo']);
    $categoria = intval($_POST['categoria']); 
    $contenido = trim($_POST['contenido']);
    $cita = isset($_POST['cita']) ? trim($_POST['cita']) : '';
    // Convertir el contenido a UTF-8
    $contenido = mb_convert_encoding($contenido, 'UTF-8', 'auto');
    
    // Actualiza la publicación en la tabla entradas
    $stmt = $conn->prepare("UPDATE entradas SET titulo = ?, categoria = ?, contenido = ?, cita = ? WHERE id_entrada = ?");
    if ($stmt) {
        $stmt->bind_param("sissi", $titulo, $categoria, $contenido, $cita, $id_entrada);
        if ($stmt->execute()) {
            
            // Procesa la imagen si se sube alguna nueva
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $destino = "uploads/";
                if (!is_dir($destino)) {
                    mkdir($destino, 0777, true);
                }
                
                $nombreArchivo = time() . "_" . basename($_FILES['imagen']['name']);
                $rutaDestino   = $destino . $nombreArchivo;
                
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                    // Si ya existe una imagen para esta entrada, actualizar el registro
                    if ($publicacion['id_imagen']) {
                        $stmtImg = $conn->prepare("UPDATE imagenes SET imagen = ? WHERE id_entrada = ?");
                        if ($stmtImg) {
                            $stmtImg->bind_param("si", $rutaDestino, $id_entrada);
                            if (!$stmtImg->execute()) {
                                error_log("Error al actualizar la imagen: " . $stmtImg->error);
                            }
                            $stmtImg->close();
                        }
                    } else {
                        // Si no existe, insertar un nuevo registro
                        $stmtImg = $conn->prepare("INSERT INTO imagenes (imagen, id_entrada) VALUES (?, ?)");
                        if ($stmtImg) {
                            $stmtImg->bind_param("si", $rutaDestino, $id_entrada);
                            if (!$stmtImg->execute()) {
                                error_log("Error al insertar la imagen: " . $stmtImg->error);
                            }
                            $stmtImg->close();
                        }
                    }
                } else {
                    header("Location: editar_publicacion.php?id={$id_entrada}&msg=" . urlencode("Error al subir la imagen.") . "&msgType=error");
                    exit();
                }
            }
            header("Location: editar_publicacion.php?id={$id_entrada}&msg=" . urlencode("Publicación actualizada exitosamente.") . "&msgType=success");
            exit();
        } else {
            header("Location: editar_publicacion.php?id={$id_entrada}&msg=" . urlencode("Error al actualizar la publicación: " . $stmt->error) . "&msgType=error");
            exit();
        }
        $stmt->close();
    } else {
        error_log("Error en la preparación de la consulta UPDATE entradas: " . $conn->error);
        header("Location: editar_publicacion.php?id={$id_entrada}&msg=" . urlencode("Error al preparar la consulta para actualizar la publicación.") . "&msgType=error");
        exit();
    }
}

$message = "";
$messageType = "";
if(isset($_GET['msg'])){
    $message = $_GET['msg'];
    $messageType = isset($_GET['msgType']) ? $_GET['msgType'] : "success";
}
?>

<!DOCTYPE html>
<html lang="<?= isset($_SESSION['idioma']) ? $_SESSION['idioma'] : 'es' ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>POALCE - Editar Publicación</title>
  <link rel="stylesheet" href="../assets/css/publicar.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/loggin_scripts.js" defer></script>
  <link rel="icon" href="/Pagina-web-PI/assets/img/POALCE-logo.ico" type="image/x-icon">
  
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
    
    // Función para insertar imagen en el editor
    function insertarImagenEnEditor() {
      // Guardar referencia al elemento actual con foco
      const activeElement = document.activeElement;
      
      // Crear un input de tipo file invisible
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.style.display = 'none';
      document.body.appendChild(input);
      
      // Cuando el usuario selecciona un archivo
      input.addEventListener('change', function() {
        if (input.files && input.files[0]) {
          const formData = new FormData();
          formData.append('image', input.files[0]);
          
          // Mostrar indicador de carga sin usar aria-hidden
          const loadingContainer = document.createElement('div');
          loadingContainer.innerHTML = '<div style="text-align:center;padding:20px;"><i class="fas fa-spinner fa-spin fa-3x"></i><p><?= $translator->__("Subiendo imagen...") ?></p></div>';
          document.body.appendChild(loadingContainer);
          
          // Subir imagen al servidor
          fetch('upload_inline_image.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(result => {
            // Eliminar indicador de carga
            document.body.removeChild(loadingContainer);
            
            if (result.success) {
              // Restaurar el foco al editor explícitamente
              const editor = document.getElementById('editor');
              editor.focus();
              
              // Insertar la imagen en el editor en la posición del cursor
              const img = document.createElement('img');
              img.src = result.file.url;
              img.alt = result.file.name;
              img.className = 'editor-inline-image'; // Añadir clase para estilos CSS
              img.style.maxWidth = '500px'; // Limitar ancho máximo
              img.style.maxHeight = '300px'; // Limitar altura máxima
              img.style.height = 'auto'; // Mantener proporción
              img.style.marginTop = '10px';
              img.style.marginBottom = '10px';
              
              // Insertar en la posición del cursor
              const selection = window.getSelection();
              if (selection.rangeCount) {
                const range = selection.getRangeAt(0);
                range.deleteContents();
                range.insertNode(img);
                
                // Mover el cursor después de la imagen
                range.setStartAfter(img);
                range.setEndAfter(img);
                selection.removeAllRanges();
                selection.addRange(range);
              }
            } else {
              alert('<?= $translator->__("Error al subir la imagen") ?>: ' + (result.message || ''));
            }
          })
          .catch(error => {
            // Eliminar indicador de carga en caso de error
            if (document.body.contains(loadingContainer)) {
              document.body.removeChild(loadingContainer);
            }
            
            alert('<?= $translator->__("Ha ocurrido un error al procesar la imagen") ?>');
            console.error('Error:', error);
          });
        }
        
        // Restaurar el foco al elemento original si es posible
        if (activeElement && typeof activeElement.focus === 'function') {
          activeElement.focus();
        }
        
        // Eliminar el input después de usarlo
        document.body.removeChild(input);
      });
      
      // Activar el diálogo de selección de archivo
      input.click();
    }
    
    // Mostrar el nombre del archivo seleccionado y previsualizar la imagen
    function mostrarNombreArchivo() {
      var input = document.getElementById('imagen');
      var nombre = input.files.length > 0 ? input.files[0].name : '';
      document.getElementById('nombre-archivo').textContent = nombre;
      
      // Previsualizar la imagen
      if (input.files && input.files[0]) {
        var reader = new FileReader();
        
        reader.onload = function(e) {
          // Comprobar si ya existe la previsualización
          var imgPreview = document.getElementById('image-preview');
          if (!imgPreview) {
            // Crear el contenedor de previsualización si no existe
            imgPreview = document.createElement('div');
            imgPreview.id = 'image-preview';
            imgPreview.style.marginTop = '10px';
            imgPreview.style.marginBottom = '20px';
            imgPreview.style.textAlign = 'center';
            
            // Insertar después del span nombre-archivo
            var nombreArchivo = document.getElementById('nombre-archivo');
            nombreArchivo.parentNode.insertBefore(imgPreview, nombreArchivo.nextSibling);
          }
          
          // Crear o actualizar la imagen
          var img = imgPreview.querySelector('img') || document.createElement('img');
          img.src = e.target.result;
          img.style.maxWidth = '100%';
          img.style.maxHeight = '300px';
          img.style.borderRadius = '8px';
          img.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
          
          // Añadir la imagen al contenedor si es nueva
          if (!imgPreview.querySelector('img')) {
            imgPreview.appendChild(img);
          }
        };
        
        reader.readAsDataURL(input.files[0]);
      } else {
        // Eliminar previsualización si no hay archivo
        var imgPreview = document.getElementById('image-preview');
        if (imgPreview) {
          imgPreview.remove();
        }
      }
    }
    
    // Función para contar palabras en el contenido del editor
    function contarPalabras(texto) {
      // Eliminar HTML tags y contar palabras
      const textoPlano = texto.replace(/<[^>]*>/g, ' ');
      const palabras = textoPlano.split(/\s+/).filter(function(palabra) {
        return palabra.length > 0;
      });
      return palabras.length;
    }
    
    // Confirmar antes de enviar el formulario y cargar contenido inicial
    document.addEventListener('DOMContentLoaded', function() {
      // Cargar el contenido actual en el editor
      var editor = document.getElementById('editor');
      editor.innerHTML = <?= json_encode($publicacion['contenido']) ?>;
      
      // Mostrar previsualización de la imagen actual si existe
      if ('<?= $publicacion['imagen'] ?>' !== '') {
        var imgPreview = document.createElement('div');
        imgPreview.id = 'image-preview-actual';
        imgPreview.style.marginTop = '10px';
        imgPreview.style.marginBottom = '20px';
        imgPreview.style.textAlign = 'center';
        
        var img = document.createElement('img');
        img.src = '<?= $publicacion['imagen'] ?>';
        img.style.maxWidth = '100%';
        img.style.maxHeight = '300px';
        img.style.borderRadius = '8px';
        img.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
        
        imgPreview.appendChild(img);
        
        // Insertar después del span imagen-actual
        var imagenActual = document.getElementById('imagen-actual');
        if (imagenActual) {
          imagenActual.appendChild(imgPreview);
        }
      }
      
      // Confirmar antes de enviar el formulario con validaciones
      document.getElementById('publicacionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const contenidoHTML = document.getElementById('editor').innerHTML;
        const numeroPalabras = contarPalabras(contenidoHTML);
        
        if (numeroPalabras < 10) {
          Swal.fire({
            title: '<?= $translator->__("Contenido insuficiente") ?>',
            text: '<?= $translator->__("El contenido debe tener al menos 10 palabras") ?>',
            icon: 'error',
            confirmButtonColor: '#719743'
          });
          return;
        }
        
        // Validar que haya una imagen (actual o nueva)
        const imagenInput = document.getElementById('imagen');
        const imagenActual = '<?= $publicacion['imagen'] ?>';
        
        if (!imagenActual && imagenInput.files.length === 0) {
          Swal.fire({
            title: '<?= $translator->__("Imagen requerida") ?>',
            text: '<?= $translator->__("Debe subir una imagen para la publicación") ?>',
            icon: 'error',
            confirmButtonColor: '#719743'
          });
          return;
        }
        
        document.getElementById('contenido').value = contenidoHTML;
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
    });
  </script>
  <style>
    /* Estilos adicionales para la previsualización */
    #image-preview, #image-preview-actual {
      transition: all 0.3s ease;
    }
    #image-preview img, #image-preview-actual img {
      transition: transform 0.3s ease;
    }
    #image-preview img:hover, #image-preview-actual img:hover {
      transform: scale(1.03);
    }
    
    /* Estilos para imágenes dentro del editor */
    #editor img.editor-inline-image {
      max-width: 500px;
      max-height: 300px;
      height: auto;
      display: block;
      margin: 10px auto;
      border-radius: 5px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    /* Para pantallas pequeñas */
    @media (max-width: 768px) {
      #editor img.editor-inline-image {
        max-width: 100%;
        max-height: 250px;
      }
    }
    
    /* Estilo para los campos requeridos */
    .required {
      color: #f00;
    }
  </style>
</head>
<body>
  <?php include '../includes/header.php'; ?>

  <main class="main">
    <h1><?= $translator->__("Editar Publicación") ?></h1>
    <form id="publicacionForm" action="editar_publicacion.php?id=<?= $id_entrada ?>" method="POST" enctype="multipart/form-data">
      <label for="titulo"><?= $translator->__("Título:") ?></label>
      <input type="text" name="titulo" id="titulo" value="<?= htmlspecialchars($publicacion['titulo']) ?>" required>

      <label for="categoria"><?= $translator->__("Categoría:") ?></label>
      <select name="categoria" id="categoria" required>
        <option value=""><?= $translator->__("Seleccione una categoría") ?></option>
        <?php foreach ($categorias as $cat): ?>
        <option value="<?= $cat['id_categoria'] ?>" <?= ($publicacion['categoria'] == $cat['id_categoria']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['categoria']) ?></option>
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
      <div id="editor" contenteditable="true"></div>
      
      <!-- Campo oculto para enviar el contenido formateado -->
      <textarea name="contenido" id="contenido" style="display:none;"></textarea>

      <label for="imagen"><?= $translator->__("Imagen") ?> / Banner <?= $translator->__("(dejar vacío para mantener la actual)") ?>:</label>
      <label for="imagen" class="custom-file-upload">
        <?= $translator->__("Seleccionar archivo") ?>
      </label>
      <input type="file" name="imagen" id="imagen" accept="image/*" onchange="mostrarNombreArchivo()">
      <span id="nombre-archivo" style="margin-left:10px;"></span>
      
      <?php if ($publicacion['imagen']): ?>
        <div id="imagen-actual">
          <p><?= $translator->__("Imagen actual") ?>: <?= basename($publicacion['imagen']) ?></p>
        </div>
      <?php endif; ?>

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
