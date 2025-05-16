<!DOCTYPE html>
<html>
    <head>
        <title>Buscador</title>
        <style>
            .publicacion {
                border: 1px solid #ccc;
                padding: 15px;
                margin: 10px 0;
                border-radius: 5px;
                background-color: #f9f9f9;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                text-decoration: none;
                color: inherit;
                display: block;
            }
            .publicacion:hover {
                background-color: rgb(100, 100, 100);
            }
            .publicacion h3 {
                margin: 0 0 10px;
            }
            .publicacion p {
                margin: 0;
            }
            .form-container {
                display: flex;
                justify-content: flex-end;
                margin-bottom: 20px;
            }
            .form-container input[type="text"] {
                padding: 10px;
                font-size: 16px;
                border: 1px solid #ccc;
                border-radius: 5px 0 0 5px;
                outline: none;
            }
            .form-container input[type="submit"] {
                background-color: chocolate;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 0 5px 5px 0;
                cursor: pointer;
                font-size: 18px;
            }
            .form-container input[type="submit"]:hover {
                background-color: rgb(12, 131, 37);
            }
        </style>
    </head>
    <body>
        <?php
        // Verificar si no estamos en una publicación
        if (!isset($_GET['id'])) {
        ?>
        <div class="form-container">
            <form action="" method="get">
                <input type="text" name="busqueda" placeholder="Buscador">
                <input type="submit" name="enviar" value="?">
            </form>
        </div>
        <?php
        }
        ?>

        <br><br><br>
        <?php
        include $_SERVER['DOCUMENT_ROOT'] . '/Pagina-web-PI/includes/db_config.php';
        $con = new mysqli(host, dbuser, dbpass, dbname);
        if ($con->connect_error) {
            die("Error en la conexión: " . $con->connect_error);
        }
        if (isset($_GET['enviar'])) {
            $busqueda = $_GET['busqueda'];

            $consulta = $con->query("SELECT * FROM entradas WHERE titulo LIKE '%$busqueda%' OR contenido LIKE '%$busqueda%'");

            while ($row = $consulta->fetch_array()) {
                echo '<a href="./publicacion.php?id=' . $row['id_entrada'] . '" class="publicacion">';
                echo '<h3>' . $row['titulo'] . '</h3>';
                echo '<p>' . substr($row['contenido'], 0, 100) . '...</p>'; // Mostrar un resumen del contenido
                echo '</a>';
            }
        }
        ?>
    </body>
</html>