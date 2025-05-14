<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = htmlspecialchars($_POST['nombre']);
    $correo = htmlspecialchars($_POST['correo']);
    $asunto = htmlspecialchars($_POST['asunto']);
    $mensaje = htmlspecialchars($_POST['mensaje']);

    $para = "tucorreo@ejemplo.com";
    $asuntoCorreo = "Nuevo mensaje desde Voces del Proceso: $asunto";
    $contenido = "Nombre: $nombre\nCorreo: $correo\nAsunto: $asunto\nMensaje:\n$mensaje";
    $headers = "From: $correo";

    if (mail($para, $asuntoCorreo, $contenido, $headers)) {
        echo "Mensaje enviado correctamente.";
    } else {
        echo "Hubo un error al enviar tu mensaje.";
    }
}

