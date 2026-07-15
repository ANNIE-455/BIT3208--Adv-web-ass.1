// =============================================
//  COOKIE HELPERS
//  PHP equivalent: setcookie() / $_COOKIE[]
//  These satisfy: Exercise 2 — cookie management
// =============================================

/**
 * Set a browser cookie
 * @param {string} name  - cookie name
 * @param {string} value - cookie value
 * @param {number} days  - expiry in days (0 = session cookie)
 */
function setCookie(name, value, days = 0) {
    let expires = '';
    if (days > 0) {
        const date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        expires = '; expires=' + date.toUTCString();
    }
    document.cookie = name + '=' + encodeURIComponent(value) + expires + '; path=/';
}

/**
 * Read a cookie by name
 * @param {string} name
 * @returns {string|null}
 */
function getCookie(name) {
    const key = name + '=';
    const cookies = document.cookie.split(';');
    for (let c of cookies) {
        c = c.trim();
        if (c.startsWith(key)) return decodeURIComponent(c.substring(key.length));
    }
    return null;
}

/**
 * Delete a cookie by name
 * @param {string} name
 */
function deleteCookie(name) {
    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/';
}

// =============================================
//  THEME TOGGLE (Light / Dark)
//  Cookie: sc_theme
//  Satisfies: Exercise 2 requirement 2 —
//  "Create a cookie to remember the user's
//   preferred theme (Light or Dark)"
// =============================================
const themeToggle = document.getElementById('theme-toggle');

// On page load, read the theme cookie and apply it
(function applyThemeCookie() {
    const savedTheme = getCookie('sc_theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-theme');
        themeToggle.classList.replace('fa-moon', 'fa-sun');
    }
})();

themeToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark-theme');
    if (document.body.classList.contains('dark-theme')) {
        themeToggle.classList.replace('fa-moon', 'fa-sun');
        // Save theme preference in cookie — expires in 30 days
        setCookie('sc_theme', 'dark', 30);
    } else {
        themeToggle.classList.replace('fa-sun', 'fa-moon');
        setCookie('sc_theme', 'light', 30);
    }
});

// =============================================
//  REMEMBER ME — auto-fill username
//  Cookie: sc_remember
//  Satisfies: Assignment —
//  "username should automatically appear the
//   next time the application is opened"
// =============================================
(function applyRememberMe() {
    const savedUsername = getCookie('sc_remember');
    const usernameField = document.getElementById('login-username');
    const rememberBox   = document.getElementById('remember-me-checkbox');

    if (savedUsername && usernameField) {
        usernameField.value = savedUsername;
        // Also tick the checkbox so the user knows it's remembered
        if (rememberBox) rememberBox.checked = true;
    }
})();

// =============================================
//  SESSION INFO BAR
//  Cookie: sc_session_info (set by auth.php after login)
//  Satisfies: Exercise 2 requirement 3 —
//  "Display the user's session ID and login time"
//  Also satisfies: Exercise 1 —
//  "Display a welcome page showing the logged-in username"
// =============================================
function loadSessionBar() {
    const raw = getCookie('sc_session_info');
    if (!raw) return; // not logged in

    try {
        const info = JSON.parse(raw);
        // Populate the session bar elements
        document.getElementById('session-username').textContent   = info.username  || '—';
        document.getElementById('session-login-time').textContent = info.login_time || '—';
        // Show only first 12 chars of session ID for readability
        document.getElementById('session-id-display').textContent =
            (info.session_id || '—').substring(0, 12) + '…';

        // Show the bar and push the header down
        const bar    = document.getElementById('session-bar');
        const header = document.querySelector('.header');
        bar.style.display = 'flex';
        header.classList.add('with-session-bar');

        // Update the user icon to indicate logged-in state
        const userIcon = document.getElementById('user-icon');
        if (userIcon) {
            userIcon.classList.replace('fa-user-circle', 'fa-user-check');
            userIcon.style.color = '#2ecc71';
            userIcon.title = 'Logged in as ' + info.username;
        }

    } catch (e) {
        // Cookie is malformed — clear it
        deleteCookie('sc_session_info');
    }
}

// =============================================
//  SESSION EXPIRY CHECK
//  Satisfies: Exercise 2 requirement 4 —
//  "Automatically redirect unauthenticated users
//   back to the login page if the session has expired"
//
//  How it works:
//  auth.php stores login_time in the cookie.
//  We check every 60 seconds. If the cookie is gone
//  (PHP session expired and PHP cleared it via
//  logout or timeout), we show a toast and open
//  the login modal automatically.
// =============================================
function checkSessionExpiry() {
    const raw = getCookie('sc_session_info');

    // If user was logged in (bar was visible) but cookie is now gone
    const bar = document.getElementById('session-bar');
    if (!raw && bar && bar.style.display !== 'none') {
        // Session has expired — hide bar, show toast, open login
        bar.style.display = 'none';
        document.querySelector('.header').classList.remove('with-session-bar');
        showSessionExpiredToast();
    }
}

