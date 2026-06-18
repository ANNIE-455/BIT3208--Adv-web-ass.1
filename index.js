document.addEventListener('DOMContentLoaded', () => {

    // ── ELEMENT REFS ──
    const navbar           = document.querySelector('.navbar');
    const menuBtn          = document.querySelector('#menu-bar');
    const searchEl         = document.querySelector('.search');
    const searchBtn        = document.querySelector('#search');
    const loginContainer   = document.querySelector('#login-form-container');
    const closeLoginBtn    = document.querySelector('#close-login-btn');
    const signInLink       = document.querySelector('.signInBtn-link');
    const signUpLink       = document.querySelector('.signUpBtn-link');
    const wrapper          = document.querySelector('#auth-wrapper');
    const loginBtn         = document.querySelector('#login-btn');

    // Cart elements
    const cartBtn          = document.querySelector('#cart-btn');
    const cartSidebar      = document.querySelector('#cart-sidebar');
    const closeCartBtn     = document.querySelector('#close-cart-btn');
    const cartOverlay      = document.querySelector('#cart-overlay');
    const cartItemsEl      = document.querySelector('#cart-items');
    const cartCountEl      = document.querySelector('#cart-count');
    const cartTotalEl      = document.querySelector('#cart-total-price');
    const cartFooter       = document.querySelector('#cart-footer');
    const cartEmpty        = document.querySelector('#cart-empty');
    const checkoutBtn      = document.querySelector('#checkout-btn');

    // Forms
    const passwordInput    = document.querySelector('#signup-password');
    const signupForm       = document.querySelector('#signup-form');
    const signinForm       = document.querySelector('#signin-form');

    // ── CART STATE ──
    // Structure: { id: { name, price, img, qty } }
    let cart = {};

    // ── OPEN / CLOSE HELPERS ──

    function openCart() {
        cartSidebar.classList.add('open');
        cartOverlay.classList.add('active');
    }

    function closeCart() {
        cartSidebar.classList.remove('open');
        cartOverlay.classList.remove('active');
    }

    function openLogin() {
        loginContainer.classList.add('active');
        closeNav();
        closeSearch();
    }

    function closeLogin() {
        loginContainer.classList.remove('active');
        wrapper.classList.remove('active-signup');
    }

    function closeNav() {
        if (navbar) navbar.classList.remove('active');
    }

    function closeSearch() {
        if (searchEl) searchEl.classList.remove('active');
    }

    // ── HEADER CONTROLS ──

    if (loginBtn) {
        loginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openLogin();
        });
    }

    if (cartBtn) {
        cartBtn.addEventListener('click', openCart);
    }

    if (closeCartBtn) {
        closeCartBtn.addEventListener('click', closeCart);
    }

    if (cartOverlay) {
        cartOverlay.addEventListener('click', closeCart);
    }

    if (menuBtn && navbar) {
        menuBtn.addEventListener('click', () => {
            navbar.classList.toggle('active');
            closeSearch();
            closeCart();
        });
    }

    if (searchBtn && searchEl) {
        searchBtn.addEventListener('click', () => {
            searchEl.classList.toggle('active');
            closeNav();
        });
    }

    if (closeLoginBtn) {
        closeLoginBtn.addEventListener('click', closeLogin);
    }

    // Close login when clicking outside the wrapper
    if (loginContainer) {
        loginContainer.addEventListener('click', (e) => {
            if (e.target === loginContainer) closeLogin();
        });
    }

    // ── SIGN IN / SIGN UP SLIDE  ──
    // FIX: use 'active-signup' class (not 'active') to match CSS

    if (signUpLink && wrapper) {
        signUpLink.addEventListener('click', (e) => {
            e.preventDefault();
            wrapper.classList.add('active-signup');
        });
    }

    if (signInLink && wrapper) {
        signInLink.addEventListener('click', (e) => {
            e.preventDefault();
            wrapper.classList.remove('active-signup');
        });
    }

    // Close dropdowns on scroll
    window.addEventListener('scroll', () => {
        closeNav();
        closeSearch();
    });

    // ── CART FUNCTIONS ──

    function cartKey(name) {
        // Simple key from product name (slug-style)
        return name.toLowerCase().replace(/\s+/g, '-');
    }

    function addToCart(name, price, img) {
        const key = cartKey(name);
        if (cart[key]) {
            cart[key].qty += 1;
        } else {
            cart[key] = { name, price: parseInt(price), img, qty: 1 };
        }
        renderCart();
        showToast(`${name} added to cart`);
        openCart();
    }

    function removeFromCart(key) {
        delete cart[key];
        renderCart();
    }

    function changeQty(key, delta) {
        if (!cart[key]) return;
        cart[key].qty += delta;
        if (cart[key].qty <= 0) {
            delete cart[key];
        }
        renderCart();
    }

    function renderCart() {
        const items = Object.entries(cart);
        const totalCount = items.reduce((sum, [, item]) => sum + item.qty, 0);
        const totalPrice = items.reduce((sum, [, item]) => sum + item.price * item.qty, 0);

        // Update badge
        cartCountEl.textContent = totalCount;

        // Show/hide empty state & footer
        if (items.length === 0) {
            cartEmpty.style.display = 'flex';
            cartFooter.style.display = 'none';
        } else {
            cartEmpty.style.display = 'none';
            cartFooter.style.display = 'block';
        }

        // Update total
        cartTotalEl.textContent = `Ksh ${totalPrice.toLocaleString()}`;

        // Remove old item rows (keep the empty placeholder)
        const oldItems = cartItemsEl.querySelectorAll('.cart-item');
        oldItems.forEach(el => el.remove());

        // Render each item
        items.forEach(([key, item]) => {
            const div = document.createElement('div');
            div.className = 'cart-item';
            div.innerHTML = `
                <img src="${item.img}" alt="${item.name}">
                <div class="cart-item-info">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">Ksh ${(item.price * item.qty).toLocaleString()}</div>
                    <div class="cart-item-controls">
                        <button class="qty-btn" data-key="${key}" data-delta="-1">−</button>
                        <span class="qty-display">${item.qty}</span>
                        <button class="qty-btn" data-key="${key}" data-delta="1">+</button>
                    </div>
                </div>
                <button class="remove-btn" data-key="${key}" title="Remove">
                    <i class="fas fa-trash-alt"></i>
                </button>
            `;
            cartItemsEl.appendChild(div);
        });

        // Attach qty and remove events
        cartItemsEl.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const key   = btn.dataset.key;
                const delta = parseInt(btn.dataset.delta);
                changeQty(key, delta);
            });
        });

        cartItemsEl.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                removeFromCart(btn.dataset.key);
            });
        });
    }

    // ── ORDER NOW BUTTONS (add to cart) ──
    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const name  = btn.dataset.name;
            const price = btn.dataset.price;
            const img   = btn.dataset.img;
            addToCart(name, price, img);
        });
    });

    // ── CHECKOUT ──
    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', () => {
            const items = Object.values(cart);
            if (items.length === 0) return;

            // NOTE: Replace this with a real backend call to your MySQL/PHP endpoint.
            // Example: fetch('/api/order.php', { method:'POST', body: JSON.stringify({ items }) })
            //
            // For now, just show a confirmation message.
            alert(`Order placed!\n\n${items.map(i => `${i.name} x${i.qty} — Ksh ${(i.price * i.qty).toLocaleString()}`).join('\n')}\n\nTotal: Ksh ${items.reduce((s, i) => s + i.price * i.qty, 0).toLocaleString()}`);
            cart = {};
            renderCart();
            closeCart();
        });
    }

    // ── TOAST NOTIFICATION ──
    let toastTimer;
    function showToast(message) {
        let toast = document.querySelector('.toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast';
            document.body.appendChild(toast);
        }
        toast.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
        toast.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 2500);
    }

    // ── PASSWORD STRENGTH ──
    if (passwordInput) {
        passwordInput.addEventListener('input', () => {
            checkStrength(passwordInput.value);
        });
    }

    // ── FORM VALIDATION: SIGN UP ──
    if (signupForm) {
        signupForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const email    = document.querySelector('#signup-email');
            const password = document.querySelector('#signup-password');
            const terms    = document.querySelector('#terms-check');
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailPattern.test(email.value)) {
                alert('Please enter a valid email address.');
                return;
            }
            if (password.value.length < 6) {
                alert('Password must be at least 6 characters.');
                return;
            }
            if (!terms.checked) {
                alert('Please agree to the terms and conditions.');
                return;
            }

            // NOTE: Replace with your backend signup API call.
            // fetch('/api/register.php', { method:'POST', body: JSON.stringify({...}) })
            alert('Account created successfully! You can now sign in.');
            wrapper.classList.remove('active-signup');
            signupForm.reset();
            checkStrength('');
        });
    }

    // ── FORM VALIDATION: SIGN IN ──
    if (signinForm) {
        signinForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const email    = document.querySelector('#signin-email');
            const password = document.querySelector('#signin-password');
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailPattern.test(email.value)) {
                alert('Please enter a valid email address.');
                return;
            }
            if (password.value.length < 1) {
                alert('Please enter your password.');
                return;
            }

            // NOTE: Replace with your backend login API call.
            // fetch('/api/login.php', { method:'POST', body: JSON.stringify({...}) })
            alert('Signed in successfully!');
            closeLogin();
            signinForm.reset();
        });
    }

    // ── SWIPER CAROUSELS ──
    if (document.querySelector('.product-row')) {
        new Swiper(".product-row", {
            spaceBetween: 30,
            loop: true,
            centeredSlides: true,
            autoplay: { delay: 9500, disableOnInteraction: false },
            pagination: { el: ".swiper-pagination", clickable: true },
            breakpoints: {
                0:    { slidesPerView: 1 },
                768:  { slidesPerView: 2 },
                1024: { slidesPerView: 3 },
            },
        });
    }

    if (document.querySelector('.blogs-row')) {
        new Swiper(".blogs-row", {
            spaceBetween: 30,
            loop: true,
            centeredSlides: true,
            autoplay: { delay: 9500, disableOnInteraction: false },
            pagination: { el: ".swiper-pagination", clickable: true },
            navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" },
            breakpoints: {
                0:    { slidesPerView: 1 },
                768:  { slidesPerView: 1 },
                1024: { slidesPerView: 1 },
            },
        });
    }

    if (document.querySelector('.review-row')) {
        new Swiper(".review-row", {
            spaceBetween: 30,
            loop: true,
            centeredSlides: true,
            autoplay: { delay: 9500, disableOnInteraction: false },
            pagination: { el: ".swiper-pagination", clickable: true },
            breakpoints: {
                0:    { slidesPerView: 1 },
                768:  { slidesPerView: 2 },
                1024: { slidesPerView: 3 },
            },
        });
    }

    // Initial render
    renderCart();
});

// ── PASSWORD STRENGTH CHECKER ──
function checkStrength(password) {
    const fill  = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    if (!fill || !label) return;

    if (password.length === 0) {
        fill.style.width      = '0%';
        fill.style.background = 'transparent';
        label.textContent     = '';
        label.className       = '';
        return;
    }

    let score = 0;
    if (password.length >= 8)            score++;
    if (/[A-Z]/.test(password))         score++;
    if (/[0-9]/.test(password))         score++;
    if (/[^A-Za-z0-9]/.test(password))  score++;

    if (score <= 1) {
        fill.style.width      = '33%';
        fill.style.background = '#ff5f5f';
        label.textContent     = 'Weak ❌';
        label.className       = 'weak';
    } else if (score <= 3) {
        fill.style.width      = '66%';
        fill.style.background = '#f0c040';
        label.textContent     = 'Medium ⚠️';
        label.className       = 'medium';
    } else {
        fill.style.width      = '100%';
        fill.style.background = '#4caf87';
        label.textContent     = 'Strong 💪';
        label.className       = 'strong';
    }
}