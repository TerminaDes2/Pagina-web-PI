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
    <title><?= $translator->__("Contacto - POALCE") ?></title>
    <link rel="stylesheet" href="/Pagina-web-PI/assets/css/Contacto.css">
    <script src="/Pagina-web-PI/assets/js/Contacto.js" defer></script>
    <!-- Agregar jQuery y SweetAlert2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Agregar soporte para modo oscuro -->
    <link rel="stylesheet" href="/Pagina-web-PI/assets/css/dark-mode.css">
    <script src="/Pagina-web-PI/assets/js/dark-mode.js" defer></script>
</head>
<body>
    <?php include(__DIR__ . '/../includes/header.php'); ?>

    <main class="contacto-contenedor">
        <h1><?= $translator->__("Contacto") ?></h1>
        <p class="frase"><?= $translator->__("\"Juntos por un empleo digno y un crecimiento sostenible.\"") ?></p>

        <div class="info-contacto">
            <div>
                <h2><?= $translator->__("Información") ?></h2>
                <p><strong><?= $translator->__("Dirección:") ?></strong> <?= $translator->__("Facultad de Ingeniería Electromecánica, Universidad de Colima, Manzanillo, Colima, México.") ?></p>
                <p><strong><?= $translator->__("Email:") ?></strong> contactopoalce@gmail.com</p>
                <p><strong><?= $translator->__("Horario:") ?></strong> <?= $translator->__("Lunes a Viernes de 7 am a 2:30 pm") ?></p>
            </div>
            <div class="mapa">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3769.6293524992147!2d-104.40258572497133!3d19.12390948209066!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x84255a99f51cf4b5%3A0x71073f9935f08f0a!2sFacultad%20de%20Ingenier%C3%ADa%20Electromec%C3%A1nica%20(FIE)!5e0!3m2!1ses!2smx!4v1747276877311!5m2!1ses!2smx"
                    width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>

        <div class="faq">
            <h2><?= $translator->__("Preguntas Frecuentes") ?></h2>
            <div class="pregunta">
                <button class="toggle-btn"><?= $translator->__("¿Qué tipo de contenido publican?") ?> ⮟</button>
                <div class="respuesta" style="display: none;"><?= $translator->__("Publicamos noticias, análisis y recursos sobre derechos laborales y desarrollo social.") ?></div>
            </div>
            <div class="pregunta">
                <button class="toggle-btn"><?= $translator->__("¿Puedo colaborar con ustedes?") ?> ⮟</button>
                <div class="respuesta" style="display: none;"><?= $translator->__("Sí, puedes escribirnos a nuestro correo para conocer las oportunidades de colaboración.") ?></div>
            </div>
            <div class="pregunta">
                <button class="toggle-btn"><?= $translator->__("¿La información está verificada?") ?> ⮟</button>
                <div class="respuesta" style="display: none;"><?= $translator->__("Sí, trabajamos con fuentes oficiales y confiables para asegurar la veracidad de lo publicado.") ?></div>
            </div>
            <div class="pregunta">
                <button class="toggle-btn"><?= $translator->__("¿Dónde puedo encontrar las reformas laborales recientes?") ?> ⮟</button>
                <div class="respuesta" style="display: none;"><?= $translator->__("En la sección Noticias, encontrarás artículos actualizados sobre reformas recientes.") ?></div>
            </div>
            <div class="pregunta">
                <button class="toggle-btn"><?= $translator->__("¿Tienen redes sociales activas?") ?> ⮟</button>
                <div class="respuesta" style="display: none;"><?= $translator->__("Sí, puedes seguirnos en Facebook, Instagram y Twitter para más contenido.") ?></div>
            </div>
        </div>

        <div class="formulario">
            <h2><?= $translator->__("Cualquier duda o aclaración. Envíanos un mensaje:") ?></h2>
            <form id="formulario-contacto" method="POST">
                <label for="nombre"><?= $translator->__("Nombre:") ?></label>
                <input type="text" name="nombre" id="nombre" required>

                <label for="correo"><?= $translator->__("Correo electrónico:") ?></label>
                <input type="email" name="correo" id="correo" required>

                <label for="asunto"><?= $translator->__("Asunto:") ?></label>
                <select name="asunto" id="asunto" required>
                    <option value=""><?= $translator->__("Selecciona una opción") ?></option>
                    <option value="consulta"><?= $translator->__("Consulta") ?></option>
                    <option value="soporte"><?= $translator->__("Soporte") ?></option>
                    <option value="otros"><?= $translator->__("Otros") ?></option>
                </select>

                <label for="mensaje"><?= $translator->__("Mensaje:") ?></label>
                <div id="mensaje" contenteditable="true"></div>
                <textarea name="mensaje-hidden" style="display:none;"></textarea>
                <div class="editor">
                    <button type="button" onclick="document.execCommand('bold')"><b>B</b></button>
                    <button type="button" onclick="document.execCommand('underline')"><u>U</u></button>
                    <button type="button" onclick="document.execCommand('justifyLeft')"><?= $translator->__("Izquierda") ?></button>
                    <button type="button" onclick="document.execCommand('justifyCenter')"><?= $translator->__("Centro") ?></button>
                    <button type="button" onclick="document.execCommand('justifyRight')"><?= $translator->__("Derecha") ?></button>
                    <button type="button" onclick="document.execCommand('justifyFull')"><?= $translator->__("Justificar") ?></button>
                </div>

                <button type="submit" class="btn-enviar"><?= $translator->__("Enviar") ?></button>
            </form>
        </div>
    </main>

    <?php include(__DIR__ . '/../includes/footer.php'); ?>
    
    <!-- Botón Subir Arriba -->
    <button id="subirArriba" title="<?= $translator->__("Volver arriba") ?>"><i class="fas fa-chevron-up"></i></button>

    <script>
        window.onscroll = function() {
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
            window.scrollTo({top: 0, behavior: 'smooth'});
        }
        
        document.getElementById("subirArriba").addEventListener("click", subirArriba);
        
        // Manejar envío del formulario de contacto usando AJAX y SweetAlert2
        $(document).ready(function() {
            $("#formulario-contacto").on("submit", function(e) {
                e.preventDefault();
                
                // Comprobar que el mensaje no está vacío
                const mensajeContenido = document.getElementById('mensaje').innerHTML.trim();
                if (!mensajeContenido) {
                    Swal.fire({
                        title: '<?= $translator->__("Por favor escribe un mensaje") ?>',
                        icon: 'warning',
                        showConfirmButton: true
                    });
                    return;
                }
                
                // Copiar el contenido del editor al campo oculto
                $("textarea[name='mensaje-hidden']").val(mensajeContenido);
                
                // Deshabilitar el botón de enviar durante el proceso
                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?= $translator->__("Enviando...") ?>');
                
                // Serializar los datos del formulario
                const formData = $(this).serialize();
                console.log("Datos a enviar:", formData);
                
                $.ajax({
                    url: "/Pagina-web-PI/includes/Contacto.php",
                    type: "POST",
                    data: formData,
                    dataType: "json",
                    success: function(response) {
                        console.log("Respuesta del servidor:", response);
                        
                        if (response.success) {
                            Swal.fire({
                                title: response.success,
                                icon: response.icon || 'success',
                                showConfirmButton: true
                            }).then(() => {
                                // Limpiar el formulario después de éxito
                                $("#formulario-contacto")[0].reset();
                                document.getElementById('mensaje').innerHTML = '';
                            });
                        } else if (response.error) {
                            Swal.fire({
                                title: response.error,
                                icon: response.icon || 'error',
                                text: response.details || '',
                                showConfirmButton: true
                            });
                        }
                        
                        // Reactivar el botón de envío
                        submitBtn.prop('disabled', false).html('<?= $translator->__("Enviar") ?>');
                    },
                    error: function(xhr, status, error) {
                        console.error("Error AJAX:", status, error, xhr.responseText);
                        
                        Swal.fire({
                            title: '<?= $translator->__("Error de conexión") ?>',
                            text: '<?= $translator->__("No se pudo procesar tu solicitud. Inténtalo más tarde.") ?>',
                            icon: 'error',
                            showConfirmButton: true
                        });
                        
                        // Reactivar el botón de envío
                        submitBtn.prop('disabled', false).html('<?= $translator->__("Enviar") ?>');
                    }
                });
            });
        });
    </script>
</body>
</html>