function showSessionExpiredToast() {
    // Create toast if it doesn't exist
    let toast = document.getElementById('session-expired-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id        = 'session-expired-toast';
        toast.className = 'session-expired-toast';
        toast.innerHTML = '<i class="fas fa-exclamation-circle"></i> Your session has expired. Please log in again.';
        document.body.appendChild(toast);
    }
    toast.classList.add('active');

    // Auto-open the login modal after a short delay
    setTimeout(() => {
        openAuth();
    }, 2000);

    // Hide toast after 6 seconds
    setTimeout(() => {
        toast.classList.remove('active');
    }, 6000);
}

// Check session every 60 seconds
setInterval(checkSessionExpiry, 60000);

// =============================================
//  LOGOUT
//  Satisfies: Exercise 1 —
//  "Provide a Logout button that invalidates
//   the session and redirects back to the login page"
// =============================================
function logoutUser() {
    // Clear all session & remember cookies on the client side
    deleteCookie('sc_session_info');
    // Note: sc_remember is intentionally kept so username
    // still auto-fills if they had "Remember Me" checked

    // Tell PHP to destroy the server-side session
    window.location.href = 'auth.php?logout=1';
}

// =============================================
//  NAVBAR TOGGLE (mobile hamburger)
// =============================================
const navbar = document.querySelector('.navbar');
document.querySelector('#menu-bar').onclick = () => {
    navbar.classList.toggle('active');
};

// =============================================
//  SEARCH BAR TOGGLE
// =============================================
const search = document.querySelector('.search');
document.querySelector('#search').onclick = () => {
    search.classList.toggle('active');
};

// =============================================
//  AUTH MODAL (Login / Sign Up)
// =============================================
const userIcon      = document.getElementById('user-icon');
const authWrapper   = document.getElementById('auth-wrapper');
const authOverlay   = document.getElementById('auth-overlay');
const closeAuth     = document.getElementById('close-auth');
const signUpBtnLink = document.querySelector('.signUpBtn-link');
const signInBtnLink = document.querySelector('.signInBtn-link');

function openAuth() {
    authWrapper.classList.add('show');
    authOverlay.classList.add('active');
}

function closeAuthModal() {
    authWrapper.classList.remove('show', 'active');
    authOverlay.classList.remove('active');
}

userIcon.addEventListener('click', openAuth);
closeAuth.addEventListener('click', closeAuthModal);
authOverlay.addEventListener('click', closeAuthModal);

signUpBtnLink.addEventListener('click', (e) => {
    e.preventDefault();
    authWrapper.classList.add('active');
});
signInBtnLink.addEventListener('click', (e) => {
    e.preventDefault();
    authWrapper.classList.remove('active');
});

// =============================================
//  ADMIN LOGIN MODAL
// =============================================
const adminWrapper = document.getElementById('admin-wrapper');
const adminOverlay = document.getElementById('admin-overlay');
const closeAdmin   = document.getElementById('close-admin');

function openAdminLogin() {
    adminWrapper.classList.add('show');
    adminOverlay.classList.add('active');
}

function closeAdminLogin() {
    adminWrapper.classList.remove('show');
    adminOverlay.classList.remove('active');
}

closeAdmin.addEventListener('click', closeAdminLogin);
adminOverlay.addEventListener('click', closeAdminLogin);

// =============================================
//  PASSWORD STRENGTH METER
// =============================================
const passwordInput = document.getElementById('signup-password');
const strengthBar   = document.getElementById('strength-bar');
const strengthText  = document.getElementById('strength-text');

passwordInput.addEventListener('input', () => {
    const val = passwordInput.value;
    let score = 0;

    if (val.length >= 8)          score++;
    if (/[A-Z]/.test(val))        score++;
    if (/[0-9]/.test(val))        score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    if (val.length === 0) {
        strengthBar.style.width      = '0%';
        strengthBar.style.background = 'transparent';
        strengthText.textContent     = '';
        return;
    }

    if (score <= 1) {
        strengthBar.style.width      = '33%';
        strengthBar.style.background = '#e74c3c';
        strengthText.textContent     = 'Weak password';
        strengthText.style.color     = '#e74c3c';
    } else if (score <= 3) {
        strengthBar.style.width      = '66%';
        strengthBar.style.background = '#f39c12';
        strengthText.textContent     = 'Medium password';
        strengthText.style.color     = '#f39c12';
    } else {
        strengthBar.style.width      = '100%';
        strengthBar.style.background = '#2ecc71';
        strengthText.textContent     = 'Strong password!';
        strengthText.style.color     = '#2ecc71';
    }
});

