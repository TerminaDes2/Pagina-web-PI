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
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Paginación
$articulos_por_pagina = 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina_actual - 1) * $articulos_por_pagina;

// Contar total de artículos para la paginación
$sql_total = "SELECT COUNT(*) as total FROM entradas";
$result_total = $conn->query($sql_total);
$fila_total = $result_total->fetch_assoc();
$total_articulos = $fila_total['total'];
$total_paginas = ceil($total_articulos / $articulos_por_pagina);

// Consulta para obtener todas las publicaciones con paginación
$sqlPosts = "SELECT e.id_entrada, e.titulo, e.contenido, e.cita, e.fecha, e.categoria, 
             u.nombre as autor, i.imagen, c.categoria as nombre_categoria
             FROM entradas e 
             LEFT JOIN imagenes i ON i.id_entrada = e.id_entrada 
             LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario
             LEFT JOIN categorias c ON e.categoria = c.id_categoria
             ORDER BY e.fecha DESC
             LIMIT ?, ?";

$stmt = $conn->prepare($sqlPosts);
$stmt->bind_param("ii", $inicio, $articulos_por_pagina);
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
    <title><?= $translator->__("Todas las Noticias") ?> - Voces del Proceso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/categorias.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .paginacion {
            text-align: center;
            margin: 30px 0;
        }
        .paginacion a, .paginacion span {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 5px;
            border: 1px solid #ddd;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .paginacion a:hover {
            background-color: #f5f5f5;
        }
        .paginacion .actual {
            background-color: #719743;
            color: white;
            border-color: #719743;
        }
        .categoria-tag {
            display: inline-block;
            background-color: #e8f4d9;
            color: #5e7f37;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <section class="categoria-header">
        <div class="container">
            <h1><?= $translator->__("Todas las Noticias") ?></h1>
            <p><?= $translator->__("Descubre todas nuestras publicaciones") ?></p>
        </div>
    </section>

    <section class="articulos-categoria">
        <div class="container">
            <?php if (empty($articulos)): ?>
                <div class="no-articulos">
                    <p><?= $translator->__("No hay artículos disponibles.") ?></p>
                </div>
            <?php else: ?>
                <div class="grid-articulos" style="grid-template-columns: 1fr;">
                    <?php foreach ($articulos as $articulo): ?>
                    <article class="articulo-card" style="display: flex; flex-direction: row;">
                        <div class="articulo-imagen" style="width: 30%; height: 250px;">
                            <?php if (!empty($articulo['imagen'])): ?>
                                <a href="publicacion.php?id=<?= $articulo['id_entrada'] ?>">
                                    <img src="<?= htmlspecialchars($articulo['imagen']) ?>" alt="<?= htmlspecialchars($articulo['titulo']) ?>">
                                </a>
                            <?php else: ?>
                                <div class="sin-imagen">
                                    <i class="fas fa-newspaper"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="articulo-contenido" style="width: 70%; padding: 25px;">
                            <?php if (!empty($articulo['nombre_categoria'])): ?>
                                <span class="categoria-tag"><?= htmlspecialchars($articulo['nombre_categoria']) ?></span>
                            <?php endif; ?>
                            <h2 style="display: block; margin-bottom: 15px; font-size: 1.6em;"><a href="publicacion.php?id=<?= $articulo['id_entrada'] ?>"><?= htmlspecialchars($articulo['titulo']) ?></a></h2>
                            <div class="post-meta">
                                <?php if (!empty($articulo['autor'])): ?>
                                <span class="author"><i class="fas fa-user"></i> <?= htmlspecialchars($articulo['autor']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($articulo['fecha'])): ?>
                                <span class="date"><i class="far fa-calendar-alt"></i> <?= date('d/m/Y', strtotime($articulo['fecha'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($articulo['cita'])): ?>
                                <blockquote><?= htmlspecialchars($articulo['cita']) ?></blockquote>
                            <?php endif; ?>
                            <p class="articulo-extracto">
                                <?php
                                // Eliminar etiquetas h2 y su contenido antes de crear el extracto
                                $contenido_limpio = preg_replace('/<h2>.*?<\/h2>/is', '', $articulo['contenido']);
                                echo strip_tags(substr($contenido_limpio, 0, 200)) . '...';
                                ?>
                            </p>
                            <a href="publicacion.php?id=<?= $articulo['id_entrada'] ?>" class="btn-leer-mas"><?= $translator->__("Leer más") ?></a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginación -->
                <div class="paginacion">
                    <?php if ($pagina_actual > 1): ?>
                        <a href="?pagina=<?= $pagina_actual - 1 ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    
                    <?php
                    // Mostrar un número limitado de enlaces a páginas
                    $inicio_rango = max(1, $pagina_actual - 2);
                    $fin_rango = min($total_paginas, $pagina_actual + 2);
                    
                    if ($inicio_rango > 1) {
                        echo '<a href="?pagina=1">1</a>';
                        if ($inicio_rango > 2) echo '<span>...</span>';
                    }
                    
                    for ($i = $inicio_rango; $i <= $fin_rango; $i++) {
                        if ($i == $pagina_actual) {
                            echo '<span class="actual">' . $i . '</span>';
                        } else {
                            echo '<a href="?pagina=' . $i . '">' . $i . '</a>';
                        }
                    }
                    
                    if ($fin_rango < $total_paginas) {
                        if ($fin_rango < $total_paginas - 1) echo '<span>...</span>';
                        echo '<a href="?pagina=' . $total_paginas . '">' . $total_paginas . '</a>';
                    }
                    ?>
                    
                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a href="?pagina=<?= $pagina_actual + 1 ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <?php $conn->close(); ?>
</body>
</html>
