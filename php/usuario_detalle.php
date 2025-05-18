<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] !== 'admin') {
    header("Location: registro.php?error=Acceso+denegado");
    exit();
}

// Verificar si se ha recibido el ID de usuario
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin.php?error=Usuario+no+especificado");
    exit();
}

$id_usuario = intval($_GET['id']);

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
    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id_usuario);
    exit();
}

// Procesar eliminación de publicaciones
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_publicacion'])) {
    $id_entrada = isset($_POST['id_entrada']) ? intval($_POST['id_entrada']) : 0;
    
    if ($_POST['accion_publicacion'] == 'eliminar_publicacion') {
        // Eliminar comentarios de la entrada
        $stmt = $conn->prepare("DELETE FROM comentarios WHERE id_entrada = ?");
        $stmt->bind_param("i", $id_entrada);
        $stmt->execute();
        $stmt->close();
        
        // Eliminar imágenes de la entrada
        $stmt = $conn->prepare("DELETE FROM imagenes WHERE id_entrada = ?");
        $stmt->bind_param("i", $id_entrada);
        $stmt->execute();
        $stmt->close();
        
        // Eliminar la entrada
        $stmt = $conn->prepare("DELETE FROM entradas WHERE id_entrada = ? AND id_usuario = ?");
        $stmt->bind_param("ii", $id_entrada, $id_usuario);
        if ($stmt->execute()) {
            $mensaje = $translator->__("Publicación eliminada correctamente.");
            $tipo_mensaje = "success";
        } else {
            $mensaje = $translator->__("Error al eliminar la publicación.") . " " . $conn->error;
            $tipo_mensaje = "error";
        }
        $stmt->close();
    }
}

// Procesar eliminación de comentarios
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_comentario'])) {
    $id_comentario = isset($_POST['id_comentario']) ? intval($_POST['id_comentario']) : 0;
    
    if ($_POST['accion_comentario'] == 'eliminar_comentario') {
        $stmt = $conn->prepare("DELETE FROM comentarios WHERE id_comentario = ? AND id_usuario = ?");
        $stmt->bind_param("ii", $id_comentario, $id_usuario);
        if ($stmt->execute()) {
            $mensaje = $translator->__("Comentario eliminado correctamente.");
            $tipo_mensaje = "success";
        } else {
            $mensaje = $translator->__("Error al eliminar el comentario.") . " " . $conn->error;
            $tipo_mensaje = "error";
        }
        $stmt->close();
    }
}

// Obtener información del usuario
$usuario = null;
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
} else {
    header("Location: admin.php?error=Usuario+no+encontrado");
    exit();
}
$stmt->close();

// Obtener publicaciones del usuario
$publicaciones = [];
$stmt = $conn->prepare("SELECT e.*, 
                      (SELECT COUNT(*) FROM comentarios WHERE id_entrada = e.id_entrada) as num_comentarios, 
                      (SELECT imagen FROM imagenes WHERE id_entrada = e.id_entrada LIMIT 1) as imagen,
                      c.categoria as nombre_categoria 
                      FROM entradas e 
                      LEFT JOIN categorias c ON e.categoria = c.id_categoria 
                      WHERE e.id_usuario = ? 
                      ORDER BY e.fecha DESC");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $publicaciones[] = $row;
}
$stmt->close();

// Obtener comentarios del usuario
$comentarios = [];
$stmt = $conn->prepare("SELECT c.*, e.titulo as titulo_entrada 
                       FROM comentarios c 
                       LEFT JOIN entradas e ON c.id_entrada = e.id_entrada 
                       WHERE c.id_usuario = ? 
                       ORDER BY c.fecha DESC");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $comentarios[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translator->__("Detalles del Usuario") ?> - <?= htmlspecialchars($usuario['nombre']) ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        function confirmarEliminarPublicacion(id, titulo) {
            Swal.fire({
                title: '<?= $translator->__("¿Estás seguro?") ?>',
                html: `<?= $translator->__("¿Realmente deseas eliminar la publicación") ?> <strong>${titulo}</strong>?<br>
                       <?= $translator->__("Esta acción eliminará también todos los comentarios asociados.") ?><br>
                       <?= $translator->__("Esta acción no se puede deshacer.") ?>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<?= $translator->__("Sí, eliminar") ?>',
                cancelButtonText: '<?= $translator->__("Cancelar") ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form-eliminar-publicacion-' + id).submit();
                }
            });
        }
        
        function confirmarEliminarComentario(id) {
            Swal.fire({
                title: '<?= $translator->__("¿Estás seguro?") ?>',
                html: `<?= $translator->__("¿Realmente deseas eliminar este comentario?") ?><br>
                       <?= $translator->__("Esta acción no se puede deshacer.") ?>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<?= $translator->__("Sí, eliminar") ?>',
                cancelButtonText: '<?= $translator->__("Cancelar") ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form-eliminar-comentario-' + id).submit();
                }
            });
        }
        
        $(document).ready(function() {
            // Cambiar entre pestañas
            $('.admin-tab-usuario').click(function() {
                var tab = $(this).data('tab');
                
                $('.admin-tab-usuario').removeClass('active');
                $(this).addClass('active');
                $('.tab-usuario-content').hide();
                $('#' + tab).fadeIn();
                
                return false;
            });
            
            // Mostrar la primera pestaña por defecto
            $('.admin-tab-usuario:first').click();
            
            // Mostrar mensaje si existe
            <?php if (isset($mensaje) && isset($tipo_mensaje)): ?>
            Swal.fire({
                icon: '<?= $tipo_mensaje ?>',
                title: '<?= $mensaje ?>',
                showConfirmButton: true
            });
            <?php endif; ?>
        });
    </script>
