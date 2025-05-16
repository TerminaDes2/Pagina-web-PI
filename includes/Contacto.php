<?php
header('Content-Type: application/json');
require_once __DIR__ . '/conexion.php';

// Verificar que la conexión a la base de datos está disponible
if (!isset($conn) || $conn->connect_error) {
    error_log("Error de conexión a la base de datos en Contacto.php: " . (isset($conn->connect_error) ? $conn->connect_error : "Variable conn no disponible"));
    echo json_encode([
        "error" => "Error de conexión a la base de datos. Por favor contacta al administrador.",
        "icon" => "error"
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar que todos los campos requeridos estén presentes
    if (empty($_POST['nombre']) || empty($_POST['correo']) || empty($_POST['asunto'])) {
        echo json_encode([
            "error" => "Por favor completa todos los campos obligatorios.",
            "icon" => "warning"
        ]);
        exit;
    }
    
    $nombre = htmlspecialchars($_POST['nombre']);
    $correo = htmlspecialchars($_POST['correo']);
    $asunto = htmlspecialchars($_POST['asunto']);
    
    // Obtener el mensaje (dar prioridad a mensaje-hidden)
    if (!empty($_POST['mensaje-hidden'])) {
        $mensaje = $_POST['mensaje-hidden'];
    } elseif (!empty($_POST['mensaje'])) {
        $mensaje = $_POST['mensaje'];
    } else {
        $mensaje = "Sin contenido";
    }
    
    try {
        // Verificar si existe la tabla mensajes, si no, crearla
        $sql_check_table = "SHOW TABLES LIKE 'mensajes_contacto'";
        $result_check = $conn->query($sql_check_table);
        
        if (!$result_check) {
            throw new Exception("Error al verificar tabla: " . $conn->error);
        }
        
        if ($result_check->num_rows == 0) {
            // Crear la tabla si no existe
            $sql_create_table = "CREATE TABLE mensajes_contacto (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                correo VARCHAR(100) NOT NULL,
                asunto VARCHAR(150) NOT NULL,
                mensaje TEXT NOT NULL,
                fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if (!$conn->query($sql_create_table)) {
                throw new Exception("Error al crear tabla: " . $conn->error);
            }
        }
        
        // Insertar el mensaje en la base de datos
        $stmt = $conn->prepare("INSERT INTO mensajes_contacto (nombre, correo, asunto, mensaje) VALUES (?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $stmt->bind_param("ssss", $nombre, $correo, $asunto, $mensaje);
        
        if ($stmt->execute()) {
            echo json_encode([
                "success" => "Mensaje enviado correctamente. Nos pondremos en contacto pronto.",
                "icon" => "success"
            ]);
            
            // Notificar por consola para fines de depuración
            error_log("Mensaje de contacto guardado: $nombre - $correo - $asunto");
        } else {
            throw new Exception("Error al ejecutar la consulta: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error en Contacto.php: " . $e->getMessage());
        echo json_encode([
            "error" => "Hubo un error al enviar tu mensaje. Por favor intenta de nuevo.",
            "details" => $e->getMessage(),
            "icon" => "error"
        ]);
    }
    
    exit;
}

// Si llegamos aquí, es porque no se hizo un POST
echo json_encode([
    "error" => "Método de solicitud no válido.",
    "icon" => "error"
]);
exit;
?>

