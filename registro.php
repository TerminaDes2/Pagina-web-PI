<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger datos del formulario y sanitizarlos
    $nombre           = trim($_POST['nombre']);
    $primer_apellido  = trim($_POST['primer_apellido']);
    $segundo_apellido = trim($_POST['segundo_apellido']);
    $correo           = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
    $contra           = $_POST['contra'];

    // Validación básica: verificar que los campos requeridos no estén vacíos
    if (empty($nombre) || empty($primer_apellido) || empty($correo) || empty($contra)) {
        echo "Por favor, completa todos los campos requeridos.";
        exit;
    }

    // Configuración de la conexión a la base de datos
    $host   = 'localhost';
    $dbname = 'economia_blog';
    $dbuser = 'root';
    $dbpass = 'administrador';

    try {
        // Crear conexión PDO
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verificar si ya existe un usuario con el mismo correo
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo");
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "El correo ya está registrado. <a href='login.html'>Inicia sesión</a>.";
            exit;
        }

        // Hashear la contraseña antes de almacenarla
        $hashed_password = password_hash($contra, PASSWORD_DEFAULT);

        // Insertar el nuevo usuario en la base de datos
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, primer_apellido, segundo_apellido, correo, contra) VALUES (:nombre, :primer_apellido, :segundo_apellido, :correo, :contra)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':primer_apellido', $primer_apellido);
        $stmt->bindParam(':segundo_apellido', $segundo_apellido);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':contra', $hashed_password);
        $stmt->execute();

        echo "Cuenta creada exitosamente. <a href='login.html'>Inicia sesión aquí</a>.";
    } catch (PDOException $e) {
        echo "Error en la conexión: " . $e->getMessage();
    }
} else {
    echo "Acceso no autorizado.";
}
?>
