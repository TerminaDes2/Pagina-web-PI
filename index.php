<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['idioma'])) {
    $_SESSION['idioma'] = 'es'; // Idioma predeterminado
}

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

// Consulta para obtener una cita aleatoria
$sqlCita = "SELECT id, texto, autor FROM citas ORDER BY RAND() LIMIT 1";
$resultCita = $conn->query($sqlCita);
$cita = ($resultCita && $resultCita->num_rows > 0) ? $resultCita->fetch_assoc() : null;

if ($cita) {
    $cita['texto'] = $translator->traducirTexto($cita['texto']);
    $cita['autor'] = $translator->traducirTexto($cita['autor']);
}

// Obtener y traducir contenido dinámico
// Consulta para el banner
$sqlBanner = "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, u.nombre as autor, 
              c.categoria as nombre_categoria, i.imagen 
              FROM entradas e 
              LEFT JOIN imagenes i ON i.id_entrada = e.id_entrada 
              LEFT JOIN categorias c ON e.categoria = c.id_categoria
              LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario
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
    "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, u.nombre as autor,
     c.categoria as nombre_categoria, i.imagen 
     FROM entradas e 
     LEFT JOIN imagenes i ON i.id_entrada = e.id_entrada 
     LEFT JOIN categorias c ON e.categoria = c.id_categoria
     LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario
     WHERE e.id_entrada <> " . $banner['id_entrada'] . " 
     ORDER BY e.id_entrada DESC" :
    "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, u.nombre as autor,
     c.categoria as nombre_categoria, i.imagen 
     FROM entradas e 
     LEFT JOIN imagenes i ON i.id_entrada = e.id_entrada 
     LEFT JOIN categorias c ON e.categoria = c.id_categoria
     LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario
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
    <title>POALCE - Trabajo Decente y Crecimiento Económico</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    

