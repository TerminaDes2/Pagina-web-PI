<?php
session_start();
$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'es';
include "../includes/lang_{$lang}.php";

// Conexión a la base de datos
$host   = 'localhost';
$dbname = 'blog';
$dbuser = 'root';
$dbpass = 'administrador';

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}

// Consulta para obtener el banner (última entrada)
$sqlBanner = "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, i.imagen 
              FROM entradas e 
              LEFT JOIN imagenes i ON e.id_imagen = i.id_imagen 
              ORDER BY e.id_entrada DESC 
              LIMIT 1";
$resultBanner = $conn->query($sqlBanner);
$banner = ($resultBanner->num_rows > 0) ? $resultBanner->fetch_assoc() : null;

// Consulta para los artículos destacados (excluyendo el banner si existe)
if ($banner) {
    $sqlPosts = "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, i.imagen 
                 FROM entradas e 
                 LEFT JOIN imagenes i ON e.id_imagen = i.id_imagen 
                 WHERE e.id_entrada <> " . $banner['id_entrada'] . " 
                 ORDER BY e.id_entrada DESC";
} else {
    $sqlPosts = "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, i.imagen 
                 FROM entradas e 
                 LEFT JOIN imagenes i ON e.id_imagen = i.id_imagen 
                 ORDER BY e.id_entrada DESC";
}
$resultPosts = $conn->query($sqlPosts);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $idioma['voces_proceso']; ?> - Trabajo Decente y Crecimiento Económico</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <!-- Se incluyen las fuentes y los estilos -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="../assets/js/Main.js" defer></script>
</head>
<body>
    <!-- Cabecera principal -->
    <header class="main-header">
        <div class="header-container">
            <div class="logo-container">
                <div class="logo">
                    <a href="../php/index.php">
                        <h1><?php echo $idioma['voces_proceso']; ?></h1>
                    </a>
                </div>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="index.php"><?php echo $idioma['inicio']; ?></a></li>
                    <li class="menu-desplegable">
                        <a href="#"><?php echo $idioma['noticias']; ?></a>
                        <div class="contenido-desplegable">
                            <!-- Submenú (opcional) -->
                        </div>
                    </li>
                    <li class="menu-desplegable">
                        <a href="contacto.php"><?php echo $idioma['contacto']; ?></a>
                        <div class="contenido-desplegable"></div>
                    </li>
                    <li class="menu-desplegable">
                        <a href="acerca-de.php"><?php echo $idioma['acerca_de']; ?></a>
                        <div class="contenido-desplegable"></div>
                    </li>
                </ul>
            </nav>
            <div class="header-actions">
                <div class="search-box">
                    <input type="text" placeholder="<?php echo $idioma['buscar']; ?>">
                    <button><i class="fas fa-search"></i></button>
                </div>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </header>

    <!-- Sección Hero con el banner -->
    <?php if ($banner): ?>
    <section class="hero-section" onclick="window.location.href='publicacion.php?id=<?php echo $banner['id_entrada']; ?>';" style="cursor:pointer;">
        <div class="hero-content">
            <h1><?php echo $banner['titulo']; ?></h1>
            <p><?php echo substr($banner['contenido'], 0, 150); ?>...</p>
        </div>
        <div class="hero-image">
            <?php if (!empty($banner['imagen'])): ?>
            <img src="<?php echo $banner['imagen']; ?>" alt="<?php echo $banner['titulo']; ?>">
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Sección de artículos destacados en formato carrusel -->
    <section class="featured-articles" id="featured-articles">
        <h2><?php echo $idioma['articulos_destacados']; ?></h2>
        <div class="carousel-container">
            <div class="carousel-track">
                <?php while ($row = $resultPosts->fetch_assoc()): ?>
                <article class="carousel-item" onclick="window.location.href='publicacion.php?id=<?php echo $row['id_entrada']; ?>';" style="cursor:pointer;">
                    <?php if (!empty($row['imagen'])): ?>
                    <img src="<?php echo $row['imagen']; ?>" alt="<?php echo $row['titulo']; ?>">
                    <?php endif; ?>
                    <h3><?php echo $row['titulo']; ?></h3>
                    <p><?php echo substr($row['contenido'], 0, 100); ?>...</p>
                    <a href="publicacion.php?id=<?php echo $row['id_entrada']; ?>" class="btn btn-secondary"><?php echo $idioma['leer_mas']; ?></a>
                </article>
                <?php endwhile; ?>
            </div>
        </div>
        <div class="carousel-controls">
            <button class="carousel-prev"><i class="fas fa-chevron-left"></i></button>
            <button class="carousel-next"><i class="fas fa-chevron-right"></i></button>
        </div>
    </section>

    <!-- Sección de información relevante -->
    <section class="info-title-section">
        <h2><?php echo $idioma['informacion_relevante']; ?></h2>
    </section>

    <section class="info-blocks">
            <div class="info-grid">
                <div class="info-block">
                    <i class="fas fa-chart-line"></i>
                    <h3>Indicadores Económicos</h3>
                    <p>Datos y estadísticas sobre el crecimiento económico y el empleo.</p>
                    <a href="#" class="btn btn-tertiary">Ver más</a>
                </div>
                <div class="info-block">
                    <i class="fas fa-briefcase"></i>
                    <h3>Derechos Laborales</h3>
                    <p>Información sobre los derechos y las responsabilidades de los trabajadores.</p>
                    <a href="#" class="btn btn-tertiary">Ver más</a>
                </div>
                <div class="info-block">
                    <i class="fas fa-users"></i>
                    <h3>Inclusión Laboral</h3>
                    <p>Estrategias para promover la diversidad y la inclusión en el trabajo.</p>
                    <a href="#" class="btn btn-tertiary">Ver más</a>
                </div>
                <div class="info-block">
                    <i class="fas fa-globe"></i>
                    <h3>Trabajo Global</h3>
                    <p>Análisis de las tendencias y los desafíos del mercado laboral a nivel mundial.</p>
                    <a href="#" class="btn btn-tertiary">Ver más</a>
                </div>
            </div>
        </section>

    <!-- Pie de página -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-logo">
                <a href="index.php">
                    <h1><?php echo $idioma['voces_proceso']; ?></h1>
                </a>
            </div>
            <div class="footer-nav">
                <!-- Opcional: enlaces adicionales -->
            </div>
            <div class="footer-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
            <div class="footer-copyright">
                <p>&copy; 2025 <?php echo $idioma['voces_proceso']; ?>. <?php echo $idioma['todos_derechos_reservados']; ?></p>
            </div>
        </div>
    </footer>
</body>
</html>

<?php
$conn->close();
?>
