<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] !== 'admin') {
    header("Location: registro.php?error=Acceso+denegado");
    exit();
}

// Verificar si el idioma está configurado en la sesión
if (!isset($_SESSION['idioma'])) {
    $_SESSION['idioma'] = 'es'; // Idioma predeterminado
}

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

// Procesar acciones sobre usuarios
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $id_usuario = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
    
    // No permitir acciones sobre el propio usuario
    if ($id_usuario == $_SESSION['usuario']['id_usuario']) {
        $mensaje = $translator->__("No puedes realizar esta acción sobre tu propia cuenta.");
        $tipo_mensaje = "warning";
    } else {
        switch ($_POST['accion']) {
            case 'eliminar_usuario':
                // Primero eliminar comentarios del usuario
                $stmt = $conn->prepare("DELETE FROM comentarios WHERE id_usuario = ?");
                $stmt->bind_param("i", $id_usuario);
                $stmt->execute();
                $stmt->close();
                
                // Obtener IDs de las entradas del usuario
                $entradas_ids = [];
                $stmt = $conn->prepare("SELECT id_entrada FROM entradas WHERE id_usuario = ?");
                $stmt->bind_param("i", $id_usuario);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $entradas_ids[] = $row['id_entrada'];
                }
                $stmt->close();
                
                // Eliminar comentarios en las entradas del usuario
                if (!empty($entradas_ids)) {
                    $placeholders = implode(',', array_fill(0, count($entradas_ids), '?'));
                    $stmt = $conn->prepare("DELETE FROM comentarios WHERE id_entrada IN ($placeholders)");
                    $types = str_repeat('i', count($entradas_ids));
                    $stmt->bind_param($types, ...$entradas_ids);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Eliminar imágenes de las entradas
                    $stmt = $conn->prepare("DELETE FROM imagenes WHERE id_entrada IN ($placeholders)");
                    $stmt->bind_param($types, ...$entradas_ids);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Eliminar las entradas
                    $stmt = $conn->prepare("DELETE FROM entradas WHERE id_usuario = ?");
                    $stmt->bind_param("i", $id_usuario);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Finalmente eliminar el usuario
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
                $stmt->bind_param("i", $id_usuario);
                if ($stmt->execute()) {
                    $mensaje = $translator->__("Usuario eliminado correctamente.");
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = $translator->__("Error al eliminar el usuario.") . " " . $conn->error;
                    $tipo_mensaje = "error";
                }
                $stmt->close();
                break;
                
            case 'hacer_admin':
                $stmt = $conn->prepare("UPDATE usuarios SET perfil = 'admin' WHERE id_usuario = ?");
                $stmt->bind_param("i", $id_usuario);
                if ($stmt->execute()) {
                    $mensaje = $translator->__("Usuario promovido a administrador correctamente.");
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = $translator->__("Error al promover al usuario.") . " " . $conn->error;
                    $tipo_mensaje = "error";
                }
                $stmt->close();
                break;
                
            case 'quitar_admin':
                $stmt = $conn->prepare("UPDATE usuarios SET perfil = 'cliente' WHERE id_usuario = ?");
                $stmt->bind_param("i", $id_usuario);
                if ($stmt->execute()) {
                    $mensaje = $translator->__("Permisos de administrador revocados correctamente.");
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = $translator->__("Error al revocar permisos de administrador.") . " " . $conn->error;
                    $tipo_mensaje = "error";
                }
                $stmt->close();
                break;
        }
    }
}

// Procesar acciones sobre publicaciones
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_publicacion'])) {
    $id_entrada = isset($_POST['id_entrada']) ? intval($_POST['id_entrada']) : 0;
    
    switch ($_POST['accion_publicacion']) {
        case 'eliminar_publicacion':
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
            $stmt = $conn->prepare("DELETE FROM entradas WHERE id_entrada = ?");
            $stmt->bind_param("i", $id_entrada);
            if ($stmt->execute()) {
                $mensaje = $translator->__("Publicación eliminada correctamente.");
                $tipo_mensaje = "success";
            } else {
                $mensaje = $translator->__("Error al eliminar la publicación.") . " " . $conn->error;
                $tipo_mensaje = "error";
            }
            $stmt->close();
            break;
    }
}

