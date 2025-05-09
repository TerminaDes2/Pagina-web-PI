<link rel="stylesheet" href="/Pagina-web-PI/assets/css/header-footer.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto+Slab:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="/Pagina-web-PI/assets/js/Main.js" defer></script>
<header class="hf-main-header">
    <div class="hf-header-container">
        <button class="hf-menu-toggle" onclick="toggleMenu()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="hf-logo-container">
            <div class="hf-logo">
                <a href="/Pagina-web-PI/index.php">
                    <h1>Voces del Proceso</h1>
                </a>
            </div>
        </div>
        <nav class="hf-main-nav">
            <ul class="hf-menu-desplegable">
                <li><a href="/Pagina-web-PI/index.php"><?= $translator->__("Inicio") ?></a></li>
                <li class="hf-menu-desplegable">
                    <a href="#"><?= $translator->__("Noticias") ?></a>
                    <div class="hf-contenido-desplegable"></div>
                </li>
                <li class="hf-menu-desplegable">
                    <a href="templates/Contacto.html"><?= $translator->__("Contacto") ?></a>
                </li>
                <li class="hf-menu-desplegable">
                    <a href="templates/Acerca-de-nosotros.html"><?= $translator->__("Acerca de") ?></a>
                </li>
            </ul>
        </nav>
        <div class="hf-header-actions">
            <div class="hf-search-box">
                <input type="text" placeholder="<?= $translator->__("Buscar...") ?>">
                <button><i class="fas fa-search"></i></button>
            </div>
            <div class="hf-social-icons">
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
<script>
    function toggleMenu() {
        const menu = document.querySelector('.hf-menu-desplegable');
        menu.classList.toggle('active');
    }
</script>
