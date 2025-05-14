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

$mensaje = '';
$tipo_mensaje = '';

// Manejar la creación de categoría
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'crear') {
        $nombre_categoria = trim($_POST['nombre_categoria']);
        
        if (!empty($nombre_categoria)) {
            $stmt = $conn->prepare("INSERT INTO categorias (categoria) VALUES (?)");
            $stmt->bind_param("s", $nombre_categoria);
            
            if ($stmt->execute()) {
                $mensaje = $translator->__("Categoría creada correctamente.");
                $tipo_mensaje = 'success';
            } else {
                $mensaje = $translator->__("Error al crear la categoría: ") . $conn->error;
                $tipo_mensaje = 'error';
            }
            $stmt->close();
        } else {
            $mensaje = $translator->__("El nombre de la categoría no puede estar vacío.");
            $tipo_mensaje = 'warning';
        }
    } elseif ($_POST['accion'] === 'editar') {
        $id_categoria = intval($_POST['id_categoria']);
        $nombre_categoria = trim($_POST['nombre_categoria']);
        
        if (!empty($nombre_categoria)) {
            $stmt = $conn->prepare("UPDATE categorias SET categoria = ? WHERE id_categoria = ?");
            $stmt->bind_param("si", $nombre_categoria, $id_categoria);
            
            if ($stmt->execute()) {
                $mensaje = $translator->__("Categoría actualizada correctamente.");
                $tipo_mensaje = 'success';
            } else {
                $mensaje = $translator->__("Error al actualizar la categoría: ") . $conn->error;
                $tipo_mensaje = 'error';
            }
            $stmt->close();
        } else {
            $mensaje = $translator->__("El nombre de la categoría no puede estar vacío.");
            $tipo_mensaje = 'warning';
        }
    } elseif ($_POST['accion'] === 'eliminar') {
        $id_categoria = intval($_POST['id_categoria']);
        
        // Verificar si la categoría está en uso
        $stmt = $conn->prepare("SELECT COUNT(*) FROM entradas WHERE categoria = ?");
        $stmt->bind_param("i", $id_categoria);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        
        if ($count > 0) {
            $mensaje = $translator->__("No se puede eliminar la categoría porque está siendo utilizada en publicaciones.");
            $tipo_mensaje = 'warning';
        } else {
            $stmt = $conn->prepare("DELETE FROM categorias WHERE id_categoria = ?");
            $stmt->bind_param("i", $id_categoria);
            
            if ($stmt->execute()) {
                $mensaje = $translator->__("Categoría eliminada correctamente.");
                $tipo_mensaje = 'success';
            } else {
                $mensaje = $translator->__("Error al eliminar la categoría: ") . $conn->error;
                $tipo_mensaje = 'error';
            }
            $stmt->close();
        }
    }
}

