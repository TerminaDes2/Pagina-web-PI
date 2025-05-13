<?php
session_start();
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

    // Obtener publicaciones del usuario seleccionado
    $stmtPublicaciones = $conn->prepare("SELECT id_entrada, titulo, categoria, fecha FROM entradas WHERE id_usuario = ?");
    $stmtPublicaciones->bind_param("i", $id_usuario_seleccionado);
    $stmtPublicaciones->execute();
    $resultPublicacionesUsuario = $stmtPublicaciones->get_result();
    $publicacionesUsuario = $resultPublicacionesUsuario->fetch_all(MYSQLI_ASSOC);
    $stmtPublicaciones->close();

    // Obtener comentarios del usuario seleccionado
    $stmtComentarios = $conn->prepare("SELECT c.id_comentario, c.descripcion AS contenido, c.fecha, e.titulo AS publicacion 
                                        FROM comentarios c 
                                        JOIN entradas e ON c.id_entrada = e.id_entrada 
                                        WHERE c.id_usuario = ?");
    if (!$stmtComentarios) {
        die("Error en la consulta de comentarios: " . $conn->error); // Depuración para mostrar el error
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

// Obtener publicaciones
$resultPublicaciones = $conn->query("SELECT id_entrada, titulo, categoria, fecha FROM entradas");
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translator->__("Gestor de Admin") ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script>
        function cambiarPestaña(pestaña) {
            // Ocultar todas las secciones
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            
            // Mostrar la sección seleccionada
            document.getElementById(pestaña).style.display = 'block';
            
            // Actualizar clases activas en las pestañas
            document.querySelectorAll('.admin-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.admin-tab[data-tab="${pestaña}"]`).classList.add('active');
            
            // Guardar la pestaña seleccionada en sessionStorage
            sessionStorage.setItem('adminTab', pestaña);
        }
        
        function cambiarPestañaUsuario(pestaña) {
            // Ocultar todas las secciones
            document.querySelectorAll('.tab-usuario-content').forEach(content => {
                content.style.display = 'none';
            });
            
            // Mostrar la sección seleccionada
            document.getElementById(pestaña).style.display = 'block';
            
            // Actualizar clases activas en las pestañas
            document.querySelectorAll('.admin-tab-usuario').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.admin-tab-usuario[data-tab="${pestaña}"]`).classList.add('active');
        }
        
        // Iniciar con la pestaña guardada o la primera
        document.addEventListener('DOMContentLoaded', () => {
            const pestañaGuardada = sessionStorage.getItem('adminTab') || 'tab-usuarios';
            cambiarPestaña(pestañaGuardada);
            
            // Inicializar pestañas de usuario si existen
            if (document.querySelector('.admin-tab-usuario')) {
                cambiarPestañaUsuario('tab-usuario-publicaciones');
            }
        });
    </script>
</head>
<body>

    <?php include "../includes/header.php"; ?>
    
    <div class="admin-container">
        <h1><?= $translator->__("Gestor de Administración") ?></h1>
        
        <div class="admin-tabs">
            <button class="admin-tab" data-tab="tab-usuarios" onclick="cambiarPestaña('tab-usuarios')"><?= $translator->__("Usuarios") ?></button>
            <button class="admin-tab" data-tab="tab-publicaciones" onclick="cambiarPestaña('tab-publicaciones')"><?= $translator->__("Publicaciones") ?></button>
            <a href="crear_publicacion.php" class="admin-tab crear-publicacion"><?= $translator->__("Crear Publicación") ?></a>
        </div>

        <div id="tab-usuarios" class="tab-content">
            <?php if (isset($_GET['ver_usuario']) && !empty($nombreUsuarioSeleccionado)): ?>
                <section class="admin-section">
                    <div class="usuario-header">
                        <h2><?= $translator->__("Usuario") ?>: <?= htmlspecialchars($nombreUsuarioSeleccionado) ?></h2>
                        <a href="gestor_usuarios.php" class="admin-button"><?= $translator->__("Volver a la lista") ?></a>
                    </div>
                    
                    <div class="admin-tabs-usuario">
                        <button class="admin-tab-usuario active" data-tab="tab-usuario-publicaciones" 
                                onclick="cambiarPestañaUsuario('tab-usuario-publicaciones')">
                            <?= $translator->__("Publicaciones") ?> (<?= count($publicacionesUsuario) ?>)
                        </button>
                        <button class="admin-tab-usuario" data-tab="tab-usuario-comentarios" 
                                onclick="cambiarPestañaUsuario('tab-usuario-comentarios')">
                            <?= $translator->__("Comentarios") ?> (<?= count($comentariosUsuario) ?>)
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($publicacionesUsuario)): ?>
                                    <tr>
                                        <td colspan="4" class="no-data"><?= $translator->__("Este usuario no tiene publicaciones") ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($publicacionesUsuario as $publicacion): ?>
                                    <tr>
                                        <td><?= $publicacion['id_entrada'] ?></td>
                                        <td><?= htmlspecialchars($publicacion['titulo']) ?></td>
                                        <td><?= htmlspecialchars($publicacion['categoria']) ?></td>
                                        <td><?= $publicacion['fecha'] ?></td>
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($comentariosUsuario)): ?>
                                    <tr>
                                        <td colspan="4" class="no-data"><?= $translator->__("Este usuario no tiene comentarios") ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($comentariosUsuario as $comentario): ?>
                                    <tr>
                                        <td><?= $comentario['id_comentario'] ?></td>
                                        <td><?= htmlspecialchars($comentario['contenido']) ?></td>
                                        <td><?= $comentario['fecha'] ?></td>
                                        <td><?= htmlspecialchars($comentario['publicacion']) ?></td>
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
                                    <a class="admin-link" href="?ver_usuario=<?= $usuario['id_usuario'] ?>"><?= $translator->__("Ver Publicaciones y Comentarios") ?></a>
                                    <?php if ($usuario['perfil'] !== 'admin'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                                        <button class="admin-button" type="submit" name="accion" value="hacer_admin"><?= $translator->__("Hacer Admin") ?></button>
                                    </form>
                                    <?php else: ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                                        <button class="admin-button" type="submit" name="accion" value="quitar_admin"><?= $translator->__("Quitar Admin") ?></button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id_usuario" value="<?= $usuario['id_usuario'] ?>">
                                        <button class="admin-button" type="submit" name="accion" value="eliminar_usuario"><?= $translator->__("Eliminar") ?></button>
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
                            <td><?= htmlspecialchars($publicacion['categoria']) ?></td>
                            <td><?= $publicacion['fecha'] ?></td>
                            <td>
                                <a class="admin-link" href="editar_publicacion.php?id=<?= $publicacion['id_entrada'] ?>"><?= $translator->__("Editar") ?></a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id_entrada" value="<?= $publicacion['id_entrada'] ?>">
                                    <button class="admin-button" type="submit" name="accion" value="eliminar_publicacion"><?= $translator->__("Eliminar") ?></button>
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
</body>
</html>
<?php $conn->close(); ?>
