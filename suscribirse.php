<?php
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);

    if ($email) {
        // Aquí podrías guardar el correo en una base de datos o archivo
        // file_put_contents("suscriptores.txt", $email . PHP_EOL, FILE_APPEND);

        echo json_encode([
            "status" => "success",
            "message" => "¡Gracias por suscribirte!"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Correo electrónico no válido."
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Método no permitido."
    ]);
}
?>
<?php include 'footer.php'; ?>
