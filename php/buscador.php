<?php
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/Pagina-web-PI/includes/db_config.php';
$con = new mysqli(host, dbuser, dbpass, dbname);
if ($con->connect_error) {
    die("Error en la conexión: " . $con->connect_error);
}
require_once $_SERVER['DOCUMENT_ROOT'] . '/Pagina-web-PI/includes/traductor.php';
$translator = new Translator($con);
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?? 'es' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $translator->__("Buscador") ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .search-section {
            background: #f7f7f7;
            padding: 40px 0 20px 0;
        }
        .search-container {
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 32px 24px;
        }
        .search-form {
            display: flex;
            margin-bottom: 32px;
        }
        .search-form input[type="text"] {
            flex: 1;
            padding: 12px 16px;
            font-size: 18px;
            border: 1px solid #ccc;
            border-radius: 8px 0 0 8px;
            outline: none;
        }
        .search-form button {
            background: #2185a9;
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 0 8px 8px 0;
            font-size: 18px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .search-form button:hover {
            background: #0c8325;
        }
        .results-list {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .result-card {
            display: flex;
            gap: 20px;
            background: #f0f6fa;
            border-radius: 10px;
            box-shadow: 0 1px 6px rgba(33,133,169,0.08);
            padding: 18px;
            align-items: flex-start;
            transition: box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
        }
        .result-card:hover {
            box-shadow: 0 4px 16px rgba(33,133,169,0.15);
            background: #e6f2fa;
        }
        .result-card img {
            max-width: 120px;
            border-radius: 8px;
            object-fit: cover;
        }
        .result-info h3 {
            margin: 0 0 8px 0;
            font-size: 1.2em;
            color: #2185a9;
        }
        .result-info p {
            margin: 0;
            color: #444;
        }
        @media (max-width: 600px) {
            .search-container { padding: 16px 8px; }
            .result-card { flex-direction: column; align-items: stretch; }
            .result-card img { max-width: 100%; }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <section class="search-section">
        <div class="search-container">
            <form class="search-form" method="get" action="">
                <input type="text" name="busqueda" placeholder="<?= $translator->__("Buscar publicaciones...") ?>" value="<?= isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : '' ?>">
                <button type="submit" name="enviar"><i class="fas fa-search"></i> <?= $translator->__("Buscar") ?></button>
            </form>
            <div class="results-list">
                <?php
                if (isset($_GET['enviar'])) {
                    $busqueda = $con->real_escape_string($_GET['busqueda']);
                    $consulta = $con->query(
                        "SELECT e.id_entrada, e.titulo, e.contenido, i.imagen, c.categoria AS nombre_categoria
                         FROM entradas e
                         LEFT JOIN imagenes i ON i.id_entrada = e.id_entrada
                         LEFT JOIN categorias c ON e.categoria = c.id_categoria
                         WHERE e.titulo LIKE '%$busqueda%' OR e.contenido LIKE '%$busqueda%'"
                    );
                    if ($consulta->num_rows > 0) {
                        while ($row = $consulta->fetch_assoc()) {
                            echo '<a href="./publicacion.php?id=' . $row['id_entrada'] . '" class="result-card">';
                            if (!empty($row['imagen'])) {
                                // Cambia la ruta para que siempre apunte a la carpeta de imágenes
                                echo '<img src="/Pagina-web-PI/imagenes/' . htmlspecialchars($row['imagen']) . '" alt="' . htmlspecialchars($row['titulo']) . '">';
                            }
                            echo '<div class="result-info">';
                            echo '<h3>' . htmlspecialchars($row['titulo']) . '</h3>';
                            $resumen = strip_tags($row['contenido']);
                            echo '<p>' . htmlspecialchars(substr($resumen, 0, 120)) . '...</p>';
                            // if (!empty($row['nombre_categoria'])) {
                            //     echo '<p style="font-size:0.95em;color:#888;"><strong>' . $translator->__("Categoría") . ':</strong> ' . htmlspecialchars($row['nombre_categoria']) . '</p>';
                            // }
                            echo '</div></a>';
                        }
                    } else {
                        echo '<p style="color:#888;">' . $translator->__("No se encontraron resultados.") . '</p>';
                    }
                }
                ?>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>
</body>
</html>