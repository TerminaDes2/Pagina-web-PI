<?php 
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

    if (isset($_POST['action']) && $_POST['action'] === 'code') {
      $codigoIngresado = isset($_POST['codigo']) ? $_POST['codigo'] : null;

      if(!$codigoIngresado) {
        echo json_encode(["error" => $translator->__("El código no fue proporcionado."), "icon" => "warning"]);
        exit;
      }

      if (!isset($_SESSION['change'])) {
        echo json_encode(["error" => $translator->__("No hay datos para verificar."), "icon" => "error"]);
        exit;
      }

      $datos = $_SESSION['change'];

      if($codigoIngresado == $datos['codigo']) {
        echo json_encode(["success" => $translator->__("Código verificado"), "icon" => "success"]);
        exit;
      } else {
        echo json_encode(["error" => $translator->__("El código ingresado es incorrecto."), "icon" => "error"]);
        exit;
      }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'change') {
      
      $nuevaContra = isset($_POST['contra_nu']) ? $_POST['contra_nu'] : null;
      $verificaContra = isset($_POST['contra_ve']) ? $_POST['contra_ve'] : null;

      if (empty($nuevaContra) || empty($verificaContra)) {
        echo json_encode(["error" => $translator->__("Por favor, completa todos los campos requeridos."), "icon" => "warning"]);
        exit;
      } 

      if (!isset($_SESSION['change']['email'])) {
        echo json_encode(["error" => "No se pudo acceder a la sesión de cambio de contraseña. Intenta nuevamente.", "icon" => "error"]);
        exit;
      }

      $email = $_SESSION['change']['email'];
      $hashed_password = password_hash($nuevaContra, PASSWORD_DEFAULT);

      if ($nuevaContra !== $verificaContra) {
        echo json_encode(["error" => "Las contraseñas no coinciden.", "icon" => "error"]);
        exit;
      }  

      try {      
        $stmt = $pdo->prepare("UPDATE usuarios SET contra = :contra WHERE correo = :correo");
        $stmt->bindParam(':contra', $hashed_password);
        $stmt->bindParam(':correo', $email);
        $stmt->execute();

        unset($_SESSION['change']);
        echo json_encode(["success" => $translator->__("Tu contraseña ha sido cambiada exitosamente."), "icon" => "success"]);
      } catch (PDOException $e) {
        echo json_encode(["error" => $translator->__("No se pudo cambiar la contraseña. Intenta nuevamente.") . $e->getMessage(), "icon" => "error"]);
      }
      exit;
    }
  }
