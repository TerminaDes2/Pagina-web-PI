<?php
session_start();

//Uso de Cookie
require_once '../includes/auth.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../php/registro.php?error=Acceso+denegado");
    exit();
}

include "db_config.php";

$conn = new mysqli(host, dbuser, dbpass, dbname);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Incluir el traductor
require_once 'traductor.php';
$translator = new Translator($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_SESSION['usuario']['id_usuario'];
    $nombre = trim($_POST['nombre']);
    $primer_apellido = trim($_POST['primer_apellido']);
    $segundo_apellido = trim($_POST['segundo_apellido']);
    $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
    
    // Verificar si se está cambiando la contraseña
    $cambiar_contra = !empty($_POST['nueva_contra']);
    
    // Primero, verificar si se ha subido una imagen
    $imagen_actualizada = false;
    $ruta_imagen = '';
    
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $directorio_destino = "../uploads/usuarios/";
        
        // Crear el directorio si no existe
        if (!is_dir($directorio_destino)) {
            mkdir($directorio_destino, 0777, true);
        }
        
        // Generar un nombre único para el archivo
        $nombre_archivo = time() . "_" . basename($_FILES['imagen']['name']);
        $ruta_destino = $directorio_destino . $nombre_archivo;
        
        // Mover el archivo subido al directorio de destino
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
            $imagen_actualizada = true;
            $ruta_imagen = "uploads/usuarios/" . $nombre_archivo;
        } else {
            header("Location: ../php/perfil.php?msg=" . urlencode("Error al subir la imagen.") . "&msgType=error");
            exit();
        }
    }
    
    // Preparar la consulta SQL según si se actualiza la contraseña y/o la imagen
    if ($cambiar_contra && $imagen_actualizada) {
        $nueva_contra = password_hash($_POST['nueva_contra'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, primer_apellido=?, segundo_apellido=?, correo=?, contra=?, imagen=? WHERE id_usuario=?");
        $stmt->bind_param("ssssssi", $nombre, $primer_apellido, $segundo_apellido, $correo, $nueva_contra, $ruta_imagen, $id_usuario);
    } elseif ($cambiar_contra) {
        $nueva_contra = password_hash($_POST['nueva_contra'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, primer_apellido=?, segundo_apellido=?, correo=?, contra=? WHERE id_usuario=?");
        $stmt->bind_param("sssssi", $nombre, $primer_apellido, $segundo_apellido, $correo, $nueva_contra, $id_usuario);
    } elseif ($imagen_actualizada) {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, primer_apellido=?, segundo_apellido=?, correo=?, imagen=? WHERE id_usuario=?");
        $stmt->bind_param("sssssi", $nombre, $primer_apellido, $segundo_apellido, $correo, $ruta_imagen, $id_usuario);
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, primer_apellido=?, segundo_apellido=?, correo=? WHERE id_usuario=?");
        $stmt->bind_param("ssssi", $nombre, $primer_apellido, $segundo_apellido, $correo, $id_usuario);
    }
    
    if ($stmt->execute()) {
        // Actualizar los datos de la sesión
        $_SESSION['usuario']['nombre'] = $nombre;
        $_SESSION['usuario']['primer_apellido'] = $primer_apellido;
        $_SESSION['usuario']['segundo_apellido'] = $segundo_apellido;
        $_SESSION['usuario']['correo'] = $correo;
        
        if ($imagen_actualizada) {
            $_SESSION['usuario']['avatar'] = $ruta_imagen;
        }
        
        header("Location: ../php/perfil.php?msg=" . urlencode("Perfil actualizado correctamente.") . "&msgType=success");
    } else {
        header("Location: ../php/perfil.php?msg=" . urlencode("Error al actualizar el perfil: " . $stmt->error) . "&msgType=error");
    }
    
    $stmt->close();
} else {
    header("Location: ../php/perfil.php?msg=" . urlencode("Método no permitido.") . "&msgType=error");
}

$conn->close();
?>