</head>
<body>
    <?php include "../includes/header.php"; ?>
    
    <div class="admin-container">
        <h1><?= $translator->__("Detalles del Usuario") ?></h1>
        
        <div class="admin-section">
            <div class="usuario-header">
                <h2>
                    <i class="fas fa-user"></i> 
                    <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['primer_apellido'] . ' ' . $usuario['segundo_apellido']) ?>
                    
                    <?php if ($usuario['perfil'] == 'admin'): ?>
                        <span class="admin-badge admin-badge-primary">
                            <i class="fas fa-user-shield"></i> <?= $translator->__("Administrador") ?>
                        </span>
                    <?php endif; ?>
                </h2>
                
                <a href="admin.php?tab=usuarios" class="admin-button">
                    <i class="fas fa-arrow-left"></i> <?= $translator->__("Volver a Usuarios") ?>
                </a>
            </div>
            
            <div class="usuario-info">
                <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px; flex-wrap: wrap;">
                    <?php if (!empty($usuario['imagen'])): ?>
                        <img src="../<?= htmlspecialchars($usuario['imagen']) ?>" alt="<?= $translator->__("Foto de perfil") ?>" 
                             style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; box-shadow: 0 3px 8px rgba(0,0,0,0.15);">
                    <?php else: ?>
                        <div style="width: 100px; height: 100px; border-radius: 50%; background-color: #e0e0e0; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user" style="font-size: 40px; color: #999;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <p><strong><?= $translator->__("Correo") ?>:</strong> <?= htmlspecialchars($usuario['correo']) ?></p>
                        <p><strong><?= $translator->__("Rol") ?>:</strong> 
                            <?php if ($usuario['perfil'] == 'admin'): ?>
                                <span style="color: #3498db; font-weight: bold;">
                                    <i class="fas fa-user-shield"></i> <?= $translator->__("Administrador") ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #2ecc71;">
                                    <i class="fas fa-user"></i> <?= $translator->__("Cliente") ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Pestañas para navegar entre publicaciones y comentarios -->
            <div class="admin-tabs-usuario">
                <a href="#" class="admin-tab-usuario active" data-tab="publicaciones-usuario">
                    <i class="fas fa-newspaper"></i> <?= $translator->__("Publicaciones") ?> 
                    <span class="admin-badge admin-badge-success"><?= count($publicaciones) ?></span>
                </a>
                <a href="#" class="admin-tab-usuario" data-tab="comentarios-usuario">
                    <i class="fas fa-comments"></i> <?= $translator->__("Comentarios") ?> 
                    <span class="admin-badge admin-badge-warning"><?= count($comentarios) ?></span>
                </a>
            </div>
            
            <!-- Contenido de pestañas -->
            
            <!-- Publicaciones del usuario -->
            <div id="publicaciones-usuario" class="tab-usuario-content">
                <?php if (empty($publicaciones)): ?>
                    <p class="no-data">
                        <i class="fas fa-file-alt"></i>
                        <?= $translator->__("Este usuario no ha realizado ninguna publicación.") ?>
                    </p>
                <?php else: ?>
                    <table class="admin-table admin-table-mobile-view">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?= $translator->__("Título") ?></th>
                                <th><?= $translator->__("Categoría") ?></th>
                                <th><?= $translator->__("Fecha") ?></th>
                                <th><?= $translator->__("Comentarios") ?></th>
                                <th><?= $translator->__("Acciones") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($publicaciones as $publicacion): ?>
                            <tr>
                                <td data-label="ID"><?= $publicacion['id_entrada'] ?></td>
                                <td data-label="<?= $translator->__("Título") ?>">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <?php if (!empty($publicacion['imagen'])): ?>
                                            <img src="<?= htmlspecialchars($publicacion['imagen']) ?>" alt="<?= $translator->__("Imagen de publicación") ?>" 
                                                 style="width: 40px; height: 40px; border-radius: 5px; object-fit: cover;">
                                        <?php endif; ?>
                                        <span><?= htmlspecialchars($publicacion['titulo']) ?></span>
                                    </div>
                                </td>
                                <td data-label="<?= $translator->__("Categoría") ?>">
                                    <?= htmlspecialchars($publicacion['nombre_categoria']) ?>
                                </td>
                                <td data-label="<?= $translator->__("Fecha") ?>"><?= $publicacion['fecha'] ?></td>
                                <td data-label="<?= $translator->__("Comentarios") ?>"><?= $publicacion['num_comentarios'] ?></td>
                                <td data-label="<?= $translator->__("Acciones") ?>">
                                    <div class="acciones-container">
                                        <!-- Ver publicación -->
                                        <a href="publicacion.php?id=<?= $publicacion['id_entrada'] ?>" class="admin-button" target="_blank">
                                            <i class="fas fa-eye"></i> <?= $translator->__("Ver") ?>
                                        </a>
                                        
                                        <!-- Editar publicación -->
                                        <a href="editar_publicacion.php?id=<?= $publicacion['id_entrada'] ?>" class="admin-button">
                                            <i class="fas fa-edit"></i> <?= $translator->__("Editar") ?>
                                        </a>
                                        
                                        <!-- Eliminar publicación -->
                                        <form id="form-eliminar-publicacion-<?= $publicacion['id_entrada'] ?>" method="POST" style="display:inline;">
                                            <input type="hidden" name="accion_publicacion" value="eliminar_publicacion">
                                            <input type="hidden" name="id_entrada" value="<?= $publicacion['id_entrada'] ?>">
                                            <button type="button" class="admin-button" onclick="confirmarEliminarPublicacion(<?= $publicacion['id_entrada'] ?>, '<?= htmlspecialchars($publicacion['titulo'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-trash-alt"></i> <?= $translator->__("Eliminar") ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Comentarios del usuario -->
            <div id="comentarios-usuario" class="tab-usuario-content" style="display: none;">
                <?php if (empty($comentarios)): ?>
                    <p class="no-data">
                        <i class="fas fa-comment-slash"></i>
                        <?= $translator->__("Este usuario no ha realizado ningún comentario.") ?>
                    </p>
                <?php else: ?>
                    <table class="admin-table admin-table-mobile-view">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?= $translator->__("Publicación") ?></th>
                                <th><?= $translator->__("Comentario") ?></th>
                                <th><?= $translator->__("Fecha") ?></th>
                                <th><?= $translator->__("Acciones") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comentarios as $comentario): ?>
                            <tr>
                                <td data-label="ID"><?= $comentario['id_comentario'] ?></td>
                                <td data-label="<?= $translator->__("Publicación") ?>">
                                    <a href="publicacion.php?id=<?= $comentario['id_entrada'] ?>" target="_blank">
                                        <?= htmlspecialchars($comentario['titulo_entrada']) ?>
                                    </a>
                                </td>
                                <td data-label="<?= $translator->__("Comentario") ?>">
                                    <?php 
                                    $texto = htmlspecialchars($comentario['descripcion']);
                                    echo (strlen($texto) > 100) ? substr($texto, 0, 100) . '...' : $texto;
                                    ?>
                                </td>
                                <td data-label="<?= $translator->__("Fecha") ?>">
                                    <?= date('d/m/Y H:i', strtotime($comentario['fecha'])) ?>
                                </td>
                                <td data-label="<?= $translator->__("Acciones") ?>">
                                    <div class="acciones-container">
                                        <!-- Ver publicación -->
                                        <a href="publicacion.php?id=<?= $comentario['id_entrada'] ?>" class="admin-button" target="_blank">
                                            <i class="fas fa-eye"></i> <?= $translator->__("Ver Publicación") ?>
                                        </a>
                                        
                                        <!-- Eliminar comentario -->
                                        <form id="form-eliminar-comentario-<?= $comentario['id_comentario'] ?>" method="POST" style="display:inline;">
                                            <input type="hidden" name="accion_comentario" value="eliminar_comentario">
                                            <input type="hidden" name="id_comentario" value="<?= $comentario['id_comentario'] ?>">
                                            <button type="button" class="admin-button" onclick="confirmarEliminarComentario(<?= $comentario['id_comentario'] ?>)">
                                                <i class="fas fa-trash-alt"></i> <?= $translator->__("Eliminar") ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include "../includes/footer.php"; ?>
</body>
</html>