// Procesar acciones sobre comentarios
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion_comentario'])) {
    $id_comentario = isset($_POST['id_comentario']) ? intval($_POST['id_comentario']) : 0;
    
    switch ($_POST['accion_comentario']) {
        case 'eliminar_comentario':
            $stmt = $conn->prepare("DELETE FROM comentarios WHERE id_comentario = ?");
            $stmt->bind_param("i", $id_comentario);
            if ($stmt->execute()) {
                $mensaje = $translator->__("Comentario eliminado correctamente.");
                $tipo_mensaje = "success";
            } else {
                $mensaje = $translator->__("Error al eliminar el comentario.") . " " . $conn->error;
                $tipo_mensaje = "error";
            }
            $stmt->close();
            break;
    }
}

// Pestaña activa por defecto
$tab_activa = isset($_GET['tab']) ? $_GET['tab'] : 'usuarios';

// Obtener todos los usuarios excepto el actual
$usuarios = [];
$id_usuario_actual = $_SESSION['usuario']['id_usuario'];
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id_usuario != ? ORDER BY nombre");
$stmt->bind_param("i", $id_usuario_actual);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}
$stmt->close();

// Obtener todas las publicaciones
$publicaciones = [];
$result = $conn->query("SELECT e.*, u.nombre, u.primer_apellido, u.segundo_apellido, c.categoria as nombre_categoria, 
                        (SELECT COUNT(*) FROM comentarios WHERE id_entrada = e.id_entrada) as num_comentarios, 
                        (SELECT imagen FROM imagenes WHERE id_entrada = e.id_entrada LIMIT 1) as imagen 
                        FROM entradas e 
                        LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario 
                        LEFT JOIN categorias c ON e.categoria = c.id_categoria 
                        ORDER BY e.fecha DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $publicaciones[] = $row;
    }
}

