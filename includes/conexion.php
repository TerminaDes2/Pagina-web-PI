<?php
// Incluir el archivo de configuración si aún no ha sido incluido
if (!defined('host')) {
    require_once __DIR__ . '/db_config.php';
}

// Establecer la conexión a la base de datos
$conn = new mysqli(host, dbuser, dbpass, dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}

// Establecer la codificación de caracteres
$conn->set_charset("utf8");
?>
