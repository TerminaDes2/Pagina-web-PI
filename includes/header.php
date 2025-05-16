<link rel="stylesheet" href="/Pagina-web-PI/assets/css/header-footer.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&family=Roboto+Slab:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="/Pagina-web-PI/assets/js/menu.js" defer></script>
<?php
// Obtener categorías dinámicamente para el menú
$categorias_menu = [];
$sql_categorias = "SELECT id_categoria, categoria FROM categorias ORDER BY categoria";
$result_categorias = $conn->query($sql_categorias);

if ($result_categorias && $result_categorias->num_rows > 0) {
    while ($row = $result_categorias->fetch_assoc()) {
        $categorias_menu[] = $row;
    }
}
?>
<header class="hf-main-header">
    <div class="hf-header-container">
        <div class="hf-logo-container">
            <button class="hf-menu-toggle" id="menuToggleBtn" aria-label="Menú" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
            <div class="hf-logo">
                <a href="/Pagina-web-PI/index.php">
                    <div class="hf-logo-wrapper">
                        <img src="/Pagina-web-PI/assets/img/POALCE.png" alt="POALCE Logo" class="hf-logo-img">
                        <div class="hf-logo-glow"></div>
                    </div>
                </a>
            </div>
        </div>
        <nav class="hf-main-nav">
            <ul class="hf-main-menu" id="mainMenu">
                <li><a href="/Pagina-web-PI/index.php" class="hf-menu-link"><i class="fas fa-home hf-menu-icon"></i><?= $translator->__("Inicio") ?></a></li>
                <li class="hf-menu-categorias">
                    <a href="/Pagina-web-PI/php/explorar.php?modo=noticias" class="hf-menu-link hf-categoria-toggle"><i class="fas fa-newspaper hf-menu-icon"></i><?= $translator->__("Noticias") ?> <i class="fas fa-chevron-down hf-dropdown-arrow"></i></a>
                    <div class="hf-contenido-categorias">
                        <?php if (!empty($categorias_menu)): ?>
                            <?php foreach ($categorias_menu as $cat): ?>
                                <a href="/Pagina-web-PI/php/explorar.php?modo=categorias&cat=<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['categoria']) ?></a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <a href="#"><?= $translator->__("No hay categorías disponibles") ?></a>
                        <?php endif; ?>
                    </div>
                </li>
                <li>
                    <a href="/Pagina-web-PI/templates/Contacto.php" class="hf-menu-link"><i class="fas fa-envelope hf-menu-icon"></i><?= $translator->__("Contacto") ?></a>
                </li>
                <li>
                    <a href="/Pagina-web-PI/templates/Acerca-de-nosotros.php" class="hf-menu-link"><i class="fas fa-info-circle hf-menu-icon"></i><?= $translator->__("Acerca de") ?></a>
                </li>
            </ul>
        </nav>
        <div class="hf-header-actions">
            <div class="hf-search-box">
                <form action="/Pagina-web-PI/php/explorar.php" method="GET">
                    <input type="hidden" name="modo" value="buscar">
                    <input type="text" name="q" placeholder="<?= $translator->__("Buscar...") ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
            <div class="hf-social-icons">
                <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
            </div>
            <form method="POST" class="language-form">
                <select name="idioma" onchange="this.form.submit()">
                    <option value="en" <?= $_SESSION['idioma'] == 'en' ? 'selected' : '' ?>><?= $translator->__("Inglés") ?></option>
                    <option value="fr" <?= $_SESSION['idioma'] == 'fr' ? 'selected' : '' ?>><?= $translator->__("Francés") ?></option>
                    <option value="es" <?= $_SESSION['idioma'] == 'es' ? 'selected' : '' ?>><?= $translator->__("Español") ?></option>
                </select>
            </form>
            <?php if (isset($_SESSION['usuario'])): ?>
                <div class="hf-user-menu">
                    <a href="#" class="hf-profile-circle" title="<?= $translator->__("Mi perfil") ?>">
                        <?php if (isset($_SESSION['usuario']['imagen']) && !empty($_SESSION['usuario']['imagen'])): ?>
                            <img src="/Pagina-web-PI/<?= ltrim($_SESSION['usuario']['imagen'], '/') ?>" alt="<?= $translator->__("Foto de perfil") ?>">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </a>
                    <div class="hf-user-dropdown">
                        <a href="/Pagina-web-PI/php/perfil.php"><?= $translator->__("Mi Perfil") ?></a>
                        <?php if ($_SESSION['usuario']['perfil'] === 'admin'): ?>
                            <a href="/Pagina-web-PI/php/admin.php"><?= $translator->__("Panel Admin") ?></a>
                        <?php endif; ?>
                        <a href="/Pagina-web-PI/includes/cerrarsesion.php"><?= $translator->__("Cerrar sesión") ?></a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/Pagina-web-PI/php/registro.php" class="hf-login-btn">
                    <i class="fas fa-user"></i> <span><?= $translator->__("Iniciar sesión") ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hf-header-decoration"></div>
</header>
