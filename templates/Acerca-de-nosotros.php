<?php
// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir la configuración y conexión a la base de datos
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/conexion.php';

// Incluir el traductor
require_once __DIR__ . '/../includes/traductor.php';
$translator = new Translator($conn);

// Procesar cambio de idioma si se envía
if (isset($_POST['idioma'])) {
    $translator->cambiarIdioma($_POST['idioma']);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= isset($_SESSION['idioma']) ? $_SESSION['idioma'] : 'es' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translator->__("Acerca de Nosotros - POALCE") ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="../assets/css/acerca-nosotros.css">
</head>
<body>
    <?php include(__DIR__ . '/../includes/header.php'); ?>

    <main class="main-content">
        <section class="contenido">
            <h1><?= $translator->__("Acerca de Nosotros") ?></h1>

            <h2><?= $translator->__("¿Quiénes somos?") ?></h2>
            <p><strong>POALCE</strong> <?= $translator->__("Somos una plataforma digital comprometida con la difusión de información clara, accesible y verificada sobre derechos laborales, reformas sociales y leyes que impactan la vida de las personas,con el proposito de ayudar a las personas a tener un futuro mejor.") ?></p>

            <h2><?= $translator->__("¿Qué hacemos?") ?></h2>
            <p><?= $translator->__("Brindamos noticias, análisis y recursos prácticos que permiten a los ciudadanos conocer y ejercer sus derechos laborales asi como permitirles el acceso a informacion para encontrar un trabajo decente con un buen sueldo, fomentando así el trabajo digno y el crecimiento económico sostenible.") ?></p>

            <h2><?= $translator->__("Misión:") ?></h2>
            <p><?= $translator->__("Impulsar el acceso a un trabajo decente y al conocimiento de los derechos laborales, empoderando a las personas mediante información confiable y fomentando la participación social activa.") ?></p>

            <h2><?= $translator->__("Visión") ?></h2>
            <p><?= $translator->__("Convertirnos en el principal referente de información laboral y de desarrollo social en América Latina, promoviendo el respeto, la equidad y el bienestar común.") ?></p>

            <h2><?= $translator->__("Valores") ?></h2>
            <ul>
                <li><strong><?= $translator->__("Compromiso social:") ?></strong> <?= $translator->__("Trabajamos para mejorar la calidad de vida de las personas.") ?></li>
                <li><strong><?= $translator->__("Veracidad:") ?></strong> <?= $translator->__("Publicamos únicamente información verificada y sustentada.") ?></li>
                <li><strong><?= $translator->__("Accesibilidad:") ?></strong> <?= $translator->__("Hacemos que el conocimiento esté al alcance de todos.") ?></li>
                <li><strong><?= $translator->__("Responsabilidad:") ?></strong> <?= $translator->__("Cumplimos con los más altos estándares éticos.") ?></li>
            </ul>

            <h2><?= $translator->__("Información Adicional") ?></h2>
            <p><?= $translator->__("En") ?> <strong>POALCE</strong>, <?= $translator->__("nos dedicamos a brindar información precisa, actualizada y verificada sobre reformas, derechos laborales y leyes que impactan a trabajadores y ciudadanos.") ?></p>
            <p><?= $translator->__("Somos un equipo comprometido con la difusión de noticias que promueven el trabajo decente, el crecimiento económico y el desarrollo sostenible.") ?></p>
            <p><?= $translator->__("Nuestra misión es impulsar una ciudadanía informada, capaz de participar activamente en las decisiones que transforman nuestras comunidades.") ?></p>
            <p><?= $translator->__("Creemos en la importancia de acercar la información de manera clara, accesible y basada en hechos, fomentando el diálogo y el análisis crítico.") ?></p>
        </section>
    </main>

    <?php include(__DIR__ . '/../includes/footer.php'); ?>

    <!-- Botón Subir Arriba -->
    <button id="subirArriba" title="<?= $translator->__("Volver arriba") ?>"><i class="fas fa-chevron-up"></i></button>

    <script>
        window.onscroll = function () {
            var boton = document.getElementById("subirArriba");
            if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
                boton.style.display = "block";
                boton.classList.add('visible');
            } else {
                boton.style.display = "none";
                boton.classList.remove('visible');
            }
        };

        function subirArriba() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        document.getElementById("subirArriba").addEventListener("click", subirArriba);
    </script>
</body>
</html>