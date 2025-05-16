<?php
session_start();

//Uso de Cookie
require_once '../includes/auth.php';

if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] !== 'admin') {
    header("Location: registro.php?error=Acceso+denegado");
    exit();
}

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

// Manejar acciones de usuarios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion']) && isset($_POST['id_usuario'])) {
        $id_usuario = intval($_POST['id_usuario']);
        $id_usuario_actual = $_SESSION['usuario']['id_usuario'];

        // Evitar que un usuario se elimine o se quite admin a sí mismo
        if ($id_usuario === $id_usuario_actual) {
            header("Location: gestor_usuarios.php?error=No+puedes+realizar+esta+acción+en+tu+propia+cuenta");
            exit();
        }

        if ($_POST['accion'] === 'hacer_admin') {
            $stmt = $conn->prepare("UPDATE usuarios SET perfil = 'admin' WHERE id_usuario = ?");
        } elseif ($_POST['accion'] === 'quitar_admin') {
            $stmt = $conn->prepare("UPDATE usuarios SET perfil = 'usuario' WHERE id_usuario = ?");
        } elseif ($_POST['accion'] === 'eliminar_usuario') {
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
        }
        if (isset($stmt)) {
            $stmt->bind_param("i", $id_usuario);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Manejar acciones de publicaciones
    if (isset($_POST['accion']) && isset($_POST['id_entrada'])) {
        $id_entrada = intval($_POST['id_entrada']);
        if ($_POST['accion'] === 'eliminar_publicacion') {
            $stmt = $conn->prepare("DELETE FROM entradas WHERE id_entrada = ?");
            $stmt->bind_param("i", $id_entrada);
            $stmt->execute();
            $stmt->close();
            
            // Si se está eliminando desde la vista de usuario, redirigir allí
            if (isset($_POST['vista_usuario']) && isset($_POST['id_usuario_vista'])) {
                $id_usuario_vista = intval($_POST['id_usuario_vista']);
                header("Location: gestor_usuarios.php?ver_usuario=" . $id_usuario_vista);
                exit();
            }
        }
    }
    
    // Manejar acciones de comentarios
    if (isset($_POST['accion']) && isset($_POST['id_comentario'])) {
        $id_comentario = intval($_POST['id_comentario']);
        if ($_POST['accion'] === 'eliminar_comentario') {
            $stmt = $conn->prepare("DELETE FROM comentarios WHERE id_comentario = ?");
            $stmt->bind_param("i", $id_comentario);
            $stmt->execute();
            $stmt->close();
            
            // Redirigir de vuelta a la vista de usuario
            if (isset($_POST['id_usuario_vista'])) {
                $id_usuario_vista = intval($_POST['id_usuario_vista']);
                header("Location: gestor_usuarios.php?ver_usuario=" . $id_usuario_vista . "#tab-usuario-comentarios");
                exit();
            }
        }
    }
}

