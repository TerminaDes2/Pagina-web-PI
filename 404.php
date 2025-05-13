<!DOCTYPE html>
<html lang="<?= $_SESSION['idioma'] ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Error 404</title>
        <link rel="stylesheet" href="estilos.css">
        <style>
            .gif-container {
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
                align-items: center;
                height: 100vh;
                padding-top: 10vh;
                text-align: center;
            }
            .gif-container img {
                max-width: 300px;
                height: auto;
            }
            .gif-container h1 {
                margin: 20px 0;
                font-size: 36px;
                color: white;
            }
            .gif-container a {
                margin-top: 20px;
                text-decoration: none;
                color: white;
                background-color: chocolate;
                padding: 10px 20px;
                border-radius: 10px;
            }
        </style>
    </head>
    <body>
        <div class="gif-container">
            <h1>Error 404: Página no encontrada</h1>
            <img src="404.gif" alt="Error 404">
            <a href="index.php">Volver a la página principal</a>
        </div>
    </body>
</html>