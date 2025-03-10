<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $host   = 'localhost';
    $dbname = 'economia_blog';
    $dbuser = 'root';
    $dbpass = 'administrador';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Error en la conexión: " . $e->getMessage());
    }

    
    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        
        $nombre           = trim($_POST['nombre']);
        $primer_apellido  = trim($_POST['primer_apellido']);
        $segundo_apellido = trim($_POST['segundo_apellido']);
        $correo           = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
        $contra           = $_POST['contra'];

        
        if (empty($nombre) || empty($primer_apellido) || empty($correo) || empty($contra)) {
            echo "Por favor, completa todos los campos requeridos.";
            exit;
        }

        
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo");
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "El correo ya está registrado. <a href='login.php'>Inicia sesión</a>.";
            exit;
        }

        
        $hashed_password = password_hash($contra, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, primer_apellido, segundo_apellido, correo, contra) VALUES (:nombre, :primer_apellido, :segundo_apellido, :correo, :contra)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':primer_apellido', $primer_apellido);
        $stmt->bindParam(':segundo_apellido', $segundo_apellido);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':contra', $hashed_password);
        $stmt->execute();

        header("Location: Main.html?success=Registro+exitoso");
        exit();
    } elseif (isset($_POST['action']) && $_POST['action'] === 'login') {
        
        $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
        $contra = $_POST['contra'];

        if (empty($correo) || empty($contra)) {
            echo "Por favor, ingresa tanto el correo como la contraseña.";
            exit;
        }

        
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo");
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            
            if (password_verify($contra, $usuario['contra'])) {
                $_SESSION['usuario'] = [
                    'nombre'          => $usuario['nombre'],
                    'primer_apellido' => $usuario['primer_apellido'],
                    'segundo_apellido'=> $usuario['segundo_apellido'],
                    'correo'          => $usuario['correo']
                ];
                header("Location: Main.html?success=Bienvenido");
                exit();
            } else {
                echo "Contraseña incorrecta.";
            }
        } else {
            echo "Usuario no encontrado.";
        }
    } else {
        echo "Acción no reconocida.";
    }
}
?>