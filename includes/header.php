<?php
// ...existing code for session and translator setup...
?>
<link rel="stylesheet" href="/Pagina-web-PI/assets/css/header-footer.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="/Pagina-web-PI/assets/js/Main.js" defer></script>
<header class="main-header">
    <div class="header-container">
        <div class="logo-container">
            <div class="logo">
                <a href="index.php">
                    <h1>Voces del Proceso</h1>
                </a>
            </div>
        </div>
        <nav class="main-nav">
            <ul>
                <li><a href="index.php"><?= $translator->__("Inicio") ?></a></li>
                <li class="menu-desplegable">
                    <a href="#"><?= $translator->__("Noticias") ?></a>
                    <div class="contenido-desplegable"></div>
                </li>
                <li class="menu-desplegable">
                    <a href="templates/Contacto.html"><?= $translator->__("Contacto") ?></a>
                </li>
                <li class="menu-desplegable">
                    <a href="templates/Acerca-de-nosotros.html"><?= $translator->__("Acerca de") ?></a>
                </li>
            </ul>
        </nav>
        <div class="header-actions">
            <div class="search-box">
                <input type="text" placeholder="<?= $translator->__("Buscar...") ?>">
                <button><i class="fas fa-search"></i></button>
            </div>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <form method="POST" class="language-form">
                    <select name="idioma" onchange="this.form.submit()">
                        <option value="en" <?= $_SESSION['idioma'] == 'en' ? 'selected' : '' ?>><?= $translator->__("Inglés") ?></option>
                        <option value="fr" <?= $_SESSION['idioma'] == 'fr' ? 'selected' : '' ?>><?= $translator->__("Francés") ?></option>
                        <option value="es" <?= $_SESSION['idioma'] == 'es' ? 'selected' : '' ?>><?= $translator->__("Español") ?></option>
                    </select>
                </form>
            </div>
        </div>
    </div>
</header>
