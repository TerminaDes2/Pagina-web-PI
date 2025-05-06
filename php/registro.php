<?php
// Libreria PHPMailer para poder mandar correo de verificación
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Incluye los archivos necesarios de PHPMailer
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';
require '../PHPMailer-master/src/Exception.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include "../includes/db_config.php";
    
    try {
      $pdo = new PDO("mysql:host=" . host . ";dbname=" . dbname . ";charset=utf8", dbuser, dbpass);
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
        $codigo           = rand(100000, 999999); // Codigo que se mandara como verificacion al correo (Codigo de 6 digitos)

        
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

        // SESION para guardar los datos de registro.
        $_SESSION['registro'] = [
          'nombre'            => $nombre,
          'primer_apellido'   => $primer_apellido,
          'segundo_apellido'  => $segundo_apellido,
          'correo'            => $correo,
          'contra'            => $contra,
          'codigo'            => $codigo
        ];

        // Enviar correo
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ballatomario1105@gmail.com';
            $mail->Password = 'sbeu lcaj evzk bysk';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Cambiar a constante recomendada
            $mail->Port = 587;

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            $mail->setFrom('ballatomario1105@gmail.com', 'Registro');
            $mail->addAddress($correo, "$nombre $primer_apellido");

            $mail->isHTML(true);
            $mail->Subject = 'Verificación de tu cuenta';
            $mail->Body =
                "<h2>¡Bienvenido!</h2>
                 <p>Hola {$nombre} {$primer_apellido} {$segundo_apellido}</p>
                 <p>Gracias por registrarte en nuestro sitio web.</p>
                 <p>Para completar tu registro, por favor utiliza el siguiente código de verificación:</p>
                 <h3>Código de verificación: </h3><h3 style='color: red;'>{$codigo}</h3>
                 <p>Ingresa este código en el formulario de verificación para activar tu cuenta.</p>
                 <p>Si no has solicitado este registro, puedes ignorar este mensaje.</p>
                 <p>¡Bienvenido a nuestra comunidad!</p>
                 <p>El equipo de [Nombre de tu página]</p>
                 <a href='localhost/pagina-web-pi/templates/verificar.html'>Verifica aquí</a>";

            $mail->send();
            echo "Código enviado al correo. <a href='../templates/verificar.html'>Verifica aquí</a>";
        } catch (Exception $e) {
            error_log("Error al enviar el correo: {$mail->ErrorInfo}"); // Registrar el error en el log
            echo "Hubo un problema al enviar el correo. Por favor, intenta más tarde.";
        }

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
                    'id_usuario'      => $usuario['id_usuario'],
                    'nombre'          => $usuario['nombre'],
                    'primer_apellido' => $usuario['primer_apellido'],
                    'segundo_apellido'=> $usuario['segundo_apellido'],
                    'correo'          => $usuario['correo']
                ];
                header("Location: crear_publicacion.php?success=Bienvenido");
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
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Voces del Proceso</title>
  <link rel="stylesheet" href="../assets/css/registro.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="../assets/js/loggin_scripts.js" defer></script>
  <script src="../assets/js/Main.js" defer></script>
</head>
<body>
  <header class="header-top">
    <div class="logo">
      <h1><a href="../index.php">Voces del Proceso</a></h1>
    </div>
    <nav class="main-nav">
      <div id="menu-button" class="menu-button">
        <img src="../assets/img/menu.svg">
        <span class="ocultar-texto">MENU</span>
      </div>
      <div class="search-bar">
        <input type="text" placeholder="Buscar..." />
      </div>
    </nav>
  </header>

  <main class="main">
    <div class="card" id="datos">
      <form id="form-right" action="registro.php" method="POST">
          <input type="hidden" name="action" value="login">
          <h1>Inicio de Sesión</h1>
          <p>Bienvenido de vuelta! Inicia sesión en tu cuenta para registrar las asistencias de tu club.</p>
          <input type="text" id="correo" name="correo" placeholder="Cuenta o correo" required>
          <input type="password" id="contra" name="contra" placeholder="Contraseña" required>
          <button type="submit" class="btn">Iniciar Sesión</button>
          <button class="btin" type="button" onclick="mostrarFormulario2()">Crear una cuenta nueva</button>
      </form>
    </div>

    <div class="card" id="register">
      <form id="form-left" action="registro.php" method="POST">
          <input type="hidden" name="action" value="register">
          <h1>Registro de Cuenta</h1>
          <input type="text" id="nombre" name="nombre" placeholder="Nombre(s)" required>
          <input type="text" id="primer_apellido" name="primer_apellido" placeholder="Primer Apellido" required>
          <input type="text" id="segundo_apellido" name="segundo_apellido" placeholder="Segundo Apellido" required>
          <input type="text" id="correo" name="correo" placeholder="Cuenta o correo" required>
          <input type="password" id="contra" name="contra" placeholder="Contraseña" required>
          <button class="btn" type="submit">Registrar</button>
          <button class="btin" type="button" onclick="mostrarFormulario()">Iniciar sesión</button>
      </form>
    </div>
  </main>

  <footer>
    <p>&copy; 2025 Voces del Proceso. Todos los derechos reservados.</p>
  </footer>
</body>
</html>