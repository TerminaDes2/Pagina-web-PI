<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recoger datos del formulario
    $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
    $contra = $_POST['contra'];

    // Validar que se hayan enviado ambos campos
    if (empty($correo) || empty($contra)) {
        echo "Por favor, ingresa tanto el correo como la contraseña.";
        exit;
    }

    // Configuración de la conexión a la base de datos
    $host = 'localhost';
    $dbname = 'economia_blog';
    $dbuser = 'root';
    $dbpass = 'administrador';

    try {
        // Crear conexión PDO
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Preparar y ejecutar consulta
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo");
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            // Verificar la contraseña (se asume que está hasheada)
            if (password_verify($contra, $usuario['contra'])) {
                // Credenciales correctas, se inicia la sesión
                $_SESSION['usuario'] = [
                    'nombre'          => $usuario['nombre'],
                    'primer_apellido' => $usuario['primer_apellido'],
                    'segundo_apellido'=> $usuario['segundo_apellido'],
                    'correo'          => $usuario['correo']
                ];
                // Redirigir a una página protegida o dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                echo "Contraseña incorrecta.";
            }
        } else {
            echo "Usuario no encontrado.";
        }
    } catch (PDOException $e) {
        echo "Error en la conexión: " . $e->getMessage();
    }
} else {
    echo "Acceso no autorizado.";
}
?>
