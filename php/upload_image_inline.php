<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
    exit();
}

include "../includes/db_config.php";

$response = ['success' => false];

// Verificar si se recibió una imagen
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $destino = "uploads/inline/";
    
    // Crear el directorio si no existe
    if (!is_dir($destino)) {
        mkdir($destino, 0777, true);
    }
    
    // Generar un nombre único para el archivo
    $nombreArchivo = time() . "_" . basename($_FILES['imagen']['name']);
    $rutaDestino = $destino . $nombreArchivo;
    
    // Intentar mover el archivo subido al destino
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
        // Éxito: Devolver la URL de la imagen
        $response = [
            'success' => true,
            'url' => $rutaDestino,
            'filename' => $nombreArchivo
        ];
    } else {
        $response = [
            'success' => false,
            'error' => 'Error al mover el archivo: ' . error_get_last()['message']
        ];
    }
} else {
    $error_code = isset($_FILES['imagen']) ? $_FILES['imagen']['error'] : 'No se recibió archivo';
    $response = [
        'success' => false,
        'error' => 'No se recibió ninguna imagen o hubo un error en la subida. Código: ' . $error_code
    ];
}

// Devolver la respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);
?>