// =============================================
//  CART
// =============================================
let cart = [];

const cartIcon    = document.getElementById('cart-icon');
const cartSidebar = document.getElementById('cart-sidebar');
const cartOverlay = document.getElementById('cart-overlay');
const closeCart   = document.getElementById('close-cart');
const cartCount   = document.getElementById('cart-count');
const cartItemsEl = document.getElementById('cart-items');
const cartTotal   = document.getElementById('cart-total');

function openCart() {
    cartSidebar.classList.add('active');
    cartOverlay.classList.add('active');
}

function closeCartSidebar() {
    cartSidebar.classList.remove('active');
    cartOverlay.classList.remove('active');
}

cartIcon.addEventListener('click', openCart);
closeCart.addEventListener('click', closeCartSidebar);
cartOverlay.addEventListener('click', closeCartSidebar);

function updateCart() {
    cartItemsEl.innerHTML = '';

    if (cart.length === 0) {
        cartItemsEl.innerHTML = '<p class="empty-cart-msg">Your cart is empty.</p>';
        cartCount.textContent = '0';
        cartTotal.textContent = 'KSh 0';
        return;
    }

    let total = 0;
    cart.forEach((item, index) => {
        total += item.price;
        const div = document.createElement('div');
        div.classList.add('cart-item');
        div.innerHTML = `
            <span>${item.name}</span>
            <span>KSh ${item.price.toLocaleString()}</span>
            <span class="remove-item" data-index="${index}">
                <i class="fas fa-trash"></i>
            </span>
        `;
        cartItemsEl.appendChild(div);
    });

    cartCount.textContent = cart.length;
    cartTotal.textContent = `KSh ${total.toLocaleString()}`;

    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', () => {
            cart.splice(parseInt(btn.dataset.index), 1);
            updateCart();
        });
    });
}

document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        cart.push({
            name:  btn.dataset.name,
            price: parseInt(btn.dataset.price)
        });
        updateCart();
        openCart();
    });
});

// =============================================
//  CHECKOUT MODAL (Cart → Map → M-Pesa)
// =============================================
const checkoutBtn     = document.getElementById('checkout-btn');
const checkoutModal   = document.getElementById('checkout-modal');
const checkoutOverlay = document.getElementById('checkout-overlay');
const closeCheckout   = document.getElementById('close-checkout-modal');
const step1           = document.getElementById('checkout-step-1');
const step2           = document.getElementById('checkout-step-2');

function openCheckoutModal() {
    if (cart.length === 0) {
        alert('Your cart is empty! Please add a cake first.');
        return;
    }
    closeCartSidebar();
    checkoutModal.classList.add('active');
    checkoutOverlay.classList.add('active');
    setTimeout(initDeliveryMap, 250);
}

function closeCheckoutModal() {
    checkoutModal.classList.remove('active');
    checkoutOverlay.classList.remove('active');
}

checkoutBtn.addEventListener('click', openCheckoutModal);
closeCheckout.addEventListener('click', closeCheckoutModal);
checkoutOverlay.addEventListener('click', closeCheckoutModal);

function goToPaymentStep() {
    const name  = document.getElementById('co-name').value.trim();
    const phone = document.getElementById('co-phone').value.trim();
    const addr  = document.getElementById('co-address').value.trim();

    if (!name || !phone || !addr) {
        alert('Please fill in your name, phone number, and delivery area before continuing.');
        return;
    }
    step1.style.display = 'none';
    step2.style.display = 'block';
}

function goBackToStep1() {
    step2.style.display = 'none';
    step1.style.display = 'block';
}

