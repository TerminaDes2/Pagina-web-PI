<?php
session_start();
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

// Verificar que el ID del comentario está definido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ../php/perfil.php?msg=ID+de+comentario+inválido&msgType=error");
    exit();
}

$id_comentario = intval($_GET['id']);
$id_usuario = $_SESSION['usuario']['id_usuario'];

// Verificar que el comentario pertenece al usuario actual
$stmt = $conn->prepare("SELECT id_comentario FROM comentarios WHERE id_comentario = ? AND id_usuario = ?");
$stmt->bind_param("ii", $id_comentario, $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $conn->close();
    header("Location: ../php/perfil.php?msg=No+tienes+permiso+para+eliminar+este+comentario&msgType=error");
    exit();
}

// Eliminar el comentario
$stmt = $conn->prepare("DELETE FROM comentarios WHERE id_comentario = ?");
$stmt->bind_param("i", $id_comentario);

if ($stmt->execute()) {
    $conn->close();
    header("Location: ../php/perfil.php?msg=Comentario+eliminado+correctamente&msgType=success");
    exit();
} else {
    $conn->close();
    header("Location: ../php/perfil.php?msg=Error+al+eliminar+el+comentario&msgType=error");
    exit();
}
?>
