<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

// Incluir configuración de la base de datos
require_once "includes/db_config.php";

// Establecer conexión a la base de datos
$conn = new mysqli(host, dbuser, dbpass, dbname);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Incluir el traductor
require_once "includes/traductor.php";
$traductor = new Translator($conn);
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?? 'es' ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Error 404 - POALCE</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="/Pagina-web-PI/assets/css/main.css">
        <link rel="stylesheet" href="/Pagina-web-PI/estilos.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="icon" href="/Pagina-web-PI/assets/img/POALCE-logo.ico" type="image/x-icon">
    </head>
    <body class="error-page">
        <div class="error-container">
            <div class="shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
            </div>
            
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            
            <h1 class="error-title"><?php echo $traductor->__('Error 404'); ?></h1>
            <p class="error-subtitle"><?php echo $traductor->__('Lo sentimos, la página que estás buscando no se encuentra disponible.'); ?></p>
            
            <img src="/Pagina-web-PI/404.gif" alt="Error 404" class="error-img">
            
            <a href="/Pagina-web-PI/index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> <?php echo $traductor->__('Volver a la página principal'); ?>
            </a>
        </div>
    </body>
</html>
<?php $conn->close(); ?>