</head>
<body>
    <?php include 'includes/header.php'; ?>

    <?php if ($banner): ?>
    <section class="hero-section" onclick="window.location.href='php/publicacion.php?id=<?= $banner['id_entrada'] ?>';" style="cursor:pointer;">
        <div class="hero-content">
            <h1><?= $banner['titulo'] ?></h1>
            <div class="post-meta">
                <?php if (!empty($banner['autor'])): ?>
                <span class="author"><i class="fas fa-user"></i> <?= htmlspecialchars($banner['autor']) ?></span>
                <?php endif; ?>
                <?php if (!empty($banner['fecha'])): ?>
                <span class="date"><i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($banner['fecha'])) ?></span>
                <?php endif; ?>
            </div>
            <div class="banner-content">
                <?php 
                // Eliminar etiquetas h2 y su contenido antes de crear el extracto
                $contenido_limpio = preg_replace('/<h2>.*?<\/h2>/is', '', $banner['contenido']);
                $contenido_limpio = strip_tags($contenido_limpio);
                $palabras = explode(' ', $contenido_limpio);
                $resumen = implode(' ', array_slice($palabras, 0, 20)) . '...';
                echo $resumen;
                ?>
            </div>
        </div>
        <div class="hero-image">
            <?php if (!empty($banner['imagen'])): ?>
            <img src="php/<?= htmlspecialchars($banner['imagen']) ?>" alt="<?= htmlspecialchars($banner['titulo']) ?>">
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Sección de call to action después del banner -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2><?= $translator->__("Únete al movimiento por el trabajo digno") ?></h2>
                <p><?= $translator->__("Conoce las propuestas, acciones y testimonios que promueven el crecimiento económico inclusivo y el trabajo decente para todos.") ?></p>
                <div class="cta-buttons">
                    <a href="php/explorar.php?modo=categorias" class="btn btn-primary"><?= $translator->__("Explorar categorías") ?></a>
                    <a href="#featured-articles" class="btn btn-secondary"><?= $translator->__("Artículos destacados") ?></a>
                </div>
            </div>
        </div>
        <div class="cta-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </section>

    <section class="featured-articles" id="featured-articles">
        <div class="bg-circle"></div> <!-- Elemento decorativo circular -->
        <h2><?= $translator->__("Artículos destacados") ?></h2>
        <div class="carousel-container">
            <div class="carousel-nav-buttons">
                <button class="carousel-button-left" aria-label="Anterior"><i class="fas fa-chevron-left"></i></button>
                <button class="carousel-button-right" aria-label="Siguiente"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="carousel-viewport">
                <div class="carousel-track">
                    <?php foreach ($articulos as $articulo): ?>
                    <article class="carousel-item">
                        <?php if (!empty($articulo['imagen'])): ?>
                        <img src="php/<?= htmlspecialchars($articulo['imagen']) ?>" alt="<?= htmlspecialchars($articulo['titulo']) ?>">
                        <?php endif; ?>
                        <div class="carousel-item-content">
                            <h3 class="carousel-title"><?= htmlspecialchars($articulo['titulo']) ?></h3>
                            <div class="post-meta">
                                <?php if (!empty($articulo['autor'])): ?>
                                <span class="author"><i class="fas fa-user"></i> <?= htmlspecialchars($articulo['autor']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($articulo['fecha'])): ?>
                                <span class="date"><i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($articulo['fecha'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php 
                            // Eliminar etiquetas h2 y su contenido antes de crear el extracto
                            $contenido_limpio = preg_replace('/<h2>.*?<\/h2>/is', '', $articulo['contenido']); 
                            $contenido_limpio = strip_tags($contenido_limpio);
                            $resumen = !empty($contenido_limpio) ? substr($contenido_limpio, 0, 150) . '...' : '';
                            ?>
                            <p class="carousel-subtitle"><?= $resumen ?></p>
                        </div>
                        <a href="php/publicacion.php?id=<?= $articulo['id_entrada'] ?>" class="btn btn-secondary">
                            <?= $translator->__("Leer más") ?>
                        </a>
                    </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Simplificar el contenedor de indicadores - el JS los generará dinámicamente -->
            <div class="carousel-indicators"></div>
        </div>
    </section>

    <!-- Sección de estadísticas clave -->
    <section class="key-stats">
        <div class="container">
            <h2><?= $translator->__("Datos clave sobre trabajo y economía") ?></h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-number" data-target="190">0</div>
                    <div class="stat-label"><?= $translator->__("Millones de desempleados globalmente") ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="stat-number" data-target="61">0</div>
                    <div class="stat-label"><?= $translator->__("% de trabajadores en empleo informal") ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-number" data-target="2">0</div>
                    <div class="stat-label"><?= $translator->__("$ diarios - Umbral de pobreza extrema") ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-venus-mars"></i>
                    </div>
                    <div class="stat-number" data-target="23">0</div>
                    <div class="stat-label"><?= $translator->__("% Brecha salarial de género") ?></div>
                </div>
            </div>
            <div class="stats-note">
                <p><?= $translator->__("Fuente: Organización Internacional del Trabajo, 2023") ?></p>
            </div>
        </div>
    </section>

    <?php if ($cita): ?>
    <!-- Sección de cita inspiradora -->
    <section class="quote-section">
        <div class="container">
            <div class="quote-content">
                <i class="fas fa-quote-left quote-icon"></i>
                <blockquote>
                    <?= htmlspecialchars($cita['texto']) ?>
                    <cite>— <?= htmlspecialchars($cita['autor']) ?></cite>
                </blockquote>
                <i class="fas fa-quote-right quote-icon"></i>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="info-title-section">
        <h2><?= $translator->__("Información relevante") ?></h2>
    </section>

    <?php
    // Obtener IDs de categorías para los bloques de información
    $categorias_info = [
        'economia' => ["Indicadores Económicos", "Economía", "Indicadores"],
        'derechos' => ["Derechos Laborales", "Derechos", "Laboral"],
        'inclusion' => ["Inclusión Laboral", "Inclusión", "Diversidad"],
        'global' => ["Trabajo Global", "Global", "Internacional"]
    ];

    $ids_categorias = [];
    foreach ($categorias_info as $clave => $nombres) {
        $ids_categorias[$clave] = 0; // Valor por defecto
        
        foreach ($nombres as $nombre) {
            // Escapar el nombre para la consulta SQL
            $nombre_escaped = $conn->real_escape_string($nombre);
            $sql = "SELECT id_categoria FROM categorias WHERE categoria LIKE '%$nombre_escaped%' LIMIT 1";
            $result = $conn->query($sql);
            
            if ($result && $result->num_rows > 0) {
                $ids_categorias[$clave] = $result->fetch_assoc()['id_categoria'];
                break; // Salir del bucle si encontramos coincidencia
            }
        }
    }
    ?>

    <section class="info-blocks">
        <div class="info-grid">
            <div class="info-block">
                <i class="fas fa-chart-line"></i>
                <h3><?= $translator->__("Indicadores Económicos") ?></h3>
                <p><?= $translator->__("Datos y estadísticas sobre el crecimiento económico y el empleo.") ?></p>
                <a href="php/explorar.php?modo=categorias&cat=<?= $ids_categorias['economia'] ?>" class="btn btn-tertiary"><?= $translator->__("Ver más") ?></a>
            </div>
            
            <div class="info-block">
                <i class="fas fa-briefcase"></i>
                <h3><?= $translator->__("Derechos Laborales") ?></h3>
                <p><?= $translator->__("Información sobre los derechos y las responsabilidades de los trabajadores.") ?></p>
                <a href="php/explorar.php?modo=categorias&cat=<?= $ids_categorias['derechos'] ?>" class="btn btn-tertiary"><?= $translator->__("Ver más") ?></a>
            </div>

            <div class="info-block">
                <i class="fas fa-users"></i>
                <h3><?= $translator->__("Inclusión Laboral") ?></h3>
                <p><?= $translator->__("Estrategias para promover la diversidad y la inclusión en el trabajo.") ?></p>
                <a href="php/explorar.php?modo=categorias&cat=<?= $ids_categorias['inclusion'] ?>" class="btn btn-tertiary"><?= $translator->__("Ver más") ?></a>
            </div>

            <div class="info-block">
                <i class="fas fa-globe"></i>
                <h3><?= $translator->__("Trabajo Global") ?></h3>
                <p><?= $translator->__("Análisis de las tendencias y los desafíos del mercado laboral a nivel mundial.") ?></p>
                <a href="php/explorar.php?modo=categorias&cat=<?= $ids_categorias['global'] ?>" class="btn btn-tertiary"><?= $translator->__("Ver más") ?></a>
            </div>
        </div>
    </section>

    <!-- Sección de boletín informativo -->
    <section class="newsletter-section">
        <div class="container">
            <div class="newsletter-content">
                <h2><?= $translator->__("Mantente informado") ?></h2>
                <p><?= $translator->__("Suscríbete a nuestro boletín para recibir actualizaciones sobre trabajo decente y crecimiento económico") ?></p>
                <form class="newsletter-form" action="php/suscribir.php" method="POST">
                    <input type="email" name="email" placeholder="<?= $translator->__("Tu correo electrónico") ?>" required>
                    <button type="submit" class="btn btn-primary"><?= $translator->__("Suscribirse") ?></button>
                </form>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animación de contadores para estadísticas
        const statNumbers = document.querySelectorAll('.stat-number');
        
        const animateStats = (entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const counter = entry.target;
                    const target = parseInt(counter.dataset.target);
                    const duration = 2000;
                    const step = target / (duration / 16);
                    let current = 0;
                    
                    const updateCounter = () => {
                        current += step;
                        if (current < target) {
                            counter.textContent = Math.ceil(current);
                            requestAnimationFrame(updateCounter);
                        } else {
                            counter.textContent = target;
                        }
                    };
                    
                    updateCounter();
                    observer.unobserve(counter);
                }
            });
        };
        
        const statsObserver = new IntersectionObserver(animateStats, {
            threshold: 0.5
        });
        
        statNumbers.forEach(stat => {
            statsObserver.observe(stat);
        });
    });
    </script>

    <?php $conn->close(); ?>
</body>
</html>