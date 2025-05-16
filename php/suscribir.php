<?php
session_start();
require_once '../includes/db_config.php';
require_once '../includes/traductor.php';

// Conexión a la base de datos
$conn = new mysqli(host, dbuser, dbpass, dbname);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

$translator = new Translator($conn);

$mensaje = '';
$tipo = '';

// Verificar si se envió un email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    
    if ($email) {
        // Verificar si ya existe en la base de datos
        $stmt = $conn->prepare("SELECT id FROM suscriptores WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $mensaje = $translator->__("Este correo ya está suscrito a nuestro boletín.");
            $tipo = 'info';
        } else {
            // Insertar nuevo suscriptor
            $stmt = $conn->prepare("INSERT INTO suscriptores (email, fecha_registro) VALUES (?, NOW())");
            $stmt->bind_param("s", $email);
            
            if ($stmt->execute()) {
                $mensaje = $translator->__("¡Gracias por suscribirte a nuestro boletín!");
                $tipo = 'success';
            } else {
                $mensaje = $translator->__("Ocurrió un error al procesar tu suscripción. Por favor, inténtalo de nuevo.");
                $tipo = 'error';
            }
        }
        
        $stmt->close();
    } else {
        $mensaje = $translator->__("Por favor, introduce un correo electrónico válido.");
        $tipo = 'error';
    }
}

// Redirigir a la página principal con mensaje
$_SESSION['mensaje'] = $mensaje;
$_SESSION['tipo_mensaje'] = $tipo;
header("Location: ../index.php#newsletter");
exit();
?>
