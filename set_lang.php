<?php
session_start();
if (isset($_GET['lang'])) {
  // Guarda el idioma seleccionado en la sesión (puede ser 'es' o 'en')
  $_SESSION['lang'] = $_GET['lang'];
}
// Redirige a la página anterior
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>
