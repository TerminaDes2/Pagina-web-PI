<footer class="hf-main-footer">
    <div class="hf-footer-wave">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M985.66,92.83C906.67,72,823.78,31,743.84,14.19c-82.26-17.34-168.06-16.33-250.45.39-57.84,11.73-114,31.07-172,41.86A600.21,600.21,0,0,1,0,27.35V120H1200V95.8C1132.19,118.92,1055.71,111.31,985.66,92.83Z" class="shape-fill"></path>
        </svg>
    </div>
    <div class="hf-footer-container">
        <div class="hf-footer-logo">
            <a href="index.php" class="hf-footer-logo-link">
                <img src="/Pagina-web-PI/assets/img/POALCE.png" alt="POALCE Logo" class="hf-footer-logo-img">
            </a>
            <p class="hf-footer-tagline"><?= $translator->__("Plataforma de noticias al alcance de todos") ?></p>
        </div>
        <div class="hf-footer-links">
            <h3><?= $translator->__("Enlaces rápidos") ?></h3>
            <ul>
                <li><a href="/Pagina-web-PI/index.php"><?= $translator->__("Inicio") ?></a></li>
                <li><a href="/Pagina-web-PI/php/explorar.php?modo=noticias"><?= $translator->__("Noticias") ?></a></li>
                <li><a href="/Pagina-web-PI/templates/Contacto.php"><?= $translator->__("Contacto") ?></a></li>
                <li><a href="/Pagina-web-PI/templates/Acerca-de-nosotros.php"><?= $translator->__("Acerca de") ?></a></li>
            </ul>
        </div>
        <div class="hf-footer-social">
            <h3><?= $translator->__("Síguenos") ?></h3>
            <div class="hf-social-icons-container">
                <a href="#" class="hf-social-icon facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://x.com/POALCE1" class="hf-social-icon twitter"><i class="fab fa-twitter"></i></a>
                <a href="https://www.instagram.com/poalse5/" class="hf-social-icon instagram"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
        <div class="hf-footer-copyright">
            <p>&copy; 2025 POALCE. <?= $translator->__("Todos los derechos reservados.") ?></p>
        </div>
    </div>
</footer>
