<?php
session_start();
if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['perfil'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

// Verificar que sea una solicitud POST con una imagen
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['image'])) {
    header('HTTP/1.0 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No se recibió ninguna imagen']);
    exit();
}

// Directorio para almacenar imágenes inline (embebidas en el contenido)
$uploadDir = '../uploads/inline/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Validar la imagen
$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($file['type'], $allowedTypes)) {
    header('HTTP/1.0 400 Bad Request');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
    exit();
}

// Generar un nombre único para el archivo
$fileName = time() . '_' . preg_replace('/\s+/', '_', $file['name']);
$uploadPath = $uploadDir . $fileName;

// Mover el archivo al directorio de destino
if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    // Construir la URL relativa para el front-end
    $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $baseUrl .= $_SERVER['HTTP_HOST'];
    $imageUrl = $baseUrl . '/Pagina-web-PI/uploads/inline/' . $fileName;
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'file' => [
            'url' => $imageUrl,
            'name' => $fileName
        ]
    ]);
} else {
    header('HTTP/1.0 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error al subir la imagen']);
}
?>
