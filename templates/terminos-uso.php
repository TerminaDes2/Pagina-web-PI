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
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= $translator->__('Términos de Uso') ?> - POALCE</title>
  <link rel="stylesheet" href="../assets/css/publicacion-nuevo.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&display=swap" rel="stylesheet">
  <!-- Agregamos Font Awesome para los iconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <!-- Agregar soporte para modo oscuro -->
  <link rel="stylesheet" href="../assets/css/dark-mode.css">
  <script src="../assets/js/dark-mode.js" defer></script>
</head>
<body>
  <?php include '../includes/header.php'; ?>

  <div class="titulo">
    <h1><?= $translator->__('Términos de Uso') ?></h1>
  </div>

  <main id="publicacion">
    <div class="noticia">
      <h2>1. <?= $translator->__('Aceptación de los Términos') ?></h2>
      <p><?= $translator->__('Al registrarte y usar el sitio, aceptas estos Términos de Uso, así como nuestra Política de Privacidad.') ?></p>

      <h2>2. <?= $translator->__('Requisitos de la cuenta') ?></h2>
      <ul>
        <li><?= $translator->__('Ser mayor de edad o contar con consentimiento de un tutor.') ?></li>
        <li><?= $translator->__('Proporcionar datos veraces.') ?></li>
        <li><?= $translator->__('Mantener tu contraseña confidencial.') ?></li>
      </ul>

      <h2>3. <?= $translator->__('Conducta del usuario') ?></h2>
      <p><?= $translator->__('No podrás:') ?></p>
      <ul>
        <li><?= $translator->__('Publicar contenido ilegal, ofensivo, violento o discriminatorio.') ?></li>
        <li><?= $translator->__('Suplantar a otro usuario o entidad.') ?></li>
        <li><?= $translator->__('Introducir software malicioso.') ?></li>
      </ul>

      <h2>4. <?= $translator->__('Contenido generado por el usuario') ?></h2>
      <p><?= $translator->__('Eres responsable del contenido que publiques (comentarios, foto de perfil, apodo). Nos concedes una licencia no exclusiva para reproducirlo y mostrarlo en la plataforma.') ?></p>

      <h2>5. <?= $translator->__('Propiedad intelectual') ?></h2>
      <p><?= $translator->__('Todos los derechos de este sitio (texto, diseño, código) son de Voces del Proceso. Queda prohibida la reproducción total o parcial sin autorización.') ?></p>

      <h2>6. <?= $translator->__('Responsabilidades y garantías') ?></h2>
      <p><?= $translator->__('Ofrecemos el servicio "tal cual" y no garantizamos disponibilidad ininterrumpida.') ?></p>
      <p><?= $translator->__('No nos responsabilizamos de las opiniones o materiales publicados por usuarios.') ?></p>

      <h2>7. <?= $translator->__('Modificaciones del servicio') ?></h2>
      <p><?= $translator->__('Podemos modificar o interrumpir total o parcialmente el servicio, con o sin aviso previo.') ?></p>

      <h2>8. <?= $translator->__('Terminación de la cuenta') ?></h2>
      <p><?= $translator->__('Podemos suspender o eliminar tu cuenta si incumples estos Términos. Tú puedes darte de baja en cualquier momento desde tu perfil.') ?></p>

      <h2>9. <?= $translator->__('Legislación aplicable y jurisdicción') ?></h2>
      <p><?= $translator->__('Estos Términos se rigen por las leyes de México. Para cualquier controversia, los tribunales de Ciudad de México serán competentes.') ?></p>

      <h2>10. <?= $translator->__('Contacto') ?></h2>
      <p><?= $translator->__('Para dudas o reclamaciones, escríbenos a contactopoalce@gmail.com.') ?></p>

      <p><em><?= $translator->__('Última actualización:') ?> <?= date('d/m/Y') ?></em></p>
    </div>
  </main>
  
  <?php include '../includes/footer.php'; ?>
</body>
</html>
