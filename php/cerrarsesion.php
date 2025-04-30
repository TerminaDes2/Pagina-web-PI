<?php
session_start();

// Destruir todas las variables de sesión
session_unset();

// Destruir la sesión
session_destroy();

// Redirigir al usuario a la página principal con un mensaje
header("Location: ../templates/Main.html?msg=" . urlencode("Sesión cerrada correctamente.") . "&msgType=success");
exit();