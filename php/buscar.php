<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

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
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['q']) ? "?q=" . urlencode($_GET['q']) : ""));
    exit();
}

// Verificar que se haya especificado un término de búsqueda
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
$resultados = [];

// Realizar la búsqueda si hay término
if (!empty($busqueda)) {
    $termino_busqueda = "%{$busqueda}%";
    
    $sql = "SELECT e.id_entrada, e.titulo, e.contenido, e.fecha, e.id_usuario, 
                   i.imagen, 
                   u.nombre, u.primer_apellido, u.segundo_apellido
            FROM entradas e 
            LEFT JOIN imagenes i ON i.id_entrada = e.id_entrada
            LEFT JOIN usuarios u ON u.id_usuario = e.id_usuario
            WHERE e.titulo LIKE ? OR e.contenido LIKE ?
            GROUP BY e.id_entrada
            ORDER BY e.fecha DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $termino_busqueda, $termino_busqueda);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Traducir contenido según el idioma
        $row['titulo'] = $translator->traducirTexto($row['titulo']);
        $row['contenido'] = $translator->traducirTexto($row['contenido']);
        
        // Formatear el nombre del autor
        $row['autor'] = $row['nombre'] . ' ' . $row['primer_apellido'] . ' ' . $row['segundo_apellido'];
        
        // Eliminar etiquetas h2 y su contenido antes de crear el extracto
        $contenido_limpio = preg_replace('/<h2>.*?<\/h2>/is', '', $row['contenido']);
        $contenido_limpio = strip_tags($contenido_limpio);
        
        // Obtener un extracto del contenido (primeras 20 palabras o menos)
        $palabras = explode(' ', $contenido_limpio);
        $palabras = array_slice($palabras, 0, 20);
        $row['extracto'] = implode(' ', $palabras) . (count(explode(' ', $contenido_limpio)) > 20 ? '...' : '');
        
        // Verificar si hay imagen, si no, asignar una por defecto
        if (empty($row['imagen'])) {
            $row['imagen'] = "../assets/img/no-image.jpg";
        }
        
        $resultados[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translator->__("Resultados de búsqueda") ?> - <?= htmlspecialchars($busqueda) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .search-results {
            padding: 40px 0;
        }
        .search-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .search-term {
            color: #007bff;
        }
        .result-count {
            color: #6c757d;
            margin-top: 10px;
        }
        .search-results-container {
            max-width: 85%;
            margin: 0 auto;
        }
        .search-result-item {
            display: flex;
            margin-bottom: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .result-image {
            flex: 0 0 250px;
            height: 180px;
            overflow: hidden;
        }
        .result-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .result-content {
            flex: 1;
            padding: 20px;
            position: relative;
        }
        .result-title {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 22px;
            display: none;
        }
        .result-title a {
            color: #333;
            text-decoration: none;
            transition: color 0.3s;
        }
        .result-title a:hover {
            color: #007bff;
        }
        .result-extract {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        .result-meta {
            color: #777;
            font-size: 0.9em;
            position: absolute;
            bottom: 20px;
            width: calc(100% - 40px);
            display: flex;
            justify-content: space-between;
        }
        .result-date {
            margin-right: 15px;
        }
        .result-author {
            font-style: italic;
        }
        .no-results {
            text-align: center;
            padding: 40px 0;
            color: #666;
        }
        .no-results i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
            display: block;
        }
        @media (max-width: 768px) {
            .search-result-item {
                flex-direction: column;
            }
            .result-image {
                flex: 0 0 auto;
                height: 200px;
                width: 100%;
            }
            .result-meta {
                position: static;
                margin-top: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include "../includes/header.php"; ?>

    <section class="search-results">
        <div class="container">
            <div class="search-header">
                <h1><?= $translator->__("Resultados de búsqueda para") ?>: <span class="search-term">"<?= htmlspecialchars($busqueda) ?>"</span></h1>
                <?php if (!empty($busqueda)): ?>
                    <p class="result-count">
                        <?= count($resultados) ?> <?= $translator->__("resultados encontrados") ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php if (empty($busqueda)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p><?= $translator->__("Introduce un término de búsqueda para encontrar noticias.") ?></p>
                </div>
            <?php elseif (empty($resultados)): ?>
                <div class="no-results">
                    <i class="fas fa-exclamation-circle"></i>
                    <p><?= $translator->__("No se encontraron resultados para") ?> "<?= htmlspecialchars($busqueda) ?>".</p>
                    <p><?= $translator->__("Intenta con otros términos de búsqueda.") ?></p>
                </div>
            <?php else: ?>
                <div class="search-results-container">
                    <?php foreach ($resultados as $resultado): ?>
                        <article class="search-result-item">
                            <div class="result-image">
                                <a href="publicacion.php?id=<?= $resultado['id_entrada'] ?>">
                                    <img src="<?= htmlspecialchars($resultado['imagen']) ?>" alt="<?= htmlspecialchars($resultado['titulo']) ?>">
                                </a>
                            </div>
                            <div class="result-content">
                                <h2 class="result-title">
                                    <a href="publicacion.php?id=<?= $resultado['id_entrada'] ?>">
                                        <?= htmlspecialchars($resultado['titulo']) ?>
                                    </a>
                                </h2>
                                <p class="result-extract">
                                    <?= htmlspecialchars($resultado['extracto']) ?>
                                </p>
                                <div class="result-meta">
                                    <span class="result-date">
                                        <i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($resultado['fecha'])) ?>
                                    </span>
                                    <span class="result-author">
                                        <i class="far fa-user"></i> <?= htmlspecialchars($resultado['autor']) ?>
                                    </span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include "../includes/footer.php"; ?>
</body>
</html>
<?php $conn->close(); ?>
