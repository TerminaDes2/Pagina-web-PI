<?php
session_start();
session_unset();
session_destroy();
setcookie('usuario_id', '', time() - 3600, '/');
header("Location: ../index.php?msg=" . urlencode("Sesión cerrada correctamente.") . "&msgType=success");
exit();