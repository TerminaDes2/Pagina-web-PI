<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Práctica PHP + MySQL</title>
    <link rel="stylesheet" href="hola.css">
</head>
<body>
    <div id="barra_log">
        <img src="UdeC_2Lizq_Negro80_.png" width="180px">
    </div>
    <div id="hola">
        <div id="register">
            <form action="principalcopy3.php" method="post">
            <input type="hidden" name="action" value="register">
            <img src="UdeC Abajo_Negro.png" width="197"  style="margin-left: 50px;">
            <h1 align="center">Inicio de Sesi&oacute;n</h1>
            <?php if (isset($_GET['error'])) { ?>
                <p class="error"><?php echo $_GET['error']; ?></p>
            <?php } ?>
                <input id="nombre" type="text" name="Nombre" tabindex="1" value="" placeholder="Nombre(s)" style="border: 1px solid rgb(255, 255, 255);"><br><br>
                <input id="apellido" type="text" name="Primer_Apellido" tabindex="1" value="" placeholder="Primer Apelldo" style="border: 1px solid rgb(255, 255, 255);"><br><br>
                <input id="segundo-app" type="text" name="Segundo_Apellido" tabindex="1" value="" placeholder="Segundo Apellido" style="border: 1px solid rgb(255, 255, 255);"><br><br>
                <input id="username-cuenta" type="text" name="username" tabindex="1" value="" placeholder="Cuenta o correo" style="border: 1px solid rgb(255, 255, 255);"><br><br>
                <input id="password-cuenta" type="password" name="password" tabindex="2" value="" placeholder="Contrase&ntilde;a" style="border: 1px solid rgb(255, 255, 255);"><br><br>
            <button class="btn" type="submit">Crear cuenta</button>
            <button class="btn" type="button" onclick="window.location.href = 'Loggin.php'">Iniciar sesion</button>
            </form>
        </div>
    </div>
    </body>
</html>

<?php
function validate($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if (isset($_POST['action']) && $_POST['action'] == 'register') {
    $nombre = validate($_POST['Nombre']);
    $primerApellido = validate($_POST['Primer_Apellido']);
    $segundoApellido = validate($_POST['Segundo_Apellido']);
    $username = validate($_POST['username']);
    $pass = validate($_POST['password']);

    if (empty($nombre) || empty($primerApellido) || empty($segundoApellido) || empty($username) || empty($pass)) {
        header("Location: Loggin.php?error=Todos los campos son obligatorios");
        exit();
    }

    // PASO 1: CREAR CONEXIÓN
    $conexion = mysqli_connect("localhost", "root", "administrador", "login");

    if (!$conexion) {
        die("Error en la conexión: " . mysqli_connect_error());
    }

    // PASO 2: CREAR CONSULTA PARA INSERTAR
    $insert = "INSERT INTO profesor (nombre, primer_apellido, segundo_apellido, correo, contra) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conexion, $insert);

    if (!$stmt) {
        die("Error en la consulta preparada: " . mysqli_error($conexion));
    }

    mysqli_stmt_bind_param($stmt, "sssss", $nombre, $primerApellido, $segundoApellido, $username, $pass);

    // PASO 3: EJECUTAR LA CONSULTA
    $resultado = mysqli_stmt_execute($stmt);

    if ($resultado) {
        // Registro exitoso
        // Aquí puedes redirigir al usuario a una página de confirmación o realizar otras acciones
        header('Location: principalcopy3.php');
        exit();
    } else {
        // Error en el registro
        header("Location: Loggin.php?error=Error al crear la cuenta");
        exit();
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conexion);
}
?>