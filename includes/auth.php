<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Evitar caché del navegador
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Zona horaria
date_default_timezone_set('America/Mexico_City');

// Verificamos si hay una cookie, actualizar los datos de la sesión siempre
if (isset($_COOKIE['usuario_id'])) {
    require_once 'db_config.php';

    try {
        $pdo = new PDO("mysql:host=" . host . ";dbname=" . dbname . ";charset=utf8", dbuser, dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$_COOKIE['usuario_id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Actualizar la sesión con los datos más recientes, incluso si ya existe una sesión
            $_SESSION['usuario'] = [
                'id_usuario'      => $usuario['id_usuario'],
                'nombre'          => $usuario['nombre'],
                'primer_apellido' => $usuario['primer_apellido'],
                'segundo_apellido'=> $usuario['segundo_apellido'],
                'correo'          => $usuario['correo'],
                'perfil'          => $usuario['perfil'] // Validar permisos actualizados
            ];
        } else {
            // Eliminar cookie inválida
            setcookie('usuario_id', '', time() - 3600, '/');
            // Si había una sesión pero la cookie ya no es válida, eliminar la sesión también
            if (isset($_SESSION['usuario'])) {
                unset($_SESSION['usuario']);
            }
        }
    } catch (PDOException $e) {
        // Puedes registrar el error si lo deseas
    }
}
?>