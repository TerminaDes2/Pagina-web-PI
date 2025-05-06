<?php
    session_start();
    $codigoIngresado = $_POST['codigo'];
    include "../includes/db_config.php";
    if (!isset($_SESSION['registro'])) {
        die("No hay datos para verificar.");
    }

    $datos = $_SESSION['registro'];

    if ($codigoIngresado == $datos['codigo']) {
        // Conectar con base de datos
        $conn = new mysqli(host, dbuser, dbpass, dbname);
        if ($conn->connect_error) {
            die("Error de conexión: " . $conn->connect_error);
        }

        $perfil = 'cliente';
        $hashed_password = password_hash($datos['contra'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, primer_apellido, segundo_apellido, correo, contra, perfil) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $datos['nombre'], $datos['primer_apellido'], $datos['segundo_apellido'], $datos['correo'], $hashed_password, $perfil);
        $stmt->execute();
        $stmt->close();
        $conn->close();

        echo "✅ Usuario registrado correctamente.";
        unset($_SESSION['registro']);
    } else {
        echo "❌ Código incorrecto.";
    }
?>
