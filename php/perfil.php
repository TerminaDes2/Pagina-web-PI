<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: registro.php?error=Acceso+denegado");
    exit();
}

if (!isset($_SESSION['idioma'])) {
    $_SESSION['idioma'] = 'es'; // Idioma predeterminado
}

// Conexión a la base de datos
include "../includes/db_config.php";

$conn = new mysqli(host, dbuser, dbpass, dbname);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
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

// Obtener los datos actuales del usuario
$id_usuario = $_SESSION['usuario']['id_usuario'];
$stmt = $conn->prepare("SELECT nombre, primer_apellido, segundo_apellido, correo, imagen FROM usuarios WHERE id_usuario = ?");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$stmt->bind_result($nombre, $primer_apellido, $segundo_apellido, $correo, $imagen_perfil);
$stmt->fetch();
$stmt->close();

// Actualizar la imagen en la sesión
if ($imagen_perfil !== $_SESSION['usuario']['imagen']) {
    $_SESSION['usuario']['imagen'] = $imagen_perfil;
}

// Obtener las publicaciones del usuario
$stmt = $conn->prepare("SELECT e.id_entrada, e.titulo, c.categoria as nombre_categoria, e.fecha 
                       FROM entradas e 
                       LEFT JOIN categorias c ON e.categoria = c.id_categoria
                       WHERE e.id_usuario = ?
                       ORDER BY e.fecha DESC");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$publicaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener los comentarios del usuario
$stmt = $conn->prepare("SELECT c.id_comentario, c.descripcion, c.fecha, e.titulo, e.id_entrada 
                       FROM comentarios c
                       JOIN entradas e ON c.id_entrada = e.id_entrada
                       WHERE c.id_usuario = ?
                       ORDER BY c.fecha DESC");
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$comentarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translator->__("Mi Perfil") ?> - Voces del Proceso</title>
    <link rel="stylesheet" href="../assets/css/perfil.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function mostrarPestana(pestanaId) {
            // Ocultar todas las pestañas
            const contenidos = document.querySelectorAll('.perfil-contenido');
            contenidos.forEach(contenido => {
                contenido.style.display = 'none';
            });
            
            // Mostrar la pestaña seleccionada
            document.getElementById(pestanaId).style.display = 'block';
            
            // Actualizar clase activa
            const enlaces = document.querySelectorAll('.perfil-nav a');
            enlaces.forEach(enlace => {
                enlace.classList.remove('active');
                if (enlace.getAttribute('data-tab') === pestanaId) {
                    enlace.classList.add('active');
                }
            });
            
            // Forzar repintado para mejorar la nitidez
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 10);
        }
        
        function mostrarVistaPrevia() {
            const input = document.getElementById('imagen');
            const preview = document.getElementById('imagen-preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function confirmarEliminarComentario(idComentario) {
            Swal.fire({
                title: '<?= $translator->__("¿Estás seguro?") ?>',
                text: '<?= $translator->__("Esta acción no se puede deshacer") ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<?= $translator->__("Sí, eliminar") ?>',
                cancelButtonText: '<?= $translator->__("Cancelar") ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../includes/eliminar_comentario.php?id=' + idComentario;
                }
            });
        }
        
        function confirmarEliminarPublicacion(idPublicacion) {
            Swal.fire({
                title: '<?= $translator->__("¿Estás seguro?") ?>',
                text: '<?= $translator->__("Esta acción eliminará permanentemente la publicación y todos sus comentarios") ?>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<?= $translator->__("Sí, eliminar") ?>',
                cancelButtonText: '<?= $translator->__("Cancelar") ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../includes/eliminar_publicacion.php?id=' + idPublicacion;
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Iniciamos en la pestaña de perfil y optimizamos el renderizado
            setTimeout(() => {
                mostrarPestana('perfil-info');
                
                // Aplicamos clase para mejorar nitidez
                document.querySelectorAll('input, select, textarea').forEach(el => {
                    el.classList.add('sharp-text');
                });
            }, 100);
            
            // Mostrar mensaje si existe
            <?php if (!empty($message)): ?>
                Swal.fire({
                    icon: '<?= $messageType ?>',
                    title: '<?= $message ?>',
                    showConfirmButton: false,
                    timer: 3000
                });
            <?php endif; ?>
        });
    </script>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="perfil-container">
        <div class="perfil-header">
            <div class="perfil-avatar">
                <?php if (!empty($imagen_perfil)): ?>
                    <img src="../<?= htmlspecialchars($imagen_perfil) ?>" alt="<?= $translator->__("Foto de perfil") ?>">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="perfil-info-header">
                <h1><?= htmlspecialchars("$nombre $primer_apellido $segundo_apellido") ?></h1>
                <p><?= htmlspecialchars($correo) ?></p>
                <?php if ($_SESSION['usuario']['perfil'] === 'admin'): ?>
                    <span class="perfil-badge"><?= $translator->__("Administrador") ?></span>
                <?php else: ?>
                    <span class="perfil-badge"><?= $translator->__("Usuario") ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="perfil-nav">
            <a href="#" data-tab="perfil-info" class="active" onclick="mostrarPestana('perfil-info'); return false;">
                <i class="fas fa-user"></i> <?= $translator->__("Mi Información") ?>
            </a>
            <?php if ($_SESSION['usuario']['perfil'] === 'admin'): ?>
            <a href="#" data-tab="perfil-publicaciones" onclick="mostrarPestana('perfil-publicaciones'); return false;">
                <i class="fas fa-newspaper"></i> <?= $translator->__("Mis Publicaciones") ?> (<?= count($publicaciones) ?>)
            </a>
            <?php endif; ?>
            <a href="#" data-tab="perfil-comentarios" onclick="mostrarPestana('perfil-comentarios'); return false;">
                <i class="fas fa-comments"></i> <?= $translator->__("Mis Comentarios") ?> (<?= count($comentarios) ?>)
            </a>
            <a href="../includes/cerrarsesion.php" class="cerrar-sesion">
                <i class="fas fa-sign-out-alt"></i> <?= $translator->__("Cerrar Sesión") ?>
            </a>
        </div>
        
        <div id="perfil-info" class="perfil-contenido">
            <h2><?= $translator->__("Actualizar mi información") ?></h2>
            
            <form action="../includes/guardar_usuario.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nombre"><?= $translator->__("Nombre") ?>:</label>
                    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($nombre) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="primer_apellido"><?= $translator->__("Primer Apellido") ?>:</label>
                    <input type="text" id="primer_apellido" name="primer_apellido" value="<?= htmlspecialchars($primer_apellido) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="segundo_apellido"><?= $translator->__("Segundo Apellido") ?>:</label>
                    <input type="text" id="segundo_apellido" name="segundo_apellido" value="<?= htmlspecialchars($segundo_apellido) ?>">
                </div>
                
                <div class="form-group">
                    <label for="correo"><?= $translator->__("Correo Electrónico") ?>:</label>
                    <input type="email" id="correo" name="correo" value="<?= htmlspecialchars($correo) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="imagen"><?= $translator->__("Imagen de Perfil") ?>:</label>
                    <input type="file" id="imagen" name="imagen" accept="image/*" onchange="mostrarVistaPrevia()">
                    <?php if (!empty($imagen_perfil)): ?>
                        <p class="imagen-actual"><?= $translator->__("Imagen actual") ?>: <a href="<?= htmlspecialchars($imagen_perfil) ?>" target="_blank"><?= basename($imagen_perfil) ?></a></p>
                    <?php endif; ?>
                    <img id="imagen-preview" src="#" alt="<?= $translator->__("Vista previa") ?>" style="display: none; max-width: 200px; margin-top: 10px;">
                </div>
                
                <div class="form-group">
                    <label for="nueva_contra"><?= $translator->__("Nueva Contraseña") ?> (<?= $translator->__("dejar en blanco para mantener la actual") ?>):</label>
                    <input type="password" id="nueva_contra" name="nueva_contra">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-guardar">
                        <i class="fas fa-save"></i> <?= $translator->__("Guardar Cambios") ?>
                    </button>
                </div>
            </form>
        </div>
        
        <?php if ($_SESSION['usuario']['perfil'] === 'admin'): ?>
        <div id="perfil-publicaciones" class="perfil-contenido" style="display: none;">
            <h2><?= $translator->__("Mis Publicaciones") ?></h2>
            
            <?php if (empty($publicaciones)): ?>
                <div class="sin-contenido">
                    <i class="fas fa-newspaper"></i>
                    <p><?= $translator->__("Aún no has creado ninguna publicación.") ?></p>
                </div>
            <?php else: ?>
                <div class="lista-contenido">
                    <table class="tabla-contenido">
                        <thead>
                            <tr>
                                <th><?= $translator->__("Título") ?></th>
                                <th><?= $translator->__("Categoría") ?></th>
                                <th><?= $translator->__("Fecha") ?></th>
                                <th><?= $translator->__("Acciones") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($publicaciones as $pub): ?>
                            <tr>
                                <td><?= htmlspecialchars($pub['titulo']) ?></td>
                                <td><?= htmlspecialchars($pub['nombre_categoria']) ?></td>
                                <td><?= $pub['fecha'] ?></td>
                                <td class="acciones">
                                    <a href="publicacion.php?id=<?= $pub['id_entrada'] ?>" class="btn-accion ver">
                                        <i class="fas fa-eye"></i> <?= $translator->__("Ver") ?>
                                    </a>
                                    <a href="editar_publicacion.php?id=<?= $pub['id_entrada'] ?>" class="btn-accion editar">
                                        <i class="fas fa-edit"></i> <?= $translator->__("Editar") ?>
                                    </a>
                                    <a href="javascript:void(0);" onclick="confirmarEliminarPublicacion(<?= $pub['id_entrada'] ?>)" class="btn-accion eliminar">
                                        <i class="fas fa-trash-alt"></i> <?= $translator->__("Eliminar") ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div id="perfil-comentarios" class="perfil-contenido" style="display: none;">
            <h2><?= $translator->__("Mis Comentarios") ?></h2>
            
            <?php if (empty($comentarios)): ?>
                <div class="sin-contenido">
                    <i class="fas fa-comments"></i>
                    <p><?= $translator->__("Aún no has realizado ningún comentario.") ?></p>
                </div>
            <?php else: ?>
                <div class="lista-contenido">
                    <table class="tabla-contenido">
                        <thead>
                            <tr>
                                <th><?= $translator->__("Comentario") ?></th>
                                <th><?= $translator->__("Publicación") ?></th>
                                <th><?= $translator->__("Fecha") ?></th>
                                <th><?= $translator->__("Acciones") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comentarios as $com): ?>
                            <tr>
                                <td><?= htmlspecialchars(substr($com['descripcion'], 0, 100)) . (strlen($com['descripcion']) > 100 ? '...' : '') ?></td>
                                <td><?= htmlspecialchars($com['titulo']) ?></td>
                                <td><?= $com['fecha'] ?></td>
                                <td class="acciones">
                                    <a href="publicacion.php?id=<?= $com['id_entrada'] ?>#comentario-<?= $com['id_comentario'] ?>" class="btn-accion ver">
                                        <i class="fas fa-eye"></i> <?= $translator->__("Ver") ?>
                                    </a>
                                    <a href="javascript:void(0);" onclick="confirmarEliminarComentario(<?= $com['id_comentario'] ?>)" class="btn-accion eliminar">
                                        <i class="fas fa-trash-alt"></i> <?= $translator->__("Eliminar") ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
<?php $conn->close(); ?>