?>
<!DOCTYPE html>
<html lang="<?= isset($_SESSION['idioma']) ? $_SESSION['idioma'] : 'es' ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Voces del Proceso</title>
  <link rel="stylesheet" href="../assets/css/registro.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Parisienne&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Agregamos Font Awesome para los iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../assets/js/loggin_scripts.js" defer></script>
  
  <!-- Añadir objeto de traducciones para JavaScript -->
  <script>
    // Objeto global de traducciones para usar en JavaScript
    window.translations = {
      'attention': '<?= $translator->__("Atención") ?>',
      'understood': '<?= $translator->__("Entendido") ?>',
      'error': '<?= $translator->__("Error") ?>',
      'passwords_match': '<?= $translator->__("¡Las contraseñas coinciden!") ?>',
      'passwords_dont_match': '<?= $translator->__("Las contraseñas no coinciden") ?>'
    };
  </script>
  
  <script>
    $(document).ready(function () {
      $("#codeModal").show();
      $(".modal").addClass("active");

      // Enviar el formulario de verificación
      $("#codeForm").on("submit", function (e) {
        e.preventDefault();
        $.ajax({
          url: "cambio_password.php",
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
                  // Solo cerrar el modal
                  $("#codeModal").hide();
                  $(".modal").removeClass("active");

                  // Aquí NO hay redirección
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

      $("#form-righ").on("submit", function (e) {
        e.preventDefault();
        // Validar coincidencia de contraseñas
        const contraInput = document.getElementById('contra_nu');
        const confirmaInput = document.getElementById('contra_ve');
        if (contraInput.value !== confirmaInput.value) {
          Swal.fire({
            title: window.translations.error || 'Error',
            text: window.translations.passwords_dont_match || 'Las contraseñas no coinciden',
            icon: 'error',
            showConfirmButton: true
          });
          return false;
        }
        
        $.ajax({
          url: "cambio_password.php",
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
                  window.location.href = "../php/registro.php";
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
          error: function () {
            Swal.fire({
              title: 'Error de conexión',
              text: 'No se pudo conectar al servidor',
              icon: 'error',
              showConfirmButton: true
            });
          }
        });
      });
      
      $(document).on('show', '.modal', function () {
        $(this).addClass('active');
      });
      
      // Validar coincidencia de contraseñas con efectos visuales
      const contraInput = document.getElementById('contra_nu');
      const confirmaInput = document.getElementById('contra_ve');
      const mensajeCoincidencia = document.getElementById('mensaje-coincidencia');
      
      function validarCoincidencia() {
        if (!confirmaInput || !mensajeCoincidencia) return;
        
        if (!confirmaInput.value) {
          mensajeCoincidencia.textContent = '';
          mensajeCoincidencia.className = 'password-match-message';
          return;
        }
        
        if (contraInput.value === confirmaInput.value) {
          mensajeCoincidencia.textContent = window.translations.passwords_match || '¡Las contraseñas coinciden!';
          mensajeCoincidencia.className = 'password-match-message password-match-success';
          mensajeCoincidencia.style.opacity = '0';
          setTimeout(() => {
            mensajeCoincidencia.style.opacity = '1';
          }, 10);
        } else {
          mensajeCoincidencia.textContent = window.translations.passwords_dont_match || 'Las contraseñas no coinciden';
          mensajeCoincidencia.className = 'password-match-message password-match-error';
          mensajeCoincidencia.style.opacity = '0';
          setTimeout(() => {
            mensajeCoincidencia.style.opacity = '1';
          }, 10);
        }
      }
      
      if (contraInput && confirmaInput) {
        contraInput.addEventListener('input', validarCoincidencia);
        confirmaInput.addEventListener('input', validarCoincidencia);
        
        // Ejecutar validación inicial si hay valores
        if (contraInput.value || confirmaInput.value) {
          validarCoincidencia();
        }
      }
    });
  </script>
</head>
<body>
  <?php include '../includes/header.php'; ?>

  <main class="main">
    <div class="card" id="datos">
      <form id="form-righ" action="cambio_password.php" method="POST">
          <input type="hidden" name="action" value="change">
          <h1><?= $translator->__("Restablecer contraseña") ?></h1>
          <p><?= $translator->__("Has solicitado recuperar el acceso a tu cuenta. Completa el proceso para restablecer tu contraseña de forma segura.") ?></p>
          
          <div class="input-container">
            <i class="fa fa-lock"></i>
            <input type="password" id="contra_nu" name="contra_nu" placeholder="<?= $translator->__("Nueva Contraseña") ?>" required>
            <button type="button" class="toggle-password" onclick="togglePassword('contra_nu')">
              <i class="fa fa-eye"></i>
            </button>
          </div>
          
          <div class="input-container">
            <i class="fa fa-lock"></i>
            <input type="password" id="contra_ve" name="contra_ve" placeholder="<?= $translator->__("Confirmar nueva contraseña") ?>" required>
            <button type="button" class="toggle-password" onclick="togglePassword('contra_ve')">
              <i class="fa fa-eye"></i>
            </button>
          </div>
          <span id="mensaje-coincidencia" class="password-match-message"></span>
          
          <button type="submit" class="btn"><?= $translator->__("Actualizar contraseña") ?></button>
      </form>
    </div>

    <!-- Modal de verificación -->
    <div id="codeModal" class="modal">
      <div class="modal-content">
        <h2><?= $translator->__("Ingresa tu código de verificación") ?></h2>
        <form id="codeForm" action="cambio_password.php" method="POST">
          <input type="hidden" name="action" value="code">
          <p><?= $translator->__("Código enviado a tu correo:") ?></p>
          <input type="text" name="codigo" required>
          <br><br>
          <button type="submit"><?= $translator->__("Verificar y Registrar") ?></button>
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