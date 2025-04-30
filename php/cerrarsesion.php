<?php
session_start();
session_unset();
session_destroy();
header("Location: ../templates/Main.html?msg=" . urlencode("Sesión cerrada correctamente.") . "&msgType=success");
exit();