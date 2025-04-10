<?php
session_start();
if (isset($_GET['lang'])) {
  // Guarda el idioma seleccionado en la sesión (puede ser 'es' o 'en')
  $_SESSION['lang'] = $_GET['lang'];
}
// Redirige a la página principal
header("Location: ../templates/Main.html");
exit();
?>
