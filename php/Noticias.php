<!DOCTYPE html>
<html>
    <head>
        <title>Noticias</title>
        <style>
            .dropdown {
                position: relative;
                display: inline-block;
            }
            .dropdown-content {
                display: none;
                position: absolute;
                background-color: #f9f9f9;
                min-width: 160px;
                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                z-index: 1;
            }
            .dropdown-content a {
                color: black;
                padding: 12px 16px;
                text-decoration: none;
                display: block;
            }
            .dropdown-content a:hover {
                background-color: #f1f1f1;
            }
            .dropdown:hover .dropdown-content {
                display: block;
            }
            .dropdown:hover .dropbtn {
                background-color: #3e8e41;
            }
        </style>
    </head>
    <body>
        <?php
        include $_SERVER['DOCUMENT_ROOT'] . '/Pagina-web-PI/includes/db_config.php';
        $con = new mysqli(host, dbuser, dbpass, dbname);
        if ($con->connect_error) {
            die("Error en la conexión: " . $con->connect_error);
        }

        $busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : ''; // Definir la variable $busqueda
        $categoria = isset($_GET['categoria']) ? $_GET['categoria'] : ''; // Definir la variable $categoria

        // Modificar la consulta para incluir el filtro por categoría si está definido
        if (!empty($categoria)) {
            $consulta = $con->query("SELECT * FROM entradas WHERE categoria = '$categoria' AND (titulo LIKE '%$busqueda%' OR contenido LIKE '%$busqueda%')");
        } else {
            $consulta = $con->query("SELECT * FROM entradas WHERE titulo LIKE '%$busqueda%' OR contenido LIKE '%$busqueda%'");
        }

        while ($row = $consulta->fetch_array()) {
            echo '<a href="./publicacion.php?id=' . $row['id_entrada'] . '" class="publicacion">';
            echo '<h3>' . $row['titulo'] . '</h3>';
            echo '<p>' . substr($row['contenido'], 0, 100) . '...</p>'; // Mostrar un resumen del contenido
            echo '</a>';
        }

        // Obtener todas las categorías de la base de datos
        $categorias = $con->query("SELECT DISTINCT categoria FROM entradas WHERE categoria IS NOT NULL");
        ?>

        <div class="dropdown">
            <button class="dropbtn">Filtrar por categorías</button>
            <div class="dropdown-content">
                <?php
                while ($categoria = $categorias->fetch_assoc()) {
                    echo '<a href="?categoria=' . urlencode($categoria['categoria']) . '">' . htmlspecialchars($categoria['categoria']) . '</a>';
                }
                ?>
            </div>
        </div>
    </body>
</html>