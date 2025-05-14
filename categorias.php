<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['idioma'])) {
    $_SESSION['idioma'] = 'es'; // Idioma predeterminado
}

// Verificar que se haya especificado una categoría
if (!isset($_GET['cat']) || !is_numeric($_GET['cat'])) {
    header("Location: index.php");
    exit();
}

$id_categoria = intval($_GET['cat']);

// Conexión a la base de datos
include "includes/db_config.php";

$conn = new mysqli(host, dbuser, dbpass, dbname);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Incluir el traductor
require_once 'includes/traductor.php';
$translator = new Translator($conn);

// Manejar cambio de idioma
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['idioma'])) {
    $translator->cambiarIdioma($_POST['idioma']);
    header("Location: " . $_SERVER['PHP_SELF'] . "?cat=" . $id_categoria);
    exit();
}

// Obtener el nombre de la categoría
$stmt = $conn->prepare("SELECT categoria FROM categorias WHERE id_categoria = ?");
$stmt->bind_param("i", $id_categoria);
$stmt->execute();
$stmt->bind_result($nombre_categoria);
$categoria_existe = $stmt->fetch();
$stmt->close();

if (!$categoria_existe) {
    header("Location: index.php");
    exit();
}

// Consulta para publicaciones de esta categoría
$sqlPosts = "SELECT e.id_entrada, e.titulo, e.contenido, e.cita, e.fecha, i.imagen 
             FROM entradas e 
             LEFT JOIN imagenes i ON i.id_entrada = e.id_entrada 
             WHERE e.categoria = ? 
             ORDER BY e.fecha DESC";

$stmt = $conn->prepare($sqlPosts);
$stmt->bind_param("i", $id_categoria);
$stmt->execute();
$resultPosts = $stmt->get_result();
$articulos = [];

while ($row = $resultPosts->fetch_assoc()) {
    $row['titulo'] = $translator->traducirTexto($row['titulo']);
    $row['contenido'] = $translator->traducirTexto($row['contenido']);
    $row['cita'] = $translator->traducirTexto($row['cita']);
    $articulos[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nombre_categoria) ?> - Voces del Proceso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/categorias.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="categoria-header">
        <div class="container">
            <h1><?= htmlspecialchars($nombre_categoria) ?></h1>
            <p><?= $translator->__("Artículos en la categoría") ?> "<?= htmlspecialchars($nombre_categoria) ?>"</p>
        </div>
    </section>

    <section class="articulos-categoria">
        <div class="container">
            <?php if (empty($articulos)): ?>
                <div class="no-articulos">
                    <p><?= $translator->__("No hay artículos disponibles en esta categoría.") ?></p>
                </div>
            <?php else: ?>
                <div class="grid-articulos">
                    <?php foreach ($articulos as $articulo): ?>
                    <article class="articulo-card">
                        <div class="articulo-imagen">
                            <?php if (!empty($articulo['imagen'])): ?>
                                <a href="php/publicacion.php?id=<?= $articulo['id_entrada'] ?>">
                                    <img src="php/<?= htmlspecialchars($articulo['imagen']) ?>" alt="<?= htmlspecialchars($articulo['titulo']) ?>">
                                </a>
                            <?php else: ?>
                                <div class="sin-imagen">
                                    <i class="fas fa-newspaper"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="articulo-contenido">
                            <h2><a href="php/publicacion.php?id=<?= $articulo['id_entrada'] ?>"><?= htmlspecialchars($articulo['titulo']) ?></a></h2>
                            <p class="articulo-fecha"><?= $articulo['fecha'] ?></p>
                            <?php if (!empty($articulo['cita'])): ?>
                                <blockquote><?= htmlspecialchars($articulo['cita']) ?></blockquote>
                            <?php endif; ?>
                            <p class="articulo-extracto"><?= strip_tags(substr($articulo['contenido'], 0, 200)) ?>...</p>
                            <a href="php/publicacion.php?id=<?= $articulo['id_entrada'] ?>" class="btn-leer-mas"><?= $translator->__("Leer más") ?></a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <?php $conn->close(); ?>
</body>
</html>