function submitCheckoutOrder() {
    const mpesa = document.getElementById('co-mpesa').value.trim();
    const code  = document.getElementById('co-mpesa-code').value.trim();
    const name  = document.getElementById('co-name').value.trim();
    const phone = document.getElementById('co-phone').value.trim();
    const addr  = document.getElementById('co-address').value.trim();

    if (!mpesa || !code) {
        alert('Please enter your M-Pesa / Airtel number and transaction code.');
        return;
    }

    const cakeList  = cart.map(i => i.name).join(', ');
    const cakeTotal = cart.reduce((sum, i) => sum + i.price, 0);

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'auth.php';

    const fields = {
        place_order:   '1',
        customer_name: name,
        phone:         phone,
        address:       addr,
        cake_name:     cakeList,
        cake_price:    cakeTotal,
        size:          'cart',
        message:       '',
        mpesa_number:  mpesa,
        mpesa_code:    code.toUpperCase(),
    };

    Object.entries(fields).forEach(([k, v]) => {
        const input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = k;
        input.value = v;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

// =============================================
//  DELIVERY MAP
// =============================================
const deliveryZones = [
    { name: 'CBD / City Centre',    lat: -1.2841, lng: 36.8155, fee: 150 },
    { name: 'Westlands',            lat: -1.2676, lng: 36.8044, fee: 200 },
    { name: 'Kilimani',             lat: -1.2921, lng: 36.7868, fee: 200 },
    { name: 'Lavington',            lat: -1.2804, lng: 36.7743, fee: 220 },
    { name: 'Karen',                lat: -1.3183, lng: 36.7108, fee: 350 },
    { name: 'Langata',              lat: -1.3527, lng: 36.7369, fee: 320 },
    { name: 'South B',              lat: -1.3175, lng: 36.8292, fee: 250 },
    { name: 'South C',              lat: -1.3064, lng: 36.8119, fee: 250 },
    { name: 'Eastleigh',            lat: -1.2728, lng: 36.8440, fee: 200 },
    { name: 'Buruburu',             lat: -1.2869, lng: 36.8697, fee: 230 },
    { name: 'Donholm',              lat: -1.2996, lng: 36.8876, fee: 250 },
    { name: 'Embakasi',             lat: -1.3194, lng: 36.9025, fee: 280 },
    { name: 'Kasarani',             lat: -1.2232, lng: 36.8971, fee: 270 },
    { name: 'Roysambu',             lat: -1.2154, lng: 36.8778, fee: 270 },
    { name: 'Ruaka',                lat: -1.1905, lng: 36.8065, fee: 350 },
    { name: 'Ruiru',                lat: -1.1456, lng: 36.9609, fee: 400 },
    { name: 'Juja',                 lat: -1.1019, lng: 37.0143, fee: 450 },
    { name: 'Thika',                lat: -1.0332, lng: 37.0692, fee: 500 },
    { name: 'Kiambu Town',          lat: -1.1709, lng: 36.8359, fee: 380 },
    { name: 'Limuru',               lat: -1.1124, lng: 36.6403, fee: 450 },
    { name: 'Kikuyu',               lat: -1.2468, lng: 36.6645, fee: 350 },
    { name: 'Githunguri',           lat: -1.0748, lng: 36.7358, fee: 420 },
    { name: 'Muthaiga',             lat: -1.2530, lng: 36.8332, fee: 220 },
    { name: 'Gigiri',               lat: -1.2379, lng: 36.8017, fee: 230 },
    { name: 'Runda',                lat: -1.2279, lng: 36.8150, fee: 240 },
    { name: 'Spring Valley',        lat: -1.2537, lng: 36.7880, fee: 220 },
    { name: 'Parklands',            lat: -1.2627, lng: 36.8209, fee: 180 },
    { name: 'Upper Hill',           lat: -1.2982, lng: 36.8168, fee: 180 },
    { name: 'Ngong Road',           lat: -1.3059, lng: 36.7712, fee: 280 },
    { name: 'Athi River / Mavoko',  lat: -1.4555, lng: 36.9780, fee: 600 },
];

const SHOP_LAT = -1.2921;
const SHOP_LNG = 36.8219;

let deliveryMap    = null;
let deliveryMarker = null;

function initDeliveryMap() {
    if (deliveryMap) {
        deliveryMap.invalidateSize();
        return;
    }

    deliveryMap = L.map('delivery-map').setView([SHOP_LAT, SHOP_LNG], 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(deliveryMap);

    L.marker([SHOP_LAT, SHOP_LNG], {
        icon: L.divIcon({
            className: '',
            html: '<div style="background:#c0392b;width:14px;height:14px;border-radius:50%;border:2px solid #fff;box-shadow:0 0 6px rgba(0,0,0,0.4);"></div>',
            iconAnchor: [7, 7],
        })
    })
    .addTo(deliveryMap)
    .bindPopup('<b>🎂 Sweet Cake Shop</b>')
    .openPopup();

    deliveryMap.on('click', function(e) {
        placeDeliveryMarker(e.latlng.lat, e.latlng.lng);
    });
}

function placeDeliveryMarker(lat, lng) {
    if (deliveryMarker) deliveryMap.removeLayer(deliveryMarker);

    deliveryMarker = L.marker([lat, lng]).addTo(deliveryMap);

    let nearest = deliveryZones[0];
    let minDist = Infinity;
    deliveryZones.forEach(zone => {
        const d = Math.hypot(zone.lat - lat, zone.lng - lng);
        if (d < minDist) { minDist = d; nearest = zone; }
    });

    document.getElementById('co-address').value = nearest.name;
    document.getElementById('delivery-fee-display').style.display = 'flex';
    document.getElementById('delivery-fee-amount').textContent    = `KSh ${nearest.fee}`;
}

// Address search autocomplete
const addressInput  = document.getElementById('co-address');
const suggestionBox = document.getElementById('address-suggestions');

addressInput.addEventListener('input', () => {
    const val = addressInput.value.toLowerCase().trim();
    suggestionBox.innerHTML = '';
    if (val.length < 2) return;

    const matches = deliveryZones.filter(z =>
        z.name.toLowerCase().includes(val)
    );

    matches.forEach(zone => {
        const div = document.createElement('div');
        div.className   = 'suggestion-item';
        div.textContent = `${zone.name} — KSh ${zone.fee}`;

        div.addEventListener('click', () => {
            addressInput.value = zone.name;
            suggestionBox.innerHTML = '';

            document.getElementById('delivery-fee-display').style.display = 'flex';
            document.getElementById('delivery-fee-amount').textContent    = `KSh ${zone.fee}`;

            if (deliveryMap) {
                if (deliveryMarker) deliveryMap.removeLayer(deliveryMarker);
                deliveryMarker = L.marker([zone.lat, zone.lng]).addTo(deliveryMap);
                deliveryMap.setView([zone.lat, zone.lng], 13);
            }
        });

        suggestionBox.appendChild(div);
    });
});

document.addEventListener('click', (e) => {
    if (!addressInput.contains(e.target) && !suggestionBox.contains(e.target)) {
        suggestionBox.innerHTML = '';
    }
});

// =============================================
//  ORDER MODAL (single cake "Order Now")
// =============================================
const modalOverlay     = document.getElementById('modal-overlay');
const orderModal       = document.getElementById('order-modal');
const closeModal       = document.getElementById('close-modal');
const modalCakeName    = document.getElementById('modal-cake-name');
const modalCakePrice   = document.getElementById('modal-cake-price');
const modalCakeDisplay = document.getElementById('modal-cake-display');

function openOrderModal(btn) {
    modalCakeName.value    = btn.dataset.name;
    modalCakePrice.value   = btn.dataset.price;
    modalCakeDisplay.value = `${btn.dataset.name} — from KSh ${parseInt(btn.dataset.price).toLocaleString()}`;
    modalOverlay.classList.add('active');
    orderModal.classList.add('active');
}

closeModal.addEventListener('click', () => {
    modalOverlay.classList.remove('active');
    orderModal.classList.remove('active');
});
modalOverlay.addEventListener('click', () => {
    modalOverlay.classList.remove('active');
    orderModal.classList.remove('active');
});

// =============================================
//  SWIPERS
// =============================================
const productSwiper = new Swiper('.product-row', {
    spaceBetween: 30,
    loop: true,
    centeredSlides: true,
    autoplay: { delay: 9500, disableOnInteraction: false },
    pagination: { el: '.swiper-pagination', clickable: true },
    breakpoints: {
        0:    { slidesPerView: 1 },
        768:  { slidesPerView: 2 },
        1024: { slidesPerView: 3 },
    },
});

const blogsSwiper = new Swiper('.blogs-row', {
    spaceBetween: 30,
    loop: true,
    centeredSlides: true,
    autoplay: { delay: 9500, disableOnInteraction: false },
    pagination: { el: '.swiper-pagination', clickable: true },
    navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
    },
    breakpoints: {
        0:    { slidesPerView: 1 },
        768:  { slidesPerView: 1 },
        1024: { slidesPerView: 1 },
    },
});

const reviewSwiper = new Swiper('.review-row', {
    spaceBetween: 30,
    loop: true,
    centeredSlides: true,
    autoplay: { delay: 9500, disableOnInteraction: false },
    pagination: { el: '.swiper-pagination', clickable: true },
    breakpoints: {
        0:    { slidesPerView: 1 },
        768:  { slidesPerView: 2 },
        1024: { slidesPerView: 3 },
    },
});

// =============================================
//  INIT — run on page load
// =============================================
window.addEventListener('load', () => {
    loadSessionBar();      // show session info if logged in
    checkSessionExpiry();  // immediate check on load
});