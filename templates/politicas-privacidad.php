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
  <title><?= $translator->__('Políticas de Privacidad') ?> - POALCE</title>
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
    <h1><?= $translator->__('Política de Privacidad') ?></h1>
  </div>

  <main id="publicacion">
    <div class="noticia">
      <h2>1. <?= $translator->__('Responsable del tratamiento') ?></h2>
      <p><strong><?= $translator->__('Titular:') ?></strong> Voces del Proceso</p>
      <p><strong><?= $translator->__('Correo de contacto:') ?></strong> contactopoalce@gmail.com</p>

      <h2>2. <?= $translator->__('Datos que recopilamos') ?></h2>
      <p><strong><?= $translator->__('Registro de usuario:') ?></strong> <?= $translator->__('nombre de usuario, correo electrónico, contraseña (almacenada cifrada).') ?></p>
      <p><strong><?= $translator->__('Perfil público:') ?></strong> <?= $translator->__('foto de perfil y apodo (nickname).') ?></p>
      <p><strong><?= $translator->__('Comentarios:') ?></strong> <?= $translator->__('contenido que publiques en secciones de comentarios.') ?></p>
      <p><strong><?= $translator->__('Datos técnicos:') ?></strong> <?= $translator->__('dirección IP, tipo de navegador, sistema operativo y registros de acceso (logs) para seguridad y análisis.') ?></p>

      <h2>3. <?= $translator->__('Finalidades del tratamiento') ?></h2>
      <p><strong><?= $translator->__('Gestión de la cuenta:') ?></strong> <?= $translator->__('creación, mantenimiento y recuperación de contraseñas.') ?></p>
      <p><strong><?= $translator->__('Interacción social:') ?></strong> <?= $translator->__('publicación de comentarios y visualización de perfiles.') ?></p>
      <p><strong><?= $translator->__('Seguridad:') ?></strong> <?= $translator->__('detección de accesos no autorizados y prevención de fraudes.') ?></p>
      <p><strong><?= $translator->__('Mejora del servicio:') ?></strong> <?= $translator->__('análisis estadístico de uso y optimización de la plataforma.') ?></p>

      <h2>4. <?= $translator->__('Bases jurídicas') ?></h2>
      <p><strong><?= $translator->__('Ejecución de contrato:') ?></strong> <?= $translator->__('para prestar el servicio de registro y comentarios.') ?></p>
      <p><strong><?= $translator->__('Consentimiento:') ?></strong> <?= $translator->__('para el tratamiento de foto de perfil y apodo, y envío de comunicaciones.') ?></p>
      <p><strong><?= $translator->__('Interés legítimo:') ?></strong> <?= $translator->__('para garantizar la seguridad de la plataforma.') ?></p>

      <h2>5. <?= $translator->__('Destinatarios y transferencias') ?></h2>
      <p><?= $translator->__('No cedemos tus datos a terceros, salvo obligación legal o proveedores de hosting y análisis estrictamente necesarios (p. ej. servicios de correo, almacenamiento en la nube).') ?></p>

      <h2>6. <?= $translator->__('Conservación') ?></h2>
      <p><strong><?= $translator->__('Datos de cuenta y perfil:') ?></strong> <?= $translator->__('hasta que elimines tu cuenta.') ?></p>
      <p><strong><?= $translator->__('Comentarios y logs:') ?></strong> <?= $translator->__('hasta 3 años para cumplimiento legal y seguridad.') ?></p>

      <h2>7. <?= $translator->__('Derechos de los usuarios') ?></h2>
      <p><?= $translator->__('Puedes ejercer los derechos de acceso, rectificación, supresión, oposición y portabilidad enviando un correo a contactopoalce@gmail.com.') ?></p>

      <h2>8. <?= $translator->__('Seguridad') ?></h2>
      <p><?= $translator->__('Implementamos medidas técnicas (cifrado de contraseñas, HTTPS, firewalls) y organizativas para proteger tus datos.') ?></p>

      <h2>9. <?= $translator->__('Cambios en la política') ?></h2>
      <p><?= $translator->__('Nos reservamos el derecho a actualizar este documento. Publicaremos la fecha de última modificación en esta misma página.') ?></p>

      <p><em><?= $translator->__('Última actualización:') ?> <?= date('d/m/Y') ?></em></p>
    </div>
  </main>
  
  <?php include '../includes/footer.php'; ?>
</body>
</html>
