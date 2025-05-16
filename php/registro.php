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
// Verificar si el idioma está configurado en la sesión, si no, establecer un idioma predeterminado
if (!isset($_SESSION['idioma'])) {
    $_SESSION['idioma'] = 'es'; // Idioma predeterminado
}

// Incluir configuración de base de datos
require_once '../includes/db_config.php';

try {
    $conn = new mysqli(host, dbuser, dbpass, dbname);
    if ($conn->connect_error) {
        throw new Exception("Error en la conexión a la base de datos: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die($e->getMessage());
}

// Incluir el traductor
require_once '../includes/traductor.php';
$translator = new Translator($conn);

// Manejar cambio de idioma
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['idioma'])) {
    $translator->cambiarIdioma($_POST['idioma']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    try {
        $pdo = new PDO("mysql:host=" . host . ";dbname=" . dbname . ";charset=utf8", dbuser, dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die(json_encode(["error" => $translator->__("Error en la conexión: ") . $e->getMessage(), "icon" => "error"]));
    }

    if (isset($_POST['action']) && $_POST['action'] === 'register') {    

        $nombre           = trim($_POST['nombre']);
        $primer_apellido  = trim($_POST['primer_apellido']);
        $segundo_apellido = trim($_POST['segundo_apellido']);
        $correo           = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
        $contra           = $_POST['contra'];
        $codigo           = rand(100000, 999999); // Codigo que se mandara como verificacion al correo (Codigo de 6 digitos)

        
        if (empty($nombre) || empty($primer_apellido) || empty($correo) || empty($contra)) {
            echo json_encode(["error" => $translator->__("Por favor, completa todos los campos requeridos."), "icon" => "warning"]);
            exit;
        }

        $foto_perfil = NULL; //Inicializamos la variable en NULL

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            // Obtener informacion de la imagen
            $foto_tmp = $_FILES['imagen']['tmp_name'];
            $foto_nombre = $_FILES['imagen']['name'];
            $foto_tipo = $_FILES['imagen']['type'];
            $foto_size = $_FILES['imagen']['size'];

            //Validacion del tipo de archivo
            $tipo_permitido = ['image/jpeg', 'image/png', 'image/gif'];

            if (!in_array($foto_tipo, $tipo_permitido)) {
                echo $translator->__("El archivo subido no es una imagen valida.");
                exit;
            }

            //Validación del tamaño del archivo
            if ($foto_size > 2 * 1024 * 1024) {
                echo $translator->__("El archivo es demasiado grande. El tamaño permitido es 2MB.");
                exit;
            }

            //Generar un nombre único para la imagen
            $foto_nombre_unico = uniqid('foto_', true) . '.' . pathinfo($foto_nombre, PATHINFO_EXTENSION);

            //Ruta donde guarda la foto
            $directorio = '../uploads/perfiles/'; //Puedes cambiar esta ruta según tus necesidades
            if (!is_dir($directorio)) {
                mkdir($directorio, 0777, true); //Crear el directorio si no existe
            }

            //Mover el archivo a la carpeta de destino
            $foto_ruta = $directorio . $foto_nombre_unico;
            /*if (move_uploaded_file($foto['tmp_name'], $foto_ruta)) {
                echo "Imagen cargada correctamente.";
            } else {
                echo "Error al cargar la imagen.";
            }*/
            move_uploaded_file($foto_tmp, $foto_ruta);

            $foto_perfil = $foto_ruta; //Guardar la ruta de la imagen
        }
        
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo");
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(["error" => $translator->__("El correo ya está registrado.") . " <a href='login.php'>" . $translator->__("Inicia sesión") . "</a>.", "icon" => "info"]);
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
          'codigo'            => $codigo,
          'imagen'            => $foto_perfil //Guardar la ruta de la imagen
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
            $mail->Subject = $translator->__('Verificación de tu cuenta');
            $mail->Body =
                "<h2>" . $translator->__("¡Bienvenido!") . "</h2>
                 <p>" . $translator->__("Hola") . " {$nombre} {$primer_apellido} {$segundo_apellido}</p>
                 <p>" . $translator->__("Gracias por registrarte en nuestro sitio web.") . "</p>
                 <p>" . $translator->__("Para completar tu registro, por favor utiliza el siguiente código de verificación:") . "</p>
                 <h3>" . $translator->__("Código de verificación:") . " </h3><h3 style='color: red;'>{$codigo}</h3>
                 <p>" . $translator->__("Ingresa este código en el formulario de verificación para activar tu cuenta.") . "</p>
                 <p>" . $translator->__("Si no has solicitado este registro, puedes ignorar este mensaje.") . "</p>
                 <p>" . $translator->__("¡Bienvenido a nuestra comunidad!") . "</p>
                 <p>" . $translator->__("El equipo de POALCE") . "</p>
                 <a href='localhost/pagina-web-pi/templates/verificar.html'>" . $translator->__("Verifica aquí") . "</a>";

            $mail->send();
            echo json_encode(["success" => $translator->__("Código enviado al correo."), "icon" => "success"]);
        } catch (Exception $e) {
            error_log("Error al enviar el correo: {$mail->ErrorInfo}"); // Registrar el error en el log
            echo json_encode(["error" => $translator->__("Hubo un problema al enviar el correo. Por favor, intenta más tarde."), "icon" => "error"]);
        }
        exit;

    } elseif (isset($_POST['action']) && $_POST['action'] === 'login') {
        
        $correo = filter_input(INPUT_POST, 'correo', FILTER_SANITIZE_EMAIL);
        $contra = $_POST['contra'];

        if (empty($correo) || empty($contra)) {
            echo json_encode(["error" => $translator->__("Por favor, ingresa tanto el correo como la contraseña."), "icon" => "warning"]);
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
                    'correo'          => $usuario['correo'],
                    'perfil'          => $usuario['perfil'] // Agregar el campo perfil
                ];

                if (isset($_POST['recordarme']) && $_POST['recordarme'] === 'true') {
                  // Guarda el ID en la cookie por 10 dias
                  setcookie('usuario_id', $usuario['id_usuario'], time() + (10 * 24 * 60 * 60), '/');
                }
              
                // En lugar de redirigir directamente, enviamos la URL en la respuesta JSON
                echo json_encode(["success" => $translator->__("Bienvenido"), "redirect" => "../index.php", "icon" => "success"]);
                exit();
            } else {
                echo json_encode(["error" => $translator->__("Contraseña incorrecta."), "icon" => "error"]);
                exit;
            }
        } else {
            echo json_encode(["error" => $translator->__("Usuario no encontrado."), "icon" => "error"]);
            exit;
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'verify') {
        $codigoIngresado = $_POST['codigo'];

        if (!isset($_SESSION['registro'])) {
            echo json_encode(["error" => $translator->__("No hay datos para verificar."), "icon" => "error"]);
            exit;
        }

        $datos = $_SESSION['registro'];

        if ($codigoIngresado == $datos['codigo']) {
            try {
                $perfil = 'cliente';
                $hashed_password = password_hash($datos['contra'], PASSWORD_DEFAULT);
                $imagen_default = ''; // Campo imagen por defecto (vacío)

                $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, primer_apellido, segundo_apellido, correo, contra, imagen, perfil) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $datos['nombre'], 
                    $datos['primer_apellido'], 
                    $datos['segundo_apellido'], 
                    $datos['correo'], 
                    $hashed_password,
                    $imagen_default,
                    $perfil
                ]);

                // Iniciar sesión automáticamente
                $_SESSION['usuario'] = [
                    'nombre'          => $datos['nombre'],
                    'primer_apellido' => $datos['primer_apellido'],
                    'segundo_apellido'=> $datos['segundo_apellido'],
                    'correo'          => $datos['correo']
                ];

                unset($_SESSION['registro']);
                echo json_encode(["success" => $translator->__("Usuario registrado correctamente."), "icon" => "success"]);
            } catch (PDOException $e) {
                echo json_encode(["error" => $translator->__("Error al registrar el usuario: ") . $e->getMessage(), "icon" => "error"]);
            }
        } else {
            echo json_encode(["error" => $translator->__("Código incorrecto. Intenta nuevamente."), "icon" => "error"]);
        }
        exit;
    } else if(isset($_POST['action']) && $_POST['action'] === 'change') {

      $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

      if (!$email) {
        echo json_encode(["error" => "Correo inválido.", "icon" => "error"]);
        exit;
      }

      $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :email");
      $stmt->bindParam(':email', $email);
      $stmt->execute();

      if ($stmt->rowCount() > 0) {

        $codigo = rand(100000, 999999);
        $_SESSION['change'] = [
          'email' => $email,
          'codigo' => $codigo
      ];

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $nombre = $row['nombre'];
        $primer_apellido = $row['primer_apellido'];
        $segundo_apellido = $row['segundo_apellido'];

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

            $mail->setFrom('ballatomario1105@gmail.com', 'Restaurar acceso a tu cuenta');
            $mail->addAddress($email, "$nombre $primer_apellido");

            $mail->isHTML(true);
            $mail->Subject = $translator->__('Restablecimiento de contraseña de tu cuenta');
            $mail->Body =
                "<h2>" . $translator->__("¡Gracias por ponerte en contacto con nosotros!") . "</h2>
                 <p>" . $translator->__("Hola") . " {$nombre} {$primer_apellido} {$segundo_apellido}</p>
                 <p>" . $translator->__("Hemos recibido una solicitud para restablecer la contraseña de tu cuenta.") . "</p>
                 <p>" . $translator->__("Si realizaste esta solicitud, utiliza el siguiente código para continuar con el proceso de restablecimiento:") . "</p>
                 <h3>" . $translator->__("Código de verificación:") . " </h3><h3 style='color: red;'>{$codigo}</h3>
                 <p>" . $translator->__("Este código es válido por un tiempo limitado por motivos de seguridad.") . "</p>
                 <p>" . $translator->__("Si no solicitaste este cambio, puedes ignorar este mensaje.") . "</p>
                 <p>" . $translator->__("Tu contraseña actual seguirá siendo válida y no se realizarán cambios en tu cuenta.") . "</p>
                 <p>" . $translator->__("Gracias por confiar en nosotros") . "</p>
                 <p>" . $translator->__("Atentamente: ") . "</p>
                 <p>" . $translator->__("El equipo de POALCE") . "</p>
                 <a href='localhost/pagina-web-pi/templates/verificar.html'>" . $translator->__("Verifica aquí") . "</a>";

            $mail->send();
            echo json_encode(["success" => $translator->__("Código enviado al correo."), "icon" => "success"]);
        } catch (Exception $e) {
            error_log("Error al enviar el correo: {$mail->ErrorInfo}"); // Registrar el error en el log
            echo json_encode(["error" => $translator->__("Hubo un problema al enviar el correo. Por favor, intenta más tarde."), "icon" => "error"]);
        }
        exit;
      } else {
        echo json_encode(["error" => $translator->__("Correo electrónico no encontrado."), "icon" => "error"]);
        exit;
      }

    } else {
        echo json_encode(["error" => $translator->__("Acción no reconocida."), "icon" => "error"]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Voces del Proceso</title>
  <link rel="stylesheet" href="../assets/css/registro.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/loggin_scripts.js" defer></script>
  <script>
    $(document).ready(function () {
      $("#form-left").on("submit", function (e) {
        e.preventDefault();
        $.ajax({
          url: "registro.php",
          type: "POST",
          data: $(this).serialize(),
          success: function (response) {
            try {
              const res = JSON.parse(response);
              if (res.success) {
                Swal.fire({
                  title: res.success,
                  icon: res.icon || 'success',
                  showConfirmButton: false,
                  timer: 2000
                }).then(() => {
                  $("#verifyModal").show();
                  $(".modal").addClass("active");
                });
              } else if (res.error) {
                Swal.fire({
                  title: res.error,
                  icon: res.icon || 'error',
                  showConfirmButton: true
                });
              }
            } catch (e) {
              Swal.fire({
                title: 'Error',
                text: response,
                icon: 'error',
                showConfirmButton: true
              });
            }
          },
        });
      });

      $("#verifyForm").on("submit", function (e) {
        e.preventDefault();
        $.ajax({
          url: "registro.php",
          type: "POST",
          data: $(this).serialize(),
          success: function (response) {
            try {
              const res = JSON.parse(response);
              if (res.success) {
                Swal.fire({
                  title: res.success,
                  icon: res.icon || 'success',
                  showConfirmButton: false,
                  timer: 2000
                }).then(() => {
                  window.location.href = "../index.php";
                });
              } else if (res.error) {
                Swal.fire({
                  title: res.error,
                  icon: res.icon || 'error',
                  showConfirmButton: true
                });
              }
            } catch (e) {
              Swal.fire({
                title: 'Error',
                text: response,
                icon: 'error',
                showConfirmButton: true
              });
            }
          },
        });
      });

      $("#form-right").on("submit", function (e) {
        e.preventDefault();
        $.ajax({
          url: "registro.php",
          type: "POST",
          data: $(this).serialize(),
          success: function (response) {
            try {
              const res = JSON.parse(response);
              if (res.success) {
                Swal.fire({
                  title: res.success,
                  icon: res.icon || 'success',
                  showConfirmButton: false,
                  timer: 1500
                }).then(() => {
                  // Si hay una redirección, navegamos a esa URL
                  if (res.redirect) {
                    window.location.href = res.redirect;
                  }
                });
              } else if (res.error) {
                Swal.fire({
                  title: res.error,
                  icon: res.icon || 'error',
                  showConfirmButton: true
                });
              }
            } catch (e) {
              console.error("Error parsing JSON: ", e, response);
              Swal.fire({
                title: 'Error',
                text: 'Ocurrió un error al procesar la respuesta',
                icon: 'error',
                showConfirmButton: true
              });
            }
          },
          error: function(xhr, status, error) {
            console.error("AJAX Error: ", status, error);
            Swal.fire({
              title: 'Error de conexión',
              text: 'No se pudo conectar con el servidor',
              icon: 'error',
              showConfirmButton: true
            });
          }
        });
      });

      $("#changePassword").on("click", function (e) {
        e.preventDefault();
        $("#changeModal").show();
        $(".modal").addClass("active");
      });


      $("#changeForm").on("submit", function (e) {
        e.preventDefault();
        $.ajax({
          url: "registro.php",
          type: "POST",
          data: $(this).serialize(),
          success: function (response) {
            try {
              const res = JSON.parse(response);
              if (res.success) {
                Swal.fire({
                  title: res.success,
                  icon: res.icon || 'success' ,
                  showConfirmButton: false,
                  timer: 2000
                }).then(() => {
                  window.location.href = "../includes/cambio_contraseña.php";
                });
              } else if (res.error) {
                Swal.fire({
                  title: res.error,
                  icon: res.icon || 'error',
                  showConfirmButton: true
                });
              }
            } catch (e) {
              Swal.fire({
                title: 'Error',
                text: response,
                icon: 'error',
                showConfirmButton: true
              });
            }
          },
        });
      });

      $(".close").on("click", function () {
        $("#verifyModal").hide();
        $(".modal").removeClass("active");
      });

      $(".close").on("click", function() {
        $(this).closest(".modal").hide();
        $(".modal").removeClass("active");
      });

      // Animar la aparición del modal
      $(document).on('show', '.modal', function () {
        $(this).addClass('active');
      });
    });
  </script>
</head>
<body>
  <?php include '../includes/header.php'; ?>

  <main class="main">
    <div class="card" id="datos">
      <form id="form-right" action="registro.php" method="POST">
        <input type="hidden" name="action" value="login">
        <h1><?= $translator->__("Inicio de Sesión") ?></h1>
        <p><?= $translator->__("Bienvenido de vuelta! Inicia sesión en tu cuenta para registrar las asistencias de tu club.") ?></p>
        <input type="text" id="correo" name="correo" placeholder="<?= $translator->__("Cuenta o correo") ?>" required>
        <input type="password" id="contra" name="contra" placeholder="<?= $translator->__("Contraseña") ?>" required>
        <a href="#" id="changePassword"><?= $translator->__("¿Olvidaste tu contraseña?") ?></a>
        <div class="switch-container">
          <input type="checkbox" name="recordarme" value="true">
          <label for="switch">Recordar mis datos de acceso</label>
        </div>
        <button type="submit" class="btn"><?= $translator->__("Iniciar Sesión") ?></button>
        <button class="btin" type="button" onclick="mostrarFormulario2()"><?= $translator->__("Crear una cuenta nueva") ?></button>
      </form>
    </div>

    <div class="card" id="register">
      <form id="form-left" action="registro.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="register">
          <h1><?= $translator->__("Registro de Cuenta") ?></h1>
          <input type="text" id="nombre" name="nombre" placeholder="<?= $translator->__("Nombre") ?>" required>
          <input type="text" id="primer_apellido" name="primer_apellido" placeholder="<?= $translator->__("Primer Apellido") ?>" required>
          <input type="text" id="segundo_apellido" name="segundo_apellido" placeholder="<?= $translator->__("Segundo Apellido") ?>" required>
          <input type="text" id="correo" name="correo" placeholder="<?= $translator->__("Cuenta o correo") ?>" required>
          <input type="password" id="contra" name="contra" placeholder="<?= $translator->__("Contraseña") ?>" required>
          <input type="file" id="imagen" name="imagen" accept="image/*">
          <button class="btn" type="submit"><?= $translator->__("Crear cuenta") ?></button> 
          <button class="btin" type="button" onclick="mostrarFormulario()"><?= $translator->__("Iniciar sesión") ?></button>
      </form>
    </div>

    <!-- Modal de verificación -->
    <div id="verifyModal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h2><?= $translator->__("Ingresa tu código de verificación") ?></h2>
        <form id="verifyForm" action="registro.php" method="POST">
          <input type="hidden" name="action" value="verify">
          <p><?= $translator->__("Código enviado a tu correo:") ?></p>
          <input type="text" name="codigo" required>
          <br><br>
          <button type="submit"><?= $translator->__("Verificar y Registrar") ?></button>
        </form>
      </div>
    </div>

    <!-- Modal envio de correo -->
    <div id="changeModal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h2><?= $translator->__("Restablecer contraseña") ?></h2>
        <form id="changeForm" action="registro.php" method="POST">
          <input type="hidden" name="action" value="change">
          <p><?= $translator->__("Correo electrónico:") ?></p>    
          <input type="text" name="email" required>
          <br><br>
          <button type="submit"><?= $translator->__("Enviar") ?></button>      
        </form>
      </div>
    </div>
  </main>

  <?php include '../includes/footer.php'; ?>
  
  <?php if (isset($_GET['error']) || isset($_GET['success'])): ?>
  <script>
    $(document).ready(function(){
      <?php if (isset($_GET['error'])): ?>
      Swal.fire({
        icon: 'error',
        title: '<?= urldecode($_GET['error']) ?>',
        showConfirmButton: false,
        timer: 2000
      });
      <?php endif; ?>
      
      <?php if (isset($_GET['success'])): ?>
      Swal.fire({
        icon: 'success',
        title: '<?= urldecode($_GET['success']) ?>',
        showConfirmButton: false,
        timer: 2000
      });
      <?php endif; ?>
    });
  </script>
  <?php endif; ?>
</body>
</html>