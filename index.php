<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

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
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Obtener y traducir contenido dinámico
// Consulta para el banner
$sqlBanner = "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, i.imagen 
              FROM entradas e 
              LEFT JOIN imagenes i ON e.id_imagen = i.id_imagen 
              ORDER BY e.id_entrada DESC 
              LIMIT 1";
$resultBanner = $conn->query($sqlBanner);
$banner = ($resultBanner->num_rows > 0) ? $resultBanner->fetch_assoc() : null;

if ($banner) {
    $banner['titulo'] = $translator->traducirTexto($banner['titulo']);
    $banner['contenido'] = $translator->traducirHTML($banner['contenido']);
}

// Consulta para artículos
$sqlPosts = $banner ? 
    "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, i.imagen 
     FROM entradas e 
     LEFT JOIN imagenes i ON e.id_imagen = i.id_imagen 
     WHERE e.id_entrada <> " . $banner['id_entrada'] . " 
     ORDER BY e.id_entrada DESC" :
    "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, i.imagen 
     FROM entradas e 
     LEFT JOIN imagenes i ON e.id_imagen = i.id_imagen 
     ORDER BY e.id_entrada DESC";

$resultPosts = $conn->query($sqlPosts);
$articulos = [];
while ($row = $resultPosts->fetch_assoc()) {
    $row['titulo'] = $translator->traducirTexto($row['titulo']);
    $row['contenido'] = $translator->traducirTexto($row['contenido']);
    $articulos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voces del Proceso - Trabajo Decente y Crecimiento Económico</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="assets/js/Main.js" defer></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <?php if ($banner): ?>
    <section class="hero-section" onclick="window.location.href='php/publicacion.php?id=<?= $banner['id_entrada'] ?>';" style="cursor:pointer;">
        <div class="hero-content">
            <h1><?= $banner['titulo'] ?></h1>
            <div class="banner-content">
                <?= $banner['contenido'] ?> <!-- Aquí se renderizará el HTML -->
            </div>
        </div>
        <div class="hero-image">
            <?php if (!empty($banner['imagen'])): ?>
            <img src="php/<?= htmlspecialchars($banner['imagen']) ?>" alt="<?= htmlspecialchars($banner['titulo']) ?>">
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="featured-articles" id="featured-articles">
        <h2><?= $translator->__("Artículos destacados") ?></h2>
        <div class="carousel-container">
            <div class="carousel-track">
                <?php foreach ($articulos as $articulo): ?>
                <article class="carousel-item" onclick="window.location.href='php/publicacion.php?id=<?= $articulo['id_entrada'] ?>';" style="cursor:pointer;">
                    <?php if (!empty($articulo['imagen'])): ?>
                    <img src="php/<?= htmlspecialchars($articulo['imagen']) ?>" alt="<?= htmlspecialchars($articulo['titulo']) ?>">
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($articulo['titulo']) ?></h3>
                    
                    <!-- Contenido sin escapar (renderiza HTML) -->
                    <div class="article-preview">
                        <?= substr($articulo['contenido'], 0, 100) ?>...
                    </div>

                    <a href="php/publicacion.php?id=<?= $articulo['id_entrada'] ?>" class="btn btn-secondary">
                        <?= $translator->__("Leer más") ?>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="carousel-controls">
            <button class="carousel-prev"><i class="fas fa-chevron-left"></i></button>
            <button class="carousel-next"><i class="fas fa-chevron-right"></i></button>
        </div>
    </section>

    <section class="info-title-section">
        <h2><?= $translator->__("Información relevante") ?></h2>
    </section>

    <section class="info-blocks">
        <div class="info-grid">
            <div class="info-block">
                <i class="fas fa-chart-line"></i>
                <h3><?= $translator->__("Indicadores Económicos") ?></h3>
                <p><?= $translator->__("Datos y estadísticas sobre el crecimiento económico y el empleo.") ?></p>
                <a href="#" class="btn btn-tertiary"><?= $translator->__("Ver más") ?></a>
            </div>
            
            <div class="info-block">
                <i class="fas fa-briefcase"></i>
                <h3><?= $translator->__("Derechos Laborales") ?></h3>
                <p><?= $translator->__("Información sobre los derechos y las responsabilidades de los trabajadores.") ?></p>
                <a href="#" class="btn btn-tertiary"><?= $translator->__("Ver más") ?></a>
            </div>

            <div class="info-block">
                <i class="fas fa-users"></i>
                <h3><?= $translator->__("Inclusión Laboral") ?></h3>
                <p><?= $translator->__("Estrategias para promover la diversidad y la inclusión en el trabajo.") ?></p>
                <a href="#" class="btn btn-tertiary"><?= $translator->__("Ver más") ?></a>
            </div>

            <div class="info-block">
                <i class="fas fa-globe"></i>
                <h3><?= $translator->__("Trabajo Global") ?></h3>
                <p><?= $translator->__("Análisis de las tendencias y los desafíos del mercado laboral a nivel mundial.") ?></p>
                <a href="#" class="btn btn-tertiary"><?= $translator->__("Ver más") ?></a>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <?php $conn->close(); ?>
</body>
</html>