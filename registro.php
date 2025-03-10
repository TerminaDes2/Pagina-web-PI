<?php
session_start();

// Procesamiento de formularios
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Configuración de la conexión a la base de datos
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

    // Diferenciar entre registro e inicio de sesión según el campo "action"
    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        // Recoger y sanitizar datos del registro
        $nombre           = trim($_POST['nombre']);
        $primer_apellido  = trim($_POST['primer_apellido']);
        $segundo_apellido = trim($_POST['segundo_apellido']);
        $correo           = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
        $contra           = $_POST['contra'];

        // Validar campos requeridos
        if (empty($nombre) || empty($primer_apellido) || empty($correo) || empty($contra)) {
            echo "Por favor, completa todos los campos requeridos.";
            exit;
        }

        // Verificar si ya existe un usuario con el mismo correo
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo");
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "El correo ya está registrado. <a href='login.php'>Inicia sesión</a>.";
            exit;
        }

        // Hashear la contraseña
        $hashed_password = password_hash($contra, PASSWORD_DEFAULT);

        // Insertar el nuevo usuario en la base de datos
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
        // Recoger y sanitizar datos del inicio de sesión
        $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
        $contra = $_POST['contra'];

        if (empty($correo) || empty($contra)) {
            echo "Por favor, ingresa tanto el correo como la contraseña.";
            exit;
        }

        // Buscar usuario en la base de datos
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo");
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Verificar la contraseña
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