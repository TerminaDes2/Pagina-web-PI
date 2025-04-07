<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $host   = 'localhost';
    $dbname = 'blog';
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

        header("Location: crear_publicacion.php?success=Registro+exitoso");
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

$lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'es';
include "lang_{$lang}.php";
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo $idioma['voces_proceso']; ?></title>
  <link rel="stylesheet" href="estilos/registro.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="loggin_scripts.js" defer></script>
  <script src="Main.js" defer></script>
</head>
<body>
  <header class="header-top">
    <div class="logo">
      <h1><a href="Main.php"><?php echo $idioma['voces_proceso']; ?></a></h1>
    </div>
    <nav class="main-nav">
      <div id="menu-button" class="menu-button">
        <img src="img/menu.svg">
        <span class="ocultar-texto"><?php echo $idioma['menu']; ?></span>
      </div>
      <div class="search-bar">
        <input type="text" placeholder=<?php echo $idioma['buscar']; ?> />
      </div>
      <div class="social-icons">
        <a href="#"><img src="img/facebook.svg" alt="Facebook"></a>
        <a href="#"><img src="img/instagram.svg" alt="Instagram"></a>
      </div>
      <!-- Selector de idioma -->
      <div class="lang-selector">
        <a href="set_lang.php?lang=es">Español</a> | 
        <a href="set_lang.php?lang=en">English</a>
      </div>
    </nav>
  </header>

  <div id="sidebar" class="sidebar">
    <button id="close-button" class="close-button"><?php echo $idioma['cerrar']; ?></button>
    <ul>
      <li><a href="Main.php"><?php echo $idioma['inicio']; ?></a></li>
      <li><a href="#"><?php echo $idioma['noticias']; ?></a></li>
      <li><a href="#"><?php echo $idioma['contacto']; ?></a></li>
      <li><a href="#"><?php echo $idioma['acerca_de']; ?></a></li>
    </ul>
    <button id="login-button" class="login-button"><?php echo $idioma['login']; ?></button>
  </div>

  <main class="main">
    <div class="card" id="datos">
      <form id="form-right" action="registro.php" method="POST">
          <input type="hidden" name="action" value="login">
          <h1><?php echo $idioma['login_title']; ?></h1>
          <p><?php echo $idioma['login_subtitle']; ?></p>
          <input type="text" id="correo" name="correo" placeholder="<?php echo $idioma['login_placeholder']; ?>" required>
          <input type="password" id="contra" name="contra" placeholder="<?php echo $idioma['login_password_placeholder']; ?>" required>
          <button type="submit" class="btn"><?php echo $idioma['login_button']; ?></button>
          <button class="btin" type="button" onclick="mostrarFormulario2()"><?php echo $idioma['crear_cuenta']; ?></button>
      </form>
    </div>

    <div class="card" id="register">
      <form id="form-left" action="registro.php" method="POST">
          <input type="hidden" name="action" value="register">
          <h1><?php echo $idioma['registro_title']; ?></h1>
          <input type="text" id="nombre" name="nombre" placeholder="<?php echo $idioma['nombre_placeholder']; ?>" required>
          <input type="text" id="primer_apellido" name="primer_apellido" placeholder="<?php echo $idioma['primer_apellido_placeholder']; ?>" required>
          <input type="text" id="segundo_apellido" name="segundo_apellido" placeholder="<?php echo $idioma['segundo_apellido_placeholder']; ?>" required>
          <input type="text" id="correo" name="correo" placeholder="<?php echo $idioma['registro_correo_placeholder']; ?>" required>
          <input type="password" id="contra" name="contra" placeholder="<?php echo $idioma['registro_password_placeholder']; ?>" required>
          <button class="btn" type="submit"><?php echo $idioma['registrar_button']; ?></button>
          <button class="btin" type="button" onclick="mostrarFormulario()"><?php echo $idioma['iniciar_sesion']; ?></button>
      </form>
    </div>
  </main>

  <footer>
    <p><?php echo $idioma['footer_text']; ?></p>
  </footer>
</body>
</html>