// Obtener todas las categorías
$categorias = [];
$result = $conn->query("SELECT id_categoria, categoria FROM categorias ORDER BY categoria");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translator->__("Gestor de Categorías") ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        .categoria-form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .categoria-form h3 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .categoria-form .form-group {
            margin-bottom: 15px;
        }
        .categoria-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .categoria-form input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .categoria-form button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        .categoria-form button:hover {
            background-color: #45a049;
        }
    </style>
    <script>
        function confirmarEliminar(id, nombre) {
            Swal.fire({
                title: '<?= $translator->__("¿Estás seguro?") ?>',
                html: `<?= $translator->__("¿Realmente deseas eliminar la categoría") ?> <strong>${nombre}</strong>?<br><?= $translator->__("Esta acción no se puede deshacer.") ?>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: '<?= $translator->__("Sí, eliminar") ?>',
                cancelButtonText: '<?= $translator->__("Cancelar") ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('delete-form-' + id);
                    form.submit();
                }
            });
        }
        
        function editarCategoria(id, nombre) {
            document.getElementById('editar-id').value = id;
            document.getElementById('editar-nombre').value = nombre;
            document.getElementById('formEditar').style.display = 'block';
            document.getElementById('formCrear').style.display = 'none';
            
            document.getElementById('formEditar').scrollIntoView({ behavior: 'smooth' });
        }
        
        function mostrarFormCrear() {
            document.getElementById('formCrear').style.display = 'block';
            document.getElementById('formEditar').style.display = 'none';
            document.getElementById('crear-nombre').value = '';
            
            document.getElementById('formCrear').scrollIntoView({ behavior: 'smooth' });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            if ('<?= $tipo_mensaje ?>' !== '') {
                Swal.fire({
                    icon: '<?= $tipo_mensaje ?>',
                    title: '<?= $mensaje ?>',
                    showConfirmButton: true
                });
            }
        });
    </script>
</head>
<body>
    <?php include "../includes/header.php"; ?>
    
    <div class="admin-container">
        <h1><?= $translator->__("Gestor de Categorías") ?></h1>
        
        <div class="admin-actions">
            <button onclick="mostrarFormCrear()" class="admin-button">
                <i class="fas fa-plus"></i> <?= $translator->__("Nueva Categoría") ?>
            </button>
            <a href="gestor_usuarios.php" class="admin-button">
                <i class="fas fa-arrow-left"></i> <?= $translator->__("Volver al Panel") ?>
            </a>
        </div>
        
        <!-- Formulario para crear categoría -->
        <div id="formCrear" class="categoria-form" style="display: none;">
            <h3><?= $translator->__("Crear Nueva Categoría") ?></h3>
            <form method="POST">
                <input type="hidden" name="accion" value="crear">
                <div class="form-group">
                    <label for="crear-nombre"><?= $translator->__("Nombre de la categoría") ?>:</label>
                    <input type="text" id="crear-nombre" name="nombre_categoria" required>
                </div>
                <button type="submit">
                    <i class="fas fa-save"></i> <?= $translator->__("Guardar Categoría") ?>
                </button>
            </form>
        </div>
        
        <!-- Formulario para editar categoría -->
        <div id="formEditar" class="categoria-form" style="display: none;">
            <h3><?= $translator->__("Editar Categoría") ?></h3>
            <form method="POST">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" id="editar-id" name="id_categoria">
                <div class="form-group">
                    <label for="editar-nombre"><?= $translator->__("Nombre de la categoría") ?>:</label>
                    <input type="text" id="editar-nombre" name="nombre_categoria" required>
                </div>
                <button type="submit">
                    <i class="fas fa-save"></i> <?= $translator->__("Actualizar Categoría") ?>
                </button>
            </form>
        </div>
        
        <!-- Lista de categorías -->
        <section class="admin-section">
            <h2><?= $translator->__("Categorías disponibles") ?></h2>
            <?php if (empty($categorias)): ?>
                <p class="no-data"><?= $translator->__("No hay categorías disponibles") ?></p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th><?= $translator->__("Nombre") ?></th>
                            <th><?= $translator->__("Acciones") ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $cat): ?>
                        <tr>
                            <td><?= $cat['id_categoria'] ?></td>
                            <td><?= htmlspecialchars($cat['categoria']) ?></td>
                            <td>
                                <button class="admin-button" onclick="editarCategoria(<?= $cat['id_categoria'] ?>, '<?= htmlspecialchars($cat['categoria'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-edit"></i> <?= $translator->__("Editar") ?>
                                </button>
                                <form id="delete-form-<?= $cat['id_categoria'] ?>" method="POST" style="display:inline;">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_categoria" value="<?= $cat['id_categoria'] ?>">
                                    <button type="button" class="admin-button" onclick="confirmarEliminar(<?= $cat['id_categoria'] ?>, '<?= htmlspecialchars($cat['categoria'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-trash-alt"></i> <?= $translator->__("Eliminar") ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>

    <?php include "../includes/footer.php"; ?>
</body>
</html>
<?php $conn->close(); ?>