// Manejar selección de usuario para ver publicaciones y comentarios
$publicacionesUsuario = [];
$comentariosUsuario = [];
$nombreUsuarioSeleccionado = '';
if (isset($_GET['ver_usuario'])) {
    $id_usuario_seleccionado = intval($_GET['ver_usuario']);
    $stmt = $conn->prepare("SELECT nombre FROM usuarios WHERE id_usuario = ?");
    $stmt->bind_param("i", $id_usuario_seleccionado);
    $stmt->execute();
    $stmt->bind_result($nombreUsuarioSeleccionado);
    $stmt->fetch();
    $stmt->close();

    // Obtener publicaciones del usuario seleccionado con categorías
    $stmtPublicaciones = $conn->prepare("SELECT e.id_entrada, e.titulo, c.categoria AS nombre_categoria, e.fecha 
                                        FROM entradas e 
                                        LEFT JOIN categorias c ON e.categoria = c.id_categoria
                                        WHERE e.id_usuario = ?");
    $stmtPublicaciones->bind_param("i", $id_usuario_seleccionado);
    $stmtPublicaciones->execute();
    $resultPublicacionesUsuario = $stmtPublicaciones->get_result();
    $publicacionesUsuario = $resultPublicacionesUsuario->fetch_all(MYSQLI_ASSOC);
    $stmtPublicaciones->close();

    // Obtener comentarios del usuario seleccionado
    $stmtComentarios = $conn->prepare("SELECT c.id_comentario, c.descripcion AS contenido, c.fecha, e.titulo AS publicacion, e.id_entrada 
                                       FROM comentarios c 
                                       JOIN entradas e ON c.id_entrada = e.id_entrada 
                                       WHERE c.id_usuario = ?");
    if (!$stmtComentarios) {
        die("Error en la consulta de comentarios: " . $conn->error);
    }
    $stmtComentarios->bind_param("i", $id_usuario_seleccionado);
    $stmtComentarios->execute();
    $resultComentariosUsuario = $stmtComentarios->get_result();
    $comentariosUsuario = $resultComentariosUsuario->fetch_all(MYSQLI_ASSOC);
    $stmtComentarios->close();
}

// Obtener usuarios (excluyendo al usuario actual)
$id_usuario_actual = $_SESSION['usuario']['id_usuario'];
$stmt = $conn->prepare("SELECT id_usuario, nombre, correo, perfil FROM usuarios WHERE id_usuario != ?");
$stmt->bind_param("i", $id_usuario_actual);
$stmt->execute();
$resultUsuarios = $stmt->get_result();
$stmt->close();

// Obtener publicaciones con categorías
$resultPublicaciones = $conn->query("SELECT e.id_entrada, e.titulo, c.categoria AS nombre_categoria, e.fecha 
                                     FROM entradas e
                                     LEFT JOIN categorias c ON e.categoria = c.id_categoria
                                     ORDER BY e.id_entrada DESC");

// Función para determinar si usar vista móvil basada en el ancho de pantalla
function agregarScriptDeteccionMovil() {
    return "
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function aplicarVistaTablasMoviles() {
            if (window.innerWidth <= 480) {
                document.querySelectorAll('.admin-table').forEach(table => {
                    table.classList.add('admin-table-mobile-view');
                    
                    // Añadir atributos data-label a cada celda td basado en el encabezado
                    table.querySelectorAll('tbody tr').forEach(row => {
                        const headerCells = row.closest('table').querySelectorAll('thead th');
                        const dataCells = row.querySelectorAll('td');
                        
                        dataCells.forEach((cell, index) => {
                            if (headerCells[index]) {
                                cell.setAttribute('data-label', headerCells[index].textContent);
                            }
                        });
                    });
                });
            } else {
                document.querySelectorAll('.admin-table').forEach(table => {
                    table.classList.remove('admin-table-mobile-view');
                });
            }
        }
        
        // Aplicar al cargar
        aplicarVistaTablasMoviles();
        
        // Aplicar al cambiar el tamaño de la ventana
        window.addEventListener('resize', aplicarVistaTablasMoviles);
    });
    </script>
    ";
}
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translator->__("Gestor de Admin") ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <?= agregarScriptDeteccionMovil() ?>
    <script>
        // Función mejorada para cambiar pestañas
        function cambiarPestaña(pestaña) {
            console.log("Cambiando a pestaña: ", pestaña);
            
            // Mostrar la sección seleccionada primero para evitar problemas de renderizado
            const tabContent = document.getElementById(pestaña);
            if (tabContent) {
                // Hacemos visible el contenido primero
                tabContent.style.display = 'block';
                
                // Luego ocultamos los demás
                document.querySelectorAll('.tab-content').forEach(content => {
                    if (content.id !== pestaña) {
                        content.style.display = 'none';
                    }
                });
                
                // Aseguramos que la animación se aplica correctamente
                setTimeout(() => {
                    tabContent.classList.add('active');
                    document.querySelectorAll('.tab-content').forEach(content => {
                        if (content.id !== pestaña) {
                            content.classList.remove('active');
                        }
                    });
                }, 10);
            } else {
                console.error("Elemento con ID '" + pestaña + "' no encontrado");
            }
            
            // Actualizar clases activas en las pestañas
            document.querySelectorAll('.admin-tab').forEach(tab => {
                tab.classList.remove('active');
                if (tab.getAttribute('data-tab') === pestaña) {
                    tab.classList.add('active');
                }
            });
            
            // Guardar la pestaña seleccionada en sessionStorage
            sessionStorage.setItem('adminTab', pestaña);
        }
        
        function cambiarPestañaUsuario(pestaña) {
            console.log("Cambiando a pestaña de usuario: ", pestaña);
            
            // Mostrar la sección de usuario seleccionada
            const tabContent = document.getElementById(pestaña);
            if (tabContent) {
                // Hacemos visible el contenido primero
                tabContent.style.display = 'block';
                
                // Luego ocultamos los demás
                document.querySelectorAll('.tab-usuario-content').forEach(content => {
                    if (content.id !== pestaña) {
                        content.style.display = 'none';
                    }
                });
            } else {
                console.error("Elemento con ID '" + pestaña + "' no encontrado");
            }
            
            // Actualizar clases activas en las pestañas
            document.querySelectorAll('.admin-tab-usuario').forEach(tab => {
                tab.classList.remove('active');
                if (tab.getAttribute('data-tab') === pestaña) {
                    tab.classList.add('active');
                }
            });
        }
        
        // Iniciar con la pestaña guardada o la primera
        document.addEventListener('DOMContentLoaded', () => {
            // Establecemos un timeout pequeño para asegurar que el DOM está completamente cargado
            setTimeout(() => {
                try {
                    const pestañaGuardada = sessionStorage.getItem('adminTab') || 'tab-usuarios';
                    cambiarPestaña(pestañaGuardada);
                    
                    // Inicializar pestañas de usuario si existen
                    if (document.querySelector('.admin-tab-usuario')) {
                        cambiarPestañaUsuario('tab-usuario-publicaciones');
                    }
                    
                    console.log("Pestañas inicializadas correctamente");
                } catch (error) {
                    console.error("Error al inicializar pestañas:", error);
                    // Fallback: asegurarse de que al menos algo es visible
                    if (document.getElementById('tab-usuarios')) {
                        document.getElementById('tab-usuarios').style.display = 'block';
                    }
                }
            }, 100);
            
            // Agregar confirmación para eliminar usuario
            document.querySelectorAll('button[value="eliminar_usuario"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.form;
                    const userId = form.querySelector('input[name="id_usuario"]').value;
                    
                    // Obtener el nombre del usuario
                    const row = this.closest('tr');
                    const userName = row.querySelector('td:nth-child(2)').textContent;
                    
                    Swal.fire({
                        title: '<?= $translator->__("¿Estás seguro?") ?>',
                        html: `<?= $translator->__("¿Realmente deseas eliminar al usuario") ?> <strong>${userName}</strong> (ID: ${userId})?<br><?= $translator->__("Esta acción no se puede deshacer.") ?>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: '<?= $translator->__("Sí, eliminar") ?>',
                        cancelButtonText: '<?= $translator->__("Cancelar") ?>'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
            
            // Agregar confirmación para eliminar publicación
            document.querySelectorAll('button[value="eliminar_publicacion"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.form;
                    const postId = form.querySelector('input[name="id_entrada"]').value;
                    
                    // Obtener el título de la publicación
                    const row = this.closest('tr');
                    const postTitle = row.querySelector('td:nth-child(2)').textContent;
                    
                    Swal.fire({
                        title: '<?= $translator->__("¿Estás seguro?") ?>',
                        html: `<?= $translator->__("¿Realmente deseas eliminar la publicación") ?> <strong>${postTitle}</strong> (ID: ${postId})?<br><?= $translator->__("Esta acción no se puede deshacer.") ?>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: '<?= $translator->__("Sí, eliminar") ?>',
                        cancelButtonText: '<?= $translator->__("Cancelar") ?>'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
            
            // Agregar confirmación para eliminar comentario
            document.querySelectorAll('button[value="eliminar_comentario"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const form = this.form;
                    const commentId = form.querySelector('input[name="id_comentario"]').value;
                    
                    // Obtener el contenido del comentario
                    const row = this.closest('tr');
                    const commentContent = row.querySelector('td:nth-child(2)').textContent;
                    
                    Swal.fire({
                        title: '<?= $translator->__("¿Estás seguro?") ?>',
                        html: `<?= $translator->__("¿Realmente deseas eliminar el comentario") ?> <strong>${commentContent}</strong> (ID: ${commentId})?<br><?= $translator->__("Esta acción no se puede deshacer.") ?>`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: '<?= $translator->__("Sí, eliminar") ?>',
                        cancelButtonText: '<?= $translator->__("Cancelar") ?>'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
            
            // Agregar alerta antes de editar
            document.querySelectorAll('a[href*="editar_publicacion.php"]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const postId = new URL(this.href).searchParams.get('id');
                    const postTitle = this.closest('tr').querySelector('td:nth-child(2)').textContent;
                    
                    Swal.fire({
                        title: '<?= $translator->__("Editar publicación") ?>',
                        html: `<?= $translator->__("Vas a editar la publicación") ?> <strong>${postTitle}</strong> (ID: ${postId})`,
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: '<?= $translator->__("Continuar") ?>',
                        cancelButtonText: '<?= $translator->__("Cancelar") ?>'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = this.href;
                        }
                    });
                });
            });
        });
    </script>
    <style>
        /* Corrección para los estilos de pestañas */
        .tab-content {
            display: none; /* Oculto por defecto */
        }
        
        .tab-content.active {
            display: block; /* Visible cuando está activo */
            opacity: 1 !important; /* Forzar visibilidad */
            transform: translateY(0) !important; /* Asegurar posición correcta */
        }
        
        /* Corrección para asegurar que la animación no deja el contenido invisible */
        @keyframes tabFadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Añadir indicadores visuales para depuración si es necesario */
        .debug-outline {
            border: 2px solid red !important;
        }
    </style>
</head>
<body>
    <?php include "../includes/header.php"; ?>
    
    <div class="admin-container">
        <h1><?= $translator->__("Gestor de Administración") ?></h1>
        
        <div class="admin-tabs">
            <button class="admin-tab active" data-tab="tab-usuarios" onclick="cambiarPestaña('tab-usuarios')">
                <i class="fas fa-users"></i> <?= $translator->__("Usuarios") ?>
            </button>
            <button class="admin-tab" data-tab="tab-publicaciones" onclick="cambiarPestaña('tab-publicaciones')">
                <i class="fas fa-newspaper"></i> <?= $translator->__("Publicaciones") ?>
            </button>
            <a href="crear_publicacion.php" class="admin-tab crear-publicacion">
                <i class="fas fa-plus-circle"></i> <?= $translator->__("Crear") ?>
            </a>
            <a href="gestor_categorias.php" class="admin-tab">
                <i class="fas fa-tags"></i> <?= $translator->__("Categorías") ?>
            </a>
        </div>

        <div id="tab-usuarios" class="tab-content">
            <?php if (isset($_GET['ver_usuario']) && !empty($nombreUsuarioSeleccionado)): ?>
                <section class="admin-section">
                    <div class="usuario-header">
                        <h2><?= $translator->__("Usuario") ?>: <?= htmlspecialchars($nombreUsuarioSeleccionado) ?></h2>
                        <a href="gestor_usuarios.php" class="admin-button">
                            <i class="fas fa-arrow-left"></i> <?= $translator->__("Volver a la lista") ?>
                        </a>
                    </div>
                    
                    <div class="admin-tabs-usuario">
                        <button class="admin-tab-usuario active" data-tab="tab-usuario-publicaciones" 
                                onclick="cambiarPestañaUsuario('tab-usuario-publicaciones')">
                            <i class="fas fa-newspaper"></i> <?= $translator->__("Publicaciones") ?> (<?= count($publicacionesUsuario) ?>)
                        </button>
                        <button class="admin-tab-usuario" data-tab="tab-usuario-comentarios" 
                                onclick="cambiarPestañaUsuario('tab-usuario-comentarios')">
                            <i class="fas fa-comments"></i> <?= $translator->__("Comentarios") ?> (<?= count($comentariosUsuario) ?>)
                        </button>
                    </div>
                    
                    <div id="tab-usuario-publicaciones" class="tab-usuario-content">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th><?= $translator->__("Título") ?></th>
                                    <th><?= $translator->__("Categoría") ?></th>
                                    <th><?= $translator->__("Fecha") ?></th>
                                    <th><?= $translator->__("Acciones") ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($publicacionesUsuario)): ?>
                                    <tr>
                                        <td colspan="5" class="no-data"><?= $translator->__("Este usuario no tiene publicaciones") ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($publicacionesUsuario as $publicacion): ?>
                                    <tr>
                                        <td><?= $publicacion['id_entrada'] ?></td>
                                        <td><?= htmlspecialchars($publicacion['titulo']) ?></td>
                                        <td><?= htmlspecialchars($publicacion['nombre_categoria']) ?></td>
                                        <td><?= $publicacion['fecha'] ?></td>
                                        <td>
                                            <a class="admin-link" href="../php/publicacion.php?id=<?= $publicacion['id_entrada'] ?>" target="_blank">
                                                <i class="fas fa-external-link-alt"></i> <?= $translator->__("Visitar") ?>
                                            </a>
                                            <a class="admin-link" href="editar_publicacion.php?id=<?= $publicacion['id_entrada'] ?>">
                                                <i class="fas fa-edit"></i> <?= $translator->__("Editar") ?>
                                            </a>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="id_entrada" value="<?= $publicacion['id_entrada'] ?>">
                                                <input type="hidden" name="vista_usuario" value="1">
                                                <input type="hidden" name="id_usuario_vista" value="<?= $id_usuario_seleccionado ?>">
                                                <button class="admin-button" type="submit" name="accion" value="eliminar_publicacion">
                                                    <i class="fas fa-trash-alt"></i> <?= $translator->__("Eliminar") ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="tab-usuario-comentarios" class="tab-usuario-content" style="display: none;">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th><?= $translator->__("Contenido") ?></th>
                                    <th><?= $translator->__("Fecha") ?></th>
                                    <th><?= $translator->__("Publicación") ?></th>
                                    <th><?= $translator->__("Acciones") ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($comentariosUsuario)): ?>
                                    <tr>
                                        <td colspan="5" class="no-data"><?= $translator->__("Este usuario no tiene comentarios") ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($comentariosUsuario as $comentario): ?>
                                    <tr>
                                        <td><?= $comentario['id_comentario'] ?></td>
                                        <td><?= htmlspecialchars($comentario['contenido']) ?></td>
                                        <td><?= $comentario['fecha'] ?></td>
                                        <td><?= htmlspecialchars($comentario['publicacion']) ?></td>
                                        <td>
                                            <a class="admin-link" href="../php/publicacion.php?id=<?= $comentario['id_entrada'] ?>#comentario-<?= $comentario['id_comentario'] ?>" target="_blank">
                                                <i class="fas fa-external-link-alt"></i> <?= $translator->__("Visitar") ?>
                                            </a>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="id_comentario" value="<?= $comentario['id_comentario'] ?>">
                                                <input type="hidden" name="id_usuario_vista" value="<?= $id_usuario_seleccionado ?>">
                                                <button class="admin-button" type="submit" name="accion" value="eliminar_comentario">
                                                    <i class="fas fa-trash-alt"></i> <?= $translator->__("Eliminar") ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php else: ?>
                <section class="admin-section">
                    <h2><?= $translator->__("Usuarios") ?></h2>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><?= $translator->__("Nombre") ?></th>
                                <th><?= $translator->__("Correo") ?></th>
                                <th><?= $translator->__("Perfil") ?></th>
                                <th><?= $translator->__("Acciones") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($usuario = $resultUsuarios->fetch_assoc()): ?>
                            <tr>
                                <td><?= $usuario['id_usuario'] ?></td>
                                <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                                <td><?= htmlspecialchars($usuario['correo']) ?></td>
                                <td><?= htmlspecialchars($usuario['perfil']) ?></td>
                                <td>
                                    <a class="admin-link" href="?ver_usuario=<?= $usuario['id_usuario'] ?>">
                                        <i class="fas fa-eye"></i> <?= $translator->__("Ver Publicaciones y Comentarios") ?>
                                    </a>
                                    <?php if ($usuario['perfil'] !== 'admin'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                                        <button class="admin-button" type="submit" name="accion" value="hacer_admin">
                                            <i class="fas fa-user-shield"></i> <?= $translator->__("Hacer Admin") ?>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                                        <button class="admin-button" type="submit" name="accion" value="quitar_admin">
                                            <i class="fas fa-user-minus"></i> <?= $translator->__("Quitar Admin") ?>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                                        <button class="admin-button" type="submit" name="accion" value="eliminar_usuario">
                                            <i class="fas fa-trash-alt"></i> <?= $translator->__("Eliminar") ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </section>
            <?php endif; ?>
        </div>

        <div id="tab-publicaciones" class="tab-content">
            <section class="admin-section">
                <h2><?= $translator->__("Publicaciones") ?></h2>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?= $translator->__("Título") ?></th>
                            <th><?= $translator->__("Categoría") ?></th>
                            <th><?= $translator->__("Fecha") ?></th>
                            <th><?= $translator->__("Acciones") ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($publicacion = $resultPublicaciones->fetch_assoc()): ?>
                        <tr>
                            <td><?= $publicacion['id_entrada'] ?></td>
                            <td><?= htmlspecialchars($publicacion['titulo']) ?></td>
                            <td><?= htmlspecialchars($publicacion['nombre_categoria']) ?></td>
                            <td><?= $publicacion['fecha'] ?></td>
                            <td>
                                <a class="admin-link" href="editar_publicacion.php?id=<?= $publicacion['id_entrada'] ?>">
                                    <i class="fas fa-edit"></i> <?= $translator->__("Editar") ?>
                                </a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id_entrada" value="<?= $publicacion['id_entrada'] ?>">
                                    <button class="admin-button" type="submit" name="accion" value="eliminar_publicacion">
                                        <i class="fas fa-trash-alt"></i> <?= $translator->__("Eliminar") ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
    
    <?php include "../includes/footer.php"; ?>
    
    <!-- Script adicional para detectar y corregir problemas de visualización -->
    <script>
        // Función para forzar visibilidad si después de 500ms nada es visible
        setTimeout(function() {
            const anyTabVisible = Array.from(document.querySelectorAll('.tab-content'))
                .some(el => el.style.display === 'block' || getComputedStyle(el).display === 'block');
            
            if (!anyTabVisible) {
                console.log("Forzando visibilidad de la pestaña por defecto");
                document.getElementById('tab-usuarios').style.display = 'block';
                document.getElementById('tab-usuarios').classList.add('active');
                document.querySelector('.admin-tab[data-tab="tab-usuarios"]').classList.add('active');
            }
        }, 500);
    </script>
</body>
</html>
<?php $conn->close(); ?>
