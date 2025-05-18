<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../php/registro.php?error=Acceso+denegado");
    exit();
}

// Verificar que el usuario es administrador
if ($_SESSION['usuario']['perfil'] !== 'admin') {
    header("Location: ../php/perfil.php?msg=No+tienes+permiso+para+realizar+esta+acción&msgType=error");
    exit();
}

include "db_config.php";

$conn = new mysqli(host, dbuser, dbpass, dbname);
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Verificar que el ID de la publicación está definido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../php/perfil.php?msg=ID+de+publicación+inválido&msgType=error");
    exit();
}

$id_entrada = intval($_GET['id']);
$id_usuario = $_SESSION['usuario']['id_usuario'];

// Verificar que la publicación pertenece al usuario actual
$stmt = $conn->prepare("SELECT id_entrada FROM entradas WHERE id_entrada = ? AND id_usuario = ?");
$stmt->bind_param("ii", $id_entrada, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $conn->close();
    header("Location: ../php/perfil.php?msg=No+tienes+permiso+para+eliminar+esta+publicación&msgType=error");
    exit();
}

// Iniciar transacción para eliminar comentarios, imágenes y la publicación
$conn->begin_transaction();

try {
    // Eliminar primero los comentarios asociados a la publicación
    $stmt = $conn->prepare("DELETE FROM comentarios WHERE id_entrada = ?");
    $stmt->bind_param("i", $id_entrada);
    $stmt->execute();
    
    // Eliminar las imágenes asociadas a la publicación
    $stmt = $conn->prepare("DELETE FROM imagenes WHERE id_entrada = ?");
    $stmt->bind_param("i", $id_entrada);
    $stmt->execute();
    
    // Finalmente eliminar la publicación
    $stmt = $conn->prepare("DELETE FROM entradas WHERE id_entrada = ?");
    $stmt->bind_param("i", $id_entrada);
    $stmt->execute();
    
    // Si todo salió bien, confirmar la transacción
    $conn->commit();
    header("Location: ../php/perfil.php?msg=Publicación+eliminada+correctamente&msgType=success");
    exit();
} catch (Exception $e) {
    // Si hubo algún error, deshacer la transacción
    $conn->rollback();
    header("Location: ../php/perfil.php?msg=Error+al+eliminar+la+publicación&msgType=error");
    exit();
} finally {
    $conn->close();
}
?>
