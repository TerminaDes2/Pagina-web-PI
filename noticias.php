<?php
$noticias = [
    [
        "imagen" => "img/noticia1.jpg",
        "titulo" => "Noticia 1",
        "resumen" => "Resumen de la noticia 1...",
        "enlace" => "#"
    ],
    [
        "imagen" => "img/noticia2.jpg",
        "titulo" => "Noticia 2",
        "resumen" => "Resumen de la noticia 2...",
        "enlace" => "#"
    ],
    [
        "imagen" => "img/noticia3.jpg",
        "titulo" => "Noticia 3",
        "resumen" => "Resumen de la noticia 3...",
        "enlace" => "#"
    ]
];

header("Content-Type: application/json");
echo json_encode($noticias);
?>
<?php include 'footer.php'; ?>
