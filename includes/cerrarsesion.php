<?php
session_start();
session_unset();
session_destroy();
header("Location: ../index.php?msg=" . urlencode("Sesión cerrada correctamente.") . "&msgType=success");
exit();