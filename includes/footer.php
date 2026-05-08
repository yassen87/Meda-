<?php
declare(strict_types=1);
?>
    <footer class="footer">
        <div class="container footer-grid">
            <div class="footer-col footer-col--brand">
                <a href="<?= esc(url('')) ?>" class="footer-logo">
                    <img src="<?= esc(url('assets/img/logo.png')) ?>" alt="<?= esc(site_name()) ?>">
                </a>
                <p class="footer-tagline"><?= esc(t('hero_subtitle')) ?></p>
            </div>
            <div class="footer-col">
                <p class="footer-heading"><?= esc(t('nav_products')) ?></p>
                <nav class="footer-nav">
                    <a href="<?= esc(url('products.php?category=women')) ?>"><?= esc(t('cat_women')) ?></a>
                    <a href="<?= esc(url('products.php?category=men')) ?>"><?= esc(t('cat_men')) ?></a>
                    <a href="<?= esc(url('products.php?category=unisex')) ?>"><?= esc(t('cat_unisex')) ?></a>
                </nav>
            </div>
            <div class="footer-col">
                <p class="footer-heading"><?= esc(t('nav_about')) ?></p>
                <div class="footer-social">
                    <a href="https://www.facebook.com/ZeinPerfumes" target="_blank" class="social-link">Facebook</a>
                    <a href="https://www.instagram.com/zein_perfume_14" target="_blank" class="social-link">Instagram</a>
                </div>
            </div>
            <div class="footer-col">
                <p class="footer-heading"><?= esc(t('footer_hours')) ?></p>
                <p><?= esc(t('contact_hours_val')) ?></p>
            </div>
            <div class="footer-col">
                <p class="footer-heading"><?= esc(t('nav_contact')) ?></p>
                <p><span dir="ltr"><a href="<?= esc(contact_phone_href()) ?>"><?= esc(CONTACT_PHONE_TEL) ?></a></span></p>
                <p><span dir="ltr"><a href="mailto:bonjour@lumiere-parfums.example">bonjour@lumiere-parfums.example</a></span></p>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; <?= (int) date('Y') ?> <?= esc(site_name()) ?>. <?= esc(t('footer_demo')) ?></p>
        </div>
    </footer>

    <!-- Quick Add Modal (Redesigned) -->
    <dialog class="modal-configure" id="quick-add-modal">
        <div class="modal-configure__content">
            <button class="modal-configure__close" aria-label="Close modal">&times;</button>
            <div class="modal-configure__body">
                <div class="modal-configure__image-col">
                    <div class="product-visual-container">
                        <img id="modal-image" src="" alt="" 
                             onerror="if(!this.dataset.triedRelative){ this.dataset.triedRelative=true; var rel='assets/uploads/'+this.dataset.rawImg; this.src=rel; } else { this.style.display='none'; document.getElementById('modal-image-placeholder').style.display='block'; }"
                             style="width: 100%; height: 100%; object-fit: cover; display: none;">
                        <div id="modal-image-placeholder" style="font-size: 3rem; color: rgba(0,0,0,0.1);">✦</div>
                    </div>
                </div>
                <div class="modal-configure__details-col">
                    <h2 id="modal-title" class="modal-title"></h2>
                    <div class="modal-price" id="modal-price-display"></div>
                    
                    <div class="modal-section">
                        <p class="modal-section-label"><?= esc(t('product_label_variant')) ?>: <span id="selected-variant-label" style="font-weight:700;"></span></p>
                        <div class="variant-grid-modern" id="modal-variants">
                            <!-- JS Populated -->
                        </div>
                    </div>

                    <form class="modal-configure__form" id="modal-form" method="post" action="<?= esc(url('cart.php')) ?>">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="id" id="modal-product-id">
                        
                        <div class="modal-qty-wrapper">
                            <div class="modal-qty-controls">
                                <button type="button" class="qty-btn" data-action="minus">&minus;</button>
                                <input type="number" name="quantity" value="1" min="1" max="99" id="modal-qty">
                                <button type="button" class="qty-btn" data-action="plus">&plus;</button>
                            </div>
                        </div>

                        <div class="modal-actions">
                            <button type="submit" name="add" class="modal-btn modal-btn--add modal-add-btn">
                                <?= esc(t('product_btn_add')) ?>
                            </button>
                            <button type="submit" name="checkout" class="modal-btn modal-btn--buy modal-checkout-btn">
                                <?= esc(t('cart_checkout')) ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </dialog>

    <script>
        window.BASE_URL = <?= json_encode(url('')) ?>;
    </script>
    <script src="<?= esc(url('assets/js/main.js')) ?>"></script>
</body>
</html>
