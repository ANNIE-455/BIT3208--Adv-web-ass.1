// =============================================
//  THEME TOGGLE (Light / Dark)
// =============================================
const themeToggle = document.getElementById('theme-toggle');

// Load saved theme
if (localStorage.getItem('theme') === 'dark') {
    document.body.classList.add('dark-theme');
    themeToggle.classList.replace('fa-moon', 'fa-sun');
}

themeToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark-theme');
    if (document.body.classList.contains('dark-theme')) {
        themeToggle.classList.replace('fa-moon', 'fa-sun');
        localStorage.setItem('theme', 'dark');
    } else {
        themeToggle.classList.replace('fa-sun', 'fa-moon');
        localStorage.setItem('theme', 'light');
    }
});

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

// Switch between Sign In and Sign Up
signUpBtnLink.addEventListener('click', (e) => {
    e.preventDefault();
    authWrapper.classList.add('active');   // slides sign-up into view
});
signInBtnLink.addEventListener('click', (e) => {
    e.preventDefault();
    authWrapper.classList.remove('active'); // slides sign-in back into view
});

// =============================================
//  PASSWORD STRENGTH METER
// =============================================
const passwordInput = document.getElementById('signup-password');
const strengthBar   = document.getElementById('strength-bar');
const strengthText  = document.getElementById('strength-text');

passwordInput.addEventListener('input', () => {
    const val = passwordInput.value;
    let score = 0;

    if (val.length >= 8)            score++;
    if (/[A-Z]/.test(val))          score++;
    if (/[0-9]/.test(val))          score++;
    if (/[^A-Za-z0-9]/.test(val))   score++;

    if (val.length === 0) {
        strengthBar.style.width = '0%';
        strengthBar.style.background = 'transparent';
        strengthText.textContent = '';
        strengthText.style.color = '#fff';
        return;
    }

    if (score <= 1) {
        strengthBar.style.width = '33%';
        strengthBar.style.background = '#e74c3c';
        strengthText.textContent = 'Weak password';
        strengthText.style.color = '#e74c3c';
    } else if (score === 2 || score === 3) {
        strengthBar.style.width = '66%';
        strengthBar.style.background = '#f39c12';
        strengthText.textContent = 'Medium password';
        strengthText.style.color = '#f39c12';
    } else {
        strengthBar.style.width = '100%';
        strengthBar.style.background = '#2ecc71';
        strengthText.textContent = 'Strong password!';
        strengthText.style.color = '#2ecc71';
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
            <span class="remove-item" data-index="${index}"><i class="fas fa-trash"></i></span>
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

// Add to cart buttons
document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        cart.push({
            name: btn.dataset.name,
            price: parseInt(btn.dataset.price)
        });
        updateCart();
        openCart();
    });
});

// =============================================
//  ORDER MODAL
// =============================================
const modalOverlay    = document.getElementById('modal-overlay');
const orderModal      = document.getElementById('order-modal');
const closeModal      = document.getElementById('close-modal');
const modalCakeName   = document.getElementById('modal-cake-name');
const modalCakePrice  = document.getElementById('modal-cake-price');
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
//  SWIPER — Products (fixed: const not var,
//  no code inside swiper config, fixed typos)
// =============================================
const productSwiper = new Swiper(".product-row", {
    spaceBetween: 30,
    loop: true,
    centeredSlides: true,
    autoplay: {
        delay: 9500,
        disableOnInteraction: false,
    },
    pagination: {
        el: ".swiper-pagination",
        clickable: true,
    },
    breakpoints: {
        0:    { slidesPerView: 1 },
        768:  { slidesPerView: 2 },
        1024: { slidesPerView: 3 },
    },
});

// SWIPER — Blogs (fixed: nextEl / prevEl typo corrected from nextE1/prevE1)
const blogsSwiper = new Swiper(".blogs-row", {
    spaceBetween: 30,
    loop: true,
    centeredSlides: true,
    autoplay: {
        delay: 9500,
        disableOnInteraction: false,
    },
    pagination: {
        el: ".swiper-pagination",
        clickable: true,
    },
    navigation: {
        nextEl: ".swiper-button-next",   // fixed: was nextE1 (number 1, not letter l)
        prevEl: ".swiper-button-prev",   // fixed: was prevE1
    },
    breakpoints: {
        0:    { slidesPerView: 1 },
        768:  { slidesPerView: 1 },
        1024: { slidesPerView: 1 },
    },
});

// SWIPER — Reviews (moved out of swiper config block — was causing syntax error)
const reviewSwiper = new Swiper(".review-row", {
    spaceBetween: 30,
    loop: true,
    centeredSlides: true,
    autoplay: {
        delay: 9500,
        disableOnInteraction: false,
    },
    pagination: {
        el: ".swiper-pagination",
        clickable: true,
    },
    breakpoints: {
        0:    { slidesPerView: 1 },
        768:  { slidesPerView: 2 },
        1024: { slidesPerView: 3 },
    },
});