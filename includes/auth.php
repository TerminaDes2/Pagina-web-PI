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
    
    // ✅ Si la sesión ya está activa, no hacer nada más
    if (isset($_SESSION['usuario'])) {
        return;
    }
    
    // ✅ Si no hay sesión pero hay cookie, intentar restaurar sesión
    if (isset($_COOKIE['usuario_id'])) {
        require_once 'db_config.php';
    
        try {
            $pdo = new PDO("mysql:host=" . host . ";dbname=" . dbname . ";charset=utf8", dbuser, dbpass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$_COOKIE['usuario_id']]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($usuario) {
                $_SESSION['usuario'] = [
                    'id_usuario'      => $usuario['id_usuario'],
                    'nombre'          => $usuario['nombre'],
                    'primer_apellido' => $usuario['primer_apellido'],
                    'segundo_apellido'=> $usuario['segundo_apellido'],
                    'correo'          => $usuario['correo'],
                    'perfil'          => $usuario['perfil']
                ];
            } else {
                // Eliminar cookie inválida
                setcookie('usuario_id', '', time() - 3600, '/');
            }
        } catch (PDOException $e) {
            // Puedes loguear el error si lo deseas
        }
    }
?>