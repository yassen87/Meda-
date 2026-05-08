(function () {
    var modal = document.getElementById('quick-add-modal');
    if (!modal) return;

    var btns = document.querySelectorAll('.btn-quick-configure');
    var closeBtn = modal.querySelector('.modal-configure__close');
    var imageEl = document.getElementById('modal-image');
    var titleEl = document.getElementById('modal-title');
    var priceDisplay = document.getElementById('modal-price-display');
    var selectedSizeLabel = document.getElementById('selected-variant-label');
    var variantsCont = document.getElementById('modal-variants');
    var idInput = document.getElementById('modal-product-id');
    var qtyInput = document.getElementById('modal-qty');
    var qtyMinus = modal.querySelector('[data-action="minus"]');
    var qtyPlus = modal.querySelector('[data-action="plus"]');

    var currentProduct = null;
    var currentVariantPrice = 0;

    function fmt(num) {
        var isAr = document.documentElement.lang === 'ar';
        var currency = (currentProduct && currentProduct.currency) ? currentProduct.currency : (isAr ? 'ج.م.' : 'EGP');
        return parseFloat(num).toFixed(2) + ' ' + currency;
    }

    function updatePrice() {
        var qty = parseInt(qtyInput.value) || 1;
        var total = currentVariantPrice * qty;
        priceDisplay.textContent = fmt(total);
    }

    function buildVariants(variants) {
        variantsCont.innerHTML = '';
        if (!variants || !variants.length) return;

        variants.forEach(function (v, i) {
            var isSel = (i === 0);
            var labelStr = v.label_ar || v.label_en || v.label;
            
            var lbl = document.createElement('label');
            lbl.className = 'variant-pill-modern';
            
            var inp = document.createElement('input');
            inp.type = 'radio';
            inp.name = 'variant_id';
            inp.value = v.id;
            inp.checked = isSel;
            
            var content = document.createElement('span');
            content.className = 'pill-content';
            content.textContent = labelStr;
            
            lbl.appendChild(inp);
            lbl.appendChild(content);
            variantsCont.appendChild(lbl);
            
            if (isSel) {
                currentVariantPrice = parseFloat(v.price) || 0;
                var stockText = "";
                if (v.stock !== undefined) {
                    stockText = " (" + (document.documentElement.lang === 'ar' ? 'المتبقي: ' : 'Stock: ') + v.stock + ")";
                }
                if (selectedSizeLabel) selectedSizeLabel.textContent = labelStr + stockText;
            }
            
            inp.addEventListener('change', function () {
                if (inp.checked) {
                    currentVariantPrice = parseFloat(v.price) || 0;
                    var sText = "";
                    if (v.stock !== undefined) {
                        sText = " (" + (document.documentElement.lang === 'ar' ? 'المتبقي: ' : 'Stock: ') + v.stock + ")";
                    }
                    if (selectedSizeLabel) selectedSizeLabel.textContent = labelStr + sText;
                    updatePrice();
                }
            });
        });
        
        updatePrice();
    }

    btns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            
            try {
                var b64Data = btn.getAttribute('data-product');
                var decodedString = decodeURIComponent(escape(window.atob(b64Data)));
                var data = JSON.parse(decodedString);
                currentProduct = data;
                
                titleEl.textContent = data.name;
                
                var placeholderEl = document.getElementById('modal-image-placeholder');
                
                function showImg(url) {
                    imageEl.src = url;
                    imageEl.style.display = 'block';
                    if (placeholderEl) placeholderEl.style.display = 'none';
                }
                function hideImg() {
                    imageEl.src = '';
                    imageEl.style.display = 'none';
                    if (placeholderEl) placeholderEl.style.display = 'block';
                }

                if (data.image && (data.image.startsWith('http://') || data.image.startsWith('https://'))) {
                    showImg(data.image);
                } else if (data.image && (data.image.includes('.') || data.image.startsWith('img_'))) {
                    var baseUrl = window.BASE_URL || '';
                    if (baseUrl && !baseUrl.endsWith('/')) baseUrl += '/';
                    showImg(baseUrl + 'assets/uploads/' + data.image);
                } else if (data.image && data.image !== 'default') {
                    var baseUrl = window.BASE_URL || '';
                    if (baseUrl && !baseUrl.endsWith('/')) baseUrl += '/';
                    showImg(baseUrl + 'assets/img/' + data.image + '.jpg');
                } else {
                    hideImg();
                }

                idInput.value = data.id;
                qtyInput.value = 1;
                buildVariants(data.variants);
                modal.showModal();
            } catch (err) {
                console.error("Failed to parse product data", err);
            }
        });
    });

    closeBtn.addEventListener('click', function () { modal.close(); });
    modal.addEventListener('click', function (e) { if (e.target === modal) modal.close(); });
    
    qtyMinus.addEventListener('click', function () {
        var v = parseInt(qtyInput.value) || 1;
        if (v > 1) { qtyInput.value = v - 1; updatePrice(); }
    });

    qtyPlus.addEventListener('click', function () {
        var v = parseInt(qtyInput.value) || 1;
        if (v < 99) { qtyInput.value = v + 1; updatePrice(); }
    });
    
    qtyInput.addEventListener('change', function () {
        var v = parseInt(qtyInput.value) || 1;
        if (v < 1) v = 1; if (v > 99) v = 99;
        qtyInput.value = v;
        updatePrice();
    });
    
    var form = document.getElementById('modal-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            var btn = e.submitter;
            
            e.preventDefault();
            var formData = new FormData(form);
            formData.append('action', 'add');
            
            // Ensure qty is sent as 'qty' to match cart.php
            var qty = document.getElementById('modal-qty').value;
            formData.set('qty', qty);
            
            // Ensure product_id is sent correctly
            var pid = document.getElementById('modal-product-id').value;
            formData.set('product_id', pid);

            if (btn && btn.name === 'checkout') {
                btn.disabled = true;
                fetch(form.action, {
                    method: 'POST',
                    body: new URLSearchParams(formData),
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
                }).then(function() {
                    window.location.href = window.BASE_URL + 'checkout.php';
                });
                return;
            }
            
            var addBtn = form.querySelector('.modal-add-btn');
            addBtn.disabled = true;
            
            fetch(form.action, {
                method: 'POST',
                body: new URLSearchParams(formData),
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            }).then(function() {
                addBtn.disabled = false;
                var badges = document.querySelectorAll('.cart-badge');
                var qtyVal = parseInt(qty) || 1;
                badges.forEach(function(b) {
                    var current = parseInt(b.textContent) || 0;
                    b.textContent = current + qtyVal;
                });
                modal.close();
            });
        });
    }

    // Navigation & Theme Toggles
    var navToggle = document.querySelector('.nav-toggle');
    var siteNav = document.getElementById('site-nav');
    if (navToggle && siteNav) {
        navToggle.addEventListener('click', function () {
            siteNav.classList.toggle('is-open');
        });
    }

    var themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            document.documentElement.classList.toggle('daylight');
        });
    }
})();