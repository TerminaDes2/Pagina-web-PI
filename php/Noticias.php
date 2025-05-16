<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Pagina-web-PI/includes/db_config.php';
$con = new mysqli(host, dbuser, dbpass, dbname);
if ($con->connect_error) {
    die("Error en la conexión: " . $con->connect_error);
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/Pagina-web-PI/includes/traductor.php';
$translator = new Translator($con);

$busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
$categoria = isset($_GET['categoria']) ? $_GET['categoria'] : '';

// Si se accede a la página sin parámetros, limpiar el filtro de categoría
if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($_GET)) {
    $categoria = '';
}

// Consulta publicaciones con imagen y nombre de categoría
$where = [];
if (!empty($categoria)) {
    $where[] = "e.categoria = '" . $con->real_escape_string($categoria) . "'";
}
if (!empty($busqueda)) {
    $busq = $con->real_escape_string($busqueda);
    $where[] = "(e.titulo LIKE '%$busq%' OR e.contenido LIKE '%$busq%')";
}
$where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$consulta = $con->query(
    "SELECT e.id_entrada, e.titulo, e.contenido, i.imagen, c.categoria AS nombre_categoria
     FROM entradas e
     LEFT JOIN imagenes i ON i.id_entrada = e.id_entrada
     LEFT JOIN categorias c ON e.categoria = c.id_categoria
     $where_sql
     ORDER BY e.id_entrada DESC"
);

// Obtener todas las categorías de la base de datos y guardarlas en un array
$categorias_result = $con->query("SELECT id_categoria, categoria FROM categorias WHERE categoria IS NOT NULL");
$categorias = [];
while ($cat = $categorias_result->fetch_assoc()) {
    $categorias[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?? 'es' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $translator->__("Noticias") ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .news-section {
            background: #f7f7f7;
            padding: 40px 0 20px 0;
        }
        .news-container {
            max-width: 1100px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 32px 24px;
        }
        .news-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .news-header h2 {
            margin: 0;
            font-size: 2em;
            color: #2185a9;
        }
        .category-filter {
            position: relative;
            display: inline-block;
        }
        .category-btn {
            background: #2185a9;
            color: #fff;
            border: none;
            padding: 10px 22px;
            border-radius: 8px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.2s;
        }
        .category-btn:hover {
            background: #0c8325;
        }
        .category-dropdown {
            display: none;
            position: absolute;
            background: #fff;
            min-width: 180px;
            box-shadow: 0 4px 16px rgba(33,133,169,0.12);
            border-radius: 8px;
            z-index: 10;
            margin-top: 4px;
        }
        .category-filter:hover .category-dropdown {
            display: block;
        }
        .category-dropdown a {
            color: #2185a9;
            padding: 12px 18px;
            text-decoration: none;
            display: block;
            border-radius: 8px;
            transition: background 0.15s;
        }
        .category-dropdown a:hover {
            background: #f0f6fa;
        }
        .remove-filter {
            color: #fff;
            background: #c0392b;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            margin-left: 12px;
            font-size: 0.98em;
        }
        .news-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 32px;
        }
        .news-card {
            background: #f0f6fa;
            border-radius: 10px;
            box-shadow: 0 1px 6px rgba(33,133,169,0.08);
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
            min-height: 220px;
        }
        .news-card:hover {
            box-shadow: 0 4px 16px rgba(33,133,169,0.15);
            background: #e6f2fa;
        }
        .news-card img {
            max-width: 100%;
            max-height: 160px;
            border-radius: 8px;
            object-fit: cover;
        }
        .news-card h3 {
            margin: 0 0 6px 0;
            font-size: 1.15em;
            color: #2185a9;
        }
        .news-card p {
            margin: 0;
            color: #444;
        }
        .news-card .news-category {
            font-size: 0.95em;
            color: #888;
            margin-top: 6px;
        }
        @media (max-width: 700px) {
            .news-container { padding: 16px 8px; }
            .news-header { flex-direction: column; gap: 18px; }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <section class="news-section">
        <div class="news-container">
            <div class="news-header">
                <h2><i class="fas fa-newspaper"></i> <?= $translator->__("Noticias") ?></h2>
                <div class="category-filter">
                    <button class="category-btn"><i class="fas fa-filter"></i> <?= $translator->__("Filtrar por categorías") ?></button>
                    <div class="category-dropdown">
                        <?php foreach ($categorias as $cat): ?>
                            <a href="?categoria=<?= urlencode($cat['id_categoria']) ?>"><?= htmlspecialchars($cat['categoria']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if (!empty($categoria)): ?>
                    <a href="Noticias.php" class="remove-filter"><?= $translator->__("Quitar filtro") ?></a>
                <?php endif; ?>
            </div>
            <div class="news-list">
                <?php
                if ($consulta->num_rows > 0) {
                    while ($row = $consulta->fetch_assoc()) {
                        echo '<a href="./publicacion.php?id=' . $row['id_entrada'] . '" class="news-card">';
                        if (!empty($row['imagen'])) {
                            echo '<img src="/Pagina-web-PI/imagenes/' . htmlspecialchars($row['imagen']) . '" alt="' . htmlspecialchars($row['titulo']) . '">';
                        }
                        echo '<h3>' . htmlspecialchars($row['titulo']) . '</h3>';
                        $resumen = strip_tags($row['contenido']);
                        echo '<p>' . htmlspecialchars(substr($resumen, 0, 120)) . '...</p>';
                        if (!empty($row['nombre_categoria'])) {
                            echo '<div class="news-category"><i class="fas fa-tag"></i> ' . htmlspecialchars($row['nombre_categoria']) . '</div>';
                        }
                        echo '</a>';
                    }
                } else {
                    echo '<p style="color:#888;">' . $translator->__("No hay noticias disponibles.") . '</p>';
                }
                ?>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>
</body>
</html>