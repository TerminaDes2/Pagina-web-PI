<?php
// Incluye la configuración de base de datos
require_once __DIR__ . '/db_config.php';

// Crea la conexión
$conn = new mysqli(host, dbuser, dbpass, dbname);

// Verifica conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establece la codificación UTF-8
$conn->set_charset("utf8");
?>