// Obtener todos los comentarios
$comentarios = [];
$result = $conn->query("SELECT c.*, u.nombre, u.primer_apellido, u.segundo_apellido, e.titulo as titulo_entrada 
                        FROM comentarios c 
                        LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario 
                        LEFT JOIN entradas e ON c.id_entrada = e.id_entrada 
                        ORDER BY c.fecha DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $comentarios[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="<?= isset($_SESSION['idioma']) ? $_SESSION['idioma'] : 'es' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translator->__("Panel de Administración") ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" href="/Pagina-web-PI/assets/img/Poalce-logo.png" type="image/x-icon">
    <script>
        function confirmarEliminarUsuario(id, nombre) {
            Swal.fire({
                title: '<?= $translator->__("¿Estás seguro?") ?>',
                html: `<?= $translator->__("¿Realmente deseas eliminar al usuario") ?> <strong>${nombre}</strong>?<br>
                       <?= $translator->__("Esta acción eliminará todas sus publicaciones y comentarios.") ?><br>
                       <?= $translator->__("Esta acción no se puede deshacer.") ?>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<?= $translator->__("Sí, eliminar") ?>',
                cancelButtonText: '<?= $translator->__("Cancelar") ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form-eliminar-usuario-' + id).submit();
                }
            });
        }
        
        function confirmarHacerAdmin(id, nombre) {
            Swal.fire({
                title: '<?= $translator->__("Confirmar acción") ?>',
                html: `<?= $translator->__("¿Deseas convertir a") ?> <strong>${nombre}</strong> <?= $translator->__("en administrador?") ?><br>
                       <?= $translator->__("Esto le dará acceso completo al panel de administración.") ?>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '<?= $translator->__("Sí, convertir en admin") ?>',
                cancelButtonText: '<?= $translator->__("Cancelar") ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form-hacer-admin-' + id).submit();
                }
            });
        }
        
        function confirmarQuitarAdmin(id, nombre) {
            Swal.fire({
                title: '<?= $translator->__("Confirmar acción") ?>',
                html: `<?= $translator->__("¿Deseas revocar los permisos de administrador de") ?> <strong>${nombre}</strong>?<br>
                       <?= $translator->__("Ya no tendrá acceso al panel de administración.") ?>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ff9800',
                cancelButtonColor: '#d33',
                confirmButtonText: '<?= $translator->__("Sí, revocar permisos") ?>',
                cancelButtonText: '<?= $translator->__("Cancelar") ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('form-quitar-admin-' + id).submit();
                }
            });
        }
        
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
            // Cambiar entre pestañas - Solo para elementos con data-tab
            $('.admin-tab').click(function() {
                // Si el elemento tiene un atributo href que no es "#", permitir la navegación normal
                if ($(this).attr('href') && $(this).attr('href') !== '#' && !$(this).data('tab')) {
                    return true; // Permitir la navegación normal
                }
                
                var tab = $(this).data('tab');
                if (!tab) return true; // Si no tiene data-tab, permitir navegación normal
                
                $('.admin-tab').removeClass('active');
                $(this).addClass('active');
                $('.tab-content').removeClass('active');
                $('#' + tab).addClass('active');
                
                // Actualizar URL sin recargar la página
                history.replaceState(null, null, '?tab=' + tab);
                
                return false;
            });
            
            // Mostrar mensaje si existe
            <?php if (isset($mensaje) && isset($tipo_mensaje)): ?>
            Swal.fire({
                icon: '<?= $tipo_mensaje ?>',
                title: '<?= $mensaje ?>',
                showConfirmButton: true
            });
            <?php endif; ?>
            
            // Botón para volver arriba
            $(window).scroll(function() {
                if ($(this).scrollTop() > 300) {
                    $('.admin-top-btn').addClass('visible');
                } else {
                    $('.admin-top-btn').removeClass('visible');
                }
            });
            
            $('.admin-top-btn').click(function() {
                $('html, body').animate({scrollTop: 0}, 500);
                return false;
            });
        });
    </script>
</head>
<body>
    <?php include "../includes/header.php"; ?>
    
    <div class="admin-container">
        <h1><?= $translator->__("Panel de Administración") ?></h1>
        
        <!-- Pestañas de navegación -->
        <div class="admin-tabs">
            <a href="#" class="admin-tab <?= $tab_activa == 'usuarios' ? 'active' : '' ?>" data-tab="usuarios">
                <i class="fas fa-users"></i> <?= $translator->__("Gestión de Usuarios") ?>
            </a>
            <a href="#" class="admin-tab <?= $tab_activa == 'publicaciones' ? 'active' : '' ?>" data-tab="publicaciones">
                <i class="fas fa-newspaper"></i> <?= $translator->__("Publicaciones") ?>
            </a>
            <a href="#" class="admin-tab <?= $tab_activa == 'comentarios' ? 'active' : '' ?>" data-tab="comentarios">
                <i class="fas fa-comments"></i> <?= $translator->__("Comentarios") ?>
            </a>
            <a href="crear_publicacion.php" class="admin-tab crear-publicacion">
                <i class="fas fa-plus-circle"></i> <?= $translator->__("Crear Publicación") ?>
            </a>
            <a href="gestor_categorias.php" class="admin-tab">
                <i class="fas fa-tags"></i> <?= $translator->__("Gestionar Categorías") ?>
            </a>
        </div>
        
        <!-- Contenido de las pestañas -->
        
        <!-- Pestaña de Usuarios -->
        <section id="usuarios" class="tab-content <?= $tab_activa == 'usuarios' ? 'active' : '' ?>">
            <div class="admin-section">
                <h2><i class="fas fa-users"></i> <?= $translator->__("Usuarios Registrados") ?></h2>
                
                <?php if (empty($usuarios)): ?>
                    <p class="no-data">
                        <i class="fas fa-user-slash"></i>
                        <?= $translator->__("No hay usuarios registrados.") ?>
                    </p>
                <?php else: ?>
                    <table class="admin-table admin-table-mobile-view">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?= $translator->__("Nombre") ?></th>
                                <th><?= $translator->__("Correo") ?></th>
                                <th><?= $translator->__("Rol") ?></th>
                                <th><?= $translator->__("Publicaciones") ?></th>
                                <th><?= $translator->__("Comentarios") ?></th>
                                <th><?= $translator->__("Acciones") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): 
                                // Contar publicaciones del usuario
                                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM entradas WHERE id_usuario = ?");
                                $stmt->bind_param("i", $usuario['id_usuario']);
                                $stmt->execute();
                                $result_pub = $stmt->get_result();
                                $num_publicaciones = $result_pub->fetch_assoc()['total'];
                                $stmt->close();
                                
                                // Contar comentarios del usuario
                                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM comentarios WHERE id_usuario = ?");
                                $stmt->bind_param("i", $usuario['id_usuario']);
                                $stmt->execute();
                                $result_com = $stmt->get_result();
                                $num_comentarios = $result_com->fetch_assoc()['total'];
                                $stmt->close();
                            ?>
                            <tr>
                                <td data-label="ID"><?= $usuario['id_usuario'] ?></td>
                                <td data-label="<?= $translator->__("Nombre") ?>">
                                    <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['primer_apellido'] . ' ' . $usuario['segundo_apellido']) ?>
                                    <?php if (!empty($usuario['imagen'])): ?>
                                        <img src="../<?= htmlspecialchars($usuario['imagen']) ?>" alt="<?= $translator->__("Foto de perfil") ?>" 
                                             style="width: 30px; height: 30px; border-radius: 50%; margin-left: 10px; object-fit: cover;">
                                    <?php endif; ?>
                                </td>
                                <td data-label="<?= $translator->__("Correo") ?>"><?= htmlspecialchars($usuario['correo']) ?></td>
                                <td data-label="<?= $translator->__("Rol") ?>">
                                    <?php if ($usuario['perfil'] == 'admin'): ?>
                                        <span style="color: #3498db; font-weight: bold;">
                                            <i class="fas fa-user-shield"></i> <?= $translator->__("Administrador") ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #2ecc71;">
                                            <i class="fas fa-user"></i> <?= $translator->__("Cliente") ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="<?= $translator->__("Publicaciones") ?>"><?= $num_publicaciones ?></td>
                                <td data-label="<?= $translator->__("Comentarios") ?>"><?= $num_comentarios ?></td>
                                <td data-label="<?= $translator->__("Acciones") ?>">
                                    <div class="acciones-container">
                                        <!-- Ver detalles de usuario -->
                                        <a href="usuario_detalle.php?id=<?= $usuario['id_usuario'] ?>" class="admin-button">
                                            <i class="fas fa-user-cog"></i> <?= $translator->__("Ver Detalles") ?>
                                        </a>
                                        
                                        <!-- Formulario para eliminar usuario -->
                                        <form id="form-eliminar-usuario-<?= $usuario['id_usuario'] ?>" method="POST" style="display:inline;">
                                            <input type="hidden" name="accion" value="eliminar_usuario">
                                            <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                                            <button type="button" class="admin-button" onclick="confirmarEliminarUsuario(<?= $usuario['id_usuario'] ?>, '<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['primer_apellido'], ENT_QUOTES) ?>')">
                                                <i class="fas fa-trash-alt"></i> <?= $translator->__("Eliminar") ?>
                                            </button>
                                        </form>
                                        
                                        <?php if ($usuario['perfil'] == 'admin'): ?>
                                            <!-- Formulario para quitar admin -->
                                            <form id="form-quitar-admin-<?= $usuario['id_usuario'] ?>" method="POST" style="display:inline;">
                                                <input type="hidden" name="accion" value="quitar_admin">
                                                <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                                                <button type="button" class="admin-button" onclick="confirmarQuitarAdmin(<?= $usuario['id_usuario'] ?>, '<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['primer_apellido'], ENT_QUOTES) ?>')">
                                                    <i class="fas fa-user-minus"></i> <?= $translator->__("Quitar Admin") ?>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <!-- Formulario para hacer admin -->
                                            <form id="form-hacer-admin-<?= $usuario['id_usuario'] ?>" method="POST" style="display:inline;">
                                                <input type="hidden" name="accion" value="hacer_admin">
                                                <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                                                <button type="button" class="admin-button" onclick="confirmarHacerAdmin(<?= $usuario['id_usuario'] ?>, '<?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['primer_apellido'], ENT_QUOTES) ?>')">
                                                    <i class="fas fa-user-shield"></i> <?= $translator->__("Hacer Admin") ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Pestaña de Publicaciones -->
        <section id="publicaciones" class="tab-content <?= $tab_activa == 'publicaciones' ? 'active' : '' ?>">
            <div class="admin-section">
                <h2><i class="fas fa-newspaper"></i> <?= $translator->__("Todas las Publicaciones") ?></h2>
                
                <?php if (empty($publicaciones)): ?>
                    <p class="no-data">
                        <i class="fas fa-file-alt"></i>
                        <?= $translator->__("No hay publicaciones disponibles.") ?>
                    </p>
                <?php else: ?>
                    <table class="admin-table admin-table-mobile-view">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?= $translator->__("Título") ?></th>
                                <th><?= $translator->__("Autor") ?></th>
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
                                <td data-label="<?= $translator->__("Autor") ?>">
                                    <?= htmlspecialchars($publicacion['nombre'] . ' ' . $publicacion['primer_apellido']) ?>
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
        </section>
        
        <!-- Pestaña de Comentarios -->
        <section id="comentarios" class="tab-content <?= $tab_activa == 'comentarios' ? 'active' : '' ?>">
            <div class="admin-section">
                <h2><i class="fas fa-comments"></i> <?= $translator->__("Todos los Comentarios") ?></h2>
                
                <?php if (empty($comentarios)): ?>
                    <p class="no-data">
                        <i class="fas fa-comment-slash"></i>
                        <?= $translator->__("No hay comentarios disponibles.") ?>
                    </p>
                <?php else: ?>
                    <table class="admin-table admin-table-mobile-view">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?= $translator->__("Usuario") ?></th>
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
                                <td data-label="<?= $translator->__("Usuario") ?>">
                                    <?= htmlspecialchars($comentario['nombre'] . ' ' . $comentario['primer_apellido']) ?>
                                </td>
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
                                    <!-- Eliminar comentario -->
                                    <form id="form-eliminar-comentario-<?= $comentario['id_comentario'] ?>" method="POST" style="display:inline;">
                                        <input type="hidden" name="accion_comentario" value="eliminar_comentario">
                                        <input type="hidden" name="id_comentario" value="<?= $comentario['id_comentario'] ?>">
                                        <button type="button" class="admin-button" onclick="confirmarEliminarComentario(<?= $comentario['id_comentario'] ?>)">
                                            <i class="fas fa-trash-alt"></i> <?= $translator->__("Eliminar") ?>
                                        </button>
                                    </form>
                                    
                                    <!-- Ver publicación -->
                                    <a href="publicacion.php?id=<?= $comentario['id_entrada'] ?>" class="admin-button" target="_blank">
                                        <i class="fas fa-eye"></i> <?= $translator->__("Ver Publicación") ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </section>
    </div>
    
    <!-- Botón para volver arriba -->
    <button class="admin-top-btn">
        <i class="fas fa-arrow-up"></i>
    </button>

    <?php include "../includes/footer.php"; ?>
</body>
</html>
