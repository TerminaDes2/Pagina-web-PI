<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: ../php/registro.php?error=Acceso+denegado");
    exit();
}

require_once 'db_config.php';
require_once 'traductor.php';

// Conexión a la base de datos
$conn = new mysqli(host, dbuser, dbpass, dbname);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Inicializar el traductor
$translator = new Translator($conn);

// Obtener datos del formulario
$id_usuario = $_SESSION['usuario']['id_usuario'];
$nombre = $_POST['nombre'];
$primer_apellido = $_POST['primer_apellido'];
$segundo_apellido = $_POST['segundo_apellido'];
$correo = $_POST['correo'];
$nueva_contra = $_POST['nueva_contra'];

// Verificar si hay una verificación en sesión para correo o contraseña
$cambio_verificado = false;

if (isset($_SESSION['perfil_verificacion'])) {
    $verificacion = $_SESSION['perfil_verificacion'];
    
    // Si la verificación fue para correo, usamos el nuevo valor verificado
    if ($verificacion['tipo'] == 'correo') {
        $correo = $verificacion['nuevo_valor'];
        $cambio_verificado = true;
    }
    
    // Si la verificación fue para contraseña, usamos la nueva contraseña verificada
    if ($verificacion['tipo'] == 'password') {
        $nueva_contra = $verificacion['nuevo_valor'];
        $cambio_verificado = true;
    }
    
    // Eliminar datos de verificación
    unset($_SESSION['perfil_verificacion']);
}

// Procesar la imagen si se ha subido una nueva
$ruta_imagen = $_SESSION['usuario']['imagen']; // Mantener la imagen actual por defecto

if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $directorio_destino = "../uploads/usuarios/";
    
    if (!is_dir($directorio_destino)) {
        mkdir($directorio_destino, 0777, true);
    }
    
    $nombre_archivo = time() . "_" . basename($_FILES['imagen']['name']);
    $ruta_destino = $directorio_destino . $nombre_archivo;
    
    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $ruta_destino)) {
        $ruta_imagen = "uploads/usuarios/" . $nombre_archivo;
    } else {
        header("Location: ../php/perfil.php?msg=" . urlencode($translator->__("Error al subir la imagen")) . "&msgType=error");
        exit();
    }
}

// Iniciar la transacción
$conn->begin_transaction();

try {
    // Actualizar datos generales
    $stmt = $conn->prepare("UPDATE usuarios SET nombre = ?, primer_apellido = ?, segundo_apellido = ?, imagen = ? WHERE id_usuario = ?");
    $stmt->bind_param("ssssi", $nombre, $primer_apellido, $segundo_apellido, $ruta_imagen, $id_usuario);
    $stmt->execute();
    
    // Si el correo fue cambiado y verificado, actualizarlo
    if ($correo != $_SESSION['usuario']['correo'] && $cambio_verificado) {
        $stmt = $conn->prepare("UPDATE usuarios SET correo = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $correo, $id_usuario);
        $stmt->execute();
        $_SESSION['usuario']['correo'] = $correo;
    }
    
    // Si la contraseña fue cambiada y verificada, actualizarla
    if (!empty($nueva_contra)) {
        $hashed_password = password_hash($nueva_contra, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET contra = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $hashed_password, $id_usuario);
        $stmt->execute();
    }
    
    // Confirmar la transacción
    $conn->commit();
    
    // Actualizar los datos en la sesión
    $_SESSION['usuario']['nombre'] = $nombre;
    $_SESSION['usuario']['primer_apellido'] = $primer_apellido;
    $_SESSION['usuario']['segundo_apellido'] = $segundo_apellido;
    $_SESSION['usuario']['imagen'] = $ruta_imagen;
    
    header("Location: ../php/perfil.php?msg=" . urlencode($translator->__("Perfil actualizado correctamente")) . "&msgType=success");
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $conn->rollback();
    header("Location: ../php/perfil.php?msg=" . urlencode($translator->__("Error al actualizar el perfil: ") . $e->getMessage()) . "&msgType=error");
}

$conn->close();
?>
